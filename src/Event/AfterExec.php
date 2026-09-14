<?php

declare(strict_types=1);

namespace Clear\Database\Event;

class AfterExec extends PdoEvent
{
    public function __construct(
        private readonly BeforeExec $before,
        private readonly int|false $result
    ) {
        parent::__construct('AfterExec');
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

    public function getResult(): int|false
    {
        return $this->result;
    }

    public function getBefore(): BeforeExec
    {
        return $this->before;
    }
}
