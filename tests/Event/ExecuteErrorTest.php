<?php

declare(strict_types=1);

namespace SugiPHP\DatabaseExt\Tests\Event;

use SugiPHP\DatabaseExt\Event\ExecuteError;
use SugiPHP\DatabaseExt\Event\BeforeExecute;
use SugiPHP\DatabaseExt\Event\PdoEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use PDOException;

#[CoversClass(ExecuteError::class)]
#[UsesClass(BeforeExecute::class)]
class ExecuteErrorTest extends TestCase
{
    private PDOException $exception;

    protected function setUp(): void
    {
        $this->exception = new PDOException('SQLSTATE[HY000]: General error: 1 no such table: users');
    }

    public function testConstructorSetsEventType(): void
    {
        $queryString = 'SELECT * FROM users';
        $params = ['id' => 1];
        $event = new ExecuteError(new BeforeExecute($queryString, $params), $this->exception);

        $this->assertEquals('ExecuteError', $event->getEventType());
    }

    public function testConstructorSetsQueryString(): void
    {
        $queryString = 'SELECT * FROM users WHERE id = ?';
        $params = ['id' => 1];
        $event = new ExecuteError(new BeforeExecute($queryString, $params), $this->exception);

        $this->assertEquals($queryString, $event->getQueryString());
    }

    public function testConstructorSetsParams(): void
    {
        $queryString = 'SELECT * FROM users WHERE id = ?';
        $params = ['id' => 1];
        $event = new ExecuteError(new BeforeExecute($queryString, $params), $this->exception);

        $this->assertEquals($params, $event->getParams());
    }

    public function testConstructorSetsException(): void
    {
        $queryString = 'SELECT * FROM users';
        $params = ['id' => 1];
        $event = new ExecuteError(new BeforeExecute($queryString, $params), $this->exception);

        $this->assertSame($this->exception, $event->getException());
    }

    public function testGetQueryStringReturnsCorrectValue(): void
    {
        $queryString = 'INSERT INTO users (name, email) VALUES (?, ?)';
        $params = ['John Doe', 'john@example.com'];
        $event = new ExecuteError(new BeforeExecute($queryString, $params), $this->exception);

        $this->assertEquals($queryString, $event->getQueryString());
    }

    public function testGetParamsReturnsCorrectValue(): void
    {
        $queryString = 'UPDATE users SET name = ? WHERE id = ?';
        $params = ['John Doe', 1];
        $event = new ExecuteError(new BeforeExecute($queryString, $params), $this->exception);

        $this->assertEquals($params, $event->getParams());
    }

    public function testGetExceptionReturnsCorrectValue(): void
    {
        $queryString = 'DELETE FROM users WHERE id = ?';
        $params = [1];
        $event = new ExecuteError(new BeforeExecute($queryString, $params), $this->exception);

        $this->assertSame($this->exception, $event->getException());
    }

    public function testExtendsPdoEvent(): void
    {
        $event = new ExecuteError(new BeforeExecute('SELECT 1', null), $this->exception);

        $this->assertInstanceOf(PdoEvent::class, $event);
    }

    public function testGetBeforeReturnsCorrectValue(): void
    {
        $before = new BeforeExecute('SELECT * FROM users', ['id' => 1]);
        $event = new ExecuteError($before, $this->exception);

        $this->assertSame($before, $event->getBefore());
    }

    public function testWithNullParams(): void
    {
        $queryString = 'SELECT * FROM users';
        $event = new ExecuteError(new BeforeExecute($queryString, null), $this->exception);

        $this->assertEquals($queryString, $event->getQueryString());
        $this->assertNull($event->getParams());
        $this->assertSame($this->exception, $event->getException());
        $this->assertEquals('ExecuteError', $event->getEventType());
    }

    public function testWithEmptyParamsArray(): void
    {
        $queryString = 'SELECT * FROM users';
        $event = new ExecuteError(new BeforeExecute($queryString, []), $this->exception);

        $this->assertEquals($queryString, $event->getQueryString());
        $this->assertEquals([], $event->getParams());
        $this->assertSame($this->exception, $event->getException());
    }

