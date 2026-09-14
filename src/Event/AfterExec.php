<?php

declare(strict_types=1);

namespace SugiPHP\DatabaseExt\Event;

class AfterExec extends FollowUpEvent
{
    public function __construct(
        BeforeExec $before,
        private readonly int|false $result
    ) {
        parent::__construct($before, 'AfterExec');
    }

    public function getResult(): int|false
    {
        return $this->result;
    }
}
