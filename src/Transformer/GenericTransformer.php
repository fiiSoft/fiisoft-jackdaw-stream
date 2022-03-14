<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Transformer;

use FiiSoft\Jackdaw\Internal\Helper;

final class GenericTransformer implements Transformer
{
    /** @var callable */
    private $transformer;
    
    private int $numOfArgs;
    
    public function __construct(callable $transformer)
    {
        $this->transformer = $transformer;
        $this->numOfArgs = Helper::getNumOfArgs($transformer);
    }
    
    /**
     * @inheritDoc
     */
    public function transform($value, $key)
    {
        $transformer = $this->transformer;
        
        switch ($this->numOfArgs) {
            case 1:
                return $transformer($value);
            case 2:
                return $transformer($value, $key);
            default:
                throw Helper::wrongNumOfArgsException('Transformer', $this->numOfArgs, 1, 2);
        }
    }
}