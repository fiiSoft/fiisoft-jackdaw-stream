<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\State\SortLimited;

use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Comparator\ItemComparator\ItemComparator;
use FiiSoft\Jackdaw\Comparator\ItemComparator\ItemComparatorFactory;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Operation\SortLimited;

final class SingleItem extends State
{
    private ItemComparator $comparator;
    private Item $best;
    
    private bool $hasOne = false;
    
    /**
     * @param Comparator|callable|null $comparator
     */
    public function __construct(SortLimited $operation, int $mode, bool $reversed, $comparator)
    {
        parent::__construct($operation);
        
        $this->comparator = ItemComparatorFactory::getFor($mode, $reversed, $comparator);
        $this->best = new Item();
    }
    
    public function hold(Item $item): void
    {
        if ($this->hasOne) {
            if ($this->comparator->compare($item, $this->best) < 0) {
                $this->best->key = $item->key;
                $this->best->value = $item->value;
            }
        } else {
            $this->best->key = $item->key;
            $this->best->value = $item->value;
            $this->hasOne = true;
        }
    }
    
    public function setLength(int $length): void
    {
        if ($length !== 1) {
            throw new \LogicException('It is forbidden to change the length of SingleItem collector');
        }
    }
    
    public function isEmpty(): bool
    {
        return !$this->hasOne;
    }
    
    public function getCollectedItems(): array
    {
        return $this->hasOne ? [$this->best] : [];
    }
    
    public function destroy(): void
    {
        //noop
    }
}