<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Transformer\Adapter;

use FiiSoft\Jackdaw\Memo\SequenceMemo;
use FiiSoft\Jackdaw\Transformer\Transformer;

final class SequenceMemoAdapter implements Transformer
{
    private SequenceMemo $memo;
    
    public function __construct(SequenceMemo $memo)
    {
        $this->memo = $memo;
    }
    
    /**
     * @param mixed $value
     * @param string|int $key
     * @return array<string|int, mixed>
     */
    public function transform($value, $key): array
    {
        return $this->memo->toArray();
    }
}