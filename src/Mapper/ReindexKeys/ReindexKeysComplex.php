<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\ReindexKeys;

use FiiSoft\Jackdaw\Mapper\Internal\StateMapper;

final class ReindexKeysComplex extends StateMapper
{
    private int $start;
    private int $step;
    
    /** @var array<int[]> */
    private array $keys = [];
    
    public function __construct(int $start = 0, int $step = 1)
    {
        $this->start = $start;
        $this->step = $step;
    }
    
    /**
     * @return array<int, mixed>|false
     */
    public function map($value, $key = null)
    {
        return \array_combine($this->keys[\count($value)] ?? $this->prepareKeys(\count($value)), $value);
    }
    
    /**
     * @inheritDoc
     */
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield $key => \array_combine(
                $this->keys[\count($value)] ?? $this->prepareKeys(\count($value)),
                $value
            );
        }
    }
    
    /**
     * @return int[]
     */
    private function prepareKeys(int $count): array
    {
        for ($i = 0, $index = $this->start; $i < $count; ++$i, $index += $this->step) {
            $this->keys[$count][] = $index;
        }
        
        return $this->keys[$count];
    }
}