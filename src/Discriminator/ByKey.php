<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Discriminator;

final class ByKey implements Discriminator
{
    /**
     * @inheritDoc
     */
    public function classify($value, $key)
    {
        return $key;
    }
}