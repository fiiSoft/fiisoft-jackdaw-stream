<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer;

final class StreamCounter implements Counter
{
    private int $count = 0;
    
    /**
     * @inheritDoc
     */
    public function consume($value, $key): void
    {
        ++$this->count;
    }
    
    /**
     * @inheritDoc
     */
    public function get(): int
    {
        return $this->count;
    }
    
    /**
     * @inheritDoc
     */
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            ++$this->count;
            
            yield $key => $value;
        }
    }
}