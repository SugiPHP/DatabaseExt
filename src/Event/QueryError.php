<?php

declare(strict_types=1);

namespace Clear\Database\Event;

use PDOException;

/**
 * Event sent when a PDOException occurs during PdoExt::query().
 */
class QueryError extends FollowUpEvent
{
    public function __construct(
        BeforeQuery $before,
        private readonly PDOException $exception,
    ) {
        parent::__construct($before, 'QueryError');
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
