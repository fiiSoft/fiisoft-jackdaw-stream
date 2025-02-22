<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer;

use FiiSoft\Jackdaw\Exception\InvalidParamException;

final class Sleeper implements Consumer
{
    private int $microseconds;
    
    public function __construct(int $microseconds)
    {
        if ($microseconds < 0) {
            throw InvalidParamException::describe('microseconds', $microseconds);
        }
        
        $this->microseconds = $microseconds;
    }
    
    public function consume($value, $key): void
    {
        \usleep($this->microseconds);
    }
    
    /**
     * @inheritDoc
     */
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            \usleep($this->microseconds);
            
            yield $key => $value;
        }
    }
}