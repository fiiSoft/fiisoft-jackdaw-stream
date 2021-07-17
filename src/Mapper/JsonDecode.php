<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Internal\Helper;

final class JsonDecode implements Mapper
{
    /** @var bool */
    private $associative;
    
    /** @var int */
    private $flags;
    
    public function __construct(int $flags = 0, bool $associative = true)
    {
        $this->associative = $associative;
        $this->flags = $flags;
    }
    
    public function map($value, $key)
    {
        if (\is_string($value)) {
            return \json_decode($value, $this->associative, 512, $this->flags);
        }
        
        throw new \LogicException('You cannot decode '.Helper::typeOfParam($value).' to JSON');
    }
}