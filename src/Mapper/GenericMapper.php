<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Mapper\Internal\BaseMapper;

final class GenericMapper extends BaseMapper
{
    /** @var callable */
    private $mapper;
    
    private int $numOfArgs;
    
    public function __construct(callable $mapper)
    {
        $this->mapper = $mapper;
        $this->numOfArgs = Helper::getNumOfArgs($mapper);
    }
    
    public function map($value, $key)
    {
        $map = $this->mapper;
    
        switch ($this->numOfArgs) {
            case 1:
                return $map($value);
            case 2:
                return $map($value, $key);
            case 0:
                return $map();
            default:
                throw Helper::wrongNumOfArgsException('Mapper', $this->numOfArgs, 0, 1, 2);
        }
    }
}