<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String\Is;

use FiiSoft\Jackdaw\Filter\String\StrIs;

final class BothIs extends StrIs
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $this->ignoreCase
            ? \strcasecmp($value, $this->value) === 0 && \strcasecmp($key, $this->value) === 0
            : $value === $this->value && $key === $this->value;
    }
    
    protected function compareCaseInsensitive(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\strcasecmp($value, $this->value) === 0 && \strcasecmp($key, $this->value) === 0) {
                yield $key => $value;
            }
        }
    }
    
    protected function compareCaseSensitive(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($value === $this->value && $key === $this->value) {
                yield $key => $value;
            }
        }
    }
}