<?php declare(strict_types=1);

namespace Madyanov\DB\Queries\MySQL;

use Madyanov\Interfaces\DB\QueryInterface;

class Where
{
    private $conditions;

    public function __construct(array $conditions = [])
    {
        $this->conditions = $conditions;
    }

    public function condition(array $condition)
    {
        $this->conditions[] = $condition;
        return $this;
    }

    public function build()
    {
        if (!$this->conditions) {
            return null;
        }

        $result = [];

        foreach ($this->conditions as $condition) {
            $result[] = $this->buildCondition($condition);
        }

        return '(' . implode(') AND (', $result) . ')';
    }

    public function getBindings()
    {
        $result = [];

        foreach ($this->conditions as $condition) {
            $result = array_merge($result, $this->getConditionBindings($condition));
        }

        return $result;
    }

    private function buildCondition(array $condition)
    {
        $fields = [];

        if (isset($condition[0])) {
            foreach ($condition as $value) {
                $fields[] = $this->buildCondition($value);
            }

            $result = '(' . implode(') OR (', $fields) . ')';
        } else {
            foreach ($condition as $key => $value) {
                if ($value instanceof QueryInterface) {
                    $fields[] = $key . ' (' . $value->build() . ')';
                } else if ($this->hasPlaceholder($key)) {
                    $fields[] = $key;
                } else if (is_array($value)) {
                    $fields[] = $key . ' IN (?' . str_repeat(', ?', count($value) - 1) . ')';
                } else {
                    $fields[] = $key . ' = ?';
                }
            }

            $result = '(' . implode(') AND (', $fields) . ')';
        }

        return $result;
    }

    private function hasPlaceholder(string $string)
    {
        return strpos($string, '?') !== false;
    }

    private function getConditionBindings(array $condition)
    {
        $result = [];

        if (isset($condition[0])) {
            foreach ($condition as $value) {
                $result = array_merge($result, $this->getConditionBindings($value));
            }
        } else {
            foreach ($condition as $field => $value) {
                if ($value instanceof QueryInterface) {
                    $result = array_merge($result, $value->getBindings());
                } else {
                    if (is_array($value) && !$this->hasPlaceholder($field)) {
                        foreach ($value as $key => $item) {
                            $value[$key] = (string) $item;
                        }
                    }

                    $result = array_merge($result, (array) $value);
                }
            }
        }

        return $result;
    }
}
