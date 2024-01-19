<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal\Collection;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Internal\Destroyable;
use FiiSoft\Jackdaw\Internal\ResultApi;
use FiiSoft\Jackdaw\Internal\ResultItem;
use FiiSoft\Jackdaw\Operation\Terminating\GroupBy;
use FiiSoft\Jackdaw\Stream;

abstract class BaseStreamCollection implements Destroyable, \Iterator
{
    protected array $keys;
    
    private GroupBy $groupBy;
    
    /** @var ResultItem [] */
    private array $results = [];
    
    private array $dataCollection;
    private bool $isDestroying = false;
    
    final public static function create(GroupBy $groupBy, array $dataCollection): self
    {
        if (\version_compare(\PHP_VERSION, '8.1.0') >= 0) {
            //@codeCoverageIgnoreStart
            return new StreamCollection81($groupBy, $dataCollection);
            //@codeCoverageIgnoreEnd
        }
        
        return new StreamCollection($groupBy, $dataCollection);
    }
    
    final protected function __construct(GroupBy $groupBy, array $dataCollection)
    {
        $this->groupBy = $groupBy;
        $this->dataCollection = $dataCollection;
        $this->keys = $this->classifiers();
    }
    
    /**
     * @param string|int|bool $id remember that bool is casted to int (true=>1, false=>0)!
     */
    final public function get($id): ResultApi
    {
        if (\is_bool($id)) {
            $id = (int) $id;
        } elseif (!\is_string($id) && !\is_int($id)) {
            throw InvalidParamException::byName('stream id');
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
    final public function toArray(): array
    {
        return $this->dataCollection;
    }
    
    final public function stream(): Stream
    {
        return Stream::from($this->dataCollection);
    }
    
    /**
     * @param int $flags
     * @return string raw data encoded to JSON
     */
    final public function toJson(int $flags = 0): string
    {
        return \json_encode($this->dataCollection, \JSON_THROW_ON_ERROR | $flags);
    }
    
    /**
     * @return string[]|int[]
     */
    final public function classifiers(): array
    {
        return \array_keys($this->dataCollection);
    }
    
    final public function current(): ResultApi
    {
        return $this->get(\current($this->keys));
    }
    
    final public function next(): void
    {
        \next($this->keys);
    }
    
    final public function valid(): bool
    {
        return \key($this->keys) !== null;
    }
    
    final public function rewind(): void
    {
        \reset($this->keys);
    }
    
    final public function destroy(): void
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