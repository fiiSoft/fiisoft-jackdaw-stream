<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw;

use FiiSoft\Jackdaw\Internal\Destroyable;
use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Internal\ResultCaster;
use FiiSoft\Jackdaw\Producer\Producer;
use FiiSoft\Jackdaw\Producer\Producers;

final class StreamMaker implements Destroyable
{
    /** @var callable */
    private $factory;
    
    private bool $isDestroying = false;
    
    /**
     * @param Stream|Producer|ResultCaster|\Traversable|\PDOStatement|callable|resource|array|scalar ...$elements
     */
    public static function of(...$elements): StreamMaker
    {
        return self::from(Producers::from($elements));
    }
    
    /**
     * @param Producer|\Iterator|ResultCaster|array|callable $factory callable MUST return new Stream every time
     */
    public static function from($factory): StreamMaker
    {
        if (\is_array($factory)) {
            $callable = static fn(): Stream => Stream::from($factory);
        } elseif ($factory instanceof Producer) {
            $callable = static fn(): Stream => Stream::from(clone $factory);
        } elseif ($factory instanceof ResultCaster) {
            $callable = static fn(): Stream => Stream::from($factory);
        } elseif ($factory instanceof \Iterator) {
            $callable = static fn(): Stream => Stream::from(clone $factory);
        } elseif (\is_callable($factory)) {
            $callable = $factory;
        } else {
            throw Helper::invalidParamException('factory', $factory);
        }
        
        return new self($callable);
    }
    
    public static function empty(): StreamMaker
    {
        return self::from(static fn(): Stream => Stream::empty());
    }
    
    private function __construct(callable $factory)
    {
        $this->factory = $factory;
    }
    
    public function start(): Stream
    {
        $factory = $this->factory;
        
        return $factory();
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->isDestroying = true;
            $this->factory = static fn(): Stream => Stream::empty();
        }
    }
}