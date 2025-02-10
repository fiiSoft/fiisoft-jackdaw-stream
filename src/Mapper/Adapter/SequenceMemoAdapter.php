<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Adapter;

use FiiSoft\Jackdaw\Mapper\Internal\StateMapper;
use FiiSoft\Jackdaw\Memo\SequenceMemo;

final class SequenceMemoAdapter extends StateMapper
{
    private SequenceMemo $memo;
    
    public function __construct(SequenceMemo $memo)
    {
        $this->memo = $memo;
    }
    
    /**
     * @param mixed $value
     * @param mixed $key
     * @return array<string|int, mixed>
     */
    public function map($value, $key = null): array
    {
        return $this->memo->toArray();
    }
}