<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal\State;

final class NextValue
{
    public bool $isSet = false;
    
    /** @var mixed */
    public $key = null;
    
    /** @var mixed */
    public $value = null;
}