<?php

declare(strict_types=1);

namespace Tests\Clear\Database;

use Clear\Database\PdoExt;
use Clear\Database\PdoInterface;
use Clear\Database\Event\{
    AfterConnect,
    AfterExec,
    AfterExecute,
    AfterQuery,
    BeforeExec,
    BeforeExecute,
    BeforeQuery
};
use Clear\Database\PdoStatementExt;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PDO database class
 */
#[CoversClass(PdoExt::class)]
#[CoversClass(AfterConnect::class)]
#[CoversClass(AfterExec::class)]
#[CoversClass(AfterExecute::class)]
#[CoversClass(AfterQuery::class)]
#[CoversClass(BeforeExec::class)]
#[CoversClass(BeforeQuery::class)]
#[CoversClass(BeforeExecute::class)]
#[CoversClass(PdoStatementExt::class)]
class PdoReadOnlyTest extends TestCase
{
    public function testCreate(): void
    {
        $this->assertNotEmpty(new PdoExt('sqlite::memory:'));
    }

    public function testPdoImplementsPdoInterface(): void
    {
        $this->assertTrue(new PdoExt('sqlite::memory:') instanceof PdoInterface);
    }

    public function testMyPdoExtendsPdo(): void
    {
        $this->assertTrue(new PdoExt('sqlite::memory:') instanceof \PDO);
    }

    public function testSetAndGetStateMethodsExists(): void
    {
        $db = new PdoExt('sqlite::memory:');
        $this->assertTrue(method_exists($db, 'setState'));
        $this->assertTrue(method_exists($db, 'getState'));
    }

    public function testSetStateReturnsSelf(): void
    {
        $db = new PdoExt('sqlite::memory:');
        $this->assertSame($db, $db->setState(PdoExt::STATE_READ_ONLY));
    }

    public function testSetAndGetStateMethods(): void
    {
        $db = new PdoExt('sqlite::memory:');
        $this->assertSame(PdoExt::STATE_READ_ONLY, $db->setState(PdoExt::STATE_READ_ONLY)->getState());
        $this->assertSame(PdoExt::STATE_READ_WRITE, $db->setState(PdoExt::STATE_READ_WRITE)->getState());
        $this->assertSame(PdoExt::STATE_UNAVAILABLE, $db->setState(PdoExt::STATE_UNAVAILABLE)->getState());
    }


    public function testInsertUpdateDeleteInReadWriteMode(): void
    {
        $db = new PdoExt('sqlite::memory:');
        $db->exec('CREATE TABLE test (id INTEGER NOT NULL)');
        $res = $db->exec('INSERT INTO test (id) VALUES (1)');
        $this->assertEquals(1, $res);

        $sth = $db->prepare('SELECT * FROM test');
        $sth->execute();
        $res = $sth->fetch(PdoExt::FETCH_ASSOC);
        $this->assertNotEmpty($res);
        $this->assertEquals(1, $res['id']);

        $res = $db->exec('UPDATE test SET id = 2 WHERE id = 1');
        $this->assertEquals(1, $res);
        $sth = $db->prepare('SELECT * FROM test');
        $sth->execute();
        $res = $sth->fetch(PdoExt::FETCH_ASSOC);
        $this->assertNotEmpty($res);
        $this->assertEquals(2, $res['id']);

        $res = $db->exec('DELETE FROM test WHERE id = 2');
        $this->assertEquals(1, $res);
        $sth = $db->prepare('SELECT * FROM test');
        $sth->execute();
        $res = $sth->fetch(PdoExt::FETCH_ASSOC);
        $this->assertEmpty($res);
    }

    public function testInsertInReadOnlyModeFails(): void
    {
        $db = new PdoExt('sqlite::memory:');
        $db->exec('CREATE TABLE test (id INTEGER NOT NULL)');
        $db->setState(PdoExt::STATE_READ_ONLY);
        $res = $db->exec('INSERT INTO test (id) VALUES (1)');
        $this->assertEquals(false, $res);
        $sth = $db->prepare('SELECT * FROM test');
        $sth->execute();
        $res = $sth->fetch(PdoExt::FETCH_ASSOC);
        $this->assertEmpty($res);
    }

    public function testUpdateAndDeleteInReadOnlyModeFails(): void
    {
        $db = new PdoExt('sqlite::memory:');
        $db->exec('CREATE TABLE test (id INTEGER NOT NULL)');
        $res = $db->exec('INSERT INTO test (id) VALUES (1)');
        $this->assertEquals(1, $res);

        $sth = $db->prepare('SELECT * FROM test');
        $sth->execute();
        $res = $sth->fetch(PdoExt::FETCH_ASSOC);
        $this->assertNotEmpty($res);

        $db->setState(PdoExt::STATE_READ_ONLY);
        $res = $db->exec('UPDATE test SET id = 2 WHERE id = 1');
        $this->assertEquals(false, $res);

        $res = $db->exec('DELETE FROM test');
        $this->assertEquals(false, $res);
    }

    public function testSelectInUnavailableModeFails(): void
    {
        $db = new PdoExt('sqlite::memory:');
        $db->exec('CREATE TABLE test (id INTEGER NOT NULL)');
        $res = $db->exec('INSERT INTO test (id) VALUES (1)');
        $this->assertEquals(1, $res);

        $db->setState(PdoExt::STATE_UNAVAILABLE);
        $sth = $db->prepare('SELECT * FROM test');
        $res = $sth->execute();
        $this->assertEquals(false, $res);
        $res = $sth->fetch(PdoExt::FETCH_ASSOC);
        $this->assertEmpty($res);
    }
}
