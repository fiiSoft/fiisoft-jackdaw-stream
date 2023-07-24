<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Collector;

abstract class BaseCollector implements Collector
{
    private bool $allowKeys;
    
    public function __construct(?bool $allowKeys = true)
    {
        $this->allowKeys = $allowKeys ?? true;
    }
    
    final public function canPreserveKeys(): bool
    {
        return $this->allowKeys;
    }
    
    final public function allowKeys(?bool $allowKeys): void
    {
        if ($allowKeys !== null) {
            $this->allowKeys = $allowKeys;
        }
    }
}