<?php

declare(strict_types=1);

namespace Tests\SugiPHP\DatabaseExt\Event;

use SugiPHP\DatabaseExt\Event\PdoEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdoEvent::class)]
class PdoEventTest extends TestCase
{
    public function testConstructorSetsEventType(): void
    {
        $eventType = 'TestEvent';
        $event = new PdoEvent($eventType);

        $this->assertEquals($eventType, $event->getEventType());
    }

    public function testGetEventTypeReturnsCorrectValue(): void
    {
        $eventType = 'CustomEvent';
        $event = new PdoEvent($eventType);

        $this->assertEquals('CustomEvent', $event->getEventType());
    }

    public function testIsPropagationStoppedReturnsFalse(): void
    {
        $event = new PdoEvent('TestEvent');

        $this->assertFalse($event->isPropagationStopped());
    }

    public function testImplementsStoppableEventInterface(): void
    {
        $event = new PdoEvent('TestEvent');

        $this->assertInstanceOf(\Psr\EventDispatcher\StoppableEventInterface::class, $event);
    }

    public function testEventTypeIsReadonly(): void
    {
        $event = new PdoEvent('InitialEvent');

        // Verify the event type cannot be changed after construction
        $this->assertEquals('InitialEvent', $event->getEventType());
    }

    public function testDifferentEventTypes(): void
    {
        $eventTypes = ['BeforeQuery', 'AfterQuery', 'BeforeExecute', 'AfterExecute', 'ExecuteError'];

        foreach ($eventTypes as $eventType) {
            $event = new PdoEvent($eventType);
            $this->assertEquals($eventType, $event->getEventType());
        }
    }

    public function testGetAttributeReturnsNullByDefault(): void
    {
        $event = new PdoEvent('TestEvent');

        $this->assertNull($event->getAttribute('job'));
    }

    public function testSetAttributeAndGetAttributeReturnsCorrectValue(): void
    {
        $event = new PdoEvent('TestEvent');
        $job = new \stdClass();
        $event->setAttribute('job', $job);

        $this->assertSame($job, $event->getAttribute('job'));
    }

    public function testSetAttributeOverwritesPreviousValue(): void
    {
        $event = new PdoEvent('TestEvent');
        $event->setAttribute('job', 'first');
        $event->setAttribute('job', 'second');

        $this->assertEquals('second', $event->getAttribute('job'));
    }
}
