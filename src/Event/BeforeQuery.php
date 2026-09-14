<?php

declare(strict_types=1);

namespace Clear\Database\Event;

/**
 * Event triggered before executing a database query.
 */
class BeforeQuery extends BeforeEvent
{
    /**
     * Construct a new BeforeQuery event.
     *
     * @param string $queryString The SQL query string to be executed.
     */
    public function __construct(string $queryString)
    {
        parent::__construct($queryString, 'BeforeQuery');
    }
}
