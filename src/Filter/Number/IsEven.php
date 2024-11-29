<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Number\IsEven\AnyIsEven;
use FiiSoft\Jackdaw\Filter\Number\IsEven\BothIsEven;
use FiiSoft\Jackdaw\Filter\Number\IsEven\KeyIsEven;
use FiiSoft\Jackdaw\Filter\Number\IsEven\ValueIsEven;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Mode;

abstract class IsEven extends ZeroArg
{
    final public static function create(int $mode): self
    {
        switch ($mode) {
            case Check::VALUE:
                return new ValueIsEven($mode);
            case Check::KEY:
                return new KeyIsEven($mode);
            case Check::BOTH:
                return new BothIsEven($mode);
            case Check::ANY:
                return new AnyIsEven($mode);
            default:
                throw Mode::invalidModeException($mode);
        }
    }
    
    final public function negate(): Filter
    {
        return IsOdd::create($this->negatedMode());
    }
}