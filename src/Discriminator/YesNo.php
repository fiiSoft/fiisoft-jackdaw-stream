<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Discriminator;

use FiiSoft\Jackdaw\Discriminator\Exception\DiscriminatorExceptionFactory;
use FiiSoft\Jackdaw\Exception\InvalidParamException;

/**
 * This discriminator can only work with other discriminator which returns boolean values.
 */
final class YesNo implements Discriminator
{
    private Discriminator $discriminator;
    
    /** @var int|string */
    private $yes;
    
    /** @var int|string */
    private $no;
    
    /**
     * @param DiscriminatorReady|callable|array<string|int>|string|int $discriminator it SHOULD returns boolean values
     * @param string|int $yes
     * @param string|int $no value of it must be different than value of $yes
     */
    public function __construct($discriminator, $yes = 'yes', $no = 'no')
    {
        if ($yes === $no) {
            throw DiscriminatorExceptionFactory::paramsYesAndNoCannotBeTheSame();
        }
        
        if ((\is_string($yes) && $yes !== '') || \is_int($yes)) {
            $this->yes = $yes;
        } else {
            throw InvalidParamException::describe('yes', $yes);
        }
        
        if ((\is_string($no) && $no !== '') || \is_int($no)) {
            $this->no = $no;
        } else {
            throw InvalidParamException::describe('no', $no);
        }
        
        $this->discriminator = Discriminators::prepare($discriminator);
    }
    
    /**
     * @inheritDoc
     */
    public function classify($value, $key = null)
    {
        return $this->discriminator->classify($value, $key) ? $this->yes : $this->no;
    }
}