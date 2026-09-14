<?php

declare(strict_types=1);

namespace Tests\Clear\Database\Event;

use Clear\Database\Event\BeforeExecute;
use Clear\Database\Event\PdoEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BeforeExecute::class)]
class BeforeExecuteTest extends TestCase
{
    public function testConstructorSetsEventType(): void
    {
        $queryString = 'SELECT * FROM users';
        $event = new BeforeExecute($queryString);

        $this->assertEquals('BeforeExecute', $event->getEventType());
    }

    public function testConstructorSetsQueryString(): void
    {
        $queryString = 'SELECT * FROM users WHERE id = ?';
        $event = new BeforeExecute($queryString);

        $this->assertEquals($queryString, $event->getQueryString());
    }

    public function testConstructorSetsParamsToNullByDefault(): void
    {
        $queryString = 'SELECT * FROM users';
        $event = new BeforeExecute($queryString);

        $this->assertNull($event->getParams());
    }

    public function testConstructorSetsParamsWhenProvided(): void
    {
        $queryString = 'SELECT * FROM users WHERE id = ?';
        $params = ['id' => 1];
        $event = new BeforeExecute($queryString, $params);

        $this->assertEquals($params, $event->getParams());
    }

    public function testGetQueryStringReturnsCorrectValue(): void
    {
        $queryString = 'INSERT INTO users (name, email) VALUES (?, ?)';
        $event = new BeforeExecute($queryString);

        $this->assertEquals($queryString, $event->getQueryString());
    }

    public function testGetParamsReturnsCorrectValue(): void
    {
        $queryString = 'UPDATE users SET name = ? WHERE id = ?';
        $params = ['John Doe', 1];
        $event = new BeforeExecute($queryString, $params);

        $this->assertEquals($params, $event->getParams());
    }

    public function testExtendsPdoEvent(): void
    {
        $event = new BeforeExecute('SELECT 1');

        $this->assertInstanceOf(PdoEvent::class, $event);
    }

    public function testQueryStringIsMutable(): void
    {
        $originalQuery = 'SELECT * FROM users';
        $event = new BeforeExecute($originalQuery);

        // Verify we can get the original query
        $this->assertEquals($originalQuery, $event->getQueryString());
    }

    public function testParamsIsMutable(): void
    {
        $queryString = 'SELECT * FROM users WHERE id = ?';
        $originalParams = ['id' => 1];
        $event = new BeforeExecute($queryString, $originalParams);

        // Verify we can get the original params
        $this->assertEquals($originalParams, $event->getParams());
    }

    public function testDifferentQueryTypes(): void
    {
        $queries = [
            'SELECT * FROM users',
            'INSERT INTO users (name) VALUES (?)',
            'UPDATE users SET name = ? WHERE id = ?',
            'DELETE FROM users WHERE id = ?'
        ];

        foreach ($queries as $query) {
            $event = new BeforeExecute($query);
            $this->assertEquals($query, $event->getQueryString());
            $this->assertEquals('BeforeExecute', $event->getEventType());
            $this->assertNull($event->getParams());
        }
    }

    public function testWithDifferentParamTypes(): void
    {
        $queryString = 'SELECT * FROM users WHERE id = ? AND active = ?';

        $testParams = [
            ['id' => 1, 'active' => true],
            ['id' => '2', 'active' => false],
            ['id' => 3.14, 'active' => 1],
            ['id' => null, 'active' => 'yes']
        ];

        foreach ($testParams as $params) {
            $event = new BeforeExecute($queryString, $params);
            $this->assertEquals($params, $event->getParams());
            $this->assertEquals($queryString, $event->getQueryString());
        }
    }

    public function testEmptyQueryString(): void
    {
        $event = new BeforeExecute('');

        $this->assertEquals('', $event->getQueryString());
        $this->assertEquals('BeforeExecute', $event->getEventType());
        $this->assertNull($event->getParams());
    }

    public function testEmptyParamsArray(): void
    {
        $queryString = 'SELECT * FROM users';
        $event = new BeforeExecute($queryString, []);

        $this->assertEquals($queryString, $event->getQueryString());
        $this->assertEquals([], $event->getParams());
    }

    public function testComplexQueryWithParams(): void
    {
        $complexQuery = 'SELECT u.*, p.title
            FROM users u
            LEFT JOIN posts p ON u.id = p.user_id
            WHERE u.active = ? AND p.published_at > ?
            ORDER BY u.created_at DESC LIMIT ?';
        $params = [true, '2023-01-01', 10];
        $event = new BeforeExecute($complexQuery, $params);

        $this->assertEquals($complexQuery, $event->getQueryString());
        $this->assertEquals($params, $event->getParams());
    }
}
