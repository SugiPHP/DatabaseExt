<?php

declare(strict_types=1);

namespace SugiPHP\DatabaseExt\Event;

use Psr\EventDispatcher\StoppableEventInterface;

/**
 * Base class for PDO events.
 */
class PdoEvent implements StoppableEventInterface
{
    private bool $propagationStopped = false;

    /**
     * @var array<string, mixed>
     */
    private array $attributes = [];

    public function __construct(private readonly string $eventType)
    {
    }

    /**
     * Get the event type.
     *
     * @return string The event type.
     */
    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }
}
