<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Producer\Adapter\ArrayAdapter;
use FiiSoft\Jackdaw\Producer\Adapter\ArrayIteratorAdapter;
use FiiSoft\Jackdaw\Producer\Adapter\CallableAdapter;
use FiiSoft\Jackdaw\Producer\Adapter\TraversableAdapter;
use FiiSoft\Jackdaw\Producer\Adapter\ReferenceAdapter;
use FiiSoft\Jackdaw\Producer\Adapter\RegistryAdapter;
use FiiSoft\Jackdaw\Producer\Generator\CollatzGenerator;
use FiiSoft\Jackdaw\Producer\Generator\CombinedArrays;
use FiiSoft\Jackdaw\Producer\Generator\CombinedGeneral;
use FiiSoft\Jackdaw\Producer\Generator\Flattener;
use FiiSoft\Jackdaw\Producer\Generator\RandomInt;
use FiiSoft\Jackdaw\Producer\Generator\RandomString;
use FiiSoft\Jackdaw\Producer\Generator\RandomUuid;
use FiiSoft\Jackdaw\Producer\Generator\SequentialInt;
use FiiSoft\Jackdaw\Producer\Generator\Tokenizer;
use FiiSoft\Jackdaw\Producer\Generator\Uuid\UuidGenerator;
use FiiSoft\Jackdaw\Producer\Resource\PDOStatementAdapter;
use FiiSoft\Jackdaw\Producer\Resource\TextFileReader;
use FiiSoft\Jackdaw\Registry\RegReader;

final class Producers
{
    /**
     * @param array<ProducerReady|resource|callable|iterable|scalar> $elements
     */
    public static function from(array $elements): Producer
    {
        $producers = self::prepare($elements);
        
        return \count($producers) === 1
            ? self::getAdapter(\reset($producers))
            : self::multiSourced(...$producers);
    }
    
    /**
     * @param array<ProducerReady|resource|callable|iterable|scalar> $elements
     */
    public static function prepare(array $elements): array
    {
        $index = 0;
        $mode = 1;
        $producers = [];
        
        foreach ($elements as $item) {
            if (\is_object($item)) {
                if ($item instanceof ProducerReady || $item instanceof \Traversable || \is_callable($item)) {
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
        
        return $producers;
    }
    
    /**
     * @param ProducerReady|resource|callable|iterable $producer
     */
    public static function getAdapter($producer): Producer
    {
        if (\is_array($producer)) {
            return new ArrayAdapter($producer);
        }
        
        if ($producer instanceof Producer) {
            return $producer;
        }
        
        if ($producer instanceof \PDOStatement) {
            return self::fromPDOStatement($producer);
        }
        
        if ($producer instanceof \ArrayIterator) {
            return new ArrayIteratorAdapter($producer);
        }
        
        if ($producer instanceof \Traversable) {
            return new TraversableAdapter($producer);
        }
        
        if ($producer instanceof UuidGenerator) {
            return self::uuidFrom($producer);
        }
        
        if ($producer instanceof RegReader) {
            return new RegistryAdapter($producer);
        }
        
        if (\is_resource($producer)) {
            return self::resource($producer);
        }
        
        if (\is_callable($producer)) {
            return new CallableAdapter($producer);
        }
        
        throw InvalidParamException::describe('producer', $producer);
    }
    
    /**
     * @param ProducerReady|resource|callable|iterable ...$producers
     */
    public static function multiSourced(...$producers): Producer
    {
        return new MultiProducer(
            ...\array_map(static fn($producer): Producer => self::getAdapter($producer), $producers)
        );
    }
    
    public static function fromPDOStatement(\PDOStatement $statement, ?int $fetchMode = null): Producer
    {
        return new PDOStatementAdapter($statement, $fetchMode);
    }
    
    /**
     * @param ProducerReady|resource|callable|iterable $keys
     * @param ProducerReady|resource|callable|iterable $values
     */
    public static function combinedFrom($keys, $values): Producer
    {
        return \is_array($keys) && \is_array($values)
            ? new CombinedArrays($keys, $values)
            : new CombinedGeneral($keys, $values);
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
    
    public static function uuidFrom(UuidGenerator $provider, int $limit = \PHP_INT_MAX): Producer
    {
        return new RandomUuid($limit, $provider);
    }
    
    public static function randomUuid(int $limit = \PHP_INT_MAX): Producer
    {
        return new RandomUuid($limit);
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
    
    /**
     * @param mixed $variable REFERENCE
     */
    public static function readFrom(&$variable): Producer
    {
        return new ReferenceAdapter($variable);
    }
}