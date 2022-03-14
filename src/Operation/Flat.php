<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class Flat extends BaseOperation
{
    public const MAX_LEVEL = 1024;
    
    private int $maxLevel;
    
    /** @var Item[] */
    private array $items = [];
    
    /**
     * @param int $level 0 means no nesting restrictions (well, almost)
     */
    public function __construct(int $level = 0)
    {
        if ($level < 0) {
            throw new \InvalidArgumentException('Invalid param level');
        }
    
        if ($level === 0 || $level > self::MAX_LEVEL) {
            $this->maxLevel = self::MAX_LEVEL;
        } else {
            $this->maxLevel = $level;
        }
    }
    
    public function handle(Signal $signal): void
    {
        if (\is_iterable($signal->item->value)) {
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
    
    private function collectItems($values, int $level): void
    {
        if ($level < $this->maxLevel) {
            foreach ($values as $key => $value) {
                if (\is_iterable($value)) {
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
    
    public function mergeWith(Flat $other): void
    {
        if ($this->maxLevel < self::MAX_LEVEL) {
            $this->maxLevel += $other->maxLevel;
            if ($this->maxLevel > self::MAX_LEVEL) {
                $this->maxLevel = self::MAX_LEVEL;
            }
        }
    }
    
    public function maxLevel(): int
    {
        return $this->maxLevel;
    }
}