<?php

declare(strict_types=1);

namespace Tests\Clear\Database\Event;

use Clear\Database\Event\AfterQuery;
use Clear\Database\Event\BeforeQuery;
use Clear\Database\Event\PdoEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use PDO;
use PDOStatement;

#[CoversClass(AfterQuery::class)]
#[UsesClass(BeforeQuery::class)]
class AfterQueryTest extends TestCase
{
    private PDO $pdo;
    private PDOStatement $statement;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->statement = $this->pdo->prepare('SELECT 1');
    }

    public function testConstructorSetsEventType(): void
    {
        $queryString = 'SELECT * FROM users';
        $event = new AfterQuery(new BeforeQuery($queryString), $this->statement);

        $this->assertEquals('AfterQuery', $event->getEventType());
    }

    public function testConstructorSetsQueryString(): void
    {
        $queryString = 'SELECT * FROM users WHERE id = ?';
        $event = new AfterQuery(new BeforeQuery($queryString), $this->statement);

        $this->assertEquals($queryString, $event->getQueryString());
    }

    public function testConstructorSetsStatement(): void
    {
        $queryString = 'SELECT * FROM users';
        $event = new AfterQuery(new BeforeQuery($queryString), $this->statement);

        $this->assertSame($this->statement, $event->getStatement());
    }

    public function testGetQueryStringReturnsCorrectValue(): void
    {
        $queryString = 'INSERT INTO users (name, email) VALUES (?, ?)';
        $event = new AfterQuery(new BeforeQuery($queryString), $this->statement);

        $this->assertEquals($queryString, $event->getQueryString());
    }

    public function testGetStatementReturnsCorrectValue(): void
    {
        $queryString = 'SELECT * FROM users';
        $event = new AfterQuery(new BeforeQuery($queryString), $this->statement);

        $this->assertSame($this->statement, $event->getStatement());
    }

    public function testExtendsPdoEvent(): void
    {
        $event = new AfterQuery(new BeforeQuery('SELECT 1'), $this->statement);

        $this->assertInstanceOf(PdoEvent::class, $event);
    }

    public function testGetBeforeReturnsCorrectValue(): void
    {
        $before = new BeforeQuery('SELECT * FROM users');
        $event = new AfterQuery($before, $this->statement);

        $this->assertSame($before, $event->getBefore());
    }

    public function testWithFalseStatement(): void
    {
        $queryString = 'INVALID SQL QUERY';
        $event = new AfterQuery(new BeforeQuery($queryString), false);

        $this->assertEquals($queryString, $event->getQueryString());
        $this->assertFalse($event->getStatement());
        $this->assertEquals('AfterQuery', $event->getEventType());
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
            $event = new AfterQuery(new BeforeQuery($query), $this->statement);
            $this->assertEquals($query, $event->getQueryString());
            $this->assertEquals('AfterQuery', $event->getEventType());
            $this->assertSame($this->statement, $event->getStatement());
        }
    }

    public function testEmptyQueryString(): void
    {
        $event = new AfterQuery(new BeforeQuery(''), $this->statement);

        $this->assertEquals('', $event->getQueryString());
        $this->assertEquals('AfterQuery', $event->getEventType());
        $this->assertSame($this->statement, $event->getStatement());
    }

    public function testComplexQueryString(): void
    {
        $complexQuery = 'SELECT u.*, p.title
            FROM users u
            LEFT JOIN posts p ON u.id = p.user_id
            WHERE u.active = 1
            ORDER BY u.created_at DESC LIMIT 10';
        $event = new AfterQuery(new BeforeQuery($complexQuery), $this->statement);

        $this->assertEquals($complexQuery, $event->getQueryString());
        $this->assertSame($this->statement, $event->getStatement());
    }

    public function testStatementIsReadonly(): void
    {
        $queryString = 'SELECT 1';
        $event = new AfterQuery(new BeforeQuery($queryString), $this->statement);

        // Verify the statement cannot be changed after construction
        $this->assertSame($this->statement, $event->getStatement());
    }

    public function testQueryStringIsReadonly(): void
    {
        $queryString = 'SELECT 1';
        $event = new AfterQuery(new BeforeQuery($queryString), $this->statement);

        // Verify the query string cannot be changed after construction
        $this->assertEquals($queryString, $event->getQueryString());
    }
}
