<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer;

use FiiSoft\Jackdaw\Internal\StreamApi;
use FiiSoft\Jackdaw\Producer\Adapter\ArrayAdapter;
use FiiSoft\Jackdaw\Producer\Adapter\IteratorAdapter;
use FiiSoft\Jackdaw\Producer\Adapter\StreamAdapter;
use FiiSoft\Jackdaw\Producer\Generator\CollatzGenerator;
use FiiSoft\Jackdaw\Producer\Generator\RandomInt;
use FiiSoft\Jackdaw\Producer\Generator\RandomString;
use FiiSoft\Jackdaw\Producer\Generator\RandomUuid;
use FiiSoft\Jackdaw\Producer\Generator\SequentialInt;
use FiiSoft\Jackdaw\Producer\Resource\PDOStatementAdapter;

final class Producers
{
    public static function from(array $elements): Producer
    {
        $index = 0;
        $mode = 1;
        $producers = [];
        
        foreach ($elements as $item) {
            if (\is_object($item)) {
                if ($item instanceof \Iterator
                    || $item instanceof StreamApi
                    || $item instanceof Producer
                    || $item instanceof \PDOStatement
                ) {
                    $mode = 1;
                } elseif ($mode === 1) {
                    $mode = 2;
                }
            } elseif (\is_array($item)) {
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
     * @param StreamApi|Producer|\Iterator|\PDOStatement|array $producer
     * @return Producer
     */
    public static function getAdapter($producer): Producer
    {
        if (\is_array($producer)) {
            $adapter = self::fromArray($producer);
        } elseif ($producer instanceof \Iterator) {
            $adapter = self::fromIterator($producer);
        } elseif ($producer instanceof StreamApi) {
            $adapter = self::fromStream($producer);
        } elseif ($producer instanceof \PDOStatement) {
            $adapter = self::fromPDOStatement($producer);
        } elseif ($producer instanceof Producer) {
            $adapter = $producer;
        } else {
            throw new \InvalidArgumentException('Invalid param producer');
        }
        
        return $adapter;
    }
    
    public static function multiSourced(...$producers): MultiProducer
    {
        return new MultiProducer(...\array_map(static function ($producer) {
            return self::getAdapter($producer);
        }, $producers));
    }
    
    public static function fromArray(array $array): ArrayAdapter
    {
        return new ArrayAdapter($array);
    }
    
    public static function fromIterator(\Iterator $iterator): IteratorAdapter
    {
        return new IteratorAdapter($iterator);
    }
    
    public static function fromStream(StreamApi $stream): StreamAdapter
    {
        return new StreamAdapter($stream);
    }
    
    public static function fromPDOStatement(\PDOStatement $statement): PDOStatementAdapter
    {
        return new PDOStatementAdapter($statement);
    }
    
    public static function randomInt(int $min = 1, int $max = \PHP_INT_MAX, int $limit = \PHP_INT_MAX): RandomInt
    {
        return new RandomInt($min, $max, $limit);
    }
    
    public static function sequentialInt(int $start = 1, int $step = 1, int $limit = \PHP_INT_MAX): SequentialInt
    {
        return new SequentialInt($start, $step, $limit);
    }
    
    public static function randomString(
        int $minLength,
        ?int $maxLength = null,
        int $limit = \PHP_INT_MAX,
        ?string $charset = null
    ): RandomString
    {
        return new RandomString($minLength, $maxLength, $limit, $charset);
    }
    
    public static function randomUuid(bool $asHex = true, int $limit = \PHP_INT_MAX): RandomUuid
    {
        return new RandomUuid($asHex, $limit);
    }
    
    public static function collatz(int $startNumber = null): CollatzGenerator
    {
        return new CollatzGenerator($startNumber);
    }
}