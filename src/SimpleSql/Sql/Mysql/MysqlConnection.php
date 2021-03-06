<?php

namespace Tqxxkj\SimpleSql\Sql\Mysql;

use Exception;
use PDO;
use Tqxxkj\SimpleSql\Sql\Pdo\PdoConnection;
use Tqxxkj\SimpleSql\Sql\PreparedStatement;

class MysqlConnection extends PdoConnection
{
    /**
     * @var int 数据库的隔离级别
     */
    private $transactionIsolationLevel;

    /**
     * @var array 用于将 MySQL 返回的隔离级别名称转换为 int 值
     */
    private $mapTransIsolationNameToValue = [
        'READ-UNCOMMITTED' => self::TRANSACTION_READ_UNCOMMITTED,
        'READ-COMMITTED' => self::TRANSACTION_READ_COMMITTED,
        'REPEATABLE-READ' => self::TRANSACTION_REPEATABLE_READ,
        'SERIALIZABLE' => self::TRANSACTION_SERIALIZABLE,
    ];

    /**
     * @return PDO
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * MysqlConnection constructor.
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }

    /**
     * @param string $sql
     * @return PreparedStatement
     */
    function prepareStatement(string $sql): PreparedStatement
    {
        $pdoStatement = $this->pdo->prepare($sql, [
            PDO::ATTR_CURSOR, PDO::CURSOR_FWDONLY
        ]);
        return new MysqlPreparedStatement($this, $pdoStatement);
    }

    /**
     * @param int $level
     * @throws Exception
     */
    function setTransactionIsolation(int $level): void
    {
        switch ($level) {
            case self::TRANSACTION_READ_UNCOMMITTED:
                $this->pdo->query('set session transaction isolation level read uncommitted');
                break;
            case self::TRANSACTION_READ_COMMITTED:
                $this->pdo->query('set session transaction isolation level read committed');
                break;
            default:
            case self::TRANSACTION_REPEATABLE_READ:
                $this->pdo->query('set session transaction isolation level repeatable read');
                break;
            case self::TRANSACTION_SERIALIZABLE:
                $this->pdo->query('set session transaction isolation level serializable');
                break;
        }
        $this->transactionIsolationLevel = $level;
    }

    function getTransactionIsolation(): int
    {
        if (!$this->transactionIsolationLevel) {
            $s = $this->pdo->query('select @@session.transaction_isolation', PDO::FETCH_COLUMN);
            $this->transactionIsolationLevel = $this->mapTransIsolationNameToValue[$s];
        }
        return $this->transactionIsolationLevel;
    }
}
