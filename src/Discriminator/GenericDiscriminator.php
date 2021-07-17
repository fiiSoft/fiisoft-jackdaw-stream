<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Discriminator;

use FiiSoft\Jackdaw\Internal\Helper;

final class GenericDiscriminator implements Discriminator
{
    /** @var callable */
    private $classifier;
    
    private int $numOfArgs;
    
    public function __construct(callable $classifier)
    {
        $this->classifier = $classifier;
        $this->numOfArgs = Helper::getNumOfArgs($classifier);
    }
    
    public function classify($value, $key)
    {
        $classify = $this->classifier;
    
        switch ($this->numOfArgs) {
            case 1: return $classify($value);
            case 2: return $classify($value, $key);
            default:
                throw Helper::wrongNumOfArgsException('Classifier', $this->numOfArgs, 1, 2);
        }
    }
}