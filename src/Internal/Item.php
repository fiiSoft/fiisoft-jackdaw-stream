<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

class Item
{
    /** @var string|int */
    public $key;
    
    /** @var mixed */
    public $value;
    
    public function __construct($key = null, $value = null)
    {
        $this->key = $key;
        $this->value = $value;
    }
    
    public function copy(): Item
    {
        return new self($this->key, $this->value);
    }
}