<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping;

use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Mapping\Zip\FullSizeZip;
use FiiSoft\Jackdaw\Operation\Mapping\Zip\ZeroSizeZip;
use FiiSoft\Jackdaw\Producer\Producer;
use FiiSoft\Jackdaw\Producer\ProducerReady;
use FiiSoft\Jackdaw\Producer\Producers;

abstract class Zip extends BaseOperation
{
    /**
     * @param array<ProducerReady|resource|callable|iterable|scalar> $sources
     */
    final public static function create(array $sources): self
    {
        $producers = \array_map(
            static fn($producer): Producer => Producers::getAdapter($producer),
            Producers::prepare($sources)
        );
        
        return empty($producers) ? new ZeroSizeZip() : new FullSizeZip(...$producers);
    }
    
    protected function __construct()
    {
    }
}