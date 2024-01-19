<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal\ItemBuffer;

interface ItemBufferClient
{
    public function setItemBuffer(ItemBuffer $buffer): void;
}