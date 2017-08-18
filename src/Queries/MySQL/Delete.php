<?php declare(strict_types=1);

namespace Madyanov\DB\Queries\MySQL;

use Madyanov\DB\QueryBuilders\MySQL;

class Delete extends AbstractQuery
{
    private $tables;
    private $where;

    public function __construct($tables, MySQL $db = null)
    {
        parent::__construct($db);
        $this->tables = (array) $tables;
        $this->where = new Where();
    }

    public function where(array $condition)
    {
        $this->where->condition($condition);
        return $this;
    }

    public function build()
    {
        $result = 'DELETE FROM ' . implode(', ', $this->tables);
        $where = $this->where->build();

        if ($where) {
            $result .= ' WHERE ' . $where;
        }

        return $result;
    }

    public function getBindings()
    {
        return $this->where->getBindings();
    }
}
