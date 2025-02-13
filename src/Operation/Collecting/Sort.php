<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting;

use FiiSoft\Jackdaw\Comparator\Comparable;
use FiiSoft\Jackdaw\Comparator\ItemComparator\ItemComparatorFactory;
use FiiSoft\Jackdaw\Comparator\Sorting\Sorting;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Producer\Internal\ForwardItemsIterator;

final class Sort extends BaseOperation
{
    private Sorting $sorting;
    
    /** @var Item[] */
    private array $items = [];
    
    /**
     * @param Comparable|callable|null $sorting
     */
    public function __construct($sorting = null)
    {
        $this->sorting = Sorting::prepare($sorting);
    }
    
    public function handle(Signal $signal): void
    {
        $this->items[] = clone $signal->item;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $this->items[] = new Item($key, $value);
        }
        
        if (\count($this->items) > 1) {
            $this->sortItems($this->items);
        }
        
        foreach ($this->items as $item) {
            yield $item->key => $item->value;
        }
        
        $this->items = [];
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        if (\count($this->items) > 1) {
            $this->sortItems($this->items);
        }
        
        $signal->restartWith(new ForwardItemsIterator($this->items), $this->next);
        $this->items = [];
        
        return true;
    }
    
    public function reverseOrder(): void
    {
        $this->sorting = $this->sorting->getReversed();
    }
    
    public function createSortLimited(int $limit): SortLimited
    {
        return SortLimited::create($limit, $this->sorting);
    }
    
    /**
     * @param Item[] $items REFERENCE
     */
    private function sortItems(array &$items): void
    {
        $comparator = ItemComparatorFactory::getForSorting($this->sorting);
        
        \usort($items, [$comparator, 'compare']);
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->items = [];
            
            parent::destroy();
        }
    }
}