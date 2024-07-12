<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Internal;

use FiiSoft\Jackdaw\Producer\Tech\BaseProducer;

final class ReverseArrayIterator extends BaseProducer
{
    /** @var array<string|int, mixed> */
    private array $source;

    private bool $reindex;
    
    /**
     * @param array<string|int, mixed> $source
     */
    public function __construct(array $source, bool $reindex = false)
    {
        $this->source = $source;
        $this->reindex = $reindex;
    }
    
    public function getIterator(): \Generator
    {
        if ($this->reindex) {
            $index = 0;
            
            for (
                \end($this->source);
                \key($this->source) !== null;
                \prev($this->source)
            ){
                yield $index++ => \current($this->source);
            }
        } else {
            for (
                \end($this->source);
                \key($this->source) !== null;
                \prev($this->source)
            ){
                yield \key($this->source) => \current($this->source);
            }
        }
    }
    
    public function destroy(): void
    {
        $this->source = [];
    }
}