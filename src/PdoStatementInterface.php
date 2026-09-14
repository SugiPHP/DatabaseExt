<?php

declare(strict_types=1);

namespace Clear\Database;

use PDO;

/**
 * PDO Statement Interface declares all PDO Statement methods as they are defined in PHP
 */
interface PdoStatementInterface
{
    /**
     * Bind a column to a PHP variable.
     *
     * @param mixed   $column
     * @param mixed   $param
     * @param integer $type
     * @param integer $maxlen
     * @param mixed   $driverdata
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function bindColumn(
        string|int $column,
        mixed &$var,
        int $type = PDO::PARAM_STR,
        int $maxLength = 0,
        mixed $driverOptions = null
    ): bool;

    /**
     * Binds a parameter to the specified variable name.
     *
     * @see http://php.net/manual/en/pdostatement.bindparam.php
     *
     * @param mixed   $parameter
     * @param mixed   $variable
     * @param integer $dataType
     * @param integer $length
     * @param mixed   $driverOptions
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function bindParam(
        string|int $param,
        mixed &$var,
        int $type = PDO::PARAM_STR,
        int $maxLength = 0,
        mixed $driverOptions = null
    ): bool;

    /**
     * Binds a value to a parameter.
     *
     * @see http://php.net/manual/en/pdostatement.bindvalue.php
     *
     * @param mixed   $parameter
     * @param mixed   $value
     * @param integer $dataType
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function bindValue(string|int $param, mixed $value, int $type = PDO::PARAM_STR): bool;

    /**
     * Closes the cursor, enabling the statement to be executed again.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function closeCursor();

    /**
     * Returns the number of columns in the result set.
     *
     * @return integer  Returns the number of columns in the result set
     * represented by the PDOStatement object. If there is no result set, columnCount() returns 0.
     */
    public function columnCount();

    /**
     * Dump an SQL prepared command.
     */
    public function debugDumpParams();

    /**
     * Fetch the SQLSTATE associated with the last operation on the statement handle.
     *
     * @return string Identical to PDO::errorCode(), except that PDOStatement::errorCode()
     * only retrieves error codes for operations performed with PDOStatement objects.
     */
    public function errorCode();

    /**
     * Fetch extended error information associated with the last operation on the statement handle.
     *
     * @return array of error information about the last operation performed by this statement handle.
     */
    public function errorInfo();

    /**
     * Executes a prepared statement.
     *
     * @param array $parameters Input parameters
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function execute(?array $params = null): bool;
}
