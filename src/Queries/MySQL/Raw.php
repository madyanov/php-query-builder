<?php declare(strict_types=1);

namespace Madyanov\DB\Queries\MySQL;

use Madyanov\DB\QueryBuilders\MySQL;

class Raw extends AbstractQuery
{
    private $sql;
    private $bindings = [];

    public function __construct(string $sql, MySQL $db = null)
    {
        parent::__construct($db);
        $this->sql = $sql;
    }

    public function bind(array $bindings)
    {
        $this->bindings = $bindings;
        return $this;
    }

    public function build()
    {
        $index = 0;

        return preg_replace_callback('/([^?])(\?\??)([^?])/', function ($match) use (&$index) {
            $values = $this->bindings[$index];
            $result = '?';

            if ($match[2] === '??') {
                $result = $values;
            } else if (is_array($values)) {
                $result = '?' . str_repeat(', ?', count($values) - 1);
            }

            $index++;
            return $match[1] . $result . $match[3];
        }, $this->sql);
    }

    public function getBindings()
    {
        $bindings = [];

        if (preg_match_all('/[^?](\?\??)[^?]/', $this->sql, $matches)) {
            foreach ($matches[1] as $index => $match) {
                if ($match !== '?') {
                    continue;
                }

                if (is_array($this->bindings[$index])) {
                    foreach ($this->bindings[$index] as $value) {
                        $bindings[] = (string) $value;
                    }
                } else {
                    $bindings[] = $this->bindings[$index];
                }
            }
        }

        return $bindings;
    }
}
