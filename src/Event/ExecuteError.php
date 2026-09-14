<?php

declare(strict_types=1);

namespace SugiPHP\DatabaseExt\Event;

use PDOException;

/**
 * Event sent when a PDOException occurs during PdoStatementExt::execute().
 *
 * Provides access to the failed query, its parameters, and the exception.
 */
class ExecuteError extends FollowUpEvent
{
    public function __construct(
        BeforeExecute $before,
        private readonly PDOException $exception
    ) {
        parent::__construct($before, 'ExecuteError');
    }

    /**
     * Get the parameters bound to the query.
     *
     * @return array|null The bound parameters or null if none were provided.
     */
    public function getParams(): ?array
    {
        return $this->getBefore()->getParams();
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
}
