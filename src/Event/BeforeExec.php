<?php

declare(strict_types=1);

namespace SugiPHP\DatabaseExt\Event;

class BeforeExec extends BeforeEvent
{
    public function __construct(string $queryString)
    {
        parent::__construct($queryString, 'BeforeExec');
    }
}
