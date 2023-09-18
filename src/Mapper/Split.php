<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Mapper\Internal\BaseMapper;

final class Split extends BaseMapper
{
    private string $separator;
    
    public function __construct(string $separator = ' ')
    {
        $this->separator = $separator;
    }
    
    public function map($value, $key): array
    {
        if (\is_string($value)) {
            return \explode($this->separator, $value);
        }
        
        throw new \LogicException('It is impossible to handle '.Helper::typeOfParam($value).' as string');
    }
}