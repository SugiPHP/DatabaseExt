<?php

declare(strict_types=1);

namespace Clear\Database\Event;

/**
 * Base class for events that follow up on a Before* event (the After* and *Error events).
 * Holds the originating Before* event and delegates getQueryString() to it.
 */
abstract class FollowUpEvent extends PdoEvent
{
    public function __construct(
        private readonly BeforeExec|BeforeQuery|BeforeExecute $before,
        string $eventType,
    ) {
        parent::__construct($eventType);
    }

    /**
     * Get the SQL query string.
     *
     * @return string The SQL query string.
     */
    public function getQueryString(): string
    {
        return $this->before->getQueryString();
    }

    public function getBefore(): BeforeExec|BeforeQuery|BeforeExecute
    {
        return $this->before;
    }
}
