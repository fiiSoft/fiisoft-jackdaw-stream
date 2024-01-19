<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Discriminator\EvenOdd;

use FiiSoft\Jackdaw\Discriminator\EvenOdd;

final class BothEvenOdd extends EvenOdd
{
    /**
     * @inheritDoc
     */
    public function classify($value, $key = null): string
    {
        $valueDiscr = ($value & 1) === 0 ? 'even' : 'odd';
        $keyDiscr = ($key & 1) === 0 ? 'even' : 'odd';
        
        return $valueDiscr.'_'.$keyDiscr;
    }
}