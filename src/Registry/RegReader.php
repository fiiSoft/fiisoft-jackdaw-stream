<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Registry;

use FiiSoft\Jackdaw\Discriminator\DiscriminatorReady;
use FiiSoft\Jackdaw\Mapper\MapperReady;
use FiiSoft\Jackdaw\Producer\ProducerReady;
use FiiSoft\Jackdaw\ValueRef\IntProvider;

interface RegReader extends ProducerReady, MapperReady, DiscriminatorReady, IntProvider
{
    /**
     * @return mixed|null
     */
    public function read();
}