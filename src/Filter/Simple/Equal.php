<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Simple;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Simple\Equal\AnyEqual;
use FiiSoft\Jackdaw\Filter\Simple\Equal\BothEqual;
use FiiSoft\Jackdaw\Filter\Simple\Equal\KeyEqual;
use FiiSoft\Jackdaw\Filter\Simple\Equal\ValueEqual;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Mode;

abstract class Equal extends SimpleFilter
{
    /**
     * @inheritDoc
     */
    final public static function create(?int $mode, $desired): self
    {
        $mode = Mode::get($mode);
        
        switch ($mode) {
            case Check::VALUE:
                return new ValueEqual($mode, $desired);
            case Check::KEY:
                return new KeyEqual($mode, $desired);
            case Check::BOTH:
                return new BothEqual($mode, $desired);
            default:
                return new AnyEqual($mode, $desired);
        }
    }
    
    final public function negate(): Filter
    {
        return NotEqual::create($this->negatedMode(), $this->desired);
    }
}