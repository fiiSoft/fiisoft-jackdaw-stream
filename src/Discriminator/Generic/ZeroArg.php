<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Discriminator\Generic;

use FiiSoft\Jackdaw\Discriminator\GenericDiscriminator;

final class ZeroArg extends GenericDiscriminator
{
    /**
     * @inheritDoc
     */
    public function classify($value, $key = null)
    {
        return ($this->callable)();
    }
}