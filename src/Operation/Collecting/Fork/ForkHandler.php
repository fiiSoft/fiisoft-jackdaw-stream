<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting\Fork;

use FiiSoft\Jackdaw\Internal\Destroyable;

interface ForkHandler extends Destroyable
{
    public function create(): ForkHandler;
    
    /**
     * @param mixed $value
     * @param mixed $key
     */
    public function accept($value, $key): void;
    
    public function isEmpty(): bool;
    
    /**
     * @return mixed|null
     */
    public function result();
}