<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator\Uuid\Ramsey;

use FiiSoft\Jackdaw\Producer\Generator\Uuid\UuidGenerator;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

abstract class RamseyUuidGenerator implements UuidGenerator
{
    protected UuidInterface $generator;
    
    public function __construct(?UuidInterface $generator)
    {
        $this->generator = $generator ?? Uuid::uuid4();
    }
}