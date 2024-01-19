<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Discriminator\EvenOdd;

use FiiSoft\Jackdaw\Discriminator\EvenOdd;

final class KeyEvenOdd extends EvenOdd
{
    /**
     * @inheritDoc
     */
    public function classify($value, $key = null): string
    {
        return ($key & 1) === 0 ? 'even' : 'odd';
    }
}