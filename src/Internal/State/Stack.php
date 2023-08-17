<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal\State;

use FiiSoft\Jackdaw\Internal\Destroyable;

final class Stack implements Destroyable
{
    /** @var Source[] */
    public array $states = [];
    
    private bool $isDestroying = false;
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->isDestroying = true;
            
            $temp = $this->states;
            $this->states = [];
            
            foreach ($temp as $state) {
                $state->destroy();
            }
        }
    }
}