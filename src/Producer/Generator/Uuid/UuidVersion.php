<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator\Uuid;

final class UuidVersion
{
    private int $version;
    
    public static function v6(): self
    {
        return new self(6);
    }
    
    public static function v4(): self
    {
        return new self(4);
    }
    
    public static function v1(): self
    {
        return new self(1);
    }
    
    public static function nil(): self
    {
        return new self(0);
    }
    
    private function __construct(int $version)
    {
        $this->version = $version;
    }
    
    public function version(): int
    {
        return $this->version;
    }
}