<?php declare(strict_types=1);

namespace Madyanov\DB\Queries\MySQL;

use Madyanov\DB\QueryBuilders\MySQL;
use Madyanov\Interfaces\DB\QueryInterface;

class Insert extends AbstractQuery
{
    private $ignore = false;
    private $table;
    private $values = [];
    private $update = [];

    public function __construct(string $table, MySQL $db = null)
    {
        parent::__construct($db);
        $this->table = $table;
    }

    public function ignore(bool $ignore = true)
    {
        $this->ignore = $ignore;
        return $this;
    }

    public function values(array $values)
    {
        $this->values[] = $values;
        return $this;
    }

    public function update(array $update)
    {
        $this->update = $update;
        return $this;
    }

    public function build()
    {
        if (!$this->values) {
            return null;
        }

        $result = 'INSERT';

        if ($this->ignore) {
            $result .= ' IGNORE';
        }

        $result .= ' INTO ' . $this->table;
        $fields = array_keys(reset($this->values));
        $result .= ' (' . implode(', ', $fields) . ')';
        $values = [];

        foreach ($this->values as $row) {
            $values[] = '(?' . str_repeat(', ?', count($row) - 1) . ')';
        }

        $result .= ' VALUES ' . implode(', ', $values);

        if ($this->update) {
            $result .= ' ON DUPLICATE KEY UPDATE ';
            $update = [];

            foreach ($this->update as $field => $value) {
                if ($value instanceof QueryInterface) {
                    $update[] = $field . ' = (' . $value->build() . ')';
                } else {
                    $update[] = $field . ' = ?';
                }
            }

            $result .= implode(', ', $update);
        }

        return $result;
    }

    public function getBindings()
    {
        $result = [];

        foreach ($this->values as $values) {
            $result = array_merge($result, array_values($values));
        }

        foreach ($this->update as $value) {
            if ($value instanceof QueryInterface) {
                $result = array_merge($result, $value->getBindings());
            } else {
                $result[] = $value;
            }
        }

        return $result;
    }
}
