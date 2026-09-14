<?php

declare(strict_types=1);

namespace Clear\Database\Event;

/**
 * Event triggered before executing a database query.
 */
class BeforeQuery extends PdoEvent
{
    /**
     * Construct a new BeforeQuery event.
     *
     * @param string $queryString The SQL query string to be executed.
     */
    public function __construct(private readonly string $queryString)
    {
        parent::__construct('BeforeQuery');
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
