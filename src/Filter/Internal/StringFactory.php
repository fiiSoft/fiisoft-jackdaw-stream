<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Internal;

use FiiSoft\Jackdaw\Filter\String\Contains;
use FiiSoft\Jackdaw\Filter\String\EndsWith;
use FiiSoft\Jackdaw\Filter\String\StartsWith;
use FiiSoft\Jackdaw\Filter\String\StringFilter;

final class StringFactory
{
    private static ?StringFactory $instance = null;
    
    public static function instance(): StringFactory
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    public function contains(string $value, bool $ignoreCase = false): StringFilter
    {
        return new Contains($value, $ignoreCase);
    }
    
    public function startsWith(string $value, bool $ignoreCase = false): StringFilter
    {
        return new StartsWith($value, $ignoreCase);
    }
    
    public function endsWith(string $value, bool $ignoreCase = false): StringFilter
    {
        return new EndsWith($value, $ignoreCase);
    }
}