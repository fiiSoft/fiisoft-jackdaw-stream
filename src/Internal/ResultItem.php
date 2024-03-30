<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Stream;
use FiiSoft\Jackdaw\Transformer\Transformer;
use FiiSoft\Jackdaw\Transformer\Transformers;

final class ResultItem implements ResultApi
{
    private bool $found = false;
    private bool $isDestroying = false;
    
    /** @var string|int|null */
    private $key;
    
    /** @var mixed */
    private $rawValue = null;
    
    /** @var mixed|null */
    private $finalValue = null;
    
    public static function createFound(Item $item, ?Transformer $transformer = null): self
    {
        return new self(true, $item->value, $item->key, null, $transformer);
    }
    
    /**
     * @param callable|mixed|null $default
     * @param string|int|null $id
     */
    public static function createNotFound($default = null, $id = null): self
    {
        return new self(false, null, $id, $default);
    }
    
    /**
     * @param mixed $data
     * @param string|int $id
     */
    public static function createFromData($data, $id): self
    {
        return new self(true, $data, $id);
    }
    
    /**
     * @param mixed $value
     * @param mixed $key
     * @param mixed $default
     */
    private function __construct(bool $found, $value, $key, $default = null, ?Transformer $transformer = null)
    {
        $this->key = $key;
        
        if ($found) {
            $this->found = true;
            $this->rawValue = $value;
            $this->transform($transformer);
        } else {
            $this->finalValue = \is_callable($default) ? $default() : $default;
        }
    }
    
    /**
     * @inheritdoc
     */
    public function found(): bool
    {
        return $this->found;
    }
    
    /**
     * @inheritdoc
     */
    public function notFound(): bool
    {
        return !$this->found;
    }
    
    /**
     * @inheritdoc
     */
    public function key()
    {
        return $this->found || $this->finalValue !== null ? $this->key ?? 0 : null;
    }
    
    /**
     * @inheritdoc
     */
    public function get()
    {
        return $this->finalValue;
    }
    
    /**
     * @inheritdoc
     */
    public function transform($transformer): self
    {
        $transformer = Transformers::getAdapter($transformer);
        
        if ($this->found) {
            $this->finalValue = $transformer !== null
                ? $transformer->transform($this->rawValue, $this->key)
                : $this->rawValue;
        }
        
        return $this;
    }
    
    /**
     * @inheritdoc
     */
    public function getOrElse($orElse)
    {
        if ($this->found) {
            return $this->finalValue;
        }
    
        return \is_callable($orElse) ? $orElse() : $orElse;
    }
    
    /**
     * @inheritdoc
     */
    public function tuple(): array
    {
        return $this->found || $this->finalValue !== null ? [$this->key ?? 0, $this->finalValue] : [];
    }
    
    /**
     * @inheritdoc
     */
    public function call($consumer): void
    {
        Consumers::getAdapter($consumer)->consume($this->finalValue, $this->key);
    }
    
    /**
     * @inheritdoc
     */
    public function toString(string $separator = ','): string
    {
        if ($this->found || $this->finalValue !== null) {
            if (\is_array($this->finalValue)) {
                return \implode($separator, $this->finalValue);
            }
    
            if ($this->finalValue instanceof \Traversable) {
                return \implode($separator, \iterator_to_array($this->finalValue, false));
            }
            
            return (string) $this->finalValue;
        }
    
        return '';
    }
    
    /**
     * @inheritdoc
     */
    public function toArray(bool $preserveKeys = false): array
    {
        return $this->asArray($preserveKeys) ?? [];
    }
    
    /**
     * @inheritdoc
     */
    public function toArrayAssoc(): array
    {
        return $this->asArray() ?? [];
    }
    
    /**
     * @inheritdoc
     */
    public function toJson(?int $flags = null, bool $preserveKeys = false): string
    {
        if (\is_iterable($this->finalValue)) {
            $data = $this->asArray($preserveKeys);
        } else {
            $data = $this->found || $this->finalValue !== null ? $this->finalValue : null;
        }

        return \json_encode($data, Helper::jsonFlags($flags));
    }
    
    /**
     * @inheritdoc
     */
    public function toJsonAssoc(?int $flags = null): string
    {
        return \json_encode($this->asArray(), Helper::jsonFlags($flags));
    }
    
    /**
     * @inheritdoc
     */
    public function stream(): Stream
    {
        return Stream::from($this);
    }
    
    /**
     * @inheritdoc
     */
    public function count(): int
    {
        if ($this->finalValue !== null) {
            if (\is_countable($this->finalValue)) {
                return \count($this->finalValue);
            }
            
            return 1;
        }
        
        return 0;
    }
    
    public function getIterator(): \Iterator
    {
        if ($this->found || $this->finalValue !== null) {
            if (\is_array($this->finalValue)) {
                return new \ArrayIterator($this->finalValue);
            }
        
            if ($this->finalValue instanceof \Iterator) {
                return $this->finalValue;
            }
    
            return new \ArrayIterator([$this->key ?? 0 => $this->finalValue]);
        }
        
        return new \ArrayIterator([]);
    }
    
    private function asArray(bool $preserveKeys = true): ?array
    {
        if ($this->found || $this->finalValue !== null) {
            if (\is_array($this->finalValue)) {
                return $preserveKeys ? $this->finalValue : \array_values($this->finalValue);
            }
        
            if ($this->finalValue instanceof \Traversable) {
                return \iterator_to_array($this->finalValue, $preserveKeys);
            }
    
            if ($preserveKeys) {
                return [$this->key ?? 0 => $this->finalValue];
            }
            
            return [$this->finalValue];
        }
        
        return null;
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->isDestroying = true;
            
            $this->finalValue = null;
            $this->rawValue = null;
        }
    }
}