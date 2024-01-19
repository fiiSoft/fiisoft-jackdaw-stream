<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Simple;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Simple\Same\AnySame;
use FiiSoft\Jackdaw\Filter\Simple\Same\BothSame;
use FiiSoft\Jackdaw\Filter\Simple\Same\KeySame;
use FiiSoft\Jackdaw\Filter\Simple\Same\ValueSame;
use FiiSoft\Jackdaw\Internal\Check;

abstract class Same extends SimpleFilter
{
    /**
     * @inheritDoc
     */
    final public static function create(?int $mode, $desired): self
    {
        $mode = Check::getMode($mode);
        
        switch ($mode) {
            case Check::VALUE:
                return new ValueSame($mode, $desired);
            case Check::KEY:
                return new KeySame($mode, $desired);
            case Check::BOTH:
                return new BothSame($mode, $desired);
            default:
                return new AnySame($mode, $desired);
        }
    }
    
    final public function negate(): Filter
    {
        return NotSame::create($this->negatedMode(), $this->desired);
    }
}