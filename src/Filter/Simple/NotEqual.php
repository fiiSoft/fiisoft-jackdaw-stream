<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Simple;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Simple\NotEqual\AnyNotEqual;
use FiiSoft\Jackdaw\Filter\Simple\NotEqual\BothNotEqual;
use FiiSoft\Jackdaw\Filter\Simple\NotEqual\KeyNotEqual;
use FiiSoft\Jackdaw\Filter\Simple\NotEqual\ValueNotEqual;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Mode;

abstract class NotEqual extends SimpleFilter
{
    /**
     * @inheritDoc
     */
    final public static function create(?int $mode, $desired): self
    {
        $mode = Mode::get($mode);
        
        switch ($mode) {
            case Check::VALUE:
                return new ValueNotEqual($mode, $desired);
            case Check::KEY:
                return new KeyNotEqual($mode, $desired);
            case Check::BOTH:
                return new BothNotEqual($mode, $desired);
            default:
                return new AnyNotEqual($mode, $desired);
        }
    }
    
    final public function negate(): Filter
    {
        return Equal::create($this->negatedMode(), $this->desired);
    }
}