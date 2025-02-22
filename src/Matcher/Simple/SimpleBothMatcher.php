<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Matcher\Simple;

use FiiSoft\Jackdaw\Matcher\Matcher;

final class SimpleBothMatcher implements Matcher
{
    private static ?Matcher $instance = null;
    
    public static function get(): Matcher
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * @inheritDoc
     */
    public function matches($value1, $value2, $key1 = null, $key2 = null): bool
    {
        return $value1 === $value2 && $key1 === $key2;
    }
    
    public function equals(Matcher $other): bool
    {
        return $other instanceof self;
    }
}