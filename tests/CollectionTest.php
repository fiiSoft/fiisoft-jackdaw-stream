<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\Collection\BaseStreamCollection;
use FiiSoft\Jackdaw\Operation\Terminating\GroupBy;
use FiiSoft\Jackdaw\Stream;
use PHPUnit\Framework\TestCase;

final class CollectionTest extends TestCase
{
    private BaseStreamCollection $collection;
    
    private array $initialData = [
        'numbers' => [6, 3, 7, 9],
        'words' => ['the', 'quick', 'brown', 'fox'],
    ];
    
    protected function setUp(): void
    {
        $this->collection = BaseStreamCollection::create(GroupBy::create('is_string'), $this->initialData);
    }
    
    public function test_get_existing_group(): void
    {
        self::assertSame('[6,3,7,9]', $this->collection->get('numbers')->toJson());
    }
    
    public function test_get_nonexisting_group(): void
    {
        self::assertSame('[]', $this->collection->get('unknown_group')->toJson());
    }
    
    public function test_get_returns_the_same_result_each_time_when_is_called(): void
    {
        $first = $this->collection->get('numbers');
        $second = $this->collection->get('numbers');
        
        self::assertSame($second, $first);
    }
    
    public function test_result_returned_from_collection_should_be_reusable(): void
    {
        $numbers = $this->collection->get('numbers');
        
        self::assertSame('[6,3,7,9]', $numbers->toJson());
        self::assertSame([6,3,7,9], $numbers->toArray());
    }
    
    public function test_get_all_as_array(): void
    {
        self::assertSame($this->initialData, $this->collection->toArray());
    }
    
    public function test_make_json_from_all(): void
    {
        self::assertSame(\json_encode($this->initialData), $this->collection->toJson());
    }
    
    public function test_it_throws_exception_on_invalid_argument_id(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('stream id'));
        
        $this->collection->get(15.55);
    }
    
    public function test_collection_is_iterable(): void
    {
        for ($i = 0; $i < 2; ++$i) {
            foreach ($this->collection as $key => $stream) {
                if ($key === 'numbers' || $key === 'words') {
                    self::assertSame($this->initialData[$key], $stream->toArray());
                } else {
                    self::fail('Unknown key: '.$key);
                }
            }
        }
    }
    
    public function test_return_collected_data_as_stream_for_further_processing(): void
    {
        $data = $this->collection
            ->stream()
            ->mapWhen(
                Filters::keyIs('numbers'),
                static fn(array $numbers): int => \max($numbers)
            )
            ->mapWhen(
                Filters::keyIs('words'),
                static function (array $words): string {
                    return Stream::from($words)
                        ->reduce(static fn(string $longest, string $current) =>
                            \strlen($current) > \strlen($longest) ? $current : $longest
                        )
                        ->get();
                }
            )->toArrayAssoc();
        
        self::assertSame(['numbers' => 9, 'words' => 'quick'], $data);
    }
}