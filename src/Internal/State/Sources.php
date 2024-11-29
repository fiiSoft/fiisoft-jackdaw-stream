<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal\State;

use FiiSoft\Jackdaw\Internal\Destroyable;

final class Sources implements Destroyable
{
    /** @var Source[] */
    public array $stack = [];
    
    private bool $isDestroying = false;
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->isDestroying = true;
            
            $temp = $this->stack;
            $this->stack = [];
            
            foreach ($temp as $state) {
                $state->destroy();
            }
        }
    }
}