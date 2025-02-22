<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer;

final class ByArgs implements Consumer
{
    /** @var callable */
    private $consumer;
    
    public function __construct(callable $consumer)
    {
        $this->consumer = $consumer;
    }
    
    /**
     * @inheritDoc
     */
    public function consume($value, $key): void
    {
        ($this->consumer)(...\array_values($value));
    }
    
    /**
     * @inheritDoc
     */
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            ($this->consumer)(...\array_values($value));
            
            yield $key => $value;
        }
    }
}