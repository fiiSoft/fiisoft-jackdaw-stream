<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

final class Item
{
    /** @var mixed */
    public $key;
    
    /** @var mixed */
    public $value;
    
    /**
     * @param mixed|null $key
     * @param mixed|null $value
     */
    public function __construct($key = null, $value = null)
    {
        $this->key = $key;
        $this->value = $value;
    }
}