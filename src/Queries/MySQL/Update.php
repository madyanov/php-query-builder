<?php declare(strict_types=1);

namespace Madyanov\DB\Queries\MySQL;

use Madyanov\DB\QueryBuilders\MySQL;
use Madyanov\Interfaces\DB\QueryInterface;

class Update extends AbstractQuery
{
    private $ignore = false;
    private $tables = [];
    private $update = [];
    private $where;

    public function __construct($tables, MySQL $db = null)
    {
        parent::__construct($db);
        $this->tables = (array) $tables;
        $this->where = new Where();
    }

    public function set(string $field, $value)
    {
        $this->update[$field] = $value;
        return $this;
    }

    public function where(array $condition)
    {
        $this->where->condition($condition);
        return $this;
    }

    public function ignore(bool $ignore = true)
    {
        $this->ignore = $ignore;
        return $this;
    }

    public function build()
    {
        $result = 'UPDATE ';

        if ($this->ignore) {
            $result .= 'IGNORE ';
        }

        $result .=  implode(', ', $this->tables) . ' SET ';
        $update = [];

        foreach ($this->update as $field => $value) {
            if ($value instanceof QueryInterface) {
                $update[] = $field . ' = (' . $value->build() . ')';
            } else {
                $update[] = $field . ' = ?';
            }
        }

        $result .= implode(', ', $update);
        $where = $this->where->build();

        if ($where) {
            $result .= ' WHERE ' . $where;
        }

        return $result;
    }

    public function getBindings()
    {
        $result = [];

        foreach ($this->update as $field => $value) {
            if ($value instanceof QueryInterface) {
                $result = array_merge($result, $value->getBindings());
            } else {
                $result[] = $value;
            }
        }

        return array_merge($result, $this->where->getBindings());
    }
}
