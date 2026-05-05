<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting\Categorize;

use FiiSoft\Jackdaw\Internal\DataAware;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Operation\Collecting\Categorize;

final class CategorizeReindexKeys extends Categorize
{
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $this->collections[$this->discriminator->classify($value, $key)][] = $value;
        }
        
        if ($this->next instanceof DataAware) {
            $this->next->setCollectedData($this->collections);
        } else {
            yield from $this->collections;
        }
        
        $this->collections = [];
    }
    
    protected function collect(Item $item): void
    {
        $this->collections[$this->discriminator->classify($item->value, $item->key)][] = $item->value;
    }
}