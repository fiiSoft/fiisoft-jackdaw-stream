<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Reducer;

use FiiSoft\Jackdaw\Internal\Item;

final class Concat implements Reducer
{
    private array $pieces = [];
    private string $separator;
    
    public function __construct(string $separator = '')
    {
        $this->separator = $separator;
    }
    
    public function consume($value): void
    {
        $this->pieces[] = (string) $value;
    }
    
    public function result(): string
    {
        return \implode($this->separator, $this->pieces);
    }
    
    public function hasResult(): bool
    {
        return !empty($this->pieces);
    }
    
    public function getResult(): Item
    {
        return new Item(0, $this->result());
    }
    
    public function reset(): void
    {
        $this->pieces = [];
    }
}