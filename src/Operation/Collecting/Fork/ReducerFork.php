<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting\Fork;

use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Collecting\Fork;
use FiiSoft\Jackdaw\Reducer\Reducer;

final class ReducerFork extends Fork
{
    private Discriminator $discriminator;
    private Reducer $prototype;
    
    /** @var Reducer[] */
    private array $reducers = [];
    
    protected function __construct(Discriminator $discriminator, Reducer $prototype)
    {
        parent::__construct();
        
        $this->discriminator = $discriminator;
        $this->prototype = $prototype;
    }
    
    public function handle(Signal $signal): void
    {
        $classifier = $this->discriminator->classify($signal->item->value, $signal->item->key);
        
        if (isset($this->reducers[$classifier])) {
            $reducer = $this->reducers[$classifier];
        } else {
            $reducer = clone $this->prototype;
            $this->reducers[$classifier] = $reducer;
        }
        
        $reducer->consume($signal->item->value);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $classifier = $this->discriminator->classify($value, $key);
            
            if (isset($this->reducers[$classifier])) {
                $reducer = $this->reducers[$classifier];
            } else {
                $reducer = clone $this->prototype;
                $this->reducers[$classifier] = $reducer;
            }
            
            $reducer->consume($value);
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
            static fn(Reducer $reducer) => $reducer->result(),
            \array_filter(
                $this->reducers,
                static fn(Reducer $reducer): bool => $reducer->hasResult()
            )
        );
    }
    
    protected function cleanUp(): void
    {
        foreach ($this->reducers as $reducer) {
            $reducer->reset();
        }
        
        $this->reducers = [];
    }
}