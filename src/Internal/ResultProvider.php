<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

interface ResultProvider extends Destroyable
{
    public function isReindexed(): bool;
    
    public function getResult(): ?Item;
}