<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer;

use FiiSoft\Jackdaw\Internal\Destroyable;
use FiiSoft\Jackdaw\Mapper\MapperReady;
use FiiSoft\Jackdaw\Stream;

/**
 * @extends \IteratorAggregate<string|int, mixed>
 */
interface Producer extends Destroyable, ProducerReady, MapperReady, \IteratorAggregate
{
    public function stream(): Stream;
}