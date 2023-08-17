<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Adapter;

use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Mapper\Internal\BaseMapper;

final class DiscriminatorAdapter extends BaseMapper
{
    private Discriminator $discriminator;
    
    public function __construct(Discriminator $discriminator)
    {
        $this->discriminator = $discriminator;
    }
    
    public function map($value, $key)
    {
        $classifier = $this->discriminator->classify($value, $key);
        
        if (\is_string($classifier) || \is_bool($classifier) || \is_int($classifier)) {
            return $classifier;
        }
        
        throw new \UnexpectedValueException(
            'Unsupported value was returned from discriminator (got '.Helper::typeOfParam($classifier).')'
        );
        
    }
}