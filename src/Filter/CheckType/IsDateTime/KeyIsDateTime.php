<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType\IsDateTime;

use FiiSoft\Jackdaw\Filter\CheckType\IsDateTime;

final class KeyIsDateTime extends IsDateTime
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $this->isDateTime($key);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->isDateTime($key)) {
                yield $key => $value;
            }
        }
    }
}