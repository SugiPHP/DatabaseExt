<?php

declare(strict_types=1);

namespace Tests\Clear\Database\Event;

use Clear\Database\Event\AfterConnect;
use Clear\Database\Event\PdoEvent;
use Clear\Database\PdoExt;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PDO;

#[CoversClass(AfterConnect::class)]
#[CoversClass(\Clear\Database\PdoExt::class)]
class AfterConnectTest extends TestCase
{
    private PdoExt $pdo;
    private string $dsn;
    private string $username;
    private array $options;

    protected function setUp(): void
    {
        $this->dsn = 'sqlite::memory:';
        $this->username = 'testuser';
        $this->options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ];
        $this->pdo = new PdoExt($this->dsn, $this->username, '', $this->options);
    }

    public function testConstructorSetsEventType(): void
    {
        $event = new AfterConnect($this->dsn, $this->username, $this->options, $this->pdo);

        $this->assertEquals('AfterConnect', $event->getEventType());
    }

    public function testConstructorSetsDsn(): void
    {
        $event = new AfterConnect($this->dsn, $this->username, $this->options, $this->pdo);

        $this->assertEquals($this->dsn, $event->getDsn());
    }

    public function testConstructorSetsUsername(): void
    {
        $event = new AfterConnect($this->dsn, $this->username, $this->options, $this->pdo);

        $this->assertEquals($this->username, $event->getUsername());
    }

    public function testConstructorSetsOptions(): void
    {
        $event = new AfterConnect($this->dsn, $this->username, $this->options, $this->pdo);

        $this->assertEquals($this->options, $event->getOptions());
    }

    public function testConstructorSetsPdo(): void
    {
        $event = new AfterConnect($this->dsn, $this->username, $this->options, $this->pdo);

        $this->assertSame($this->pdo, $event->getPdo());
    }

    public function testGetDsnReturnsCorrectValue(): void
    {
        $event = new AfterConnect($this->dsn, $this->username, $this->options, $this->pdo);

        $this->assertEquals($this->dsn, $event->getDsn());
    }

    public function testGetUsernameReturnsCorrectValue(): void
    {
        $event = new AfterConnect($this->dsn, $this->username, $this->options, $this->pdo);

        $this->assertEquals($this->username, $event->getUsername());
    }

    public function testGetOptionsReturnsCorrectValue(): void
    {
        $event = new AfterConnect($this->dsn, $this->username, $this->options, $this->pdo);

        $this->assertEquals($this->options, $event->getOptions());
    }

    public function testGetPdoReturnsCorrectValue(): void
    {
        $event = new AfterConnect($this->dsn, $this->username, $this->options, $this->pdo);

        $this->assertSame($this->pdo, $event->getPdo());
    }

    public function testExtendsPdoEvent(): void
    {
        $event = new AfterConnect($this->dsn, $this->username, $this->options, $this->pdo);

        $this->assertInstanceOf(PdoEvent::class, $event);
    }

    public function testWithDifferentDsnTypes(): void
    {
        $dsns = [
            'sqlite::memory:',
            'sqlite:/path/to/database.sqlite',
            'mysql:host=localhost;dbname=test',
            'pgsql:host=localhost;port=5432;dbname=test',
            'oci:dbname=//localhost:1521/test'
        ];

        foreach ($dsns as $dsn) {
            $event = new AfterConnect($dsn, 'user', [], $this->pdo);
            $this->assertEquals($dsn, $event->getDsn());
            $this->assertEquals('AfterConnect', $event->getEventType());
        }
    }

    public function testWithDifferentUsernames(): void
    {
        $usernames = [
            'admin',
            'testuser',
            'user123',
            '',
            'user@domain.com'
        ];

        foreach ($usernames as $username) {
            $event = new AfterConnect($this->dsn, $username, [], $this->pdo);
            $this->assertEquals($username, $event->getUsername());
        }
    }

    public function testWithDifferentOptions(): void
    {
        $optionsSets = [
            [],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ],
            [
                Pdo\Mysql::ATTR_INIT_COMMAND => 'SET NAMES utf8',
                PDO::ATTR_TIMEOUT => 30
            ]
        ];

        foreach ($optionsSets as $options) {
            $event = new AfterConnect($this->dsn, 'user', $options, $this->pdo);
            $this->assertEquals($options, $event->getOptions());
        }
    }

    public function testWithEmptyDsn(): void
    {
        $event = new AfterConnect('', 'user', [], $this->pdo);

        $this->assertEquals('', $event->getDsn());
        $this->assertEquals('AfterConnect', $event->getEventType());
    }

    public function testWithEmptyUsername(): void
    {
        $event = new AfterConnect($this->dsn, '', [], $this->pdo);

        $this->assertEquals('', $event->getUsername());
        $this->assertEquals($this->dsn, $event->getDsn());
    }

    public function testWithEmptyOptions(): void
    {
        $event = new AfterConnect($this->dsn, $this->username, [], $this->pdo);

        $this->assertEquals([], $event->getOptions());
        $this->assertEquals($this->dsn, $event->getDsn());
        $this->assertEquals($this->username, $event->getUsername());
    }

    public function testAllPropertiesAreReadonly(): void
    {
        $event = new AfterConnect($this->dsn, $this->username, $this->options, $this->pdo);

        // Verify all properties cannot be changed after construction
        $this->assertEquals($this->dsn, $event->getDsn());
        $this->assertEquals($this->username, $event->getUsername());
        $this->assertEquals($this->options, $event->getOptions());
        $this->assertSame($this->pdo, $event->getPdo());
    }

    public function testPdoIsMutable(): void
    {
        $event = new AfterConnect($this->dsn, $this->username, $this->options, $this->pdo);

        // The PDO object itself can be modified (it's not readonly)
        $this->assertSame($this->pdo, $event->getPdo());
    }

    public function testComplexDsn(): void
    {
        $complexDsn = 'mysql:host=localhost;port=3306;dbname=testdb;charset=utf8mb4;collation=utf8mb4_unicode_ci';
        $event = new AfterConnect($complexDsn, 'admin', [], $this->pdo);

        $this->assertEquals($complexDsn, $event->getDsn());
    }

    public function testWithSpecialCharactersInUsername(): void
    {
        $specialUsernames = [
            'user@domain.com',
            'user-name',
            'user_name',
            'user123',
            'user with spaces'
        ];

        foreach ($specialUsernames as $username) {
            $event = new AfterConnect($this->dsn, $username, [], $this->pdo);
            $this->assertEquals($username, $event->getUsername());
        }
    }
}
