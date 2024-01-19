<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Discriminator;

final class ByValue implements Discriminator
{
    /**
     * @inheritDoc
     */
    public function classify($value, $key = null)
    {
        return $value;
    }
}