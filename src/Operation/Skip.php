<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class Skip extends BaseOperation
{
    /** @var int */
    private $offset;
    
    /** @var int */
    private $count = 0;
    
    public function __construct(int $offset)
    {
        if ($offset < 0) {
            throw new \InvalidArgumentException('Invalid param offset');
        }
        
        $this->offset = $offset;
    }
    
    public function handle(Signal $signal)
    {
        if ($this->count === $this->offset) {
            $this->next->handle($signal);
        } else {
            ++$this->count;
        }
    }
}