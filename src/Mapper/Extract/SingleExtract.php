<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Extract;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Mapper\Extract;

final class SingleExtract extends Extract
{
    /** @var string|int */
    private $field;
    
    /**
     * @param string|int $field
     * @param mixed|null $orElse
     */
    protected function __construct($field, $orElse = null)
    {
        parent::__construct($orElse);
        
        $this->field = Helper::validField($field, 'field');
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null)
    {
        return $value[$this->field] ?? $this->orElse;
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield $key => $value[$this->field] ?? $this->orElse;
        }
    }
}