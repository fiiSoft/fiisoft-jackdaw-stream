<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Registry;

interface ValueKeyWriter extends RegWriter
{
    public function value(): RegReader;
    
    public function key(): RegReader;
}