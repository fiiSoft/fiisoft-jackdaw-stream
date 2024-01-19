<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Adapter;

use FiiSoft\Jackdaw\Mapper\Internal\StateMapper;

final class GeneratorAdapter extends StateMapper
{
    private \Generator $source;
    
    public function __construct(\Generator $source)
    {
        $this->source = $source;
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null)
    {
        if ($this->source->valid()) {
            $value = $this->source->current();
            $this->source->next();

            return $value;
        }
        
        return $this->isValueMapper ? $value : $key;
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->source->valid()) {
                $value = $this->source->current();
                $this->source->next();
            }
            
            yield $key => $value;
        }
    }
    
    protected function buildKeyMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->source->valid()) {
                $key = $this->source->current();
                $this->source->next();
            }
            
            yield $key => $value;
        }
    }
}