<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer;

use FiiSoft\Jackdaw\Internal\Result;
use FiiSoft\Jackdaw\Internal\StreamApi;
use FiiSoft\Jackdaw\Producer\Adapter\ArrayAdapter;
use FiiSoft\Jackdaw\Producer\Adapter\IteratorAdapter;
use FiiSoft\Jackdaw\Producer\Adapter\ResultAdapter;
use FiiSoft\Jackdaw\Producer\Adapter\StreamAdapter;
use FiiSoft\Jackdaw\Producer\Generator\CollatzGenerator;
use FiiSoft\Jackdaw\Producer\Generator\Flattener;
use FiiSoft\Jackdaw\Producer\Generator\RandomInt;
use FiiSoft\Jackdaw\Producer\Generator\RandomString;
use FiiSoft\Jackdaw\Producer\Generator\RandomUuid;
use FiiSoft\Jackdaw\Producer\Generator\SequentialInt;
use FiiSoft\Jackdaw\Producer\Generator\Tokenizer;
use FiiSoft\Jackdaw\Producer\Resource\PDOStatementAdapter;
use FiiSoft\Jackdaw\Producer\Resource\TextFileReader;

final class Producers
{
    /**
     * @param array<StreamApi|Producer|Result|\Iterator|\PDOStatement|resource|array|scalar> $elements
     * @return Producer
     */
    public static function from(array $elements): Producer
    {
        $index = 0;
        $mode = 1;
        $producers = [];
        
        foreach ($elements as $item) {
            if (\is_object($item)) {
                if ($item instanceof \Iterator
                    || $item instanceof StreamApi
                    || $item instanceof Result
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
     * @param StreamApi|Producer|Result|\Iterator|\PDOStatement|resource|array $producer
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
        } elseif ($producer instanceof Result) {
            $adapter = self::fromResult($producer);
        } elseif ($producer instanceof \PDOStatement) {
            $adapter = self::fromPDOStatement($producer);
        } elseif ($producer instanceof Producer) {
            $adapter = $producer;
        } elseif (\is_resource($producer)) {
            $adapter = self::resource($producer);
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
    
    public static function fromResult(Result $result): ResultAdapter
    {
        return new ResultAdapter($result);
    }
    
    public static function fromPDOStatement(\PDOStatement $statement, ?int $fetchMode = null): PDOStatementAdapter
    {
        return new PDOStatementAdapter($statement, $fetchMode);
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
    
    /**
     * @param resource $resource it have to be readable
     * @param bool $closeOnFinish
     * @param int|null $readByes
     * @return TextFileReader
     */
    public static function resource($resource, bool $closeOnFinish = false, ?int $readByes = null): TextFileReader
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