<?php declare(strict_types=1);

namespace Madyanov\DB\QueryBuilders;

use Madyanov\DB\Queries\MySQL\Delete;
use Madyanov\DB\Queries\MySQL\Insert;
use Madyanov\DB\Queries\MySQL\Raw;
use Madyanov\DB\Queries\MySQL\Select;
use Madyanov\DB\Queries\MySQL\Update;
use Madyanov\Interfaces\DB\QueryBuilderInterface;
use Madyanov\Interfaces\DB\QueryInterface;

class MySQL implements QueryBuilderInterface
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function insert($table)
    {
        return new Insert($table, $this);
    }

    public function select($table)
    {
        return new Select($table, $this);
    }

    public function update($table)
    {
        return new Update($table, $this);
    }

    public function delete($table)
    {
        return new Delete($table, $this);
    }

    public function query(string $sql)
    {
        return new Raw($sql, $this);
    }

    public function transaction(callable $handler)
    {
        $this->pdo->beginTransaction();

        try {
            if ($handler($this) !== false) {
                $this->pdo->commit();
            } else {
                $this->pdo->rollBack();
            }
        } catch (\Exception $e) {
            $this->pdo->rollBack();
        }
    }

    public function execute(QueryInterface $query)
    {
        $result = $this->executeQuery($query);

        if ($query instanceof Insert) {
            return $this->pdo->lastInsertId();
        }

        return $result->rowCount();
    }

    public function fetchLazy(QueryInterface $query)
    {
        $result = $this->executeQuery($query);

        while (($row = $result->fetch(\PDO::FETCH_LAZY)) !== false) {
            yield $row;
        }
    }

    public function fetchChunks(QueryInterface $query, int $size)
    {
        $result = $this->executeQuery($query);
        $chunk = [];

        while (($row = $result->fetch()) !== false) {
            $chunk[] = $row;

            if (count($chunk) === $size) {
                yield $chunk;
                $chunk = [];
            }
        }

        if ($chunk) {
            yield $chunk;
        }
    }

    public function fetchOne(QueryInterface $query)
    {
        return $this->executeQuery($query)->fetchColumn();
    }

    public function fetchRow(QueryInterface $query)
    {
        return $this->executeQuery($query)->fetch();
    }

    public function fetchColumn(QueryInterface $query)
    {
        return $this->executeQuery($query)->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function fetchAll(QueryInterface $query)
    {
        return $this->executeQuery($query)->fetchAll();
    }

    public function fetchPairs(QueryInterface $query)
    {
        return $this->executeQuery($query)->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    public function fetchAssoc(QueryInterface $query)
    {
        return $this->executeQuery($query)->fetchAll(\PDO::FETCH_UNIQUE);
    }

    private function executeQuery(QueryInterface $query)
    {
        $result = $this->pdo->prepare($query->build());
        $result->execute($query->getBindings());
        return $result;
    }
}
