<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Discriminator;

final class Reference implements Discriminator
{
    /** @var mixed REFERENCE */
    private $discriminator;
    
    /**
     * @param mixed $discriminator REFERENCE
     */
    public function __construct(&$discriminator)
    {
        $this->discriminator = &$discriminator;
    }
    
    /**
     * @inheritDoc
     */
    public function classify($value, $key = null)
    {
        return $this->discriminator;
    }
}