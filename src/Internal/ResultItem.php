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
    public function toString(): string
    {
        return $this->found || $this->value !== null ? (string) $this->value : '';
    }
    
    /**
     * @inheritdoc
     */
    public function toArray(): array
    {
        return $this->found || $this->value !== null ? [$this->value] : [];
    }
    
    /**
     * @inheritdoc
     */
    public function toArrayAssoc(): array
    {
        return $this->found || $this->value !== null ? [$this->key ?? 0 => $this->value] : [];
    }
    
    /**
     * @inheritdoc
     */
    public function toJson(int $flags = 0): string
    {
        $data = $this->found || $this->value !== null ? $this->value : null;
        return \json_encode($data, $flags);
    }
    
    /**
     * @inheritdoc
     */
    public function toJsonAssoc(int $flags = 0): string
    {
        $data = $this->found || $this->value !== null ? [$this->key ?? 0 => $this->value] : null;
        return \json_encode($data, $flags);
    }
    
    /**
     * @inheritdoc
     */
    public function __toString(): string
    {
        return $this->toString();
    }
    
    /**
     * @inheritdoc
     */
    public function run()
    {
        //do noting
    }
}