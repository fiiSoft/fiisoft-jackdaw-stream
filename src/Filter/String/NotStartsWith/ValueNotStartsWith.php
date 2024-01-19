<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String\NotStartsWith;

use FiiSoft\Jackdaw\Filter\String\NotStartsWith;

final class ValueNotStartsWith extends NotStartsWith
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $this->ignoreCase
            ? \mb_stripos($value, $this->value) !== 0
            : \mb_strpos($value, $this->value) !== 0;
    }
    
    protected function compareCaseInsensitive(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\mb_stripos($value, $this->value) !== 0) {
                yield $key => $value;
            }
        }
    }
    
    protected function compareCaseSensitive(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\mb_strpos($value, $this->value) !== 0) {
                yield $key => $value;
            }
        }
    }
}