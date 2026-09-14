<?php

declare(strict_types=1);

namespace Clear\Database;

use Clear\Database\PdoInterface;
use Clear\Database\Event\{
    AfterConnect,
    AfterExec,
    AfterQuery,
    BeforeExec,
    BeforeQuery,
    ExecError,
    QueryError
};
use Psr\EventDispatcher\EventDispatcherInterface;
use PDO;
use PDOException;
use InvalidArgumentException;

/**
 * PdoExt extends PHP's internal PDO with PSR-14 Event Dispatcher and Read/Write state
 */
class PdoExt extends PDO implements PdoInterface
{
    public const STATE_READ_ONLY   = 'r';
    public const STATE_READ_WRITE  = 'rw';
    public const STATE_UNAVAILABLE = '-';

    protected ?EventDispatcherInterface $dispatcher = null;

    /**
     * Database State - ReadWrite, ReadOnly and unavailable (NONE)
     *
     * @var string
     */
    private string $state = self::STATE_READ_WRITE;

    public function __construct(string $dsn, string $username = '', string $passwd = '', array $options = [])
    {
        $dispatcher = null;
        if (isset($options['dispatcher']) && ($options['dispatcher'] instanceof EventDispatcherInterface)) {
            $dispatcher = $options['dispatcher'];
            unset($options['dispatcher']);
        }
        parent::__construct($dsn, $username, $passwd, $options);
        if (!array_key_exists(PDO::ATTR_ERRMODE, $options)) {
            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        $this->setEventDispatcher($dispatcher);
        $this->dispatch(new AfterConnect($dsn, $username, $options, $this));
    }

    /**
     * when set, the Event Dispatcher will be used to dispatch events.
     */
    public function setEventDispatcher(?EventDispatcherInterface $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
        $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, ['\Clear\Database\PdoStatementExt', [$this, $this->dispatcher]]);
    }

    /**
     * {@inheritDoc}
     */
    public function exec(string $statement): int|false
    {
        if (!$this->canExecute($statement)) {
            return false;
        }

        $before = new BeforeExec($statement);
        $this->dispatch($before);
        try {
            $res = parent::exec($statement);
        } catch (PDOException $e) {
            $this->dispatch(new ExecError($before, $e));
            throw $e;
        }
        $this->dispatch(new AfterExec($before, $res));

        return $res;
    }

    /**
     * {@inheritDoc}
     */
    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PdoStatementExt|false
    {
        if (!$this->canExecute($query)) {
            return false;
        }

        $before = new BeforeQuery($query);
        $this->dispatch($before);
        try {
            $sth = parent::query($query, $fetchMode, ...$fetchModeArgs);
        } catch (PDOException $e) {
            $this->dispatch(new QueryError($before, $e));
            throw $e;
        }
        $this->dispatch(new AfterQuery($before, $sth));

        return $sth;
    }

    /**
     * Returns the Database Read/Write state
     *
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * Sets the Database Read/Write state
     *
     * @param string $state
     * @return self
     * @throws InvalidArgumentException on invalid state
     */
    public function setState(string $state): self
    {
        if ($this->state === $state) {
            return $this;
        }

        if (!in_array($state, [self::STATE_READ_WRITE, self::STATE_READ_ONLY, self::STATE_UNAVAILABLE])) {
            throw new InvalidArgumentException('Invalid state provided');
        }

        // Second layer of read-only mode enforcement for PostgreSQL
        if ($this->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
            parent::exec('SET default_transaction_read_only = ' . ($state === self::STATE_READ_WRITE ? 'off' : 'on'));
        }

        $this->state = $state;

        return $this;
    }

    public function canExecute(string $queryString): bool
    {
        if (self::STATE_READ_WRITE === $this->state) {
            return true;
        }
        if (self::STATE_UNAVAILABLE === $this->state) {
            return false;
        }
        return !preg_match(
            "/^(ALTER|CREATE|DELETE|DROP|GRANT|INSERT|RENAME|REVOKE|TRUNCATE|UPDATE)\s/i",
            trim($queryString)
        );
    }

    private function dispatch(object $event): void
    {
        if (isset($this->dispatcher)) {
            $this->dispatcher->dispatch($event);
        }
    }
}
