<?php

declare(strict_types=1);

namespace Tests\Clear\Database\Event;

use Clear\Database\Event\BeforeQuery;
use Clear\Database\Event\PdoEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BeforeQuery::class)]
class BeforeQueryTest extends TestCase
{
    public function testConstructorSetsEventType(): void
    {
        $queryString = 'SELECT * FROM users';
        $event = new BeforeQuery($queryString);

        $this->assertEquals('BeforeQuery', $event->getEventType());
    }

    public function testConstructorSetsQueryString(): void
    {
        $queryString = 'SELECT * FROM users WHERE id = ?';
        $event = new BeforeQuery($queryString);

        $this->assertEquals($queryString, $event->getQueryString());
    }

    public function testGetQueryStringReturnsCorrectValue(): void
    {
        $queryString = 'INSERT INTO users (name, email) VALUES (?, ?)';
        $event = new BeforeQuery($queryString);

        $this->assertEquals($queryString, $event->getQueryString());
    }

    public function testExtendsPdoEvent(): void
    {
        $event = new BeforeQuery('SELECT 1');

        $this->assertInstanceOf(PdoEvent::class, $event);
    }

    public function testQueryStringIsMutable(): void
    {
        $originalQuery = 'SELECT * FROM users';
        $event = new BeforeQuery($originalQuery);

        // Verify we can get the original query
        $this->assertEquals($originalQuery, $event->getQueryString());
    }

    public function testDifferentQueryTypes(): void
    {
        $queries = [
            'SELECT * FROM users',
            'INSERT INTO users (name) VALUES (?)',
            'UPDATE users SET name = ? WHERE id = ?',
            'DELETE FROM users WHERE id = ?',
            'CREATE TABLE test (id INTEGER PRIMARY KEY)',
            'DROP TABLE test'
        ];

        foreach ($queries as $query) {
            $event = new BeforeQuery($query);
            $this->assertEquals($query, $event->getQueryString());
            $this->assertEquals('BeforeQuery', $event->getEventType());
        }
    }

    public function testEmptyQueryString(): void
    {
        $event = new BeforeQuery('');

        $this->assertEquals('', $event->getQueryString());
        $this->assertEquals('BeforeQuery', $event->getEventType());
    }

    public function testComplexQueryString(): void
    {
        $complexQuery = 'SELECT u.*, p.title
            FROM users u
            LEFT JOIN posts p ON u.id = p.user_id
            WHERE u.active = ? AND p.published_at > ?
            ORDER BY u.created_at DESC LIMIT 10';
        $event = new BeforeQuery($complexQuery);

        $this->assertEquals($complexQuery, $event->getQueryString());
    }
}
