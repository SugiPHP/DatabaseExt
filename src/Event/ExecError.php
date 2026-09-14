<?php

declare(strict_types=1);

namespace Clear\Database\Event;

use PDOException;

/**
 * Event sent when a PDOException occurs during PdoExt::exec().
 */
class ExecError extends PdoEvent
{
    public function __construct(
        private readonly BeforeExec $before,
        private readonly PDOException $exception,
    ) {
        parent::__construct('ExecError');
    }

    /**
     * Get the SQL statement string.
     *
     * @return string The SQL statement string.
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

    public function getBefore(): BeforeExec
    {
        return $this->before;
    }
}
