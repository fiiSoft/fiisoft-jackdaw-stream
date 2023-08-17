<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Discriminator;

use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Predicate\Predicate;

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
     * @param Discriminator|Condition|Predicate|Filter|Mapper|callable|string|int $discriminator
     * @param string|int $yes
     * @param string|int $no value of it must be different than value of $yes
     */
    public function __construct($discriminator, $yes = 'yes', $no = 'no')
    {
        if ($yes === $no) {
            throw new \LogicException('Params yes and no cannot be the same');
        }
        
        if ((\is_string($yes) && $yes !== '') || \is_int($yes)) {
            $this->yes = $yes;
        } else {
            throw new \InvalidArgumentException('Invalid param yes');
        }
        
        if ((\is_string($no) && $no !== '') || \is_int($no)) {
            $this->no = $no;
        } else {
            throw new \InvalidArgumentException('Invalid param no');
        }
        
        $this->discriminator = Discriminators::prepare($discriminator);
    }
    
    /**
     * @inheritDoc
     */
    public function classify($value, $key)
    {
        $classifier = $this->discriminator->classify($value, $key);
        
        if ($classifier === true) {
            return $this->yes;
        }
        
        if ($classifier === false) {
            return $this->no;
        }
        
        throw new \RuntimeException('YesNo discriminator can only work with boolean results');
    }
}