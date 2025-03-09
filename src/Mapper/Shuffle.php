<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Mapper\Internal\StatelessMapper;

final class Shuffle extends StatelessMapper
{
    /**
     * @inheritDoc
     */
    public function map($value, $key = null)
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
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\is_array($value)) {
                \shuffle($value);
                yield $key => $value;
            } elseif (\is_string($value)) {
                yield $key => \str_shuffle($value);
            } elseif ($value instanceof \Traversable) {
                $value = \iterator_to_array($value);
                \shuffle($value);
                yield $key => $value;
            } else {
                yield $key => $value;
            }
        }
    }
}