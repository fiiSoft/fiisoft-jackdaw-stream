<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic\OpXNOR;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Check;

final class FilterXNORAny extends BaseXNOR
{
    /**
     * @param Filter|callable|mixed $first
     * @param Filter|callable|mixed $second
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
        return $this->first->isAllowed($value) === $this->second->isAllowed($value)
            || $this->first->isAllowed($key) === $this->second->isAllowed($key);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->first->isAllowed($value) === $this->second->isAllowed($value)
                || $this->first->isAllowed($key) === $this->second->isAllowed($key)
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