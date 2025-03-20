<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting\Fork;

use FiiSoft\Jackdaw\Producer\Tech\BaseProducer;

final class ForkProducer extends BaseProducer
{
    /** @var ForkHandler[] */
    private array $handlers;
    
    /**
     * @param ForkHandler[] $handlers
     */
    public function __construct(array $handlers)
    {
        $this->handlers = $handlers;
    }
    
    /**
     * @inheritDoc
     */
    public function getIterator(): \Generator
    {
        foreach ($this->handlers as $key => $handler) {
            if ($handler->isEmpty()) {
                continue;
            }
            
            yield $key => $handler->result();
        }
    }
    
    /**
     * @inheritDoc
     */
    public function destroy(): void
    {
        foreach ($this->handlers as $handler) {
            $handler->destroy();
        }
        
        $this->handlers = [];
    }
}