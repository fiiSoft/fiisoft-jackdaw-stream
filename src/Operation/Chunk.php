<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class Chunk extends BaseOperation
{
    /** @var int */
    private $size;
    
    /** @var int */
    private $count = 0;
    
    /** @var int */
    private $index = 0;
    
    /** @var array */
    private $chunked = [];
    
    /** @var bool */
    private $preserveKeys;
    
    public function __construct(int $size, bool $preserveKeys = false)
    {
        if ($size < 1) {
            throw new \InvalidArgumentException('Invalid param size');
        }
        
        $this->size = $size;
        $this->preserveKeys = $preserveKeys;
    }
    
    public function handle(Signal $signal)
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
    
    public function streamingFinished(Signal $signal)
    {
        if (!empty($this->chunked) && $signal->isStreamEmpty()) {
            $signal->resume();
            $this->pass($signal);
        } else {
            parent::streamingFinished($signal);
        }
    }
    
    private function pass(Signal $signal)
    {
        $signal->item->key = $this->index++;
        $signal->item->value = $this->chunked;
        
        $this->count = 0;
        $this->chunked = [];
        
        $this->next->handle($signal);
    }
}