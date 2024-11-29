<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

interface ResultProvider
{
    public function hasResult(): bool;
    
    public function getResult(): Item;
}