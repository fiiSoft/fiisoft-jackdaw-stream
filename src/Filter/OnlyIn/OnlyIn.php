<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\OnlyIn;

use FiiSoft\Jackdaw\Filter\BaseFilter;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\IdleFilter;
use FiiSoft\Jackdaw\Filter\OnlyIn\Ints\IntsAnyOnlyIn;
use FiiSoft\Jackdaw\Filter\OnlyIn\Ints\IntsBothOnlyIn;
use FiiSoft\Jackdaw\Filter\OnlyIn\Ints\IntsKeyOnlyIn;
use FiiSoft\Jackdaw\Filter\OnlyIn\Ints\IntsValueOnlyIn;
use FiiSoft\Jackdaw\Filter\OnlyIn\Mixed\MixedAnyOnlyIn;
use FiiSoft\Jackdaw\Filter\OnlyIn\Mixed\MixedBothOnlyIn;
use FiiSoft\Jackdaw\Filter\OnlyIn\Mixed\MixedKeyOnlyIn;
use FiiSoft\Jackdaw\Filter\OnlyIn\Mixed\MixedValueOnlyIn;
use FiiSoft\Jackdaw\Filter\OnlyIn\Other\OtherAnyOnlyIn;
use FiiSoft\Jackdaw\Filter\OnlyIn\Other\OtherBothOnlyIn;
use FiiSoft\Jackdaw\Filter\OnlyIn\Other\OtherKeyOnlyIn;
use FiiSoft\Jackdaw\Filter\OnlyIn\Other\OtherValueOnlyIn;
use FiiSoft\Jackdaw\Filter\OnlyIn\Strings\StringsAnyOnlyIn;
use FiiSoft\Jackdaw\Filter\OnlyIn\Strings\StringsBothOnlyIn;
use FiiSoft\Jackdaw\Filter\OnlyIn\Strings\StringsKeyOnlyIn;
use FiiSoft\Jackdaw\Filter\OnlyIn\Strings\StringsValueOnlyIn;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Mode;

abstract class OnlyIn extends BaseFilter
{
    private const MIXED = 0, INTS = 1, STRINGS = 2, OTHER = 3;
    
    /** @var array<int, bool> */
    protected array $ints = [];
    
    /** @var array<string, bool> */
    protected array $strings = [];
    
    /** @var array<int, mixed> */
    protected array $other = [];
    
    /**
     * @param array<string|int, mixed> $values
     */
    final public static function create(?int $mode, array $values): Filter
    {
        if (empty($values)) {
            return IdleFilter::false($mode);
        }
        
        $ints = $strings = $other = [];
        
        foreach ($values as $value) {
            if (\is_int($value)) {
                $ints[$value] = true;
            } elseif (\is_string($value)) {
                $strings[$value] = true;
            } else {
                $other[] = $value;
            }
        }
        
        return self::createFilter($mode, $ints, $strings, $other);
    }
    
    /**
     * @param array<int, bool> $ints
     * @param array<string, bool> $strings
     * @param array<int, mixed> $other
     */
    private static function createFilter(?int $mode, array $ints, array $strings, array $other): self
    {
        $mode = Mode::get($mode);
        $workMode = self::determineWorkMode($ints, $strings, $other);
        
        if ($workMode === self::INTS) {
            switch ($mode) {
                case Check::VALUE:
                    return new IntsValueOnlyIn($mode, $ints, $strings, $other);
                case Check::KEY:
                    return new IntsKeyOnlyIn($mode, $ints, $strings, $other);
                case Check::BOTH:
                    return new IntsBothOnlyIn($mode, $ints, $strings, $other);
                default:
                    return new IntsAnyOnlyIn($mode, $ints, $strings, $other);
            }
        }

        if ($workMode === self::STRINGS) {
            switch ($mode) {
                case Check::VALUE:
                    return new StringsValueOnlyIn($mode, $ints, $strings, $other);
                case Check::KEY:
                    return new StringsKeyOnlyIn($mode, $ints, $strings, $other);
                case Check::BOTH:
                    return new StringsBothOnlyIn($mode, $ints, $strings, $other);
                default:
                    return new StringsAnyOnlyIn($mode, $ints, $strings, $other);
            }
        }
        
        if ($workMode === self::OTHER) {
            switch ($mode) {
                case Check::VALUE:
                    return new OtherValueOnlyIn($mode, $ints, $strings, $other);
                case Check::KEY:
                    return new OtherKeyOnlyIn($mode, $ints, $strings, $other);
                case Check::BOTH:
                    return new OtherBothOnlyIn($mode, $ints, $strings, $other);
                default:
                    return new OtherAnyOnlyIn($mode, $ints, $strings, $other);
            }
        }

        switch ($mode) {
            case Check::VALUE:
                return new MixedValueOnlyIn($mode, $ints, $strings, $other);
            case Check::KEY:
                return new MixedKeyOnlyIn($mode, $ints, $strings, $other);
            case Check::BOTH:
                return new MixedBothOnlyIn($mode, $ints, $strings, $other);
            default:
                return new MixedAnyOnlyIn($mode, $ints, $strings, $other);
        }
    }
    
    /**
     * @param array<int, bool> $ints
     * @param array<string, bool> $strings
     * @param array<int, mixed> $other
     */
    final protected function __construct(int $mode, array $ints, array $strings, array $other)
    {
        parent::__construct($mode);
        
        $this->ints = $ints;
        $this->strings = $strings;
        $this->other = $other;
    }
    
    final public function negate(): Filter
    {
        return $this->createDefaultNOT(true);
    }
    
    final public function inMode(?int $mode): Filter
    {
        return $mode !== null && $mode !== $this->mode
            ? self::createFilter($mode, $this->ints, $this->strings, $this->other)
            : $this;
    }
    
    final public function equals(Filter $other): bool
    {
        return $other === $this || $other instanceof $this
            && $other->ints === $this->ints
            && $other->strings === $this->strings
            && $other->other === $this->other
            && parent::equals($other);
    }
    
    /**
     * @param array<int, bool> $ints
     * @param array<string, bool> $strings
     * @param array<int, mixed> $other
     */
    private static function determineWorkMode(array $ints, array $strings, array $other): int
    {
        $map = [
            1 => [
                1 => [
                    0 => self::OTHER,
                ],
                0 => [
                    1 => self::STRINGS,
                ],
            ],
            0 => [
                1 => [
                    1 => self::INTS,
                ],
            ],
        ];
        
        return $map[empty($ints)][empty($strings)][empty($other)] ?? self::MIXED;
    }
}