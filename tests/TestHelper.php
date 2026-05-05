<?php declare(strict_types=1);

namespace FiiSoft\Test\Helper;

final class TestHelper
{
    private static ?bool $shouldSet = null;
    
    public static function callMethod(object $object, string $name): void
    {
        $method = self::getMethod($object, $name);
        
        $method->invoke($object);
    }
    
    /**
     * @param object|string $object
     */
    public static function getMethod($object, string $name): \ReflectionMethod
    {
        if ($object instanceof \ReflectionClass) {
            $refl = $object;
        } elseif(\is_string($object)) {
            $refl = new \ReflectionClass($object);
        } else {
            $refl = new \ReflectionObject($object);
        }
        
        $method = $refl->getMethod($name);
        self::makeMethodAccessible($method);
        
        return $method;
    }
    
    private static function makeMethodAccessible(\ReflectionMethod $method): void
    {
        if (self::shouldSetAccessible()) {
            $method->setAccessible(true);
        }
    }
    
    /**
     * @return mixed
     */
    public static function getValueOfProp(object $object, string $name)
    {
        return self::getProperty($object, $name)->getValue($object);
    }
    
    public static function getProperty(object $object, string $name): \ReflectionProperty
    {
        if ($object instanceof \ReflectionClass) {
            $prop = $object->getProperty($name);
        } else {
            $prop = (new \ReflectionObject($object))->getProperty($name);
        }
        
        self::makePropAccessible($prop);
        
        return $prop;
    }
    
    private static function makePropAccessible(\ReflectionProperty $prop): void
    {
        if (self::shouldSetAccessible()) {
            $prop->setAccessible(true);
        }
    }
    
    private static function shouldSetAccessible(): bool
    {
        if (self::$shouldSet === null) {
            self::$shouldSet = \version_compare(\PHP_VERSION, '8.1.0') < 0;
        }
        
        return self::$shouldSet;
    }
}