<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Resource;

use FiiSoft\Jackdaw\Producer\Tech\BaseProducer;

final class PDOStatementAdapter extends BaseProducer
{
    private \PDOStatement $statement;
    private int $fetchMode;
    
    public function __construct(\PDOStatement $statement, ?int $fetchMode = null)
    {
        $this->statement = $statement;
        $this->fetchMode = $fetchMode ?? \PDO::FETCH_ASSOC;
    }
    
    public function getIterator(): \Generator
    {
        $count = 0;
        
        while (true) {
            $row = $this->statement->fetch($this->fetchMode);
            
            if ($row !== false) {
                yield $count++ => $row;
            } else {
                break;
            }
        }
    }
}