<?php

declare(strict_types=1);

namespace Tests\Clear\Database\Event;

use Clear\Database\Event\ExecError;
use Clear\Database\Event\BeforeExec;
use Clear\Database\Event\PdoEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use PDOException;

#[CoversClass(ExecError::class)]
#[UsesClass(BeforeExec::class)]
class ExecErrorTest extends TestCase
{
    private PDOException $exception;

    protected function setUp(): void
    {
        $this->exception = new PDOException('SQLSTATE[HY000]: General error: 1 no such table: users');
    }

    public function testConstructorSetsEventType(): void
    {
        $event = new ExecError(new BeforeExec('DELETE FROM users'), $this->exception);

        $this->assertEquals('ExecError', $event->getEventType());
    }

    public function testConstructorSetsQueryString(): void
    {
        $queryString = 'DELETE FROM users WHERE id = 1';
        $event = new ExecError(new BeforeExec($queryString), $this->exception);

        $this->assertEquals($queryString, $event->getQueryString());
    }

    public function testConstructorSetsException(): void
    {
        $event = new ExecError(new BeforeExec('DELETE FROM users'), $this->exception);

        $this->assertSame($this->exception, $event->getException());
    }

    public function testExtendsPdoEvent(): void
    {
        $event = new ExecError(new BeforeExec('DELETE FROM users'), $this->exception);

        $this->assertInstanceOf(PdoEvent::class, $event);
    }

    public function testGetBeforeReturnsCorrectValue(): void
    {
        $before = new BeforeExec('DELETE FROM users');
        $event = new ExecError($before, $this->exception);

        $this->assertSame($before, $event->getBefore());
    }

    public function testEmptyQueryString(): void
    {
        $event = new ExecError(new BeforeExec(''), $this->exception);

        $this->assertEquals('', $event->getQueryString());
        $this->assertEquals('ExecError', $event->getEventType());
        $this->assertSame($this->exception, $event->getException());
    }

    public function testWithDifferentExceptionMessages(): void
    {
        $exceptions = [
            new PDOException('SQLSTATE[HY000]: General error: 1 no such table: users'),
            new PDOException('SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed'),
            new PDOException('SQLSTATE[HY000]: General error: 1 database is locked'),
        ];

        foreach ($exceptions as $exception) {
            $event = new ExecError(new BeforeExec('DELETE FROM users'), $exception);
            $this->assertSame($exception, $event->getException());
        }
    }
}
