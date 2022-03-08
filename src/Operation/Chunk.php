<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class Chunk extends BaseOperation
{
    private int $size;
    private int $count = 0;
    private int $index = 0;
    
    private array $chunked = [];
    private bool $preserveKeys;
    
    public function __construct(int $size, bool $preserveKeys = false)
    {
        if ($size < 1) {
            throw new \InvalidArgumentException('Invalid param size');
        }
        
        $this->size = $size;
        $this->preserveKeys = $preserveKeys;
    }
    
    public function handle(Signal $signal): void
    {
        $item = $signal->item;
        
        if ($this->size === 1) {
            $item->value = [$this->preserveKeys ? $item->key : 0 => $item->value];
            $item->key = $this->index++;
            
            $this->next->handle($signal);
            return;
        }
        
        if ($this->preserveKeys) {
            $this->chunked[$item->key] = $item->value;
        } else {
            $this->chunked[] = $item->value;
        }
    
        if (++$this->count === $this->size) {
            $this->pass($signal);
        }
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        if (!empty($this->chunked) && $signal->isStreamEmpty()) {
            $signal->resume();
            $this->pass($signal);
            return true;
        }
    
        return parent::streamingFinished($signal);
    }
    
    private function pass(Signal $signal): void
    {
        $signal->item->key = $this->index++;
        $signal->item->value = $this->chunked;
        
        $this->count = 0;
        $this->chunked = [];
        
        $this->next->handle($signal);
    }
}