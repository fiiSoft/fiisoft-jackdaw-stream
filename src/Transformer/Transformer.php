<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Transformer;

interface Transformer extends TransformerReady
{
    /**
     * @param mixed $value
     * @param string|int $key
     * @return mixed
     */
    public function transform($value, $key);
}