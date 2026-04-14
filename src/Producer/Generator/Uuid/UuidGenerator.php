<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator\Uuid;

use FiiSoft\Jackdaw\Producer\ProducerReady;

interface UuidGenerator extends ProducerReady
{
    public function create(): string;
}