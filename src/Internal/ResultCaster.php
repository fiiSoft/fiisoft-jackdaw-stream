<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Mapper\MapperReady;
use FiiSoft\Jackdaw\Producer\ProducerReady;

interface ResultCaster extends ProducerReady, MapperReady
{
    public function toString(string $separator = ','): string;
    
    public function toJson(?int $flags = null, bool $preserveKeys = false): string;
    
    /**
     * It works in the same way as toJson($flags, true).
     */
    public function toJsonAssoc(?int $flags = null): string;
    
    /**
     * @return array<string|int, mixed>
     */
    public function toArray(bool $preserveKeys = false): array;
    
    /**
     * It works in the same way as toArray(true).
     *
     * @return array<string|int, mixed>
     */
    public function toArrayAssoc(): array;
}