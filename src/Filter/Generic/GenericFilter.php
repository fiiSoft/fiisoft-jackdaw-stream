<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Generic;

use FiiSoft\Jackdaw\Filter\BaseFilter;
use FiiSoft\Jackdaw\Filter\Exception\FilterExceptionFactory;
use FiiSoft\Jackdaw\Filter\Generic\ModeDependent\AnyGeneric;
use FiiSoft\Jackdaw\Filter\Generic\ModeDependent\BothGeneric;
use FiiSoft\Jackdaw\Filter\Generic\ModeDependent\KeyGeneric;
use FiiSoft\Jackdaw\Filter\Generic\ModeDependent\ValueGeneric;
use FiiSoft\Jackdaw\Filter\Generic\ModeIndependent\TwoArgs;
use FiiSoft\Jackdaw\Filter\Generic\ModeIndependent\ZeroArgs;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Helper;

abstract class GenericFilter extends BaseFilter
{
    /** @var callable */
    protected $callable;
    
    protected bool $expected = true;
    
    final public static function create(callable $callable, ?int $mode, bool $isNegation = false): self
    {
        $numOfArgs = Helper::getNumOfArgs($callable);
        $expected = !$isNegation;
        
        switch ($numOfArgs) {
            case 1:
                $mode = Check::getMode($mode);
                
                switch ($mode) {
                    case Check::VALUE:
                        return new ValueGeneric($callable, $expected, $mode);
                    case Check::KEY:
                        return new KeyGeneric($callable, $expected, $mode);
                    case Check::BOTH:
                        return new BothGeneric($callable, $expected, $mode);
                    default:
                        return new AnyGeneric($callable, $expected, $mode);
                }
            case 2:
                return new TwoArgs($callable, $expected);
            case 0:
                return new ZeroArgs($callable, $expected);
            default:
                throw FilterExceptionFactory::invalidParamFilter($numOfArgs);
        }
    }
    
    final protected function __construct(callable $callable, bool $expected, ?int $mode = null)
    {
        parent::__construct($mode);
        
        $this->callable = $callable;
        $this->expected = $expected;
    }
}