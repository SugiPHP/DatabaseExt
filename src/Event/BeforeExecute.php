<?php

declare(strict_types=1);

namespace Clear\Database\Event;

/**
 * Event dispatched before a query is executed.
 */
class BeforeExecute extends PdoEvent
{
    /**
     * @param string     $queryString The SQL query to be executed
     * @param array|null $params      The parameters to be bound to the query
     */
    public function __construct(
        private readonly string $queryString,
        private readonly ?array $params = null,
    ) {
        parent::__construct('BeforeExecute');
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

    /**
     * Get the parameters bound to the query.
     *
     * @return array|null The bound parameters or null if none were provided.
     */
    public function getParams(): ?array
    {
        return $this->params;
    }
}
