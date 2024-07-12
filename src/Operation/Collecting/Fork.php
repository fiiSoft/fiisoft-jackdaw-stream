<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting;

use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Discriminator\DiscriminatorReady;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Internal\ForkCollaborator;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\ProcessOperation;
use FiiSoft\Jackdaw\Operation\Terminating\FinalOperation;
use FiiSoft\Jackdaw\Producer\Producers;
use FiiSoft\Jackdaw\Stream;

final class Fork extends ProcessOperation
{
    private Discriminator $discriminator;
    private ForkCollaborator $prototype;
    
    /** @var Stream[] */
    private array $streams = [];
    
    /**
     * @param DiscriminatorReady|callable|array<string|int> $discriminator
     */
    public function __construct($discriminator, ForkCollaborator $prototype)
    {
        $this->discriminator = Discriminators::getAdapter($discriminator);
        $this->prototype = $prototype;
    }
    
    public function handle(Signal $signal): void
    {
        $classifier = $this->discriminator->classify($signal->item->value, $signal->item->key);
        if (\is_bool($classifier)) {
            $classifier = (int) $classifier;
        }
    
        if (isset($this->streams[$classifier])) {
            $stream = $this->streams[$classifier];
        } else {
            $stream = $this->prototype->cloneStream();
            $this->streams[$classifier] = $stream;
        }
        
        $stream->process($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        $signal = $this->createSignal();
        $item = $signal->item;
        
        foreach ($stream as $item->key => $item->value) {
            $classifier = $this->discriminator->classify($signal->item->value, $signal->item->key);
            if (\is_bool($classifier)) {
                $classifier = (int) $classifier;
            }
            
            if (isset($this->streams[$classifier])) {
                $fork = $this->streams[$classifier];
            } else {
                $fork = $this->prototype->cloneStream();
                $this->streams[$classifier] = $fork;
            }
            
            $fork->process($signal);
        }
        
        yield from $this->extractData();
        
        $this->destroySubstreams();
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        $signal->restartWith(Producers::getAdapter($this->extractData()), $this->next);
        
        $this->destroySubstreams();
        
        return true;
    }
    
    /**
     * @return array<string|int, mixed>
     */
    private function extractData(): array
    {
        return \array_map(
            static fn(FinalOperation $op) => $op->get(),
            \array_filter(
                \array_map(
                    static fn(Stream $stream): ?FinalOperation => $stream->getFinalOperation(),
                    $this->streams
                ),
                static fn(?FinalOperation $op): bool => $op !== null
            )
        );
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            
            $this->destroySubstreams();
            
            parent::destroy();
        }
    }
    
    private function destroySubstreams(): void
    {
        foreach ($this->streams as $stream) {
            $stream->destroy();
        }
        
        $this->streams = [];
    }
}