<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\StreamMaker;

final class StreamCollection implements \Iterator
{
    /** @var StreamMaker [] */
    private array $streams = [];
    
    private array $dataCollection;
    private array $keys;
    
    public function __construct(array $dataCollection)
    {
        $this->dataCollection = $dataCollection;
        $this->keys = $this->classifiers();
    }
    
    /**
     * @param string|int|bool $id remember that bool is casted to int (true=>1, false=>0)!
     */
    public function get($id): StreamMaker
    {
        if (\is_bool($id)) {
            $id = (int) $id;
        } elseif (!\is_string($id) && !\is_int($id)) {
            throw new \InvalidArgumentException('Invalid param stream id');
        }
    
        if (!isset($this->streams[$id])) {
            $this->streams[$id] = StreamMaker::from($this->dataCollection[$id] ?? []);
        }
    
        return $this->streams[$id];
    }
    
    /**
     * @return array raw data
     */
    public function toArray(): array
    {
        return $this->dataCollection;
    }
    
    public function stream(): StreamMaker
    {
        return StreamMaker::from($this->dataCollection);
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
    
    public function current(): StreamMaker
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
}