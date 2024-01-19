<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Cast\ToBool;

use FiiSoft\Jackdaw\Mapper\Internal\FieldCastMapper;
use FiiSoft\Jackdaw\Mapper\Cast\ToBool;

final class ToBoolFields extends ToBool
{
    use FieldCastMapper;
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null)
    {
        foreach ($this->fields as $field) {
            $value[$field] = (bool) $value[$field];
        }
        
        return $value;
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            foreach ($this->fields as $field) {
                $value[$field] = (bool) $value[$field];
            }
            
            yield $key => $value;
        }
    }
}