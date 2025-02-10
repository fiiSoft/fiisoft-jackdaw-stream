<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting\Fork;

use FiiSoft\Jackdaw\Collector\IterableCollector;
use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Collecting\Fork;

final class CollectorFork extends Fork
{
    private Discriminator $discriminator;
    private IterableCollector $prototype;
    
    /** @var IterableCollector[] */
    private array $collectors = [];
    
    private bool $keepKeys;
    
    protected function __construct(Discriminator $discriminator, IterableCollector $prototype)
    {
        parent::__construct();
        
        $this->discriminator = $discriminator;
        $this->prototype = $prototype;
        $this->keepKeys = $prototype->canPreserveKeys();
    }
    
    public function handle(Signal $signal): void
    {
        $classifier = $this->discriminator->classify($signal->item->value, $signal->item->key);
        
        if (isset($this->collectors[$classifier])) {
            $collector = $this->collectors[$classifier];
        } else {
            $collector = clone $this->prototype;
            $this->collectors[$classifier] = $collector;
        }
        
        if ($this->keepKeys) {
            $collector->set($signal->item->key, $signal->item->value);
        } else {
            $collector->add($signal->item->value);
        }
    }
    
    /**
     * @inheritDoc
     */
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $classifier = $this->discriminator->classify($value, $key);
            
            if (isset($this->collectors[$classifier])) {
                $collector = $this->collectors[$classifier];
            } else {
                $collector = clone $this->prototype;
                $this->collectors[$classifier] = $collector;
            }
            
            if ($this->keepKeys) {
                $collector->set($key, $value);
            } else {
                $collector->add($value);
            }
        }
        
        yield from $this->extractData();
        
        $this->cleanUp();
    }
    
    /**
     * @inheritDoc
     */
    protected function extractData(): array
    {
        return \array_map(
            static fn(IterableCollector $collector): array => $collector->toArray(),
            \array_filter(
                $this->collectors,
                static fn(IterableCollector $collector): bool => $collector->count() > 0
            )
        );
    }
    
    protected function cleanUp(): void
    {
        foreach ($this->collectors as $collector) {
            $collector->clear();
        }
        
        $this->collectors = [];
    }
}