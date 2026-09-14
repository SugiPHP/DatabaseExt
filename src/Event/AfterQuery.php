<?php

declare(strict_types=1);

namespace Clear\Database\Event;

use PDOStatement;

/**
 * Event triggered after executing a database query.
 */
class AfterQuery extends PdoEvent
{
    /**
     * Construct a new AfterQuery event.
     *
     * @param BeforeQuery        $before    The BeforeQuery event that was triggered before this event.
     * @param PDOStatement|false $statement The resulting PDOStatement or false on failure.
     */
    public function __construct(
        private readonly BeforeQuery $before,
        private readonly PDOStatement|false $statement,
    ) {
        parent::__construct('AfterQuery');
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

    /**
     * Get the PDOStatement result.
     *
     * @return PDOStatement|false The PDOStatement object, or false if the query failed.
     */
    public function getStatement(): PDOStatement|false
    {
        return $this->statement;
    }

    public function getBefore(): BeforeQuery
    {
        return $this->before;
    }
}
