<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Resource;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Producer;

final class TextFileReader implements Producer
{
    /** @var resource */
    private $resource;
    
    private int $readBytes;
    private bool $closeOnFinish;
    
    /**
     * @param resource $resource
     */
    public function __construct($resource, bool $closeOnFinish = false, ?int $readBytes = null)
    {
        if (\is_resource($resource)) {
            $this->resource = $resource;
        } else {
            throw new \InvalidArgumentException('Invalid param resource');
        }
    
        if ($readBytes === null || $readBytes >= 1) {
            $this->readBytes = $readBytes ?? 1024;
        } else {
            throw new \InvalidArgumentException('Invalid param readBytes');
        }
        
        $this->closeOnFinish = $closeOnFinish;
    }
    
    public function feed(Item $item): \Generator
    {
        $lineNumber = 0;
    
        LOOP:
        $item->value = \fgets($this->resource, $this->readBytes);
        
        if ($item->value !== false) {
            $item->key = $lineNumber++;
            yield;
            
            goto LOOP; //Ohhh I love it so much! It makes me get goosebumps! <3
        }
    
        if ($this->closeOnFinish) {
            \fclose($this->resource);
        }
    }
}