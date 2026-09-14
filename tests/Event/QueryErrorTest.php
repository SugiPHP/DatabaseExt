<?php

declare(strict_types=1);

namespace Tests\SugiPHP\DatabaseExt\Event;

use SugiPHP\DatabaseExt\Event\QueryError;
use SugiPHP\DatabaseExt\Event\BeforeQuery;
use SugiPHP\DatabaseExt\Event\PdoEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use PDOException;

#[CoversClass(QueryError::class)]
#[UsesClass(BeforeQuery::class)]
class QueryErrorTest extends TestCase
{
    private PDOException $exception;

    protected function setUp(): void
    {
        $this->exception = new PDOException('SQLSTATE[HY000]: General error: 1 no such table: users');
    }

    public function testConstructorSetsEventType(): void
    {
        $event = new QueryError(new BeforeQuery('SELECT * FROM users'), $this->exception);

        $this->assertEquals('QueryError', $event->getEventType());
    }

    public function testConstructorSetsQueryString(): void
    {
        $queryString = 'SELECT * FROM users WHERE id = 1';
        $event = new QueryError(new BeforeQuery($queryString), $this->exception);

        $this->assertEquals($queryString, $event->getQueryString());
    }

    public function testConstructorSetsException(): void
    {
        $event = new QueryError(new BeforeQuery('SELECT * FROM users'), $this->exception);

        $this->assertSame($this->exception, $event->getException());
    }

    public function testExtendsPdoEvent(): void
    {
        $event = new QueryError(new BeforeQuery('SELECT * FROM users'), $this->exception);

        $this->assertInstanceOf(PdoEvent::class, $event);
    }

    public function testGetBeforeReturnsCorrectValue(): void
    {
        $before = new BeforeQuery('SELECT * FROM users');
        $event = new QueryError($before, $this->exception);

        $this->assertSame($before, $event->getBefore());
    }

    public function testEmptyQueryString(): void
    {
        $event = new QueryError(new BeforeQuery(''), $this->exception);

        $this->assertEquals('', $event->getQueryString());
        $this->assertEquals('QueryError', $event->getEventType());
        $this->assertSame($this->exception, $event->getException());
    }

    public function testWithDifferentExceptionMessages(): void
    {
        $exceptions = [
            new PDOException('SQLSTATE[HY000]: General error: 1 no such table: users'),
            new PDOException('SQLSTATE[HY000]: General error: 1 near "INVALID": syntax error'),
            new PDOException('SQLSTATE[HY000]: General error: 1 database is locked'),
        ];

        foreach ($exceptions as $exception) {
            $event = new QueryError(new BeforeQuery('SELECT * FROM users'), $exception);
            $this->assertSame($exception, $event->getException());
        }
    }
}
