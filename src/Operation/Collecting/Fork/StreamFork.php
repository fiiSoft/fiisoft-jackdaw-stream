<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting\Fork;

use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Internal\ForkCollaborator;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Collecting\Fork;
use FiiSoft\Jackdaw\Operation\Terminating\FinalOperation;
use FiiSoft\Jackdaw\Stream;

final class StreamFork extends Fork
{
    private Discriminator $discriminator;
    private ForkCollaborator $prototype;
    
    /** @var Stream[] */
    private array $streams = [];
    
    protected function __construct(Discriminator $discriminator, ForkCollaborator $prototype)
    {
        parent::__construct();
        
        $this->discriminator = $discriminator;
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
        
        $this->cleanUp();
    }
    
    /**
     * @return array<string|int, mixed>
     */
    protected function extractData(): array
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
    
    protected function cleanUp(): void
    {
        foreach ($this->streams as $stream) {
            $stream->destroy();
        }
        
        $this->streams = [];
    }
}