<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Adapter;

use FiiSoft\Jackdaw\Producer\Tech\BaseProducer;

final class ArrayAdapter extends BaseProducer
{
    private array $source;
    
    public function __construct(array $source)
    {
        $this->source = $source;
    }
    
    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->source);
    }
    
    public function destroy(): void
    {
        $this->source = [];
    }
}