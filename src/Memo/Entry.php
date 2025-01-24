<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo;

final class Entry
{
    /** @var mixed */
    public $key;
    
    /** @var mixed */
    public $value;
    
    /**
     * @param mixed $key
     * @param mixed $value
     */
    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }
    
    /**
     * @param mixed $key
     * @param mixed $value
     */
    public function is($key, $value): bool
    {
        return $this->key === $key && $this->value === $value;
    }
    
    /**
     * @return array{string|int, mixed}
     */
    public function asTuple(): array
    {
        return [$this->key, $this->value];
    }
    
    /**
     * @return array<string|int, mixed>
     */
    public function asPair(): array
    {
        return [$this->key => $this->value];
    }
}