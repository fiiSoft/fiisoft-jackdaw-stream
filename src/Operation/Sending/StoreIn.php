<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Sending\StoreIn\StoreInKeepKeys;
use FiiSoft\Jackdaw\Operation\Sending\StoreIn\StoreInReindexKeys;

abstract class StoreIn extends BaseOperation
{
    /** @var \ArrayAccess<string|int, mixed>|array<string|int, mixed> */
    protected $buffer;
    
    /**
     * @param \ArrayAccess<string|int, mixed>|array<string|int, mixed> $buffer REFERENCE
     */
    final public static function create(&$buffer, bool $reindex = false): self
    {
        return $reindex ? new StoreInReindexKeys($buffer) : new StoreInKeepKeys($buffer);
    }
    
    /**
     * @param \ArrayAccess<string|int, mixed>|array<string|int, mixed> $buffer REFERENCE
     */
    final protected function __construct(&$buffer)
    {
        if (\is_array($buffer) || $buffer instanceof \ArrayAccess) {
            $this->buffer = &$buffer;
        } else {
            throw InvalidParamException::describe('buffer', $buffer);
        }
    }
}