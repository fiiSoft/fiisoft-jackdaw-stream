<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting;

use FiiSoft\Jackdaw\Discriminator\DiscriminatorReady;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Collecting\Fork\BaseFork;
use FiiSoft\Jackdaw\Operation\Collecting\Fork\ForkHandler;
use FiiSoft\Jackdaw\Operation\Collecting\Fork\ForkHandlerFactory;
use FiiSoft\Jackdaw\Operation\Internal\ForkReady;

final class Fork extends BaseFork
{
    private ForkHandler $prototype;
    
    /**
     * @param DiscriminatorReady|callable|array<string|int> $discriminator
     */
    public function __construct($discriminator, ForkReady $prototype)
    {
        parent::__construct($discriminator);
        
        $this->prototype = ForkHandlerFactory::adaptPrototype($prototype);
    }
    
    public function prepare(): void
    {
        parent::prepare();
        
        $this->prototype->prepare();
    }
    
    public function handle(Signal $signal): void
    {
        $classifier = $this->discriminator->classify($signal->item->value, $signal->item->key);
        
        if (isset($this->handlers[$classifier])) {
            $handler = $this->handlers[$classifier];
        } else {
            $handler = $this->prototype->create();
            $this->handlers[$classifier] = $handler;
        }
        
        $handler->accept($signal->item->value, $signal->item->key);
    }
    
    /**
     * @inheritDoc
     */
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $classifier = $this->discriminator->classify($value, $key);
            
            if (isset($this->handlers[$classifier])) {
                $handler = $this->handlers[$classifier];
            } else {
                $handler = $this->prototype->create();
                $this->handlers[$classifier] = $handler;
            }
            
            $handler->accept($value, $key);
        }
        
        yield from $this->finishStreaming();
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            parent::destroy();
            
            $this->prototype->destroy();
        }
    }
}