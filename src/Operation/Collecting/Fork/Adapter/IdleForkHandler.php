<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting\Fork\Adapter;

use FiiSoft\Jackdaw\Operation\Collecting\Fork\ForkHandler;
use FiiSoft\Jackdaw\Operation\Collecting\ForkReady;

final class IdleForkHandler implements ForkHandler, ForkReady
{
    public function create(): ForkHandler
    {
        return $this;
    }
    
    /**
     * @inheritDoc
     */
    public function accept($value, $key): void
    {
        //noop
    }
    
    /**
     * @return true
     */
    public function isEmpty(): bool
    {
        return true;
    }
    
    /**
     * @inheritDoc
     */
    public function result()
    {
        return null;
    }
    
    /**
     * @inheritDoc
     */
    public function destroy(): void
    {
        //noop
    }
}