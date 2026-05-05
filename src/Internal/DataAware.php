<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

interface DataAware
{
    /**
     * @param Item[] $items
     */
    public function setCollectedItems(array $items): void;
    
    /**
     * @param array<string|int, mixed> $data
     */
    public function setCollectedData(array $data): void;
}