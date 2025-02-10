<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Matcher;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Matcher\Generic\Boolean\GenericBothMatcher;
use FiiSoft\Jackdaw\Matcher\Generic\Boolean\GenericFullMatcher;
use FiiSoft\Jackdaw\Matcher\Generic\Boolean\GenericKeyMatcher;
use FiiSoft\Jackdaw\Matcher\Generic\Boolean\GenericValueMatcher;
use FiiSoft\Jackdaw\Matcher\Generic\Integer\ComparativeBothMatcher;
use FiiSoft\Jackdaw\Matcher\Generic\Integer\ComparativeFullMatcher;
use FiiSoft\Jackdaw\Matcher\Generic\Integer\ComparativeKeyMatcher;
use FiiSoft\Jackdaw\Matcher\Generic\Integer\ComparativeValueMatcher;
use FiiSoft\Jackdaw\Matcher\Simple\SimpleBothMatcher;
use FiiSoft\Jackdaw\Matcher\Simple\SimpleKeyMatcher;
use FiiSoft\Jackdaw\Matcher\Simple\SimpleValueMatcher;

final class MatchBy
{
    /** @var array<string, array<int, Matcher>> */
    private static array $cache = [];
    
    /**
     * @param Matcher|callable|null $matcher callable must accept two or four arguments and return bool or int
     */
    public static function getAdapter($matcher = null): Matcher
    {
        if ($matcher === null) {
            return self::values();
        }
        
        if ($matcher instanceof Matcher) {
            return $matcher;
        }
        
        if (\is_callable($matcher)) {
            return self::generic($matcher, Check::VALUE);
        }
        
        throw InvalidParamException::describe('matcher', $matcher);
    }
    
    /**
     * @param callable|null $matcher callable must accept two arguments and return bool or int
     */
    public static function values(?callable $matcher = null): Matcher
    {
        return $matcher === null ? SimpleValueMatcher::get() : self::generic($matcher, Check::VALUE, 2);
    }
    
    /**
     * @param callable|null $matcher callable must accept two arguments and return bool or int
     */
    public static function keys(?callable $matcher = null): Matcher
    {
        return $matcher === null ? SimpleKeyMatcher::get() : self::generic($matcher, Check::KEY, 2);
    }
    
    /**
     * @param callable|null $matcher callable must accept two arguments and return bool or int
     */
    public static function both(?callable $matcher = null): Matcher
    {
        return $matcher === null ? SimpleBothMatcher::get() : self::generic($matcher, Check::BOTH, 2);
    }
    
    /**
     * @param callable $matcher it must accept four arguments: value1, value2, key1, key2 and return bool or int
     */
    public static function full(callable $matcher): Matcher
    {
        return self::generic($matcher, Check::ANY, 4);
    }
    
    private static function generic(callable $callable, int $mode, ?int $requiredNumOfArgs = null): Matcher
    {
        if (\is_string($callable)) {
            if (!isset(self::$cache[$callable][$mode])) {
                self::$cache[$callable][$mode] = self::create($callable, $mode, $requiredNumOfArgs);
            }
            
            return self::$cache[$callable][$mode];
        }
        
        return self::create($callable, $mode, $requiredNumOfArgs);
    }
    
    private static function create(callable $callable, int $mode, ?int $requiredNumOfArgs = null): Matcher
    {
        $numOfArgs = Helper::getNumOfArgs($callable);
        
        if ($requiredNumOfArgs !== null && $numOfArgs !== $requiredNumOfArgs) {
            throw Helper::wrongNumOfArgsException('Matcher', $numOfArgs, $requiredNumOfArgs);
        }
        
        if (Helper::isDeclaredReturnTypeInt($callable)) {
            if ($numOfArgs === 2) {
                switch ($mode) {
                    case Check::VALUE:
                        return new ComparativeValueMatcher($callable);
                    case Check::KEY:
                        return new ComparativeKeyMatcher($callable);
                    default:
                        return new ComparativeBothMatcher($callable);
                }
            } elseif ($numOfArgs === 4) {
                return new ComparativeFullMatcher($callable);
            }
        } elseif ($numOfArgs === 2) {
            switch ($mode) {
                case Check::VALUE:
                    return new GenericValueMatcher($callable);
                case Check::KEY:
                    return new GenericKeyMatcher($callable);
                default:
                    return new GenericBothMatcher($callable);
            }
        } elseif ($numOfArgs === 4) {
            return new GenericFullMatcher($callable);
        }
        
        throw Helper::wrongNumOfArgsException('Matcher', $numOfArgs, 2, 4);
    }
}