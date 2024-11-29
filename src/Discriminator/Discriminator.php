<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Discriminator;

use FiiSoft\Jackdaw\Comparator\ComparatorReady;
use FiiSoft\Jackdaw\Mapper\MapperReady;

interface Discriminator extends MapperReady, ComparatorReady, DiscriminatorReady
{
    /**
     * @param mixed $value
     * @param mixed|null $key
     * @return string|int|bool used to classify element to some group; just remember that bool will be cast to int
     */
    public function classify($value, $key = null);
}