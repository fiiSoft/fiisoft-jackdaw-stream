<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Cast;

use FiiSoft\Jackdaw\Mapper\Internal\StateMapper;

final class ToArray extends StateMapper
{
    private bool $appendKey;
    
    public function __construct(bool $appendKey = false)
    {
        $this->appendKey = $appendKey;
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null)
    {
        if ($value instanceof \Traversable) {
            return \iterator_to_array($value);
        }
        
        if (\is_array($value)) {
            return $value;
        }
    
        return $this->appendKey ? [$key => $value] : [$value];
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($value instanceof \Traversable) {
                yield $key => \iterator_to_array($value);
            } elseif (\is_array($value)) {
                yield $key => $value;
            } elseif ($this->appendKey) {
                yield $key => [$key => $value];
            } else {
                yield $key => [$value];
            }
        }
    }
}