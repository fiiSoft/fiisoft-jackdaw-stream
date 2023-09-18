<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator\Uuid;

use FiiSoft\Jackdaw\Producer\Generator\Uuid\Ramsey\RamseyDefault;
use FiiSoft\Jackdaw\Producer\Generator\Uuid\Ramsey\RamseyHex;
use FiiSoft\Jackdaw\Producer\Generator\Uuid\Symfony\SymfonyBase32;
use FiiSoft\Jackdaw\Producer\Generator\Uuid\Symfony\SymfonyBase58;
use FiiSoft\Jackdaw\Producer\Generator\Uuid\Symfony\SymfonyDefault;
use Ramsey\Uuid\UuidInterface as RamseyUuid;
use Symfony\Component\Uid\AbstractUid as SymfonyUuid;

final class UuidProvider
{
    /**
     * @codeCoverageIgnore
     */
    public static function default(bool $compact = true): UuidGenerator
    {
        if (\interface_exists(RamseyUuid::class)) {
            return $compact ? self::ramseyHex() : self::ramsey();
        }
        
        if (\class_exists(SymfonyUuid::class)) {
            return $compact ? self::symfonyBase58() : self::symfony();
        }
        
        throw new \LogicException(
            'You have to have installed either ramsey/uuid or symfony/uid to create default UuidProvider'
        );
    }
    
    public static function ramsey(?RamseyUuid $generator = null): UuidGenerator
    {
        return new RamseyDefault($generator);
    }
    
    public static function ramseyHex(?RamseyUuid $generator = null): UuidGenerator
    {
        return new RamseyHex($generator);
    }
    
    public static function symfony(?SymfonyUuid $generator = null): UuidGenerator
    {
        return new SymfonyDefault($generator);
    }
    
    public static function symfonyBase32(?SymfonyUuid $generator = null): UuidGenerator
    {
        return new SymfonyBase32($generator);
    }
    
    public static function symfonyBase58(?SymfonyUuid $generator = null): UuidGenerator
    {
        return new SymfonyBase58($generator);
    }
}