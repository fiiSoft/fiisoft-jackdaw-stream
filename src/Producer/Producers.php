<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer;

use FiiSoft\Jackdaw\Internal\ResultApi;
use FiiSoft\Jackdaw\Producer\Adapter\ArrayAdapter;
use FiiSoft\Jackdaw\Producer\Adapter\ArrayIteratorAdapter;
use FiiSoft\Jackdaw\Producer\Adapter\IteratorAdapter;
use FiiSoft\Jackdaw\Producer\Adapter\ResultAdapter;
use FiiSoft\Jackdaw\Producer\Generator\CollatzGenerator;
use FiiSoft\Jackdaw\Producer\Generator\Flattener;
use FiiSoft\Jackdaw\Producer\Generator\RandomInt;
use FiiSoft\Jackdaw\Producer\Generator\RandomString;
use FiiSoft\Jackdaw\Producer\Generator\RandomUuid;
use FiiSoft\Jackdaw\Producer\Generator\SequentialInt;
use FiiSoft\Jackdaw\Producer\Generator\Tokenizer;
use FiiSoft\Jackdaw\Producer\Resource\PDOStatementAdapter;
use FiiSoft\Jackdaw\Producer\Resource\TextFileReader;
use FiiSoft\Jackdaw\Stream;

final class Producers
{
    /**
     * @param array<Stream|Producer|ResultApi|\Traversable|\PDOStatement|resource|array|scalar> $elements
     */
    public static function from(array $elements): Producer
    {
        $index = 0;
        $mode = 1;
        $producers = [];
        
        foreach ($elements as $item) {
            if (\is_object($item)) {
                if ($item instanceof \Traversable
                    || $item instanceof ResultApi
                    || $item instanceof Producer
                ) {
                    $mode = 1;
                } elseif ($mode === 1) {
                    $mode = 2;
                }
            } elseif (\is_array($item) || \is_resource($item)) {
                $mode = 1;
            } elseif ($mode === 1) {
                $mode = 2;
            }
            
            if ($mode === 3) {
                $producers[$index][] = $item;
            } elseif ($mode === 2) {
                $mode = 3;
                $producers[++$index][] = $item;
            } else {
                $producers[++$index] = $item;
            }
        }
        
        return \count($producers) === 1
            ? self::getAdapter(\reset($producers))
            : self::multiSourced(...$producers);
    }
    
    /**
     * @param Stream|Producer|ResultApi|\Traversable|\PDOStatement|resource|array $producer
     */
    public static function getAdapter($producer): Producer
    {
        if (\is_array($producer)) {
            return self::fromArray($producer);
        }
        
        if ($producer instanceof Producer) {
            return $producer;
        }
        
        if ($producer instanceof ResultApi) {
            return self::fromResult($producer);
        }
        
        if ($producer instanceof \PDOStatement) {
            return self::fromPDOStatement($producer);
        }
        
        if ($producer instanceof \ArrayIterator) {
            return self::fromArrayIterator($producer);
        }
        
        if ($producer instanceof \Traversable) {
            return self::fromIterator($producer);
        }
        
        if (\is_resource($producer)) {
            return self::resource($producer);
        }
        
        throw new \InvalidArgumentException('Invalid param producer');
    }
    
    /**
     * @param Stream|Producer|ResultApi|\Traversable|\PDOStatement|resource|array $producers
     */
    public static function multiSourced(...$producers): Producer
    {
        return new MultiProducer(
            ...\array_map(static fn($producer): Producer => self::getAdapter($producer), $producers)
        );
    }
    
    public static function fromArray(array $array): Producer
    {
        return new ArrayAdapter($array);
    }
    
    public static function fromIterator(\Traversable $iterator): Producer
    {
        return new IteratorAdapter($iterator);
    }
    
    public static function fromArrayIterator(\ArrayIterator $iterator): Producer
    {
        return new ArrayIteratorAdapter($iterator);
    }
    
    public static function fromResult(ResultApi $result): Producer
    {
        return new ResultAdapter($result);
    }
    
    public static function fromPDOStatement(\PDOStatement $statement, ?int $fetchMode = null): Producer
    {
        return new PDOStatementAdapter($statement, $fetchMode);
    }
    
    public static function randomInt(int $min = 1, int $max = \PHP_INT_MAX, int $limit = \PHP_INT_MAX): Producer
    {
        return new RandomInt($min, $max, $limit);
    }
    
    public static function sequentialInt(int $start = 1, int $step = 1, int $limit = \PHP_INT_MAX): Producer
    {
        return new SequentialInt($start, $step, $limit);
    }
    
    public static function randomString(
        int $minLength,
        ?int $maxLength = null,
        int $limit = \PHP_INT_MAX,
        ?string $charset = null
    ): Producer
    {
        return new RandomString($minLength, $maxLength, $limit, $charset);
    }
    
    public static function randomUuid(bool $asHex = true, int $limit = \PHP_INT_MAX): Producer
    {
        return new RandomUuid($asHex, $limit);
    }
    
    public static function collatz(int $startNumber = null): Producer
    {
        return new CollatzGenerator($startNumber);
    }
    
    /**
     * @param resource $resource it have to be readable
     * @param bool $closeOnFinish
     * @param int|null $readByes
     */
    public static function resource($resource, bool $closeOnFinish = false, ?int $readByes = null): Producer
    {
        return new TextFileReader($resource, $closeOnFinish, $readByes);
    }
    
    public static function tokenizer(string $tokens, string $string = ''): Tokenizer
    {
        return new Tokenizer($tokens, $string);
    }
    
    public static function flattener(iterable $iterable = [], int $level = 0): Flattener
    {
        return new Flattener($iterable, $level);
    }
    
    public static function queue(array $elements = []): QueueProducer
    {
        return new QueueProducer($elements);
    }
}