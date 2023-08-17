<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Collector\Collector;
use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Consumer\Consumer;
use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Internal\ResultApi;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Internal\StreamPipe;
use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Operation\Internal\Dispatcher\Handler;
use FiiSoft\Jackdaw\Operation\Internal\Dispatcher\Handlers;
use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Predicate\Predicate;
use FiiSoft\Jackdaw\Reducer\Reducer;
use FiiSoft\Jackdaw\Stream;

final class Dispatch extends StreamPipe implements Operation
{
    use CommonOperationCode;
    
    private Discriminator $discriminator;
    
    /** @var Handler[] */
    private array $handlers;
    
    /** @var StreamPipe[] */
    private array $streams = [];
    
    /**
     * @param Discriminator|Condition|Predicate|Filter|Mapper|callable|array $discriminator
     * @param Stream[]|LastOperation[]|ResultApi[]|Collector[]|Consumer[]|Reducer[] $handlers
     */
    public function __construct($discriminator, array $handlers)
    {
        if (empty($handlers)) {
            throw new \InvalidArgumentException('Param handlers cannot be empty');
        }
        
        $this->discriminator = Discriminators::getAdapter($discriminator);
        $this->handlers = Handlers::prepare($handlers);
        
        foreach ($handlers as $handler) {
            if ($handler instanceof StreamPipe) {
                
                $id = \spl_object_id($handler);
                
                if (!isset($this->streams[$id])) {
                    $this->streams[$id] = $handler;
                    $handler->prepareSubstream(false);
                }
            }
        }
    }
    
    public function handle(Signal $signal): void
    {
        $classifier = $this->discriminator->classify($signal->item->value, $signal->item->key);
        
        if (\is_bool($classifier)) {
            $classifier = (int) $classifier;
        } elseif (!\is_string($classifier) && !\is_int($classifier)) {
            throw new \UnexpectedValueException(
                'Value returned from discriminator is inappropriate (got '.Helper::typeOfParam($classifier).')'
            );
        }
        
        if (isset($this->handlers[$classifier])) {
            $this->handlers[$classifier]->handle($signal);
        } else {
            throw new \RuntimeException('There is no handler defined for classifier '.$classifier);
        }
        
        $this->next->handle($signal);
    }
    
    public function assignStream(Stream $stream): void
    {
        if ($this->next !== null) {
            $this->next->assignStream($stream);
        }
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        while (!empty($this->streams)) {
            foreach ($this->streams as $key => $stream) {
                if (!$stream->continueIteration()) {
                    unset($this->streams[$key]);
                }
            }
        }
        
        return $this->next->streamingFinished($signal);
    }
    
    protected function __clone()
    {
        if ($this->next !== null) {
            $this->next = clone $this->next;
            $this->next->setPrev($this);
        }
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->isDestroying = true;
            
            $this->handlers = [];
            $this->streams = [];
            
            if ($this->next !== null) {
                $this->next->destroy();
                $this->next = null;
            }
            
            if ($this->prev !== null) {
                $this->prev->destroy();
                $this->prev = null;
            }
        }
    }
}