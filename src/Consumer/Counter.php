<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer;

use FiiSoft\Jackdaw\Internal\StreamAware;
use FiiSoft\Jackdaw\Internal\StreamState;
use FiiSoft\Jackdaw\Stream;

final class Counter extends StreamState implements Consumer, StreamAware
{
    private int $count = 0;
    
    private ?Stream $stream = null;
    
    /**
     * @inheritDoc
     */
    public function consume($value, $key): void
    {
        ++$this->count;
    }
    
    /**
     * Alias for method get() for convenient use.
     */
    public function count(): int
    {
        return $this->get();
    }
    
    /**
     * Alias for method count() for convenient use.
     */
    public function get(): int
    {
        if ($this->stream !== null && $this->stream->isNotStartedYet()) {
            $this->stream->run();
            $this->stream = null;
        }
        
        return $this->count;
    }
    
    public function assignStream(Stream $stream): void
    {
        $this->stream = $stream;
    }
}