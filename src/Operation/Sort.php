<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Comparator\ItemComparator\ItemComparatorFactory;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Internal\DataCollector;
use FiiSoft\Jackdaw\Producer\Internal\ForwardItemsIterator;
use FiiSoft\Jackdaw\Producer\Producer;
use FiiSoft\Jackdaw\Producer\Producers;

final class Sort extends BaseOperation implements DataCollector
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
            $comparator = ItemComparatorFactory::getFor($this->mode, $this->reversed, $this->comparator);
            \usort($this->items, static fn(Item $first, Item $second): int => $comparator->compare($first, $second));
        }
        
        if ($this->next instanceof DataCollector) {
            $signal->continueFrom($this->next);
            
            return $this->next->acceptCollectedItems($this->items, $signal, false);
        }
        
        $signal->restartWith(new ForwardItemsIterator($this->items), $this->next);
        
        return true;
    }
    
    public function collectDataFromProducer(Producer $producer, Signal $signal, bool $reindexed): bool
    {
        $item = $signal->item;
        
        foreach ($producer->feed($item) as $_) {
            $this->items[] = $item->copy();
        }
        
        return $this->streamingFinished($signal);
    }
    
    public function acceptSimpleData(array $data, Signal $signal, bool $reindexed): bool
    {
        if (\count($data) > 1) {
            if ($this->mode === Check::VALUE || $this->mode === Check::KEY) {
                if ($this->mode === Check::VALUE) {
                    \uasort($data, $this->createSimpleComparator());
                } else {
                    \uksort($data, $this->createSimpleComparator());
                }
                
                if ($this->next instanceof DataCollector) {
                    $signal->continueFrom($this->next);
                    
                    return $this->next->acceptSimpleData($data, $signal, false);
                }
            } else {
                $items = [];
                foreach ($data as $key => $value) {
                    $items[] = new Item($key, $value);
                }
                
                return $this->acceptCollectedItems($items, $signal, false);
            }
        }
        
        $signal->restartWith(Producers::fromArray($data), $this->next);
        
        return true;
    }
    
    /**
     * @param Item[] $items
     */
    public function acceptCollectedItems(array $items, Signal $signal, bool $reindexed): bool
    {
        if (\count($items) > 1) {
            $comparator = ItemComparatorFactory::getFor($this->mode, $this->reversed, $this->comparator);
            \usort($items, static fn(Item $first, Item $second): int => $comparator->compare($first, $second));
        }
        
        if ($this->next instanceof DataCollector) {
            $signal->continueFrom($this->next);
            
            return $this->next->acceptCollectedItems($items, $signal, false);
        }
        
        $signal->restartWith(new ForwardItemsIterator($items), $this->next);
        
        return true;
    }
    
    public function reverseOrder(): void
    {
        $this->reversed = !$this->reversed;
    }
    
    public function createSortLimited(int $limit): SortLimited
    {
        return new SortLimited($limit, $this->comparator, $this->mode, $this->reversed);
    }
    
    private function createSimpleComparator(): callable
    {
        if ($this->comparator === null) {
            if ($this->reversed) {
                return static fn($b, $a): int => $a <=> $b;
            }
            
            return static fn($a, $b): int => $a <=> $b;
        }
        
        if ($this->reversed) {
            return fn($b, $a): int => $this->comparator->compare($a, $b);
        }
        
        return fn($a, $b): int => $this->comparator->compare($a, $b);
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->items = [];
            
            parent::destroy();
        }
    }
}