<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class Unique extends BaseOperation
{
    private ?Comparator $comparator = null;
    private int $mode;
    
    /** @var Item[] */
    private array $keysAndValues = [];
    
    private array $valuesMap = [];
    private array $values = [];
    
    /**
     * @param Comparator|callable|null $comparator
     */
    public function __construct($comparator = null, int $mode = Check::VALUE)
    {
        $this->comparator = Comparators::getAdapter($comparator);
        $this->mode = Check::getMode($mode);
    }
    
    public function handle(Signal $signal): void
    {
        switch ($this->mode) {
            case Check::VALUE:
                $passed = $this->isUnique($signal->item->value);
            break;
            case Check::KEY:
                $passed = $this->isUnique($signal->item->key);
            break;
            case Check::BOTH:
                $passed = $this->isValueAndKeyUnique($signal->item);
            break;
            case Check::ANY:
                $passed = $this->isValueOrKeyUnique($signal->item);
            break;
        }
        
        if ($passed) {
            $this->next->handle($signal);
        }
    }
    
    private function isValueAndKeyUnique(Item $item): bool
    {
        if ($this->comparator === null) {
            foreach ($this->keysAndValues as $prev) {
                if ($prev->value === $item->value || $prev->key === $item->key
                    || $prev->value === $item->key || $prev->key === $item->value
                ) {
                    return false;
                }
            }
        } else {
            foreach ($this->keysAndValues as $prev) {
                if ($this->comparator->compareAssoc($prev->value, $item->value, $prev->key, $item->key) === 0) {
                    return false;
                }
            }
        }
        
        $this->keysAndValues[] = $item->copy();
        return true;
    }
    
    private function isValueOrKeyUnique(Item $item): bool
    {
        $isValueUnique = $this->isUnique($item->value);
    
        if ($isValueUnique && $item->value === $item->key) {
            return true;
        }
    
        $isKeyUnique = $this->isUnique($item->key);
        
        return $isValueUnique || $isKeyUnique;
    }
    
    private function isUnique($value): bool
    {
        if ($this->comparator === null) {
            if (\is_int($value) || \is_string($value)) {
                if (isset($this->valuesMap[$value])) {
                    return false;
                }
    
                $this->valuesMap[$value] = true;
                return true;
            }
    
            if (\in_array($value, $this->values, true)) {
                return false;
            }
    
            $this->values[] = $value;
            return true;
        }
    
        foreach ($this->values as $val) {
            if ($this->comparator->compare($val, $value) === 0) {
                return false;
            }
        }
    
        $this->values[] = $value;
        return true;
    }
}