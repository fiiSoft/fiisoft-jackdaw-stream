<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class Tail extends BaseOperation
{
    /** @var \SplFixedArray|null */
    private $buffer;
    
    /** @var int */
    private $length;
    
    /** @var int */
    private $index = 0;
    
    public function __construct(int $length)
    {
        if ($length < 0) {
            throw new \InvalidArgumentException('Invalid param length');
        }
    
        $this->length = $length;
        $this->buffer = new \SplFixedArray($length);
    }
    
    public function handle(Signal $signal)
    {
        if ($this->length > 0) {
            if (isset($this->buffer[$this->index])) {
                $signal->item->copyTo($this->buffer[$this->index]);
            } else {
                $this->buffer[$this->index] = $signal->item->copy();
            }
            
            if (++$this->index === $this->length) {
                $this->index = 0;
            }
        }
    }
    
    public function streamingFinished(Signal $signal)
    {
        $items = [];
        
        if ($this->buffer->count() > 0) {
            $count = \min($this->length, $this->buffer->count());
            
            for ($i = 0; $i < $count; ++$i) {
                if ($this->index === $count) {
                    $this->index = 0;
                }
                
                $items[] = $this->buffer[$this->index++];
            }
            
            $this->buffer->setSize(0);
        }
        
        $signal->restartFrom($this->next, $items);
    }
    
    public function mergeWith(Tail $other)
    {
        $this->length = \min($this->length, $other->length);
    }
    
    public function length(): int
    {
        return $this->length;
    }
}