<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\ItemComparator;

use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Internal\Check;

final class ItemComparatorFactory
{
    /**
     * @param Comparator|callable|null $custom
     */
    public static function getFor(int $mode, bool $reversed = false, $custom = null): ItemComparator
    {
        $mode = Check::getMode($mode);
        
        $choice = ($custom !== null ? 'custom_' : 'default_') . ($reversed ? 'reversed_' : 'normal_');
        
        if ($mode === Check::VALUE) {
            $choice .= 'value';
        } elseif ($mode === Check::KEY) {
            $choice .= 'key';
        } else {
            $choice .= 'assoc';
        }
        
        $custom = Comparators::getAdapter($custom);
        
        switch ($choice) {
            case 'default_normal_value': return new DefaultNormalValueComparator();
            case 'default_normal_key': return new DefaultNormalKeyComparator();
            case 'default_normal_assoc': return new DefaultNormalAssocComparator();
            case 'default_reversed_value': return new DefaultReversedValueComparator();
            case 'default_reversed_key': return new DefaultReversedKeyComparator();
            case 'default_reversed_assoc': return new DefaultReversedAssocComparator();
     
            case 'custom_normal_value': return new CustomNormalValueComparator($custom);
            case 'custom_normal_key': return new CustomNormalKeyComparator($custom);
            case 'custom_normal_assoc': return new CustomNormalAssocComparator($custom);
            case 'custom_reversed_value': return new CustomReversedValueComparator($custom);
            case 'custom_reversed_key': return new CustomReversedKeyComparator($custom);
            case 'custom_reversed_assoc': return new CustomReversedAssocComparator($custom);
            
            //@codeCoverageIgnoreStart
            default:
                throw new \UnexpectedValueException('Unknown choice in ItemComparatorFactory: '.$choice);
            // @codeCoverageIgnoreEnd
        }
    }
}