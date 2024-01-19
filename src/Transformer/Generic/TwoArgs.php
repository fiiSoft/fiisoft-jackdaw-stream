<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Transformer\Generic;

use FiiSoft\Jackdaw\Transformer\GenericTransformer;

final class TwoArgs extends GenericTransformer
{
    /**
     * @inheritDoc
     */
    public function transform($value, $key)
    {
        return ($this->callable)($value, $key);
    }
}