<?php declare(strict_types=1);

namespace Madyanov\DB\Queries\MySQL;

use Madyanov\DB\QueryBuilders\MySQL;
use Madyanov\Interfaces\DB\QueryInterface;

class Select extends AbstractQuery
{
    private $distinct = false;
    private $tables;
    private $fields = [];
    private $cases = [];
    private $where;
    private $group = [];
    private $having;
    private $order = [];
    private $limit = [];

    public function __construct($tables, MySQL $db = null)
    {
        parent::__construct($db);
        $this->tables = (array) $tables;
        $this->where = new Where();
        $this->having = new Where();
    }

    public function distinct(bool $distinct = true)
    {
        $this->distinct = $distinct;
        return $this;
    }

    public function fields($fields)
    {
        $this->fields = (array) $fields;
        return $this;
    }

    public function cases($cases)
    {
        $this->cases = (array) $cases;
        return $this;
    }

    public function where(array $condition)
    {
        $this->where->condition($condition);
        return $this;
    }

    public function group($group)
    {
        $this->group = (array) $group;
        return $this;
    }

    public function having(array $condition)
    {
        $this->having->condition($condition);
        return $this;
    }

    public function order($order)
    {
        $this->order = (array) $order;
        return $this;
    }

    public function limit(int $limit, int $offset = 0)
    {
        $this->limit = [$limit, $offset];
        return $this;
    }

    public function build()
    {
        $result = 'SELECT ';

        if ($this->distinct){
            $result .= 'DISTINCT ';
        }

        if ($this->fields) {
            $result .= $this->buildFields();
        } else {
            $result .= '*';
        }

        if ($this->cases) {
            $result .= ', ' . $this->buildCases();
        }

        $result .= ' FROM ' . implode(', ', $this->tables);
        $where = $this->where->build();

        if ($where) {
            $result .= ' WHERE ' . $where;
        }

        if ($this->group) {
            $result .= ' GROUP ' . implode(', ', $this->group);
        }

        $having = $this->having->build();

        if ($having) {
            $result .= ' HAVING ' . $having;
        }

        if ($this->order) {
            $result .= ' ORDER BY ' . implode(', ', $this->order);
        }

        if ($this->limit) {
            $result .= ' LIMIT ? OFFSET ?';
        }

        return $result;
    }

    public function getBindings()
    {
        $bindings = [];

        if ($this->cases) {
            foreach ($this->cases as $alias => $innerCases) {
                foreach ($innerCases as $case) {
                    if (is_array($case)) {
                        list($when, $then) = $case;

                        $when = new Where([$when]);
                        $bindings = array_merge($bindings, $when->getBindings());

                        if ($then instanceof QueryInterface) {
                            $bindings = array_merge($bindings, $then->getBindings());
                        } else {
                            $bindings[] = $then;
                        }
                    } else if ($case instanceof QueryInterface) {
                        $bindings = array_merge($bindings, $case->getBindings());
                    } else {
                        $bindings[] = $case;
                    }
                }
            }
        }

        return array_merge(
            $bindings,
            $this->where->getBindings(),
            $this->having->getBindings(),
            $this->limit
        );
    }

    public function find($where)
    {
        if (!is_array($where)) {
            $where = ['id' => $where];
        }

        return $this->where($where)->fetchRow();
    }

    public function fetchCount(string $distinctField = null)
    {
        $count = 'count(*)';

        if ($distinctField) {
            $count = 'count(distinct ' . $distinctField . ')';
        }

        return (int) $this->fields($count)->fetchOne();
    }

    public function fetchLazy()
    {
        return $this->db->fetchLazy($this);
    }

    public function fetchChunks(int $size)
    {
        return $this->db->fetchChunks($this, $size);
    }

    public function fetchOne($field = null)
    {
        if ($field) {
            $this->fields($field);
        }

        return $this->db->fetchOne($this);
    }

    public function fetchRow()
    {
        return $this->db->fetchRow($this);
    }

    public function fetchColumn($field = null)
    {
        if ($field) {
            $this->fields($field);
        }

        return $this->db->fetchColumn($this);
    }

    public function fetchAll()
    {
        return $this->db->fetchAll($this);
    }

    public function fetchPairs()
    {
        return $this->db->fetchPairs($this);
    }

    public function fetchAssoc()
    {
        return $this->db->fetchAssoc($this);
    }

    private function buildFields()
    {
        $fields = [];

        foreach ($this->fields as $alias => $field) {
            if (is_string($alias)) {
                $fields[] = $field . ' AS ' . $alias;
            } else {
                $fields[] = $field;
            }
        }

        return implode(', ', $fields);
    }

    private function buildCases()
    {
        $cases = [];

        foreach ($this->cases as $alias => $innerCases) {
            $whens = [];

            foreach ($innerCases as $case) {
                if (is_array($case)) {
                    list($when, $then) = $case;

                    $when = new Where([$when]);
                    $when = 'WHEN ' . $when->build();

                    if ($then instanceof QueryInterface) {
                        $when .= ' THEN ' . $then->build();
                    } else {
                        $when .= ' THEN ?';
                    }

                    $whens[] = $when;
                } else if ($case instanceof QueryInterface) {
                    $whens[] = 'ELSE ' . $case->build();
                } else {
                    $whens[] = 'ELSE ?';
                }
            }

            $cases[] = 'CASE ' . implode(' ', $whens) . ' END AS ' . $alias;
        }

        return implode(', ', $cases);
    }
}
