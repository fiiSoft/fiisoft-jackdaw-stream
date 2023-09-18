<?php

namespace FiiSoft\Jackdaw\Producer\Generator\Uuid;

interface UuidGenerator
{
    public function create(): string;
}