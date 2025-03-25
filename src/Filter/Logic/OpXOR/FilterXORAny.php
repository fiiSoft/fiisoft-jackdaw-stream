<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic\OpXOR;

use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Internal\Check;

final class FilterXORAny extends BaseXOR
{
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $first
     * @param FilterReady|callable|array<string|int, mixed>|scalar $second
     */
    protected function __construct($first, $second)
    {
        parent::__construct($first, $second, Check::VALUE);
    }
    
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return ($this->first->isAllowed($value) XOR $this->second->isAllowed($value))
            || ($this->first->isAllowed($key) XOR $this->second->isAllowed($key));
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ( ($this->first->isAllowed($value) XOR $this->second->isAllowed($value))
                || ($this->first->isAllowed($key) XOR $this->second->isAllowed($key))
            ) {
                yield $key => $value;
            }
        }
    }
    
    public function getMode(): int
    {
        return Check::ANY;
    }
}