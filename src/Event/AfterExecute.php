<?php

declare(strict_types=1);

namespace Clear\Database\Event;

/**
 * Event dispatched after a PDO statement is executed.
 * Contains the executed statement, parameters, and execution result.
 */
class AfterExecute extends FollowUpEvent
{
    public function __construct(
        BeforeExecute $before,
        private readonly bool $result
    ) {
        parent::__construct($before, 'AfterExecute');
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

    public function getResult(): bool
    {
        return $this->result;
    }
}
