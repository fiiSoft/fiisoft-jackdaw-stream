<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Strategy\Unique;

final class ValueOrKeyStandard extends AbstractValueOrKey
{
    private array $valuesMap = [];
    private array $values = [];
    
    protected function isUnique($value): bool
    {
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
}