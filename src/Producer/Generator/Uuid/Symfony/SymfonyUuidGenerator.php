<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator\Uuid\Symfony;

use FiiSoft\Jackdaw\Producer\Generator\Uuid\UuidGenerator;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Uid\Uuid;

abstract class SymfonyUuidGenerator implements UuidGenerator
{
    protected AbstractUid $generator;
    
    public function __construct(?AbstractUid $generator)
    {
        $this->generator = $generator ?? Uuid::v4();
    }
}