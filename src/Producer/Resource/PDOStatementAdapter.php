<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Resource;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\BaseProducer;

final class PDOStatementAdapter extends BaseProducer
{
    private \PDOStatement $statement;
    private ?int $fetchMode;
    
    public function __construct(\PDOStatement $statement, ?int $fetchMode = null)
    {
        $this->statement = $statement;
        $this->fetchMode = $fetchMode;
    }
    
    public function feed(Item $item): \Generator
    {
        $count = 0;
        
        while (true) {
            $row = $this->statement->fetch($this->fetchMode);
            
            if ($row !== false) {
                $item->key = $count++;
                $item->value = $row;
                yield;
            } else {
                break;
            }
        }
    }
}