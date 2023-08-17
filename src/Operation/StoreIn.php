<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class StoreIn extends BaseOperation
{
    /** @var \ArrayAccess|array */
    private $buffer;
    
    private bool $reindex;
    
    /**
     * @param \ArrayAccess|array $buffer REFERENCE
     */
    public function __construct(&$buffer, bool $reindex = false)
    {
        if (\is_array($buffer) || $buffer instanceof \ArrayAccess) {
            $this->buffer = &$buffer;
            $this->reindex = $reindex;
        } else {
            throw new \InvalidArgumentException('Invalid param buffer');
        }
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->reindex) {
            $this->buffer[] = $signal->item->value;
        } else {
            $this->buffer[$signal->item->key] = $signal->item->value;
        }
        
        $this->next->handle($signal);
    }
}