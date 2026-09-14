<?php

declare(strict_types=1);

namespace SugiPHP\DatabaseExt\Event;

use PDOException;

/**
 * Event sent when a PDOException occurs during PdoExt::exec().
 */
class ExecError extends FollowUpEvent
{
    public function __construct(
        BeforeExec $before,
        private readonly PDOException $exception,
    ) {
        parent::__construct($before, 'ExecError');
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
