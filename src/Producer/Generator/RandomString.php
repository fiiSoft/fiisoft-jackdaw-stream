<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator;

use FiiSoft\Jackdaw\Producer\Generator\Exception\GeneratorExceptionFactory;
use FiiSoft\Jackdaw\Producer\Tech\LimitedProducer;

final class RandomString extends LimitedProducer
{
    private const DEFAULT_CHARSET = 'qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM1234567890';
    
    private int $minLength;
    private int $maxLength;
    
    /** @var string[] */
    private array $chars = [];
    
    public function __construct(
        int $minLength,
        ?int $maxLength = null,
        int $limit = \PHP_INT_MAX,
        ?string $charset = null
    ) {
        parent::__construct($limit);
        
        if ($maxLength !== null && $maxLength < $minLength) {
            throw GeneratorExceptionFactory::maxLengthCannotBeLessThanMinLength();
        }
    
        $this->minLength = $minLength;
        $this->maxLength = $maxLength ?? $minLength;
        
        $this->chars = \str_split($charset ?: self::DEFAULT_CHARSET);
    }
    
    public function getIterator(): \Generator
    {
        $count = 0;
        
        while ($count !== $this->limit) {
    
            $length = $this->minLength === $this->maxLength
                ? $this->minLength
                : \mt_rand($this->minLength, $this->maxLength);
            
            \shuffle($this->chars);
            
            yield $count++ => \implode('', \array_slice($this->chars, 0, $length));
        }
    }
}