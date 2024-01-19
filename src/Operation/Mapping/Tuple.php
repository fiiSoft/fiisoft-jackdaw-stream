<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping;

use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Mapping\Tuple\AssocTuple;
use FiiSoft\Jackdaw\Operation\Mapping\Tuple\NumericTuple;

abstract class Tuple extends BaseOperation
{
    private bool $isAssoc;
    
    final public static function create(bool $assoc = false): self
    {
        return $assoc ? new AssocTuple(true) : new NumericTuple(false);
    }
    
    final protected function __construct(bool $isAssoc)
    {
        $this->isAssoc = $isAssoc;
    }
    
    final public function isAssoc(): bool
    {
        return $this->isAssoc;
    }
}