<?php

namespace FiiSoft\Jackdaw\Registry;

use FiiSoft\Jackdaw\Discriminator\DiscriminatorReady;
use FiiSoft\Jackdaw\Mapper\MapperReady;
use FiiSoft\Jackdaw\Producer\ProducerReady;

interface RegReader extends ProducerReady, MapperReady, DiscriminatorReady
{
    /**
     * @return mixed|null
     */
    public function read();
}