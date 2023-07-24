<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Tech\LimitedProducer;
use Ramsey\Uuid\Uuid;

final class RandomUuid extends LimitedProducer
{
    private bool $asHex;
    
    public function __construct(bool $asHex = true, int $limit = \PHP_INT_MAX)
    {
        parent::__construct($limit);
        
        $this->asHex = $asHex;
    }
    
    public function feed(Item $item): \Generator
    {
        $count = 0;
        
        while ($count !== $this->limit) {
    
            $item->key = $count++;
            $item->value = $this->asHex ? Uuid::uuid4()->getHex()->toString() : Uuid::uuid4()->toString();
            
            yield;
        }
    }
    
    public function getLast(): ?Item
    {
        return null;
    }
}