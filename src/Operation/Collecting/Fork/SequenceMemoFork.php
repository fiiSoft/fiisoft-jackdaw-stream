<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting\Fork;

use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Memo\SequenceMemo;
use FiiSoft\Jackdaw\Operation\Collecting\Fork;

final class SequenceMemoFork extends Fork
{
    private Discriminator $discriminator;
    private SequenceMemo $prototype;
    
    /** @var SequenceMemo [] */
    private array $sequences = [];
    
    protected function __construct(Discriminator $discriminator, SequenceMemo $prototype)
    {
        parent::__construct();
        
        $this->discriminator = $discriminator;
        $this->prototype = $prototype;
    }
    
    public function handle(Signal $signal): void
    {
        $classifier = $this->discriminator->classify($signal->item->value, $signal->item->key);
        
        if (isset($this->sequences[$classifier])) {
            $sequence = $this->sequences[$classifier];
        } else {
            $sequence = clone $this->prototype;
            $this->sequences[$classifier] = $sequence;
        }
        
        $sequence->write($signal->item->value, $signal->item->key);
    }
    
    /**
     * @inheritDoc
     */
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $classifier = $this->discriminator->classify($value, $key);
            
            if (isset($this->sequences[$classifier])) {
                $sequence = $this->sequences[$classifier];
            } else {
                $sequence = clone $this->prototype;
                $this->sequences[$classifier] = $sequence;
            }
            
            $sequence->write($value, $key);
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
            static fn(SequenceMemo $sequence): array => $sequence->toArray(),
            \array_filter(
                $this->sequences,
                static fn(SequenceMemo $sequence): bool => $sequence->count() > 0
            )
        );
    }
    
    protected function cleanUp(): void
    {
        foreach ($this->sequences as $sequence) {
            $sequence->clear();
        }
        
        $this->sequences = [];
    }
}