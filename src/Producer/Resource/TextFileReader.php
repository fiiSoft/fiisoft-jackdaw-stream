<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Resource;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Producer\Tech\BaseProducer;

final class TextFileReader extends BaseProducer
{
    /** @var resource|null */
    private $resource;
    
    private int $readBytes;
    private ?int $startingPosition = null;
    private bool $closeOnFinish;
    
    /**
     * @param resource $resource
     */
    public function __construct($resource, bool $closeOnFinish = false, ?int $readBytes = null)
    {
        if (\is_resource($resource)) {
            $this->resource = $resource;
        } else {
            throw InvalidParamException::describe('resource', $resource);
        }
    
        if ($readBytes === null || $readBytes >= 1) {
            $this->readBytes = $readBytes ?? 1024;
        } else {
            throw InvalidParamException::describe('readBytes', $readBytes);
        }
        
        $this->closeOnFinish = $closeOnFinish;
        
        $startinPosition = \ftell($this->resource);
        if (\is_int($startinPosition)) {
            $this->startingPosition = $startinPosition;
        }
    }
    
    public function getIterator(): \Generator
    {
        if ($this->resource === null) {
            return;
        }
        
        $lineNumber = 0;
    
        LOOP:
        $value = \fgets($this->resource, $this->readBytes);
        
        if ($value !== false) {
            yield $lineNumber++ => $value;

            goto LOOP; //Ohhh I love it so much! It makes me get goosebumps! <3
        }
    
        if ($this->closeOnFinish) {
            \fclose($this->resource);
            $this->resource = null;
        } elseif ($this->startingPosition !== null) {
            \fseek($this->resource, $this->startingPosition);
        }
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            if ($this->resource !== null && $this->closeOnFinish) {
                \fclose($this->resource);
            }
            
            $this->resource = null;
            
            parent::destroy();
        }
    }
}