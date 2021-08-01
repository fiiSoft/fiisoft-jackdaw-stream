<?php

namespace FiiSoft\Jackdaw\Internal;

interface ResultCaster
{
    /**
     * @param string $separator
     * @return string
     */
    public function toString(string $separator = ','): string;
    
    /**
     * @param int $flags
     * @param bool $preserveKeys
     * @return string
     */
    public function toJson(int $flags = 0, bool $preserveKeys = false): string;
    
    /**
     * It works in the same way as toJson($flags, true).
     *
     * @param int $flags
     * @return string
     */
    public function toJsonAssoc(int $flags = 0): string;
    
    /**
     * @param bool $preserveKeys
     * @return array
     */
    public function toArray(bool $preserveKeys = false): array;
    
    /**
     * It works in the same way as toArray(true).
     *
     * @return array
     */
    public function toArrayAssoc(): array;
}