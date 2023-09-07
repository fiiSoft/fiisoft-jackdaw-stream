<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Collector\Adapter\Iterable;

use FiiSoft\Jackdaw\Collector\BaseCollector;
use FiiSoft\Jackdaw\Collector\IterableCollector;

abstract class BaseIterableCollector extends BaseCollector implements IterableCollector, \IteratorAggregate
{
    final public function toString(string $separator = ','): string
    {
        return \implode($separator, $this->getData());
    }
    
    final public function toJson(int $flags = 0): string
    {
        return \json_encode($this->getData(), \JSON_THROW_ON_ERROR | $flags);
    }
}