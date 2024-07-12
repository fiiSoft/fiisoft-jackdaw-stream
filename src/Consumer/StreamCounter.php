<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer;

use FiiSoft\Jackdaw\Internal\StreamAware;
use FiiSoft\Jackdaw\Internal\StreamState;
use FiiSoft\Jackdaw\Stream;

final class StreamCounter extends StreamState implements Counter, StreamAware
{
    private int $count = 0;
    
    /** @var Stream[] */
    private array $streams = [];
    
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
    public function count(): int
    {
        foreach ($this->streams as $id => $stream) {
            unset($this->streams[$id]);
            
            if ($stream->isNotStartedYet()) {
                $stream->run();
            }
        }
        
        return $this->count;
    }
    
    /**
     * @inheritDoc
     */
    public function get(): int
    {
        return $this->count;
    }
    
    public function assignStream(Stream $stream): void
    {
        $this->streams[\spl_object_id($stream)] = $stream;
    }
}