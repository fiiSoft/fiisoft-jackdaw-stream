<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class Flat extends BaseOperation
{
    /** @var int */
    private $maxLevel;
    
    /** @var Item[] */
    private $items = [];
    
    /**
     * @param int $level 0 means no nesting restrictions (well, almost)
     */
    public function __construct(int $level = 0)
    {
        if ($level < 0) {
            throw new \InvalidArgumentException('Invalid param level');
        }
        
        $this->maxLevel = $level === 0 ? \PHP_INT_MAX : $level;
    }
    
    public function handle(Signal $signal)
    {
        if (\is_array($signal->item->value) || $signal->item->value instanceof \Traversable) {
            $this->collectItems($signal->item->value, 1);
            if (empty($this->items)) {
                return;
            }
            
            if (\count($this->items) === 1) {
                foreach ($this->items as $item) {
                    $signal->item->key = $item->key;
                    $signal->item->value = $item->value;
                    $this->next->handle($signal);
                }
            } else {
                $signal->continueFrom($this->next, $this->items);
            }
            
            $this->items = [];
        } else {
            $this->next->handle($signal);
        }
    }
    
    private function collectItems($values, int $level)
    {
        if ($level < $this->maxLevel) {
            foreach ($values as $key => $value) {
                if (\is_array($value) || $value instanceof \Traversable) {
                    $this->collectItems($value, $level + 1);
                } else {
                    $this->items[] = new Item($key, $value);
                }
            }
        } else {
            foreach ($values as $key => $value) {
                $this->items[] = new Item($key, $value);
            }
        }
    }
}