<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal;

interface Reindexable
{
    public function isReindexed(): bool;
}