<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Mapper\Internal\BaseMapper;

final class Shuffle extends BaseMapper
{
    public function map($value, $key)
    {
        if (\is_array($value)) {
            \shuffle($value);
        } elseif (\is_string($value)) {
            $value = \str_shuffle($value);
        } elseif ($value instanceof \Traversable) {
            $value = \iterator_to_array($value);
            \shuffle($value);
        }
        
        return $value;
    }
}