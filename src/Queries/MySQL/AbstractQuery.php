<?php declare(strict_types=1);

namespace Madyanov\DB\Queries\MySQL;

use Madyanov\DB\QueryBuilders\MySQL;
use Madyanov\Interfaces\DB\QueryInterface;

abstract class AbstractQuery implements QueryInterface
{
    protected $db;

    public function __construct(MySQL $db = null)
    {
        $this->db = $db;
    }

    public function execute()
    {
        return $this->db->execute($this);
    }
}
