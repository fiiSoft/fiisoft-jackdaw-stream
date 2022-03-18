<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Transformer\Transformer;
use FiiSoft\Jackdaw\Transformer\Transformers;

final class ResultItem implements ResultApi
{
    private ?Transformer $transformer = null;
    
    private bool $found = false;
    
    /** @var string|int */
    private $key = null;
    
    /** @var mixed */
    private $rawValue = null;
    
    /** @var mixed */
    private $finalValue = null;
    
    public static function createFound(Item $item, ?Transformer $transformer = null): self
    {
        return new self($item, null, $transformer);
    }
    
    public static function createNotFound($default = null): self
    {
        return new self(null, $default);
    }
    
    private function __construct(?Item $item, $default = null, ?Transformer $transformer = null)
    {
        if ($item !== null) {
            $this->found = true;
            $this->rawValue = $item->value;
            $this->key = $item->key;
            $this->transformer = $transformer;
            
            $this->prepareResult();
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
        $this->transformer = Transformers::getAdapter($transformer);
        $this->prepareResult();
        
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
        if ($preserveKeys || \is_iterable($this->finalValue)) {
            return $this->toArrayAssoc();
        }
        
        return $this->found || $this->finalValue !== null ? [$this->finalValue] : [];
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
    public function toJson(int $flags = 0, bool $preserveKeys = false): string
    {
        if ($preserveKeys || \is_iterable($this->finalValue)) {
            return $this->toJsonAssoc($flags);
        }
    
        $data = $this->found || $this->finalValue !== null ? $this->finalValue : null;
        
        return \json_encode($data, \JSON_THROW_ON_ERROR | $flags);
    }
    
    /**
     * @inheritdoc
     */
    public function toJsonAssoc(int $flags = 0): string
    {
        return \json_encode($this->asArray(), \JSON_THROW_ON_ERROR | $flags);
    }
    
    private function asArray(): ?array
    {
        if ($this->found || $this->finalValue !== null) {
            if (\is_array($this->finalValue)) {
                return $this->finalValue;
            }
        
            if ($this->finalValue instanceof \Traversable) {
                return \iterator_to_array($this->finalValue);
            }
        
            return [$this->key ?? 0 => $this->finalValue];
        }
        
        return null;
    }
    
    /**
     * @inheritdoc
     */
    public function run(): void
    {
        //do noting
    }
    
    private function prepareResult(): void
    {
        if ($this->found) {
            $this->finalValue = $this->transformer !== null
             ? $this->transformer->transform($this->rawValue, $this->key)
             : $this->rawValue;
        }
    }
}