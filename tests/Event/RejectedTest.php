<?php

declare(strict_types=1);

namespace SugiPHP\DatabaseExt\Tests\Event;

use SugiPHP\DatabaseExt\Event\PdoEvent;
use SugiPHP\DatabaseExt\Event\Rejected;
use SugiPHP\DatabaseExt\PdoExt;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Rejected::class)]
class RejectedTest extends TestCase
{
    public function testConstructorSetsEventType(): void
    {
        $event = new Rejected('DELETE FROM users', PdoExt::STATE_READ_ONLY);

        $this->assertEquals('Rejected', $event->getEventType());
    }

    public function testConstructorSetsQueryString(): void
    {
        $queryString = 'DELETE FROM users WHERE id = ?';
        $event = new Rejected($queryString, PdoExt::STATE_READ_ONLY);

        $this->assertEquals($queryString, $event->getQueryString());
    }

    public function testConstructorSetsState(): void
    {
        $event = new Rejected('DELETE FROM users', PdoExt::STATE_UNAVAILABLE);

        $this->assertEquals(PdoExt::STATE_UNAVAILABLE, $event->getState());
    }

    public function testConstructorSetsParamsToNullByDefault(): void
    {
        $event = new Rejected('DELETE FROM users WHERE id = ?', PdoExt::STATE_READ_ONLY);

        $this->assertNull($event->getParams());
    }

    public function testConstructorSetsParamsWhenProvided(): void
    {
        $params = ['id' => 1];
        $event = new Rejected('DELETE FROM users WHERE id = ?', PdoExt::STATE_READ_ONLY, $params);

        $this->assertEquals($params, $event->getParams());
    }

    public function testExtendsPdoEvent(): void
    {
        $event = new Rejected('DELETE FROM users', PdoExt::STATE_READ_ONLY);

        $this->assertInstanceOf(PdoEvent::class, $event);
    }
}
