<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Consumer\Consumers;

final class ResultItem implements Result
{
    /** @var bool */
    private $found = false;
    
    /** @var string|int */
    private $key = null;
    
    /** @var mixed */
    private $value = null;
    
    public static function createFound(Item $item): Result
    {
        return new self($item);
    }
    
    public static function createNotFound($default = null): Result
    {
        return new self(null, $default);
    }
    
    private function __construct(Item $item = null, $default = null)
    {
        if ($item !== null) {
            $this->found = true;
            $this->value = $item->value;
            $this->key = $item->key;
        } else {
            $this->value = $default;
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
        return $this->found || $this->value !== null ? $this->key ?? 0 : null;
    }
    
    /**
     * @inheritdoc
     */
    public function get()
    {
        return $this->value;
    }
    
    /**
     * @inheritdoc
     */
    public function tuple(): array
    {
        return $this->found || $this->value !== null ? [$this->key ?? 0, $this->value] : [];
    }
    
    /**
     * @inheritdoc
     */
    public function call($consumer)
    {
        Consumers::getAdapter($consumer)->consume($this->value, $this->key);
    }
    
    /**
     * @inheritdoc
     */
    public function toString(string $separator = ','): string
    {
        if ($this->found || $this->value !== null) {
            if (\is_array($this->value)) {
                return \implode($separator, $this->value);
            }
    
            if ($this->value instanceof \Traversable) {
                return \implode($separator, \iterator_to_array($this->value, false));
            }
            
            return (string) $this->value;
        }
    
        return '';
    }
    
    /**
     * @inheritdoc
     */
    public function toArray(bool $preserveKeys = false): array
    {
        if ($preserveKeys || \is_array($this->value) || $this->value instanceof \Traversable) {
            return $this->toArrayAssoc();
        }
        
        return $this->found || $this->value !== null ? [$this->value] : [];
    }
    
    /**
     * @inheritdoc
     */
    public function toArrayAssoc(): array
    {
        return $this->getValue() ?? [];
    }
    
    /**
     * @inheritdoc
     */
    public function toJson(int $flags = 0, bool $preserveKeys = false): string
    {
        if ($preserveKeys || \is_array($this->value) || $this->value instanceof \Traversable) {
            return $this->toJsonAssoc($flags);
        }
    
        $data = $this->found || $this->value !== null ? $this->value : null;
        return \json_encode($data, $flags);
    }
    
    /**
     * @inheritdoc
     */
    public function toJsonAssoc(int $flags = 0): string
    {
        return \json_encode($this->getValue(), $flags);
    }
    
    private function getValue()
    {
        if ($this->found || $this->value !== null) {
            if (\is_array($this->value)) {
                return $this->value;
            }
        
            if ($this->value instanceof \Traversable) {
                return \iterator_to_array($this->value);
            }
        
            return [$this->key ?? 0 => $this->value];
        }
        
        return null;
    }
    
    /**
     * @inheritdoc
     */
    public function run()
    {
        //do noting
    }
}