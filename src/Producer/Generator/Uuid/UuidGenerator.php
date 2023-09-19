<?php

namespace FiiSoft\Jackdaw\Producer\Generator\Uuid;

use FiiSoft\Jackdaw\Producer\ProducerReady;

interface UuidGenerator extends ProducerReady
{
    public function create(): string;
}