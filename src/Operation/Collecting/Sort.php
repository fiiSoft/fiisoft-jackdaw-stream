<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting;

use FiiSoft\Jackdaw\Comparator\ComparatorReady;
use FiiSoft\Jackdaw\Comparator\Sorting\Sorting;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;

final class Sort extends BaseSort
{
    private Sorting $sorting;
    
    /** @var Item[] */
    private array $items = [];
    
    /**
     * @param ComparatorReady|callable|null $sorting
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
        
        yield from $this->sortedItems($this->sorting, $this->items);
        
        $this->items = [];
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        $this->restartWithSortedItems($signal, $this->sorting, $this->items);
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
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->items = [];
            
            parent::destroy();
        }
    }
}