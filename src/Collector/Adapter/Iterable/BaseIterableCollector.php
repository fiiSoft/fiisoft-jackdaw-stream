<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Collector\Adapter\Iterable;

use FiiSoft\Jackdaw\Collector\BaseCollector;
use FiiSoft\Jackdaw\Collector\IterableCollector;
use FiiSoft\Jackdaw\Internal\Helper;

/**
 * @implements \IteratorAggregate<string|int, mixed>
 */
abstract class BaseIterableCollector extends BaseCollector implements IterableCollector, \IteratorAggregate
{
    final public function toString(string $separator = ','): string
    {
        return \implode($separator, $this->toArray());
    }
    
    final public function toJson(?int $flags = null): string
    {
        return \json_encode($this->toArray(), Helper::jsonFlags($flags));
    }
}