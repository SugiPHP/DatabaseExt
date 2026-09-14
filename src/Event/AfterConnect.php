<?php

declare(strict_types=1);

namespace SugiPHP\DatabaseExt\Event;

use SugiPHP\DatabaseExt\PdoExt;

/**
 * Event dispatched after a PDO class is constructed.
 */
class AfterConnect extends PdoEvent
{
    public function __construct(
        private readonly string $dsn,
        private readonly string $username,
        private readonly array $options,
        private readonly PdoExt $pdo
    ) {
        parent::__construct('AfterConnect');
    }

    public function getDsn(): string
    {
        return $this->dsn;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getPdo(): PdoExt
    {
        return $this->pdo;
    }
}
