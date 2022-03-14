<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer;

final class Sleeper implements Consumer
{
    private int $microseconds;
    
    public function __construct(int $microseconds)
    {
        if ($microseconds < 0) {
            throw new \InvalidArgumentException('Invalid param microseconds');
        }
        
        $this->microseconds = $microseconds;
    }
    
    public function consume($value, $key): void
    {
        \usleep($this->microseconds);
    }
}