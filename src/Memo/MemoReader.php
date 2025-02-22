<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo;

use FiiSoft\Jackdaw\Discriminator\DiscriminatorReady;
use FiiSoft\Jackdaw\Mapper\MapperReady;
use FiiSoft\Jackdaw\Producer\ProducerReady;
use FiiSoft\Jackdaw\ValueRef\IntProvider;

interface MemoReader extends IntProvider, MapperReady, DiscriminatorReady, ProducerReady
{
    /**
     * @return mixed|null
     */
    public function read();
    
    public function equals(MemoReader $other): bool;
}