<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Discriminator\EvenOdd;

use FiiSoft\Jackdaw\Discriminator\EvenOdd;

final class ValueEvenOdd extends EvenOdd
{
    /**
     * @inheritDoc
     */
    public function classify($value, $key = null): string
    {
        return ($value & 1) === 0 ? 'even' : 'odd';
    }
}