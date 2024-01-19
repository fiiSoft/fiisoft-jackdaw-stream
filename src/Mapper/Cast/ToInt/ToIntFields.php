<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Cast\ToInt;

use FiiSoft\Jackdaw\Mapper\Internal\FieldCastMapper;
use FiiSoft\Jackdaw\Mapper\Cast\ToInt;

final class ToIntFields extends ToInt
{
    use FieldCastMapper;
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null)
    {
        foreach ($this->fields as $field) {
            $value[$field] = (int) $value[$field];
        }
        
        return $value;
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            foreach ($this->fields as $field) {
                $value[$field] = (int) $value[$field];
            }
            
            yield $key => $value;
        }
    }
}