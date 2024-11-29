<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String;

use FiiSoft\Jackdaw\Filter\String\Contains\AnyContains;
use FiiSoft\Jackdaw\Filter\String\Contains\BothContains;
use FiiSoft\Jackdaw\Filter\String\Contains\KeyContains;
use FiiSoft\Jackdaw\Filter\String\Contains\ValueContains;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Mode;

abstract class Contains extends StringFilterSingle
{
    final public static function create(int $mode, string $value, bool $ignoreCase = false): self
    {
        switch ($mode) {
            case Check::VALUE:
                return new ValueContains($mode, $value, $ignoreCase);
            case Check::KEY:
                return new KeyContains($mode, $value, $ignoreCase);
            case Check::BOTH:
                return new BothContains($mode, $value, $ignoreCase);
            case Check::ANY:
                return new AnyContains($mode, $value, $ignoreCase);
            default:
                throw Mode::invalidModeException($mode);
        }
    }
    
    final public function negate(): StringFilter
    {
        return NotContains::create($this->negatedMode(), $this->value, $this->ignoreCase);
    }
}