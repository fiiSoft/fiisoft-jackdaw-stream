<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Reducer;

final class Concat implements Reducer
{
    /** @var string[] */
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
    
    public function reset(): void
    {
        $this->pieces = [];
    }
}