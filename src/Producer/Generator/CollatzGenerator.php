<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Producer\Tech\BaseProducer;

/**
 * Made purely for fun. Enjoy!
 */
final class CollatzGenerator extends BaseProducer
{
    private ?int $startNumber = null;
    private int $count = 0;
    
    public function __construct(?int $startNumber = null)
    {
        if ($startNumber !== null && $startNumber < 1) {
            throw InvalidParamException::describe('startNumber', $startNumber);
        }
        
        $this->startNumber = $startNumber;
    }
    
    public function getIterator(): \Generator
    {
        $number = $this->startNumber ?? $this->findStartNumber();
    
        yield $this->count++ => $number;
    
        while ($number > 1) {
            if (($number & 1) === 0) {
                $number >>= 1;
            } else {
                $number = (3 * $number + 1);
            }
    
            yield $this->count++ => $number;
        }
    }
    
    private function findStartNumber(): int
    {
        return (int) (\PHP_INT_MAX / \mt_rand(3577, (int) (\PHP_INT_MAX / 3577)));
    }
}