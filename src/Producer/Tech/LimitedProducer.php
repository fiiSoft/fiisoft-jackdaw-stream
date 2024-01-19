<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Tech;

use FiiSoft\Jackdaw\Exception\InvalidParamException;

abstract class LimitedProducer extends BaseProducer
{
    protected int $limit;
    
    public function __construct(int $limit = \PHP_INT_MAX)
    {
        if ($limit < 0) {
            throw InvalidParamException::describe('limit', $limit);
        }
        
        $this->limit = $limit;
    }
}