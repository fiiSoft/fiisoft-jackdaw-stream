<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Number\IsOdd\AnyIsOdd;
use FiiSoft\Jackdaw\Filter\Number\IsOdd\BothIsOdd;
use FiiSoft\Jackdaw\Filter\Number\IsOdd\KeyIsOdd;
use FiiSoft\Jackdaw\Filter\Number\IsOdd\ValueIsOdd;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Mode;

abstract class IsOdd extends ZeroArg
{
    final public static function create(int $mode): self
    {
        switch ($mode) {
            case Check::VALUE:
                return new ValueIsOdd($mode);
            case Check::KEY:
                return new KeyIsOdd($mode);
            case Check::BOTH:
                return new BothIsOdd($mode);
            case Check::ANY:
                return new AnyIsOdd($mode);
            default:
                throw Mode::invalidModeException($mode);
        }
    }
    
    final public function negate(): Filter
    {
        return IsEven::create($this->negatedMode());
    }
}