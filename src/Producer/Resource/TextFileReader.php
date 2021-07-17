<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Resource;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Producer;

final class TextFileReader implements Producer
{
    /** @var resource */
    private $fp;
    
    /**
     * @param resource $filePointer
     */
    public function __construct($filePointer)
    {
        if (!\is_resource($filePointer)) {
            throw new \InvalidArgumentException('Invalid param filePointer');
        }
        
        $this->fp = $filePointer;
    }
    
    public function feed(Item $item): \Generator
    {
        $lineNumber = 0;
    
        LOOP:
        $item->value = \fgets($this->fp);
        
        if ($item->value !== false) {
            $item->key = $lineNumber++;
            yield;
            
            goto LOOP; //Ohhh I love it so much! It makes me get goosebumps! <3
        }
    }
}