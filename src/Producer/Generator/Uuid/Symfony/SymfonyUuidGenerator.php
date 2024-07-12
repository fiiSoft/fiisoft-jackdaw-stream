<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator\Uuid\Symfony;

use FiiSoft\Jackdaw\Producer\Generator\Uuid\Exception\UuidUnavailableException;
use FiiSoft\Jackdaw\Producer\Generator\Uuid\UuidGenerator;
use FiiSoft\Jackdaw\Producer\Generator\Uuid\UuidVersion;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Uid\Uuid;

abstract class SymfonyUuidGenerator implements UuidGenerator
{
    /** @var callable */
    protected $factory;
    
    public function __construct(?UuidVersion $version = null)
    {
        if ($version === null) {
            //@codeCoverageIgnoreStart
            if (\method_exists(Uuid::class, 'v6')) {
                $version = UuidVersion::v6();
            } elseif (\method_exists(Uuid::class, 'v4')) {
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