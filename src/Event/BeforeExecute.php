<?php

declare(strict_types=1);

namespace SugiPHP\DatabaseExt\Event;

/**
 * Event dispatched before a query is executed.
 */
class BeforeExecute extends BeforeEvent
{
    /**
     * @param string     $queryString The SQL query to be executed
     * @param array|null $params      The parameters to be bound to the query
     */
    public function __construct(
        string $queryString,
        private readonly ?array $params = null,
    ) {
        parent::__construct($queryString, 'BeforeExecute');
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
