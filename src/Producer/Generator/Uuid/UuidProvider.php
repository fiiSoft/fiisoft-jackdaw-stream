<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator\Uuid;

use FiiSoft\Jackdaw\Producer\Generator\Uuid\Exception\UuidUnavailableException;
use FiiSoft\Jackdaw\Producer\Generator\Uuid\Ramsey\RamseyDefault;
use FiiSoft\Jackdaw\Producer\Generator\Uuid\Ramsey\RamseyHex;
use FiiSoft\Jackdaw\Producer\Generator\Uuid\Symfony\SymfonyBase32;
use FiiSoft\Jackdaw\Producer\Generator\Uuid\Symfony\SymfonyBase58;
use FiiSoft\Jackdaw\Producer\Generator\Uuid\Symfony\SymfonyDefault;
use Ramsey\Uuid\UuidInterface as RamseyUuid;
use Symfony\Component\Uid\AbstractUid as SymfonyUuid;

final class UuidProvider
{
    public static function version(UuidVersion $version): UuidGenerator
    {
        return self::default(true, $version);
    }
    
    /**
     * @codeCoverageIgnore
     */
    public static function default(bool $compact = true, ?UuidVersion $version = null): UuidGenerator
    {
        if (\class_exists(SymfonyUuid::class)) {
            return $compact ? self::symfonyBase58($version) : self::symfony($version);
        }
        
        if (\interface_exists(RamseyUuid::class)) {
            return $compact ? self::ramseyHex($version) : self::ramsey($version);
        }
        
        throw UuidUnavailableException::create();
    }
    
    public static function ramsey(?UuidVersion $version = null): UuidGenerator
    {
        return new RamseyDefault($version);
    }
    
    public static function ramseyHex(?UuidVersion $version = null): UuidGenerator
    {
        return new RamseyHex($version);
    }
    
    public static function symfony(?UuidVersion $version = null): UuidGenerator
    {
        return new SymfonyDefault($version);
    }
    
    public static function symfonyBase32(?UuidVersion $version = null): UuidGenerator
    {
        return new SymfonyBase32($version);
    }
    
    public static function symfonyBase58(?UuidVersion $version = null): UuidGenerator
    {
        return new SymfonyBase58($version);
    }
}