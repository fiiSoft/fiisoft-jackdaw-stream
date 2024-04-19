<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Resource;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Producer\Tech\BaseProducer;

final class TextFileReader extends BaseProducer
{
    /** @var resource|null */
    private $resource;
    
    private ?int $readBytes = null;
    private ?int $startingPosition = null;
    
    private ?string $filepath = null;
    
    private bool $closeOnFinish;
    
    /**
     * @param resource|string $resource resource or full filepath; it have to be readable
     */
    public function __construct($resource, bool $closeOnFinish = false, ?int $readBytes = null)
    {
        if (\is_resource($resource)) {
            $this->resource = $resource;
            $this->closeOnFinish = $closeOnFinish;
        } elseif (\is_string($resource) && $this->openFileForRead($resource)) {
            $this->filepath = $resource;
            $this->closeOnFinish = true;
        } else {
            throw InvalidParamException::describe('resource', $resource);
        }
        
        if ($readBytes === null || $readBytes >= 1) {
            $this->readBytes = $readBytes;
        } else {
            throw InvalidParamException::describe('readBytes', $readBytes);
        }
    }
    
    public function getIterator(): \Generator
    {
        $this->prepare();
        
        if ($this->resource === null) {
            return;
        }
        
        $lineNumber = 0;
        
        if ($this->readBytes !== null) {
            LOOP_1:
            $value = \fgets($this->resource, $this->readBytes);
            if ($value !== false) {
                yield $lineNumber++ => $value;
                goto LOOP_1;
            }
        } else {
            LOOP_2:
            $value = \fgets($this->resource);
            if ($value !== false) {
                yield $lineNumber++ => $value;
                goto LOOP_2;
            }
        }
        
        $this->finish();
    }
    
    private function prepare(): void
    {
        if ($this->resource !== null) {
            if ($this->startingPosition === null && !$this->closeOnFinish) {
                $startinPosition = \ftell($this->resource);
                if (\is_int($startinPosition)) {
                    $this->startingPosition = $startinPosition;
                }
            }
        } elseif ($this->filepath !== null) {
            $this->openFileForRead($this->filepath);
        }
    }
    
    private function openFileForRead(string $filename): bool
    {
        if ($filename !== '') {
            $fp = @\fopen($filename, 'rb');
            
            if ($fp !== false) {
                $this->resource = $fp;
                return true;
            }
        }
        
        return false;
    }
    
    private function finish(): void
    {
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
            $this->filepath = null;
            
            parent::destroy();
        }
    }
}