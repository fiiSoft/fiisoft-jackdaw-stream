<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer;

use FiiSoft\Jackdaw\Internal\Helper;

final class GenericConsumer implements Consumer
{
    /** @var callable */
    private $consumer;
    
    private int $numOfArgs;
    
    public function __construct(callable $consumer)
    {
        $this->consumer = $consumer;
        $this->numOfArgs = Helper::getNumOfArgs($consumer);
    }
    
    public function consume($value, $key): void
    {
        $consume = $this->consumer;
    
        switch ($this->numOfArgs) {
            case 0:
                $consume();
            break;
            case 1:
                $consume($value);
            break;
            case 2:
                $consume($value, $key);
            break;
            default:
                throw Helper::wrongNumOfArgsException('Consumer', $this->numOfArgs, 0, 1, 2);
        }
    }
}