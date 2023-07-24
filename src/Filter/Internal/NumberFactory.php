<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Internal;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Number\Between;
use FiiSoft\Jackdaw\Filter\Number\Equal;
use FiiSoft\Jackdaw\Filter\Number\GreaterOrEqual;
use FiiSoft\Jackdaw\Filter\Number\GreaterThan;
use FiiSoft\Jackdaw\Filter\Number\IsEven;
use FiiSoft\Jackdaw\Filter\Number\IsOdd;
use FiiSoft\Jackdaw\Filter\Number\LessOrEqual;
use FiiSoft\Jackdaw\Filter\Number\LessThan;
use FiiSoft\Jackdaw\Filter\Number\NotEqual;

final class NumberFactory
{
    private static ?NumberFactory $instance = null;
    
    public static function instance(): NumberFactory
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    private function __construct()
    {
    }
    
    public function eq($value): Filter
    {
        return new Equal($value);
    }
    
    public function ne($value): Filter
    {
        return new NotEqual($value);
    }
    
    public function lt($value): Filter
    {
        return new LessThan($value);
    }
    
    public function le($value): Filter
    {
        return new LessOrEqual($value);
    }
    
    public function gt($value): Filter
    {
        return new GreaterThan($value);
    }
    
    public function ge($value): Filter
    {
        return new GreaterOrEqual($value);
    }
    
    public function isEven(): Filter
    {
        return new IsEven();
    }
    
    public function isOdd(): Filter
    {
        return new IsOdd();
    }
    
    /**
     * @param float|int $lower
     * @param float|int $higher
     */
    public function between($lower, $higher): Filter
    {
        return new Between($lower, $higher);
    }
}