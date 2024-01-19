<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\MoveTo;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Mapper\MoveTo;

final class MoveToFieldKey extends MoveTo
{
    /** @var int|string */
    private $key;
    
    /**
     * @param string|int $field
     * @param string|int $key
     */
    protected function __construct($field, $key)
    {
        parent::__construct($field);
        
        $this->key = Helper::validField($key, 'key');
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null): array
    {
        return [
            $this->key  => $key,
            $this->field => $value,
        ];
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield $key => [
                $this->key => $key,
                $this->field => $value,
            ];
        }
    }
}