    public function testWithDifferentExceptionTypes(): void
    {
        $queryString = 'SELECT * FROM users';
        $params = ['id' => 1];

        $exceptions = [
            new PDOException('SQLSTATE[HY000]: General error: 1 no such table: users'),
            new PDOException('SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed'),
            new PDOException('SQLSTATE[HY000]: General error: 1 near "INVALID": syntax error'),
            new PDOException('SQLSTATE[HY000]: General error: 1 database is locked')
        ];

        foreach ($exceptions as $exception) {
            $event = new ExecuteError(new BeforeExecute($queryString, $params), $exception);
            $this->assertEquals($queryString, $event->getQueryString());
            $this->assertEquals($params, $event->getParams());
            $this->assertSame($exception, $event->getException());
        }
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
            $event = new ExecuteError(new BeforeExecute($query, null), $this->exception);
            $this->assertEquals($query, $event->getQueryString());
            $this->assertEquals('ExecuteError', $event->getEventType());
            $this->assertSame($this->exception, $event->getException());
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
            $event = new ExecuteError(new BeforeExecute($queryString, $params), $this->exception);
            $this->assertEquals($params, $event->getParams());
            $this->assertEquals($queryString, $event->getQueryString());
            $this->assertSame($this->exception, $event->getException());
        }
    }

    public function testEmptyQueryString(): void
    {
        $event = new ExecuteError(new BeforeExecute('', null), $this->exception);

        $this->assertEquals('', $event->getQueryString());
        $this->assertEquals('ExecuteError', $event->getEventType());
        $this->assertSame($this->exception, $event->getException());
    }

    public function testComplexQueryWithParams(): void
    {
        $complexQuery = 'SELECT u.*, p.title
            FROM users u
            LEFT JOIN posts p ON u.id = p.user_id
            WHERE u.active = ? AND p.published_at > ?
            ORDER BY u.created_at DESC LIMIT ?';
        $params = [true, '2023-01-01', 10];
        $event = new ExecuteError(new BeforeExecute($complexQuery, $params), $this->exception);

        $this->assertEquals($complexQuery, $event->getQueryString());
        $this->assertEquals($params, $event->getParams());
        $this->assertSame($this->exception, $event->getException());
    }

    public function testAllPropertiesAreReadonly(): void
    {
        $queryString = 'SELECT 1';
        $params = ['test'];
        $event = new ExecuteError(new BeforeExecute($queryString, $params), $this->exception);

        // Verify all properties cannot be changed after construction
        $this->assertEquals($queryString, $event->getQueryString());
        $this->assertEquals($params, $event->getParams());
        $this->assertSame($this->exception, $event->getException());
    }

    public function testExceptionIsMutable(): void
    {
        $queryString = 'SELECT 1';
        $event = new ExecuteError(new BeforeExecute($queryString, null), $this->exception);

        // The exception object itself can be modified (it's not readonly)
        $this->assertSame($this->exception, $event->getException());
    }

    public function testWithSpecificPdoExceptionCodes(): void
    {
        $queryString = 'SELECT * FROM users';

        $exceptions = [
            new PDOException('SQLSTATE[HY000]: General error: 1 no such table: users'),
            new PDOException('SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed'),
            new PDOException('SQLSTATE[HY000]: General error: 1 near "INVALID": syntax error'),
            new PDOException('SQLSTATE[HY000]: General error: 1 database is locked')
        ];

        foreach ($exceptions as $exception) {
            $event = new ExecuteError(new BeforeExecute($queryString, null), $exception);
            $this->assertSame($exception, $event->getException());
            $this->assertEquals($queryString, $event->getQueryString());
        }
    }

    public function testWithExceptionContainingSpecialCharacters(): void
    {
        $queryString = 'SELECT * FROM "users" WHERE name = \'John\'';
        $exception = new PDOException('SQLSTATE[HY000]: General error: 1 near "John": syntax error');
        $event = new ExecuteError(new BeforeExecute($queryString, null), $exception);

        $this->assertEquals($queryString, $event->getQueryString());
        $this->assertSame($exception, $event->getException());
    }
}
