<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo\Adapter\SingleMemo;

use FiiSoft\Jackdaw\Memo\SingleMemo;

abstract class BaseSingleMemo implements SingleMemo
{
    /** @var mixed */
    protected $value = null;
    
    /**
     * @param mixed|null $initial
     */
    final public function __construct($initial = null)
    {
        $this->value = $initial;
    }
    
    /**
     * @inheritDoc
     */
    final public function read()
    {
        return $this->value;
    }
}