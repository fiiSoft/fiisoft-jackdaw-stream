<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Simple\Equal;

use FiiSoft\Jackdaw\Filter\Simple\Equal;

final class AnyEqual extends Equal
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $value == $this->desired || $key == $this->desired;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($value == $this->desired || $key == $this->desired) {
                yield $key => $value;
            }
        }
    }
}