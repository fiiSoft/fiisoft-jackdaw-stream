<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\BaseProducer;
use Ramsey\Uuid\Uuid;

final class RandomUuid extends BaseProducer
{
    private int $count = 0;
    private int $limit;
    
    private bool $asHex;
    
    public function __construct(bool $asHex = true, int $limit = \PHP_INT_MAX)
    {
        if ($limit < 0) {
            throw new \InvalidArgumentException('Invalid param limit');
        }
    
        $this->limit = $limit;
        $this->asHex = $asHex;
    }
    
    public function feed(Item $item): \Generator
    {
        while ($this->count !== $this->limit) {
    
            $item->key = $this->count++;
            $item->value = $this->asHex ? Uuid::uuid4()->getHex()->toString() : Uuid::uuid4()->toString();
            
            yield;
        }
    }
}