<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Internal\StreamPipe;

final class FeedMany extends BaseOperation
{
    /** @var StreamPipe[] */
    private array $streams;
    
    public function __construct(StreamPipe ...$streams)
    {
        if (empty($streams)) {
            throw new \InvalidArgumentException('FeedMany requires at least one stream');
        }
        
        $this->streams = $streams;
    }
    
    public function handle(Signal $signal): void
    {
        foreach ($this->streams as $key => $stream) {
            if (!$signal->sendTo($stream)) {
                unset($this->streams[$key]);
            }
        }
        
        $this->next->handle($signal);
    }
    
    public function add(Feed $next): void
    {
        $this->streams[] = $next->stream();
    }
}