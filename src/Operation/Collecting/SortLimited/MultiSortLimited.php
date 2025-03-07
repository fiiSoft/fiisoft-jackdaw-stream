<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting\SortLimited;

use FiiSoft\Jackdaw\Comparator\ItemComparator\ItemComparatorFactory;
use FiiSoft\Jackdaw\Comparator\Sorting\Sorting;
use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Collecting\SortLimited;
use FiiSoft\Jackdaw\Producer\Internal\ForwardItemsIterator;
use FiiSoft\Jackdaw\Producer\Internal\ReverseItemsIterator;
use FiiSoft\Jackdaw\Producer\Producer;

final class MultiSortLimited extends SortLimited
{
    private Item $top;

    /** @var \SplHeap<Item> */
    private \SplHeap $buffer;

    /** @var Item[] */
    private array $items = [];
    
    private int $limit, $count = 0;

    protected function __construct(int $limit, Sorting $sorting)
    {
        parent::__construct($sorting);

        if ($limit < 2) {
            throw InvalidParamException::describe('limit', $limit);
        }

        $this->limit = $limit;
        $this->buffer = new class extends \SplHeap { public function compare($value1, $value2): int { return 0; }};
    }

    public function handle(Signal $signal): void
    {
        if ($this->count < $this->limit) {
            $this->items[] = clone $signal->item;
            
            if (++$this->count === $this->limit) {
                $this->fillBuffer();
            }
        } elseif ($this->buffer->compare($signal->item, $this->top) < 0) {
            $this->buffer->extract();

            $this->top->key = $signal->item->key;
            $this->top->value = $signal->item->value;

            $this->buffer->insert($this->top);
            $this->top = $this->buffer->top();
        }
    }
    
    /**
     * @inheritDoc
     */
    public function streamingFinished(Signal $signal): bool
    {
        if (empty($this->items)) {
            return parent::streamingFinished($signal);
        }
        
        $this->sortItems();
        
        $signal->restartWith(new ForwardItemsIterator($this->items), $this->next);
        $this->items = [];
        
        return true;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        $item = new Item();

        foreach ($stream as $item->key => $item->value) {
            if ($this->count < $this->limit) {
                $this->items[] = clone $item;

                if (++$this->count === $this->limit) {
                    $this->fillBuffer();
                }
            } elseif ($this->buffer->compare($item, $this->top) < 0) {
                $this->buffer->extract();

                $this->top->key = $item->key;
                $this->top->value = $item->value;

                $this->buffer->insert($this->top);
                $this->top = $this->buffer->top();
            }
        }

        if (empty($this->items)) {
            if ($this->isEmpty()) {
                return [];
            }

            yield from $this->createProducer();
        } else {
            $this->sortItems();

            foreach ($this->items as $x) {
                yield $x->key => $x->value;
            }

            $this->items = [];
        }
    }
    
    private function sortItems(): void
    {
        $comparator = ItemComparatorFactory::getForSorting($this->sorting);
        
        \usort($this->items, [$comparator, 'compare']);
    }
    
    private function fillBuffer(): void
    {
        $this->buffer = HeapFactory::createHeapForSorting($this->sorting);
        
        foreach ($this->items as $item) {
            $this->buffer->insert($item);
        }
        
        $this->top = $this->buffer->top();
        $this->items = [];
    }

    public function applyLimit(int $limit): bool
    {
        $limit = \min($this->limit, $limit);

        if ($limit !== $this->limit) {
            if ($limit < 2) {
                return false;
            }

            $this->limit = $limit;
        }

        return true;
    }

    public function limit(): int
    {
        return $this->limit;
    }

    protected function createProducer(): Producer
    {
        $data = [];
        foreach ($this->buffer as $item) {
            $data[] = $item;
        }

        return new ReverseItemsIterator($data);
    }

    protected function isEmpty(): bool
    {
        return $this->count === 0;
    }

    protected function __clone()
    {
        parent::__clone();

        $this->buffer = clone $this->buffer;
    }

    public function destroy(): void
    {
        if (!$this->isDestroying) {
            parent::destroy();

            foreach ($this->buffer as $_) {
                //noop
            }
        }
    }
}