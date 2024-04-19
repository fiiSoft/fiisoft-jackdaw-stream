<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Cast\ToTime;

use FiiSoft\Jackdaw\Mapper\Cast\ToTime;
use FiiSoft\Jackdaw\Mapper\Internal\FieldCastMapper;

final class ToTimeFields extends ToTime
{
    use FieldCastMapper;
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null)
    {
        foreach ($this->fields as $field) {
            $value[$field] = $this->cast($value[$field]);
        }
        
        return $value;
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            foreach ($this->fields as $field) {
                $value[$field] = $this->cast($value[$field]);
            }
            
            yield $key => $value;
        }
    }
}