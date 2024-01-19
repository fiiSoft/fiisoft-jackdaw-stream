<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String;

use FiiSoft\Jackdaw\Filter\String\Is\AnyIs;
use FiiSoft\Jackdaw\Filter\String\Is\BothIs;
use FiiSoft\Jackdaw\Filter\String\Is\KeyIs;
use FiiSoft\Jackdaw\Filter\String\Is\ValueIs;
use FiiSoft\Jackdaw\Internal\Check;

abstract class StrIs extends StringFilterSingle
{
    final public static function create(int $mode, string $value, bool $ignoreCase = false): self
    {
        switch ($mode) {
            case Check::VALUE:
                return new ValueIs($mode, $value, $ignoreCase);
            case Check::KEY:
                return new KeyIs($mode, $value, $ignoreCase);
            case Check::BOTH:
                return new BothIs($mode, $value, $ignoreCase);
            case Check::ANY:
                return new AnyIs($mode, $value, $ignoreCase);
            default:
                throw Check::invalidModeException($mode);
        }
    }
    
    final public function negate(): StringFilter
    {
        return StrIsNot::create($this->negatedMode(), $this->value, $this->ignoreCase);
    }
}