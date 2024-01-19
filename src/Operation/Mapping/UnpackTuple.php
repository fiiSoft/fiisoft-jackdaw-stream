<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping;

use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Mapping\UnpackTuple\UnpackAssocTuple;
use FiiSoft\Jackdaw\Operation\Mapping\UnpackTuple\UnpackNumericTuple;

abstract class UnpackTuple extends BaseOperation
{
    private bool $assoc;
    
    final public static function create(bool $assoc = false): self
    {
        return $assoc ? new UnpackAssocTuple(true) : new UnpackNumericTuple(false);
    }
    
    final protected function __construct(bool $assoc = false)
    {
        $this->assoc = $assoc;
    }
    
    final public function isAssoc(): bool
    {
        return $this->assoc;
    }
}