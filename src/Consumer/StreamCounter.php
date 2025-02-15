<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer;

use FiiSoft\Jackdaw\Internal\StreamAware;
use FiiSoft\Jackdaw\Stream;

final class StreamCounter implements Counter, StreamAware
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
        while (!empty($this->streams)) {
            $stream = \array_shift($this->streams);
            $stream->run(true);
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