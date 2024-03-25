<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator\Uuid\Symfony;

use FiiSoft\Jackdaw\Producer\Generator\Uuid\Exception\UuidUnavailableException;
use FiiSoft\Jackdaw\Producer\Generator\Uuid\UuidGenerator;
use FiiSoft\Jackdaw\Producer\Generator\Uuid\UuidVersion;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV4;
use Symfony\Component\Uid\UuidV6;

abstract class SymfonyUuidGenerator implements UuidGenerator
{
    /** @var callable */
    protected $factory;
    
    public function __construct(?UuidVersion $version = null)
    {
        if ($version === null) {
            //@codeCoverageIgnoreStart
            if (\class_exists(UuidV6::class)) {
                $version = UuidVersion::v6();
            } elseif (\class_exists(UuidV4::class)) {
                $version = UuidVersion::v4();
            } else {
                $version = UuidVersion::nil();
            }
            //@codeCoverageIgnoreEnd
        }
        
        switch ($version->version()) {
            case 6:
                $this->factory = static fn(): AbstractUid => Uuid::v6();
            break;
            case 4:
                $this->factory = static fn(): AbstractUid => Uuid::v4();
            break;
            case 1:
                $this->factory = static fn(): AbstractUid => Uuid::v1();
            break;
            default:
                throw UuidUnavailableException::create();
        }
    }
}