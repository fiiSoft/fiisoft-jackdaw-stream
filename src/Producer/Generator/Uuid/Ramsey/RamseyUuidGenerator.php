<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator\Uuid\Ramsey;

use FiiSoft\Jackdaw\Producer\Generator\Uuid\Exception\UuidUnavailableException;
use FiiSoft\Jackdaw\Producer\Generator\Uuid\UuidGenerator;
use FiiSoft\Jackdaw\Producer\Generator\Uuid\UuidVersion;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

abstract class RamseyUuidGenerator implements UuidGenerator
{
    /** @var callable */
    protected $factory;
    
    final public function __construct(?UuidVersion $version = null)
    {
        if ($version === null) {
            //@codeCoverageIgnoreStart
            if (\method_exists(Uuid::class, 'uuid6')) {
                $version = UuidVersion::v6();
            } elseif (\method_exists(Uuid::class, 'uuid4')) {
                $version = UuidVersion::v4();
            } else {
                $version = UuidVersion::nil();
            }
            //@codeCoverageIgnoreEnd
        }
        
        switch ($version->version()) {
            case 6:
                $this->factory = static fn(): UuidInterface => Uuid::uuid6();
            break;
            case 4:
                $this->factory = static fn(): UuidInterface => Uuid::uuid4();
            break;
            case 1:
                $this->factory = static fn(): UuidInterface => Uuid::uuid1();
            break;
            default:
                throw UuidUnavailableException::create();
        }
    }
}