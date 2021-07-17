<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Producer;

final class RandomString implements Producer
{
    private const DEFAULT_CHARSET = 'qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM1234567890';
    
    private int $minLength;
    private int $maxLength;
    
    private int $count = 0;
    private int $limit;
    
    /** @var string[] */
    private array $chars = [];
    
    public function __construct(
        int $minLength,
        ?int $maxLength = null,
        int $limit = \PHP_INT_MAX,
        ?string $charset = null
    ) {
        if ($maxLength !== null && $maxLength < $minLength) {
            throw new \InvalidArgumentException('Max length cannot be less than min length');
        }
    
        if ($limit < 0) {
            throw new \InvalidArgumentException('Invalid param limit');
        }
    
        $this->minLength = $minLength;
        $this->maxLength = $maxLength ?? $minLength;
        $this->limit = $limit;
        
        $this->chars = \str_split($charset ?: self::DEFAULT_CHARSET);
    }
    
    public function feed(Item $item): \Generator
    {
        while ($this->count !== $this->limit) {
    
            $length = $this->minLength === $this->maxLength
                ? $this->minLength
                : \mt_rand($this->minLength, $this->maxLength);
            
            \shuffle($this->chars);
            
            $item->key = $this->count++;
            $item->value = \implode(\array_slice($this->chars, 0, $length));
            
            yield;
        }
    }
}