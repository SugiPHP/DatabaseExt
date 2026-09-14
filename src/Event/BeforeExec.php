<?php

declare(strict_types=1);

namespace Clear\Database\Event;

class BeforeExec extends BeforeEvent
{
    public function __construct(string $queryString)
    {
        parent::__construct($queryString, 'BeforeExec');
    }
}
