<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Discriminator;

use FiiSoft\Jackdaw\Exception\UnsupportedValueException;

final class DayOfWeek implements Discriminator
{
    /**
     * @inheritDoc
     */
    public function classify($value, $key = null): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('D');
        }
        
        throw UnsupportedValueException::cannotCastNonTimeObjectToString($value);
    }
}