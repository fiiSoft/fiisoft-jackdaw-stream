<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator;

use FiiSoft\Jackdaw\Producer\Tech\BaseProducer;

final class CombinedArrays extends BaseProducer
{
    private array $keys;
    private array $values;
    
    /**
     * @param array $keys it MUST be standard numerical array indexed from 0
     * @param array $values it MUST be standard numerical array indexed from 0
     */
    public function __construct(array $keys, array $values)
    {
        $this->keys = $keys;
        $this->values = $values;
    }
    
    public function getIterator(): \Generator
    {
        for ($i = 0, $j = \min(\count($this->keys), \count($this->values)); $i < $j; ++$i) {
            yield $this->keys[$i] => $this->values[$i];
        }
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->keys = $this->values = [];
            
            parent::destroy();
        }
    }
}