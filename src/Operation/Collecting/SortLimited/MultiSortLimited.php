<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting\SortLimited;

use FiiSoft\Jackdaw\Comparator\Sorting\Sorting;
use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Collecting\SortLimited;
use FiiSoft\Jackdaw\Producer\Internal\ReverseItemsIterator;
use FiiSoft\Jackdaw\Producer\Producer;

final class MultiSortLimited extends SortLimited
{
    private Item $top;

    /** @var \SplHeap<Item> */
    private \SplHeap $buffer;

    private int $limit, $count = 0;

    protected function __construct(int $limit, Sorting $sorting)
    {
        parent::__construct($sorting);

        if ($limit < 2) {
            throw InvalidParamException::describe('limit', $limit);
        }

        $this->limit = $limit;
        $this->buffer = HeapFactory::createHeapForSorting($sorting);
    }

    public function handle(Signal $signal): void
    {
        if ($this->count === $this->limit) {
            $this->top = $this->buffer->top();

            if ($this->buffer->compare($signal->item, $this->top) < 0) {
                $this->buffer->extract();

                $this->top->key = $signal->item->key;
                $this->top->value = $signal->item->value;

                $this->buffer->insert($this->top);
            }
        } else {
            ++$this->count;
            $this->buffer->insert(clone $signal->item);
        }
    }

    public function buildStream(iterable $stream): iterable
    {
        $item = new Item();

        foreach ($stream as $item->key => $item->value) {
            if ($this->count === $this->limit) {
                $this->top = $this->buffer->top();

                if ($this->buffer->compare($item, $this->top) < 0) {
                    $this->buffer->extract();

                    $this->top->key = $item->key;
                    $this->top->value = $item->value;

                    $this->buffer->insert($this->top);
                }
            } else {
                ++$this->count;
                $this->buffer->insert(clone $item);
            }
        }

        if ($this->isEmpty()) {
            return [];
        }

        yield from $this->createProducer();
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