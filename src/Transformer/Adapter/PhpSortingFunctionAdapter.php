<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Transformer\Adapter;

use FiiSoft\Jackdaw\Transformer\Transformer;

final class PhpSortingFunctionAdapter implements Transformer
{
    /** @var callable */
    private $sortFunc;
    
    public function __construct(callable $sortFunc)
    {
        $this->sortFunc = $sortFunc;
    }
    
    /**
     * @inheritDoc
     */
    public function transform($value, $key)
    {
        ($this->sortFunc)($value);
        
        return $value;
    }
}