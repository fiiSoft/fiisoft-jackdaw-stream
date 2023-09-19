<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Registry\Writer;

use FiiSoft\Jackdaw\Registry\RegWriter;
use FiiSoft\Jackdaw\Registry\Storage;

abstract class SingleWriter implements RegWriter
{
    protected Storage $storage;
    
    protected string $name;
    
    public function __construct(Storage $storage, string $name)
    {
        $this->storage = $storage;
        $this->name = $name;
    }
}