<?php

declare(strict_types=1);

namespace SugiPHP\DatabaseExt;

use SugiPHP\DatabaseExt\Event\AfterExecute;
use SugiPHP\DatabaseExt\Event\BeforeExecute;
use SugiPHP\DatabaseExt\Event\ExecuteError;
use SugiPHP\DatabaseExt\Event\Rejected;
use Psr\EventDispatcher\EventDispatcherInterface;
use PDOStatement;
use PDOException;

/**
 * PDOStatementExt extends PHP internal PDOStatement with additional event dispatching and write protection
 */
class PdoStatementExt extends PDOStatement implements PdoStatementInterface
{
    protected function __construct(private PdoExt $connection, private ?EventDispatcherInterface $dispatcher)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function execute(?array $params = null): bool
    {
        if (!$this->connection->canExecute($this->queryString)) {
            $this->dispatch(new Rejected($this->queryString, $this->connection->getState(), $params));
            return false;
        }

        $before = new BeforeExecute($this->queryString, $params);
        $this->dispatch($before);
        try {
            $result = parent::execute($params);
        } catch (PDOException $e) {
            $this->dispatch(new ExecuteError($before, $e));
            throw $e;
        }
        $this->dispatch(new AfterExecute($before, $result));

        return $result;
    }

    private function dispatch(object $event): void
    {
        if (isset($this->dispatcher)) {
            $this->dispatcher->dispatch($event);
        }
    }
}
