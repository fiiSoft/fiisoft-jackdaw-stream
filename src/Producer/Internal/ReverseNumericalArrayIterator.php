<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Internal;

use FiiSoft\Jackdaw\Producer\Tech\BaseProducer;

final class ReverseNumericalArrayIterator extends BaseProducer
{
    /** @var array<int, mixed> */
    private array $data;
    
    private bool $reindex;
    
    /**
     * @param array<int, mixed> $data
     */
    public function __construct(array $data, bool $reindex = false)
    {
        $this->data = $data;
        $this->reindex = $reindex;
    }
    
    public function getIterator(): \Generator
    {
        if ($this->reindex) {
            for ($index = 0, $i = \count($this->data) - 1; $i >= 0; --$i) {
                yield $index++ => $this->data[$i];
            }
        } else {
            for ($i = \count($this->data) - 1; $i >= 0; --$i) {
                yield $i => $this->data[$i];
            }
        }
    }
    
    public function destroy(): void
    {
        $this->data = [];
    }
}