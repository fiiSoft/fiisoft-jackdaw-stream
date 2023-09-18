<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Comparator\Comparable;
use FiiSoft\Jackdaw\Comparator\ItemComparator\ItemComparatorFactory;
use FiiSoft\Jackdaw\Comparator\Sorting\Sorting;
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
    private Sorting $sorting;
    
    /** @var Item[] */
    private array $items = [];
    
    /**
     * @param Sorting|Comparable|callable|null $sorting
     */
    public function __construct($sorting = null)
    {
        $this->sorting = Sorting::prepare($sorting);
    }
    
    public function handle(Signal $signal): void
    {
        $this->items[] = $signal->item->copy();
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        if (\count($this->items) > 1) {
            $this->sortItems($this->items);
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
            if ($this->sortSimpleData($data)) {
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
            $this->sortItems($items);
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
        $this->sorting = $this->sorting->getReversed();
    }
    
    public function createSortLimited(int $limit): SortLimited
    {
        return new SortLimited($limit, $this->sorting);
    }
    
    /**
     * @param Item[] $items REFERENCE
     */
    private function sortItems(array &$items): void
    {
        $comparator = ItemComparatorFactory::getForSorting($this->sorting);
        
        \usort($items, [$comparator, 'compare']);
    }
    
    /**
     * @param array $data REFERENCE
     */
    private function sortSimpleData(array &$data): bool
    {
        $mode = $this->sorting->mode();
        
        if ($mode === Check::VALUE) {
            \uasort($data, $this->createSimpleComparator());
            return true;
        }
        
        if ($mode === Check::KEY) {
            \uksort($data, $this->createSimpleComparator());
            return true;
        }
        
        return false;
    }
    
    private function createSimpleComparator(): callable
    {
        $comparator = $this->sorting->comparator();
        
        if ($comparator === null) {
            if ($this->sorting->isReversed()) {
                return static fn($b, $a): int => \gettype($a) <=> \gettype($b) ?: $a <=> $b;
            }
            
            return static fn($a, $b): int => \gettype($a) <=> \gettype($b) ?: $a <=> $b;
        }
        
        if ($this->sorting->isReversed()) {
            return static fn($b, $a): int => $comparator->compare($a, $b);
        }
        
        return [$comparator, 'compare'];
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->items = [];
            
            parent::destroy();
        }
    }
}