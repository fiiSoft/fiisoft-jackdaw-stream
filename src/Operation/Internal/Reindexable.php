<?php

namespace FiiSoft\Jackdaw\Operation\Internal;

interface Reindexable
{
    public function isReindexed(): bool;
}