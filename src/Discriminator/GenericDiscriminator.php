<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Discriminator;

use FiiSoft\Jackdaw\Discriminator\Exception\DiscriminatorExceptionFactory;
use FiiSoft\Jackdaw\Discriminator\Generic\OneArg;
use FiiSoft\Jackdaw\Discriminator\Generic\TwoArgs;
use FiiSoft\Jackdaw\Discriminator\Generic\ZeroArg;
use FiiSoft\Jackdaw\Internal\Helper;

abstract class GenericDiscriminator implements Discriminator
{
    /** @var callable */
    protected $callable;
    
    final public static function create(callable $classifier): self
    {
        $numOfArgs = Helper::getNumOfArgs($classifier);
        
        switch ($numOfArgs) {
            case 1:
                return new OneArg($classifier);
            case 2:
                return new TwoArgs($classifier);
            case 0:
                return new ZeroArg($classifier);
            default:
                throw DiscriminatorExceptionFactory::invalidParamClassifier($numOfArgs);
        }
    }
    
    final protected function __construct(callable $classifier)
    {
        $this->callable = $classifier;
    }
}