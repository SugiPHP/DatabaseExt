<?php

declare(strict_types=1);

namespace Tests\Clear\Database\Event;

use Clear\Database\Event\BeforeExec;
use Clear\Database\Event\PdoEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BeforeExec::class)]
class BeforeExecTest extends TestCase
{
    public function testConstructorSetsEventType(): void
    {
        $queryString = 'SELECT * FROM users';
        $event = new BeforeExec($queryString);

        $this->assertEquals('BeforeExec', $event->getEventType());
    }

    public function testConstructorSetsQueryString(): void
    {
        $queryString = 'SELECT * FROM users WHERE id = ?';
        $event = new BeforeExec($queryString);

        $this->assertEquals($queryString, $event->getQueryString());
    }

    public function testGetQueryStringReturnsCorrectValue(): void
    {
        $queryString = 'INSERT INTO users (name, email) VALUES (?, ?)';
        $event = new BeforeExec($queryString);

        $this->assertEquals($queryString, $event->getQueryString());
    }

    public function testExtendsPdoEvent(): void
    {
        $event = new BeforeExec('SELECT 1');

        $this->assertInstanceOf(PdoEvent::class, $event);
    }

    public function testQueryStringIsMutable(): void
    {
        $originalQuery = 'SELECT * FROM users';
        $event = new BeforeExec($originalQuery);

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
            'DROP TABLE test',
            'ALTER TABLE users ADD COLUMN phone VARCHAR(20)'
        ];

        foreach ($queries as $query) {
            $event = new BeforeExec($query);
            $this->assertEquals($query, $event->getQueryString());
            $this->assertEquals('BeforeExec', $event->getEventType());
        }
    }

    public function testEmptyQueryString(): void
    {
        $event = new BeforeExec('');

        $this->assertEquals('', $event->getQueryString());
        $this->assertEquals('BeforeExec', $event->getEventType());
    }

    public function testComplexQueryString(): void
    {
        $complexQuery = 'SELECT u.*, p.title, c.name as category_name
            FROM users u
            LEFT JOIN posts p ON u.id = p.user_id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE u.active = ? AND p.published_at > ?
            ORDER BY u.created_at DESC LIMIT 10';
        $event = new BeforeExec($complexQuery);

        $this->assertEquals($complexQuery, $event->getQueryString());
    }

    public function testSqliteSpecificQueries(): void
    {
        $queries = [
            'PRAGMA table_info(users)',
            'PRAGMA foreign_keys = ON',
            'VACUUM',
            'ANALYZE',
            'REINDEX'
        ];

        foreach ($queries as $query) {
            $event = new BeforeExec($query);
            $this->assertEquals($query, $event->getQueryString());
            $this->assertEquals('BeforeExec', $event->getEventType());
        }
    }

    public function testMultiStatementQuery(): void
    {
        $multiQuery = 'CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT);
            INSERT INTO users (name) VALUES ("John"); SELECT * FROM users;';
        $event = new BeforeExec($multiQuery);

        $this->assertEquals($multiQuery, $event->getQueryString());
    }

    public function testQueryWithSpecialCharacters(): void
    {
        $queryWithSpecialChars = 'SELECT * FROM "users" WHERE name = \'John\' AND email LIKE "%@example.com"';
        $event = new BeforeExec($queryWithSpecialChars);

        $this->assertEquals($queryWithSpecialChars, $event->getQueryString());
    }

    public function testVeryLongQuery(): void
    {
        $longQuery = str_repeat('SELECT * FROM users WHERE id = ? AND ', 100) . 'id > 0';
        $event = new BeforeExec($longQuery);

        $this->assertEquals($longQuery, $event->getQueryString());
    }
}
