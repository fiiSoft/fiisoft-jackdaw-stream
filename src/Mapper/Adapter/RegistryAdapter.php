<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Adapter;

use FiiSoft\Jackdaw\Mapper\Internal\StateMapper;
use FiiSoft\Jackdaw\Registry\RegReader;

final class RegistryAdapter extends StateMapper
{
    private RegReader $reader;
    
    public function __construct(RegReader $reader)
    {
        $this->reader = $reader;
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null)
    {
        return $this->reader->read();
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $_) {
            yield $key => $this->reader->read();
        }
    }
    
    protected function buildKeyMapper(iterable $stream): iterable
    {
        foreach ($stream as $value) {
            yield $this->reader->read() => $value;
        }
    }
}