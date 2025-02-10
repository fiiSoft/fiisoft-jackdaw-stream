<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Discriminator\DiscriminatorReady;
use FiiSoft\Jackdaw\Internal\StreamBuilder;
use FiiSoft\Jackdaw\Transformer\TransformerReady;

interface Mapper extends MapperReady, DiscriminatorReady, TransformerReady, StreamBuilder
{
    /**
     * @param mixed $value
     * @param mixed $key
     * @return mixed
     */
    public function map($value, $key = null);
    
    /**
     * @return bool true when other mapper has been merged
     */
    public function mergeWith(Mapper $other): bool;
    
    /**
     * @return bool true when this and other mappers are the same, so they map arguments in identical way
     */
    public function equals(Mapper $other): bool;
    
    /**
     * @return Mapper a new instance that "knows" it's for mapping keys instead of values
     */
    public function makeKeyMapper(): Mapper;
}