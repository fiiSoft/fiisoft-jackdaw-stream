<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class SkipNth extends BaseOperation
{
    private int $num, $count = 0;
    
    public function __construct(int $num)
    {
        if ($num < 2) {
            throw InvalidParamException::describe('num', $num);
        }
        
        $this->num = $num;
    }
    
    public function handle(Signal $signal): void
    {
        if (++$this->count === $this->num) {
            $this->count = 0;
        } else {
            $this->next->handle($signal);
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (++$this->count === $this->num) {
                $this->count = 0;
            } else {
                yield $key => $value;
            }
        }
    }
    
    public function num(): int
    {
        return $this->num;
    }
}