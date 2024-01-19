<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Discriminator;

final class ByField implements Discriminator
{
    /** @var int|string */
    private $field;
    
    /** @var int|string|null */
    private $orElse;
    
    /**
     * @param string|int $field
     * @param string|int|null $orElse
     */
    public function __construct($field, $orElse = null)
    {
        $this->field = $field;
        $this->orElse = $orElse;
    }
    
    /**
     * @inheritDoc
     */
    public function classify($value, $key = null)
    {
        return $value[$this->field] ?? $this->orElse;
    }
}