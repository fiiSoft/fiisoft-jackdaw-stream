<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\OnlyIn\Other;

use FiiSoft\Jackdaw\Filter\OnlyIn\OnlyIn;

final class OtherValueOnlyIn extends OnlyIn
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return \in_array($value, $this->other);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\in_array($value, $this->other)) {
                yield $key => $value;
            }
        }
    }
}