<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Operation\Operation;

interface SingularOperation
{
    public function isSingular(): bool;
    
    public function getSingular(): Operation;
}