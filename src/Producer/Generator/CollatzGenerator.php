<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\BaseProducer;

/**
 * Made purely for fun. Enjoy!
 */
final class CollatzGenerator extends BaseProducer
{
    private ?int $startNumber = null;
    private int $count = 0;
    
    public function __construct(int $startNumber = null)
    {
        if ($startNumber !== null && $startNumber < 1) {
            throw new \InvalidArgumentException('Invalid param startNumber');
        }
        
        $this->startNumber = $startNumber;
    }
    
    public function feed(Item $item): \Generator
    {
        $number = $this->startNumber ?? $this->findStartNumber();
    
        $item->key = $this->count++;
        $item->value = $number;
        yield;
    
        while ($number > 1) {
            if (($number & 1) === 0) {
                $number >>= 1;
            } else {
                $number = (3 * $number + 1);
            }
    
            $item->key = $this->count++;
            $item->value = (int) $number;
            yield;
        }
    }
    
    private function findStartNumber(): int
    {
        return (int) (\PHP_INT_MAX / \mt_rand(3577, \PHP_INT_MAX / 3577));
    }
}