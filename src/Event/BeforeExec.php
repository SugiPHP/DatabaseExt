<?php

declare(strict_types=1);

namespace Clear\Database\Event;

class BeforeExec extends PdoEvent
{
    public function __construct(private readonly string $queryString)
    {
        parent::__construct('BeforeExec');
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
