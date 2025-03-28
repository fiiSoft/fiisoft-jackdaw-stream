<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Special;

use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Special\CollectInArray\InArrayKeepKeys;
use FiiSoft\Jackdaw\Operation\Special\CollectInArray\InArrayReindexKeys;

abstract class CollectInArray extends BaseOperation
{
    /** @var array<string|int, mixed> */
    protected array $result = [];
    
    final public static function create(bool $preserveKeys = false): self
    {
        return $preserveKeys ? new InArrayKeepKeys() : new InArrayReindexKeys();
    }
    
    final protected function __construct()
    {
    }
    
    /**
     * @return array<string|int, mixed>
     */
    final public function result(): array
    {
        try {
            return $this->result;
        } finally {
            $this->result = [];
        }
    }
}