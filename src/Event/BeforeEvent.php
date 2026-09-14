<?php

declare(strict_types=1);

namespace SugiPHP\DatabaseExt\Event;

/**
 * Base class for events dispatched before a statement runs (the Before* events).
 * Holds the SQL query string and exposes it via getQueryString().
 */
abstract class BeforeEvent extends PdoEvent
{
    public function __construct(
        private readonly string $queryString,
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
        return $this->queryString;
    }
}
