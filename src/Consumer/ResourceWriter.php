<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Internal\Check;

final class ResourceWriter implements Consumer
{
    /** @var resource */
    private $resource;
    
    private int $mode;
    
    /**
     * @param resource $resource
     */
    public function __construct($resource, int $mode = Check::VALUE)
    {
        if (\is_resource($resource)) {
            $this->resource = $resource;
        } else {
            throw InvalidParamException::describe('resource', $resource);
        }
        
        $this->mode = $mode;
    }
    
    /**
     * @param mixed $value anything that can be casted to string
     * @param mixed $key anything that can be casted to string
     * @return void
     */
    public function consume($value, $key): void
    {
        switch ($this->mode) {
            case Check::VALUE:
                \fwrite($this->resource, (string) $value);
            break;
            
            case Check::KEY:
                \fwrite($this->resource, (string) $key);
            break;
            
            default:
                \fwrite($this->resource, $key.':'.$value);
        }
    }
    
    /**
     * @inheritDoc
     */
    public function buildStream(iterable $stream): iterable
    {
        switch ($this->mode) {
            case Check::VALUE:
                foreach ($stream as $key => $value) {
                    \fwrite($this->resource, (string) $value);
                    yield $key => $value;
                }
            break;
            
            case Check::KEY:
                foreach ($stream as $key => $value) {
                    \fwrite($this->resource, (string) $key);
                    yield $key => $value;
                }
            break;
            
            default:
                foreach ($stream as $key => $value) {
                    \fwrite($this->resource, $key.':'.$value);
                    yield $key => $value;
                }
        }
    }
}