<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType;

use FiiSoft\Jackdaw\Filter\CheckType\IsDateTime\AnyIsDateTime;
use FiiSoft\Jackdaw\Filter\CheckType\IsDateTime\BothIsDateTime;
use FiiSoft\Jackdaw\Filter\CheckType\IsDateTime\KeyIsDateTime;
use FiiSoft\Jackdaw\Filter\CheckType\IsDateTime\ValueIsDateTime;
use FiiSoft\Jackdaw\Internal\Check;

abstract class IsDateTime extends CheckType
{
    final public static function create(?int $mode): self
    {
        $mode = Check::getMode($mode);
        
        switch ($mode) {
            case Check::VALUE:
                return new ValueIsDateTime($mode);
            case Check::KEY:
                return new KeyIsDateTime($mode);
            case Check::BOTH:
                return new BothIsDateTime($mode);
            default:
                return new AnyIsDateTime($mode);
        }
    }
    
    /**
     * @param mixed $value
     */
    final protected function isDateTime($value): bool
    {
        if ($value instanceof \DateTimeInterface) {
            return true;
        }
        
        if (\is_string($value) && $value !== '') {
            try {
                new \DateTimeImmutable($value);
                return true;
            } catch (\Exception $_) {
                return false;
            }
        }
        
        return false;
    }
}