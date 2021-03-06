<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Producer\Internal\ForwardItemsIterator;

final class Sort extends BaseOperation
{
    private ?Comparator $comparator = null;
    
    private bool $reversed;
    private int $mode;
    
    /** @var Item[] */
    private array $items = [];
    
    /**
     * @param Comparator|callable|null $comparator
     * @param int $mode
     * @param bool $reversed
     */
    public function __construct(
        $comparator = null,
        int $mode = Check::VALUE,
        bool $reversed = false
    ) {
        $this->comparator = Comparators::getAdapter($comparator);
        $this->mode = Check::getMode($mode);
        $this->reversed = $reversed;
    }
    
    public function handle(Signal $signal): void
    {
        $this->items[] = $signal->item->copy();
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        if (\count($this->items) > 1) {
            $this->sortItems();
        }
        
        $signal->restartWith(new ForwardItemsIterator($this->items), $this->next);
        
        return true;
    }
    
    private function sortItems(): void
    {
        switch ($this->mode) {
            case Check::VALUE:
                if ($this->comparator === null) {
                    $comparator = static function (Item $a, Item $b) {
                        return $a->value <=> $b->value;
                    };
                } else {
                    $comparator = function (Item $a, Item $b) {
                        return $this->comparator->compare($a->value, $b->value);
                    };
                }
            break;
            case Check::KEY:
                if ($this->comparator === null) {
                    $comparator = static function (Item $a, Item $b) {
                        return $a->key <=> $b->key;
                    };
                } else {
                    $comparator = function (Item $a, Item $b) {
                        return $this->comparator->compare($a->key, $b->key);
                    };
                }
            break;
            default:
                if ($this->comparator === null) {
                    $comparator = static function (Item $a, Item $b) {
                        return $a->value <=> $b->value ?: $a->key <=> $b->key;
                    };
                } else {
                    $comparator = function (Item $a, Item $b) {
                        return $this->comparator->compareAssoc($a->value, $b->value, $a->key, $b->key);
                    };
                }
        }
        
        if ($this->reversed) {
            $comparator = static function (Item $a, Item $b) use ($comparator) {
                return $comparator($b, $a);
            };
        }
        
        \usort($this->items, $comparator);
    }
    
    public function reverseOrder(): void
    {
        $this->reversed = !$this->reversed;
    }
    
    public function createSortLimited(int $limit): SortLimited
    {
        return new SortLimited($limit, $this->comparator, $this->mode, $this->reversed);
    }
}