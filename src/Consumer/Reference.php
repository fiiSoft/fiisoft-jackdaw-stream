<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer;

final class Reference implements Consumer
{
    /** @var \Closure */
    private $setter;
    
    /**
     * @param mixed $value REFERENCE
     * @param mixed $key REFERENCE
     */
    public function __construct(&$value, &$key)
    {
        $this->setter = static function ($v, $k) use (&$value, &$key) {
            $value = $v;
            $key = $k;
        };
    }
    
    /**
     * @inheritDoc
     */
    public function consume($value, $key): void
    {
        $setter = $this->setter;
        $setter($value, $key);
    }
}