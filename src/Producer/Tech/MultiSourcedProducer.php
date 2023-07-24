<?php

namespace FiiSoft\Jackdaw\Producer\Tech;

use FiiSoft\Jackdaw\Producer\Producer;

interface MultiSourcedProducer extends Producer
{
    public function addProducer(Producer $producer): void;
    
    /**
     * @return Producer[]
     */
    public function getProducers(): array;
}