<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\State\ItemBuffer;

interface ItemBufferClient
{
    public function setItemBuffer(ItemBuffer $buffer): void;
}