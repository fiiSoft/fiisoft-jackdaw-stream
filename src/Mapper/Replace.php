<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Mapper\Internal\BaseMapper;

final class Replace extends BaseMapper
{
    /** @var array|string */
    private $search;
    
    /** @var array|string */
    private $replace;
    
    /**
     * It works with strings and produces strings. Internally, it's a wrapper for \str_replace.
     *
     * @param array|string $search
     * @param array|string $replace
     */
    public function __construct($search, $replace)
    {
        if (!\is_string($search) && !\is_array($search)) {
            throw Helper::invalidParamException('search', $search);
        }
        
        if (!\is_string($replace) && !\is_array($replace)) {
            throw Helper::invalidParamException('replace', $replace);
        }
        
        if ($search === '' || $search === []) {
            throw new \InvalidArgumentException('Invalid param search');
        }
        
        $this->search = $search;
        $this->replace = $replace;
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key)
    {
        if (\is_string($value)) {
            return \str_replace($this->search, $this->replace, $value);
        }
        
        throw new \LogicException('Unable to replace chars in '.Helper::typeOfParam($value));
    }
}