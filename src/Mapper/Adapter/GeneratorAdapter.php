<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Adapter;

use FiiSoft\Jackdaw\Mapper\Internal\BaseMapper;

final class GeneratorAdapter extends BaseMapper
{
    private \Generator $source;
    
    public function __construct(\Generator $source)
    {
        $this->source = $source;
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key)
    {
        if ($this->source->valid()) {
            $value = $this->source->current();
            $this->source->next();

            return $value;
        }
        
        return $this->isValueMapper ? $value : $key;
    }
}