<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator;

use FiiSoft\Jackdaw\Producer\Generator\Uuid\UuidGenerator;
use FiiSoft\Jackdaw\Producer\Generator\Uuid\UuidProvider;
use FiiSoft\Jackdaw\Producer\Tech\LimitedProducer;

final class RandomUuid extends LimitedProducer
{
    private UuidGenerator $provider;
    
    public function __construct(int $limit = \PHP_INT_MAX, ?UuidGenerator $provider = null)
    {
        parent::__construct($limit);
        
        $this->provider = $provider ?? UuidProvider::default();
    }
    
    public function getIterator(): \Generator
    {
        $count = 0;
        
        while ($count !== $this->limit) {
            yield $count++ => $this->provider->create();
        }
    }
}