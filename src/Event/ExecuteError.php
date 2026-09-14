<?php

declare(strict_types=1);

namespace Clear\Database\Event;

use PDOException;

/**
 * Event sent when a PDOException occurs during PdoStatementExt::execute().
 *
 * Provides access to the failed query, its parameters, and the exception.
 */
class ExecuteError extends PdoEvent
{
    public function __construct(
        private readonly BeforeExecute $before,
        private readonly PDOException $exception
    ) {
        parent::__construct('ExecuteError');
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
     * Get the parameters bound to the query.
     *
     * @return array|null The bound parameters or null if none were provided.
     */
    public function getParams(): ?array
    {
        return $this->before->getParams();
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

    public function getBefore(): BeforeExecute
    {
        return $this->before;
    }
}
