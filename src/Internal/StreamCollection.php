<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Operation\Terminating\GroupBy;
use FiiSoft\Jackdaw\Stream;

final class StreamCollection implements Destroyable, \Iterator
{
    private GroupBy $groupBy;
    
    /** @var ResultItem [] */
    private array $results = [];
    
    private array $dataCollection;
    private array $keys;

    private bool $isDestroying = false;
    
    public function __construct(GroupBy $groupBy, array $dataCollection)
    {
        $this->groupBy = $groupBy;
        $this->dataCollection = $dataCollection;
        $this->keys = $this->classifiers();
    }
    
    /**
     * @param string|int|bool $id remember that bool is casted to int (true=>1, false=>0)!
     */
    public function get($id): ResultApi
    {
        if (\is_bool($id)) {
            $id = (int) $id;
        } elseif (!\is_string($id) && !\is_int($id)) {
            throw new \InvalidArgumentException('Invalid param stream id');
        }
    
        if (!isset($this->results[$id])) {
            if (isset($this->dataCollection[$id])) {
                $this->results[$id] = ResultItem::createFromData($this->dataCollection[$id], $id);
            } else {
                $this->results[$id] = ResultItem::createNotFound([]);
            }
        }
    
        return $this->results[$id];
    }
    
    /**
     * @return array raw data
     */
    public function toArray(): array
    {
        return $this->dataCollection;
    }
    
    public function stream(): Stream
    {
        return Stream::from($this->dataCollection);
    }
    
    /**
     * @param int $flags
     * @return string raw data encoded to JSON
     */
    public function toJson(int $flags = 0): string
    {
        return \json_encode($this->dataCollection, \JSON_THROW_ON_ERROR | $flags);
    }
    
    /**
     * @return string[]|int[]
     */
    public function classifiers(): array
    {
        return \array_keys($this->dataCollection);
    }
    
    public function current(): ResultApi
    {
        return $this->get(\current($this->keys));
    }
    
    public function next(): void
    {
        \next($this->keys);
    }
    
    /**
     * @return string|int
     */
    public function key()
    {
        return \current($this->keys);
    }
    
    public function valid(): bool
    {
        return \key($this->keys) !== null;
    }
    
    public function rewind(): void
    {
        \reset($this->keys);
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->isDestroying = true;
            
            foreach ($this->results as $result) {
                $result->destroy();
            }
            
            $this->results = [];
            $this->dataCollection = [];
            $this->keys = [];
            
            $this->groupBy->destroy();
        }
    }
}