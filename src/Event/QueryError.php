<?php

declare(strict_types=1);

namespace Clear\Database\Event;

use PDOException;

/**
 * Event sent when a PDOException occurs during PdoExt::query().
 */
class QueryError extends PdoEvent
{
    public function __construct(
        private readonly BeforeQuery $before,
        private readonly PDOException $exception,
    ) {
        parent::__construct('QueryError');
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

    /**
     * Get the PDOException that occurred during execution.
     *
     * @return PDOException The exception that was thrown.
     */
    public function getException(): PDOException
    {
        return $this->exception;
    }

    public function getBefore(): BeforeQuery
    {
        return $this->before;
    }
}
