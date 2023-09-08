<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Resource;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Tech\NonCountableProducer;

final class PDOStatementAdapter extends NonCountableProducer
{
    private \PDOStatement $statement;
    private ?int $fetchMode;
    
    public function __construct(\PDOStatement $statement, ?int $fetchMode = null)
    {
        $this->statement = $statement;
        $this->fetchMode = $fetchMode ?? \PDO::FETCH_ASSOC;
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