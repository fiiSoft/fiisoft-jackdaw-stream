<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Reducer;

use FiiSoft\Jackdaw\Exception\InvalidParamException;

final class MultiReducer implements Reducer
{
    /** @var Reducer[]  */
    private array $pattern = [];
    
    /** @var array<string|int, mixed>|null  */
    private ?array $result = null;
    
    /**
     * @param array<Reducer|callable> $pattern
     */
    public function __construct(array $pattern)
    {
        if (empty($pattern)) {
            throw InvalidParamException::describe('pattern', $pattern);
        }
        
        $this->pattern = \array_map(static fn($item): Reducer => Reducers::getAdapter($item), $pattern);
    }
    
    /**
     * @inheritDoc
     */
    public function consume($value): void
    {
        foreach ($this->pattern as $reducer) {
            $reducer->consume($value);
        }
    }
    
    /**
     * @return array<string|int, mixed>|null
     */
    public function result(): ?array
    {
        return $this->result;
    }
    
    public function reset(): void
    {
        $this->result = null;
        
        foreach ($this->pattern as $reducer) {
            $reducer->reset();
        }
    }
    
    public function hasResult(): bool
    {
        if ($this->result === null) {
            $this->prepareResult();
        }
        
        return $this->result !== null;
    }
    
    private function prepareResult(): void
    {
        $result = [];
        
        foreach ($this->pattern as $key => $reducer) {
            if ($reducer->hasResult()) {
                $result[$key] = $reducer->result();
            }
        }
        
        if (!empty($result)) {
            $this->result = $result;
        }
    }
    
    public function __clone()
    {
        foreach ($this->pattern as $key => $reducer) {
            if (\is_object($reducer)) {
                $this->pattern[$key] = clone $reducer;
            }
        }
    }
}