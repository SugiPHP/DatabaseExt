<?php

declare(strict_types=1);

namespace Tests\Clear\Database\Event;

use Clear\Database\Event\AfterExecute;
use Clear\Database\Event\BeforeExecute;
use Clear\Database\Event\PdoEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AfterExecute::class)]
#[UsesClass(BeforeExecute::class)]
class AfterExecuteTest extends TestCase
{
    public function testConstructorSetsEventType(): void
    {
        $queryString = 'SELECT * FROM users';
        $params = ['id' => 1];
        $result = true;
        $event = new AfterExecute(new BeforeExecute($queryString, $params), $result);

        $this->assertEquals('AfterExecute', $event->getEventType());
    }

    public function testConstructorSetsQueryString(): void
    {
        $queryString = 'SELECT * FROM users WHERE id = ?';
        $params = ['id' => 1];
        $result = true;
        $event = new AfterExecute(new BeforeExecute($queryString, $params), $result);

        $this->assertEquals($queryString, $event->getQueryString());
    }

    public function testConstructorSetsParams(): void
    {
        $queryString = 'SELECT * FROM users WHERE id = ?';
        $params = ['id' => 1];
        $result = true;
        $event = new AfterExecute(new BeforeExecute($queryString, $params), $result);

        $this->assertEquals($params, $event->getParams());
    }

    public function testConstructorSetsResult(): void
    {
        $queryString = 'SELECT * FROM users WHERE id = ?';
        $params = ['id' => 1];
        $result = true;
        $event = new AfterExecute(new BeforeExecute($queryString, $params), $result);

        $this->assertEquals($result, $event->getResult());
    }

    public function testGetQueryStringReturnsCorrectValue(): void
    {
        $queryString = 'INSERT INTO users (name, email) VALUES (?, ?)';
        $params = ['John Doe', 'john@example.com'];
        $result = true;
        $event = new AfterExecute(new BeforeExecute($queryString, $params), $result);

        $this->assertEquals($queryString, $event->getQueryString());
    }

    public function testGetParamsReturnsCorrectValue(): void
    {
        $queryString = 'UPDATE users SET name = ? WHERE id = ?';
        $params = ['John Doe', 1];
        $result = true;
        $event = new AfterExecute(new BeforeExecute($queryString, $params), $result);

        $this->assertEquals($params, $event->getParams());
    }

    public function testGetResultReturnsCorrectValue(): void
    {
        $queryString = 'DELETE FROM users WHERE id = ?';
        $params = [1];
        $result = false;
        $event = new AfterExecute(new BeforeExecute($queryString, $params), $result);

        $this->assertEquals($result, $event->getResult());
    }

    public function testExtendsPdoEvent(): void
    {
        $event = new AfterExecute(new BeforeExecute('SELECT 1', null), true);

        $this->assertInstanceOf(PdoEvent::class, $event);
    }

    public function testGetBeforeReturnsCorrectValue(): void
    {
        $before = new BeforeExecute('SELECT * FROM users', ['id' => 1]);
        $event = new AfterExecute($before, true);

        $this->assertSame($before, $event->getBefore());
    }

    public function testWithNullParams(): void
    {
        $queryString = 'SELECT * FROM users';
        $event = new AfterExecute(new BeforeExecute($queryString, null), true);

        $this->assertEquals($queryString, $event->getQueryString());
        $this->assertNull($event->getParams());
        $this->assertTrue($event->getResult());
        $this->assertEquals('AfterExecute', $event->getEventType());
    }

    public function testWithEmptyParamsArray(): void
    {
        $queryString = 'SELECT * FROM users';
        $event = new AfterExecute(new BeforeExecute($queryString, []), true);

        $this->assertEquals($queryString, $event->getQueryString());
        $this->assertEquals([], $event->getParams());
        $this->assertTrue($event->getResult());
    }

    public function testWithFalseResult(): void
    {
        $queryString = 'INVALID SQL QUERY';
        $params = ['test'];
        $event = new AfterExecute(new BeforeExecute($queryString, $params), false);

        $this->assertEquals($queryString, $event->getQueryString());
        $this->assertEquals($params, $event->getParams());
        $this->assertFalse($event->getResult());
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
            $event = new AfterExecute(new BeforeExecute($query, null), true);
            $this->assertEquals($query, $event->getQueryString());
            $this->assertEquals('AfterExecute', $event->getEventType());
            $this->assertTrue($event->getResult());
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
            $event = new AfterExecute(new BeforeExecute($queryString, $params), true);
            $this->assertEquals($params, $event->getParams());
            $this->assertEquals($queryString, $event->getQueryString());
            $this->assertTrue($event->getResult());
        }
    }

    public function testEmptyQueryString(): void
    {
        $event = new AfterExecute(new BeforeExecute('', null), true);

        $this->assertEquals('', $event->getQueryString());
        $this->assertEquals('AfterExecute', $event->getEventType());
        $this->assertTrue($event->getResult());
    }

    public function testComplexQueryWithParams(): void
    {
        $complexQuery = 'SELECT u.*, p.title
            FROM users u
            LEFT JOIN posts p ON u.id = p.user_id
            WHERE u.active = ? AND p.published_at > ?
            ORDER BY u.created_at DESC LIMIT ?';
        $params = [true, '2023-01-01', 10];
        $event = new AfterExecute(new BeforeExecute($complexQuery, $params), true);

        $this->assertEquals($complexQuery, $event->getQueryString());
        $this->assertEquals($params, $event->getParams());
        $this->assertTrue($event->getResult());
    }

    public function testAllPropertiesAreReadonly(): void
    {
        $queryString = 'SELECT 1';
        $params = ['test'];
        $result = true;
        $event = new AfterExecute(new BeforeExecute($queryString, $params), $result);

        // Verify all properties cannot be changed after construction
        $this->assertEquals($queryString, $event->getQueryString());
        $this->assertEquals($params, $event->getParams());
        $this->assertEquals($result, $event->getResult());
    }
}
