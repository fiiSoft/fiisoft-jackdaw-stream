<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String;

use FiiSoft\Jackdaw\Filter\String\IsNot\AnyIsNot;
use FiiSoft\Jackdaw\Filter\String\IsNot\BothIsNot;
use FiiSoft\Jackdaw\Filter\String\IsNot\KeyIsNot;
use FiiSoft\Jackdaw\Filter\String\IsNot\ValueIsNot;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Mode;

abstract class StrIsNot extends StringFilterSingle
{
    final public static function create(int $mode, string $value, bool $ignoreCase = false): self
    {
        switch ($mode) {
            case Check::VALUE:
                return new ValueIsNot($mode, $value, $ignoreCase);
            case Check::KEY:
                return new KeyIsNot($mode, $value, $ignoreCase);
            case Check::BOTH:
                return new BothIsNot($mode, $value, $ignoreCase);
            case Check::ANY:
                return new AnyIsNot($mode, $value, $ignoreCase);
            default:
                throw Mode::invalidModeException($mode);
        }
    }
    
    final public function negate(): StringFilter
    {
        return StrIs::create($this->negatedMode(), $this->value, $this->ignoreCase);
    }
}