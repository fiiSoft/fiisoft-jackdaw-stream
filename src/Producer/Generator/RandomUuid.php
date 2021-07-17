<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Producer;
use Ramsey\Uuid\Uuid;

final class RandomUuid implements Producer
{
    /** @var int */
    private $count = 0;
    
    /** @var int */
    private $limit;
    
    /** @var bool */
    private $asHex;
    
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
            $item->value = $this->asHex ? Uuid::uuid4()->getHex() : Uuid::uuid4()->toString();
            
            yield;
        }
    }
}