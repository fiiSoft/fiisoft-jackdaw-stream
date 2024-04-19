<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Exception\UnsupportedValueException;
use FiiSoft\Jackdaw\Mapper\Internal\StateMapper;

final class FormatTime extends StateMapper
{
    private string $format;
    
    public function __construct(string $format = 'Y-m-d H:i:s')
    {
        if ($format === '') {
            throw InvalidParamException::describe('format', $format);
        }
        
        $this->format = $format;
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null)
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format($this->format);
        }
        
        throw UnsupportedValueException::cannotCastNonTimeObjectToString($value);
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($value instanceof \DateTimeInterface) {
                yield $key => $value->format($this->format);
            } else {
                throw UnsupportedValueException::cannotCastNonTimeObjectToString($value);
            }
        }
    }
}