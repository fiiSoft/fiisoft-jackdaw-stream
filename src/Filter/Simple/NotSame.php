<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Simple;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Simple\NotSame\AnyNotSame;
use FiiSoft\Jackdaw\Filter\Simple\NotSame\BothNotSame;
use FiiSoft\Jackdaw\Filter\Simple\NotSame\KeyNotSame;
use FiiSoft\Jackdaw\Filter\Simple\NotSame\ValueNotSame;
use FiiSoft\Jackdaw\Internal\Check;

abstract class NotSame extends SimpleFilter
{
    /**
     * @inheritDoc
     */
    final public static function create(?int $mode, $desired): self
    {
        $mode = Check::getMode($mode);
        
        switch ($mode) {
            case Check::VALUE:
                return new ValueNotSame($mode, $desired);
            case Check::KEY:
                return new KeyNotSame($mode, $desired);
            case Check::BOTH:
                return new BothNotSame($mode, $desired);
            default:
                return new AnyNotSame($mode, $desired);
        }
    }
    
    final public function negate(): Filter
    {
        return Same::create($this->negatedMode(), $this->desired);
    }
}