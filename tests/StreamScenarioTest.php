<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Collector\Collectors;
use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Comparator\Sorting\By;
use FiiSoft\Jackdaw\Comparator\Sorting\Key;
use FiiSoft\Jackdaw\Comparator\Sorting\Value;
use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Memo\Memo;
use FiiSoft\Jackdaw\Producer\Producers;
use FiiSoft\Jackdaw\Reducer\Reducers;
use FiiSoft\Jackdaw\Stream;
use PHPUnit\Framework\TestCase;

final class StreamScenarioTest extends TestCase
{
    public function test_scenario_01(): void
    {
        $row1 = ['id' => 2, 'name' => 'Kate'];
        $row2 = ['id' => 5, 'name' => 'Chris'];
        $row3 = ['id' => 8, 'name' => 'Joanna'];
        
        $stream = Stream::from([$row1, $row2, $row3])
            ->flat()
            ->filter('name', Check::KEY)
            ->sort();
        
        self::assertSame('Chris,Joanna,Kate', $stream->toString());
    }
    
    public function test_scenario_02(): void
    {
        $stream = Stream::from(['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'])
            ->chunk(3)
            ->map(Mappers::reverse())
            ->flat()
            ->map('strtoupper');
    
        self::assertSame(['C', 'B', 'A', 'F', 'E', 'D', 'H', 'G'], $stream->toArray());
    }
    
    public function test_scenario_03(): void
    {
        //given
        $counter1 = 0;
        $counter2 = 0;
        $buffer = new \ArrayObject();
        
        $stream = Stream::from([4, 7, 2, 'a', 8, null, 5, 3, 7])
            ->notNull()
            ->countIn($counter1)
            ->limit(6)
            ->onlyIntegers()
            ->countIn($counter2)
            ->map(static fn(int $x) => $x ** 2)
            ->omit(Filters::greaterThan(50))
            ->collectIn($buffer, true);
        
        //when
        $stream->run();
        
        //then
        self::assertSame(6, $counter1);
        self::assertSame(5, $counter2);
        self::assertSame([16, 49, 4, 25], $buffer->getArrayCopy());
    }
    
    public function test_scenario_04(): void
    {
        //given
        $buffer1 = [];
        $buffer2 = new \ArrayObject();
    
        $inputData = [4, 'c' => 7, 2, 'a', 'z' => 8, null, 5, '', 3, 7];
        
        $producer = Producers::getAdapter(static fn(): Stream => Stream::from($inputData)
            ->onlyIntegers()
            ->limit(5)
            ->skip(2)
        );
        
        //when
        foreach ($producer->stream() as $key => $value) {
            $buffer1[$key] = $value;
        }
        
        //or
        $producer->stream()->collectIn($buffer2, true)->run();
        
        //or
        $buffer3 = $producer->stream()->toArray();
        
        //then
        self::assertSame([1 => 2, 'z' => 8, 4 => 5], $buffer1);
        self::assertSame([2, 8, 5], $buffer2->getArrayCopy());
        self::assertSame([2, 8, 5], $buffer3);
    }
    
    public function test_scenario_05(): void
    {
        self::assertSame(10, Stream::from([1,0,2,9,3,8,4,7,5,6,1,0,2,9,3,8,4,8,5,7,6])->unique()->count()->get());
    }
    
    public function test_scenario_06(): void
    {
        $numbers = Stream::from(['6', '3', '2', null, '5', '0', null, '3', '2', '1', null])
            ->map('intval')
            ->filter(Filters::greaterThan(0))
            ->unique()
            ->sort()
            ->toArray();
            
        self::assertSame([1, 2, 3, 5, 6], $numbers);
    }
    
    public function test_scenario_07(): void
    {
        $otherStream = Stream::of(7,4,3,8,7,6,9,1,2,7,6);
    
        $result = Stream::of(['a', 'b'], $otherStream->unique(), 'z', ['c', 'd'])
            ->chunk(2, true)
            ->map(Mappers::concat('|'))
            ->toString(' ');
        
        self::assertSame('a|b 7|4 3|8 6|9 1|2 z|c d', $result);
    }
    
    public function test_scenario_08(): void
    {
        $words = Stream::from(['The quick brown fox jumps over the lazy dog'])
            ->flatMap(Mappers::split())
            ->map('strtolower')
            ->sort('strlen')
            ->toString();
        
        self::assertSame('the,fox,the,dog,over,lazy,quick,brown,jumps', $words);
    }
    
    public function test_scenario_09(): void
    {
        $inputData = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5, 'f' => 6, 'g' => 7, 'h' => 8];
        $producer = Producers::getAdapter(static fn(): Stream => Stream::from($inputData)->limit(7)->chunk(3, true));
    
        self::assertSame('[[1,2,3],[4,5,6],[7]]', $producer->stream()->limit(3)->toJson());
        self::assertSame('[[1,2,3],[4,5,6]]', $producer->stream()->limit(2)->toJson());
        
        self::assertSame(
            '[[1,2,3],[4,5,6]]',
            $producer->stream()->filter(static fn($ch): bool => \count($ch) === 3)->toJson()
        );
        self::assertSame('[[1,2,3],[4,5,6]]', $producer->stream()->filter(Filters::size()->eq(3))->toJson());
        
        self::assertSame('[[1,2,3],[4,5,6]]', $producer->stream()->limit(2)->filter(Filters::size()->eq(3))->toJson());
    }
    
    public function test_scenario_10(): void
    {
        $inputData = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5, 'f' => 6, 'g' => 7, 'h' => 8];
        $stream = Stream::from($inputData)
            ->limit(7) //without h=>8
            ->while(Filters::lessOrEqual(5)) //it should stop on f=>6
            ->chunk(3);
        
        self::assertSame('[{"a":1,"b":2,"c":3},{"d":4,"e":5}]', $stream->toJson());
    }
    
    public function test_scenario_11(): void
    {
        $inputData = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5, 'f' => 6, 'g' => 7, 'h' => 8];
        $stream = Stream::from($inputData)
            ->while(Filters::lessOrEqual(5)) //it should stop on f=>6
            ->sort(); //only five first should be passed to sort
        
        self::assertSame('1,2,3,4,5', $stream->toString());
    }
    
    public function test_scenario_12(): void
    {
        $inputData = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5, 'f' => 6, 'g' => 7, 'h' => 8];
        $stream = Stream::from($inputData)
            ->while(Filters::lessOrEqual(5)) //it should stop on f=>6
            ->sort() //only five first should be passed to sort
            ->chunk(3, true); //it should produce two chunks: 3 and 2 elements
    
        self::assertSame('[[1,2,3],[4,5]]', $stream->toJson());
    }
    
    public function test_scenario_13(): void
    {
        $stream = Stream::from(['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k'])
            ->chunk(5) //[[a,b,c,d,e],[f,g,h,i,j],[k]]
            ->flatMap(Mappers::reverse()) //[e,d,c,b,a,j,i,h,g,f,k]
            ->chunk(4) //[[e,d,c,b],[a,j,i,h],[g,f,k]]
            ->map(Mappers::concat()) //[edcb,ajih,gfk]
            ->sort(); //[ajih,edcb,gfk]
    
        $result = $stream->toJson();
        
        self::assertSame('["ajih","edcb","gfk"]', $result);
    }
    
    
    public function test_scenario_14(): void
    {
        $rowset = [
            ['id' => 2, 'name' => 'Kate', 'age' => 35],
            ['id' => 9, 'name' => 'Chris', 'age' => 26],
            ['id' => 6, 'name' => 'Joanna', 'age' => 35],
            ['id' => 5, 'name' => 'Chris', 'age' => 26],
            ['id' => 7, 'name' => 'Sue', 'age' => 17],
        ];
        
        $stream = Stream::from($rowset)
            ->filterBy('age', Filters::greaterOrEqual(25))
            ->limit(3)
            ->extract(['name', 'age'])
            ->sortBy('age asc', 'name desc');
            
        $actual = $stream->toArray();
        
        $expected = [
            ['name' => 'Chris', 'age' => 26],
            ['name' => 'Kate', 'age' => 35],
            ['name' => 'Joanna', 'age' => 35],
        ];
        
        self::assertSame($expected, $actual);
    }
    
    public function test_scenario_15(): void
    {
        $rowset = [
            ['id' => 7, 'name' => 'Sue', 'age' => 17],
            ['id' => 2, 'name' => 'Kate', 'age' => 35],
            ['id' => 9, 'name' => 'Chris', 'age' => 26],
            ['id' => 6, 'name' => 'Joanna', 'age' => 30],
            ['id' => 5, 'name' => 'Chris', 'age' => 26],
        ];
    
        $actual = Stream::from($rowset)
            ->filterBy('age', Filters::number()->ge(30))
            ->flat()
            ->limit(5)
            ->toArray();
        
        self::assertSame([2, 'Kate', 35, 6, 'Joanna'], $actual);
    }
    
    public function test_scenario_16(): void
    {
        $rowset = [
            ['id' => 7, 'name' => 'Sue', 'age' => 17],
            ['id' => 2, 'name' => 'Kate', 'age' => 35],
            ['id' => 9, 'name' => 'Chris', 'age' => 26],
            ['id' => 6, 'name' => 'Joanna', 'age' => 30],
            ['id' => 5, 'name' => 'Chris', 'age' => 26],
        ];
    
        $actual = Stream::from($rowset)
            ->filterBy('age', Filters::number()->ge(30))
            ->flat()
            ->limit(5)
            ->chunk(3)
            ->toArray();
        
        self::assertSame([
            ['id' => 2, 'name' => 'Kate', 'age' => 35],
            ['id' => 6, 'name' => 'Joanna']
        ], $actual);
    }
    
    public function test_scenario_17(): void
    {
        $stream = Stream::from($this->rowsetDataset())
            ->filterBy('name', 'Chris')
            ->filterBy('age', Filters::number()->ge(30))
            ->flat()
            ->chunk(3, true);
        
        self::assertSame([[4, 'Chris', 30], [11, 'Chris', 42], [13, 'Chris', 62]], $stream->toArray());
    }
    
    public function test_scenario_18(): void
    {
        $stream = Stream::from($this->rowsetDataset())
            ->filterBy('name', 'Chris')
            ->filterBy('age', Filters::number()->ge(30))
            ->flat()
            ->chunk(3, true)
            ->limit(2);
    
        self::assertSame([[4, 'Chris', 30], [11, 'Chris', 42]], $stream->toArray());
    }
    
    public function test_scenario_19(): void
    {
        $stream = Stream::from($this->rowsetDataset())
            ->filterBy('name', 'Chris')
            ->filterBy('age', Filters::number()->ge(30))
            ->flat()
            ->limit(8)
            ->chunk(3, true)
            ->limit(2);
    
        self::assertSame([[4, 'Chris', 30], [11, 'Chris', 42]], $stream->toArray());
    }
    
    public function test_scenario_20(): void
    {
        $stream = Stream::from($this->rowsetDataset())
            ->filterBy('name', 'Chris')
            ->filterBy('age', Filters::number()->ge(30))
            ->flat()
            ->limit(8)
            ->chunk(3, true);
    
        self::assertSame([[4, 'Chris', 30], [11, 'Chris', 42], [13, 'Chris']], $stream->toArray());
    }
    
    public function test_scenario_21(): void
    {
        $stream = Stream::from($this->rowsetDataset())
            ->filterBy('name', 'Chris')
            ->filterBy('age', Filters::number()->ge(30))
            ->flat()
            ->limit(8);
    
        self::assertSame([4, 'Chris', 30, 11, 'Chris', 42, 13, 'Chris'], $stream->toArray());
    }
    
    public function test_scenario_22(): void
    {
        $stream = Stream::from($this->rowsetDataset())
            ->filterBy('name', 'Chris')
            ->filterBy('age', Filters::number()->ge(30))
            ->flat()
            ->limit(8)
            ->omit('Chris');
    
        self::assertSame([4, 30, 11, 42, 13], $stream->toArray());
    }
    
    public function test_scenario_23(): void
    {
        $stream = Stream::from($this->rowsetDataset())
            ->filterBy('name', 'Chris')
            ->filterBy('age', Filters::number()->ge(30))
            ->flat()
            ->limit(8)
            ->without(['Chris'])
            ->skip(2)
            ->limit(2);
    
        self::assertSame([11, 42], $stream->toArray());
    }
    
    public function test_scenario_24(): void
    {
        $stream = Stream::from($this->rowsetDataset())
            ->feed(Stream::empty()->extract('name')->unique()->sort()->limit(5)
                ->collectIn($names = Collectors::values())
            )
            ->feed(Stream::empty()->extract('id')->collectIn($allIds = Collectors::default()))
            ->filterBy('name', 'Chris')
            ->filterBy('age', Filters::number()->ge(30))
            ->feed(Stream::empty()->extract('id')->collectIn($ids = Collectors::values()))
            ->flat()
            ->limit(8)
            ->without(['Chris'])
            ->skip(2)
            ->limit(2);
    
        self::assertSame([11, 42], $stream->toArray());
        self::assertSame([4, 11], $ids->toArray());
        self::assertSame(['Chris', 'Joanna', 'Joe', 'Kate', 'Mike'], $names->toArray());
        self::assertSame(11, $allIds->stream()->reduce('max')->get());
    }
    
    private function rowsetDataset(): array
    {
        return [
            ['id' => 7, 'name' => 'Sue', 'age' => 17],
            ['id' => 2, 'name' => 'Kate', 'age' => 35],
            ['id' => 9, 'name' => 'Chris', 'age' => 26],
            ['id' => 6, 'name' => 'Joanna', 'age' => 30],
            ['id' => 5, 'name' => 'Chris', 'age' => 26],
            ['id' => 1, 'name' => 'Chris', 'age' => 22],
            ['id' => 10, 'name' => 'Phil', 'age' => 45],
            ['id' => 4, 'name' => 'Chris', 'age' => 30],
            ['id' => 5, 'name' => 'Mike', 'age' => 55],
            ['id' => 2, 'name' => 'Joe', 'age' => 18],
            ['id' => 11, 'name' => 'Chris', 'age' => 42],
            ['id' => 8, 'name' => 'Sam', 'age' => 35],
            ['id' => 13, 'name' => 'Chris', 'age' => 62],
            ['id' => 3, 'name' => 'Doris', 'age' => 38],
            ['id' => 12, 'name' => 'Nathaniel', 'age' => 46],
        ];
    }
    
    public function test_scenario_25(): void
    {
        $count = Stream::from([6, 'a', 3, 'n', 2, 'g'])->onlyStrings()->count();
        self::assertSame(3, $count->get());
    }
    
    public function test_scenario_26(): void
    {
        $counter = Stream::empty()->lessOrEqual(3)->count();
        $source = Stream::from([1, 2, 3, 4, 5])->greaterOrEqual(3)->feed($counter);
        
        self::assertSame(3, $source->count()->get());
        self::assertSame(1, $counter->get());
    }
    
    public function test_scenario_27(): void
    {
        $consumer = Stream::empty()->onlyIntegers()->greaterThan(0)->reduce(Reducers::sum());
        Stream::from(['v', -3, 'a', 5, 'f', 2, 'u', 0, 'b', -1])->limit(7)->feed($consumer);
    
        self::assertSame(7, $consumer->get());
    }
    
    public function test_scenario_28(): void
    {
        $data = [
            ['val' => 3],
            ['val' => 'a'],
            ['val' => 5],
            ['val' => null],
            ['val' => '2'],
            ['val' => 0],
            ['val' => 'b'],
            ['val' => '3'],
            ['val' => 1],
        ];
        
        $result = Stream::from($data)
            ->extract('val')
            ->onlyNumeric()
            ->castToInt()
            ->greaterThan(0)
            ->unique()
            ->sort()
            ->toArray();
            
        self::assertSame([1, 2, 3, 5], $result);
    }
    
    public function test_scenario_29(): void
    {
        $data = [
            ['val' => 3],
            ['val' => 'a'],
            ['val' => 5],
            ['val' => null],
            ['val' => '2'],
            ['val' => 0],
            ['val' => 'b'],
            ['val' => '3'],
            ['val' => 1],
        ];
        
        $result = Stream::from($data)
            ->fold([], static function (array $result, array $data): array {
                if (\is_numeric($data['val'])) {
                    $value = (int) $data['val'];
                    if ($value > 0) {
                        $result[$value] = $value;
                    }
                }
                return $result;
            })
            ->get();
            
        \sort($result, \SORT_REGULAR);
        
        self::assertSame([1, 2, 3, 5], $result);
    }
    
    public function test_scenario_30(): void
    {
        self::assertSame([7, 8, 9], Stream::from([5, 3, 8, 1, 3, 7, 6, 9, 0, 2, 4])->sort()->tail(3)->toArray());
    }
    
    public function test_scenario_31(): void
    {
        self::assertSame([6, 5], Stream::from([0, 6, 2, 8, 1, 3, 7, 9, 2, 5, 4])->worst(5)->tail(2)->toArray());
    }
    
    public function test_scenario_32(): void
    {
        $rowset = [
            ['id' => 7, 'name' => 'Sue', 'age' => 17, 'sex' => 'female'],
            ['id' => 2, 'name' => 'Kate', 'age' => 35, 'sex' => 'female'],
            ['id' => 9, 'name' => 'Chris', 'age' => 26, 'sex' => 'male'],
            ['id' => 6, 'name' => 'Joanna', 'age' => 30, 'sex' => 'female'],
            ['id' => 5, 'name' => 'Chris', 'age' => 26, 'sex' => 'male'],
        ];
    
        $actual = Stream::from($rowset)
            ->filterBy('age', Filters::number()->ge(20))
            ->filterBy('name', Filters::onlyIn(['Sue', 'Kate', 'Joanna']))
            ->filterBy('sex', 'female')
            ->mapField('name', 'strrev')
            ->mapField('name', 'mb_strtolower')
            ->mapField('age', 0)
            ->remove('sex')
            ->reverse()
            ->limit(5)
            ->sortBy('id')
            ->reverse()
            ->shuffle()
            ->sortBy('id desc')
            ->call(Consumers::counter())
            ->call(Consumers::counter())
            ->toArray();
        
        $expected = [
            ['id' => 6, 'name' => 'annaoj', 'age' => 0],
            ['id' => 2, 'name' => 'etak', 'age' => 0],
        ];
        
        self::assertSame($expected, $actual);
    }
    
    public function test_scenario_33(): void
    {
        $data = ['b', 'a', 'c'];
        $expected = ['c', 'b', 'a'];
    
        self::assertSame($expected, Stream::from($data)->sort()->limit(3)->reverse()->toArray());
        self::assertSame($expected, Stream::from($data)->best(3)->reverse()->toArray());
        self::assertSame($expected, Stream::from($data)->worst(3)->toArray());
    }
    
    public function test_scenario_34(): void
    {
        $rowset = [
            ['id' => 7, 'name' => 'Sue', 'age' => 17, 'sex' => 'female'],
            ['id' => 2, 'name' => 'Kate', 'age' => 35, 'sex' => 'female'],
            ['id' => 9, 'name' => 'Chris', 'age' => 26, 'sex' => 'male'],
            ['id' => 6, 'name' => 'Joanna', 'age' => 30, 'sex' => 'female'],
            ['id' => 5, 'name' => 'Chris', 'age' => 26, 'sex' => 'male'],
        ];
        
        $result = Stream::from($rowset)
            ->filterBy('age', Filters::greaterOrEqual(50))
            ->tail(2)
            ->extract('name')
            ->toArray();
        
        self::assertEmpty($result);
    }
    
    public function test_scenario_35(): void
    {
        //given
        $counter = Consumers::counter();
        
        $stream = Stream::from($this->rowsetDataset());
        $stream->filterBy('age', Filters::greaterOrEqual(40));
        $stream->filterBy('name', Filters::onlyIn(['Chris', 'Mike']));
        $stream->call($counter);
        $stream->extract('age');
        
        $result = $stream->reduce(Reducers::average(0));
        
        //then
        self::assertSame(3, $counter->count());
        self::assertSame(53.0, $result->get());
    }
    
    public function test_scenario_36(): void
    {
        $result = Stream::from(['The quick brown fox', 'jumps over the lazy dog.'])
            ->map(Mappers::tokenize(' .'))
            ->flat()
            ->toString(' ');
        
        self::assertSame('The quick brown fox jumps over the lazy dog', $result);
    }
    
    public function test_scenario_37(): void
    {
        $result = Stream::from(['The quick brown fox', 'jumps over the lazy dog.'])
            ->map('mb_strtolower')
            ->map(Mappers::tokenize(' .'))
            ->map(Reducers::longest())
            ->sort('mb_strlen')
            ->toString(' ');
        
        self::assertSame('quick jumps', $result);
    }
    
    public function test_scenario_38(): void
    {
        $rowset = [
            ['id' => 7, 'name' => 'Sue', 'age' => 17, 'sex' => 'female'],
            ['id' => 2, 'name' => 'Kate', 'age' => 35, 'sex' => 'female'],
            ['id' => 9, 'name' => 'Chris', 'age' => 26, 'sex' => 'male'],
            ['id' => 6, 'name' => 'Joanna', 'age' => 30, 'sex' => 'female'],
            ['id' => 5, 'name' => 'Chris', 'age' => 26, 'sex' => 'male'],
        ];
        
        $womenAge = Stream::from($rowset)
            ->filterBy('sex', 'female')
            ->extract('age')
            ->collect();
        
        $average = Stream::from($womenAge)->reduce(Reducers::average());
        self::assertSame((17 + 35 + 30) / 3, $average->get());
        
        $menAge = Stream::from($rowset)
            ->filterBy('sex', 'male')
            ->extract('age')
            ->collect();
        
        self::assertSame((26 + 26) / 2, Stream::of($menAge)->reduce(Reducers::average())->get());
        
        $totalAge = Stream::empty()->join($womenAge, $menAge)->reduce(Reducers::sum());
        self::assertSame(17 + 35 + 26 + 30 + 26, $totalAge->get());
    }
    
    public function test_scenario_39(): void
    {
        $buffer = Collectors::default();
        
        Stream::from(['foo', 123, 'bar', 456])
            ->feed(
                Stream::empty()
                    ->onlyStrings()
                    ->collectIn($buffer)
            )
            ->onlyIntegers()
            ->collectIn($buffer)
            ->run();
        
        self::assertSame(['foo', 123, 'bar', 456], $buffer->toArray());
    }
    
    public function test_scenario_40(): void
    {
        $buffer = Collectors::default();
    
        Stream::from(['foo', 123, 'bar', 456])
            ->feed(
                Stream::from(['z', 5, 'd'])
                    ->join(['a', 7, 'c'])
                    ->onlyStrings()
                    ->collectIn($buffer, true)
            )
            ->onlyIntegers()
            ->collectIn($buffer)
            ->run();
    
        self::assertSame(['foo', 123, 'bar', 456, 'z', 'd', 'a', 'c'], $buffer->toArray());
    }
    
    public function test_scenario_41(): void
    {
        $rowset = [
            ['id' => 7, 'name' => 'Sue', 'age' => 17, 'sex' => 'female'],
            ['id' => 2, 'name' => 'Kate', 'age' => 35, 'sex' => 'female'],
            ['id' => 9, 'name' => 'Chris', 'age' => 26, 'sex' => 'male'],
            ['id' => 6, 'name' => 'Joanna', 'age' => 30, 'sex' => 'female'],
            ['id' => 5, 'name' => 'Chris', 'age' => 26, 'sex' => 'male'],
        ];
        
        $adultsWomen = Stream::from($rowset)
            ->filterBy('age', Filters::greaterOrEqual(18))
            ->filterBy('sex', 'female')
            ->collect();
        
        self::assertTrue($adultsWomen->found());
        self::assertCount(2, $adultsWomen);
        
        self::assertSame(
            [
                1 => ['id' => 2, 'name' => 'Kate', 'age' => 35, 'sex' => 'female'],
                3 => ['id' => 6, 'name' => 'Joanna', 'age' => 30, 'sex' => 'female'],
            ],
            $adultsWomen->toArrayAssoc()
        );
        
        $names = $adultsWomen->stream()->extract('name')->collect();
        self::assertSame('["Kate","Joanna"]', $names->toJson());
    }
    
    public function test_scenario_42(): void
    {
        $rowset = [
            ['id' => 2, 'name' => 'Sue', 'age' => 35],
            ['id' => 9, 'name' => 'Chris', 'age' => 26],
            ['id' => 6, 'name' => 'Joanna', 'age' => 35],
            ['id' => 5, 'name' => 'Chris', 'age' => 26],
            ['id' => 7, 'name' => 'Sue', 'age' => 17],
        ];
        
        $avgAgeByName = Stream::from($rowset)
            ->sortBy('name')
            ->chunkBy('name')
            ->map(static fn(array $rows): array => \array_column($rows, 'age'))
            ->map(Reducers::average())
            ->toArrayAssoc();
        
        self::assertSame([
            'Chris' => (26 + 26) / 2,
            'Joanna' => 35,
            'Sue' => (35 + 17) / 2,
        ], $avgAgeByName);
    }
    
    public function test_scenario_43(): void
    {
        $rowset = [
            ['id' => 2, 'name' => 'Sue', 'age' => 35],
            ['id' => 9, 'name' => 'Chris', 'age' => 26],
            ['id' => 6, 'name' => 'Joanna', 'age' => 35],
            ['id' => 5, 'name' => 'Chris', 'age' => 26],
            ['id' => 7, 'name' => 'Sue', 'age' => 17],
        ];
    
        $avgAgeByName = Stream::from($rowset)
            ->mapKV(static fn(array $row): array => [$row['name'] => $row['age']])
            ->groupBy(Discriminators::byKey(), true)
            ->stream()
            ->map(Reducers::average())
            ->sort(By::key())
            ->toArrayAssoc();
    
        self::assertSame([
            'Chris' => (26 + 26) / 2,
            'Joanna' => 35,
            'Sue' => (35 + 17) / 2,
        ], $avgAgeByName);
    }
    
    public function test_scenario_44(): void
    {
        $rowset = [
            ['id' => 2, 'name' => 'Sue', 'age' => 35],
            ['id' => 9, 'name' => 'Chris', 'age' => 26],
            ['id' => 6, 'name' => 'Joanna', 'age' => 35],
            ['id' => 5, 'name' => 'Chris', 'age' => 26],
            ['id' => 7, 'name' => 'Sue', 'age' => 17],
        ];
    
        $countByName = Stream::from($rowset)
            ->extract('name')
            ->groupBy(Discriminators::byValue())
            ->stream()
            ->map(Reducers::count())
            ->toArrayAssoc();
    
        self::assertSame([
            'Sue' => 2,
            'Chris' => 2,
            'Joanna' => 1,
        ], $countByName);
    }
    
    public function test_scenario_45(): void
    {
        $rowset = [
            ['id' => 2, 'name' => 'Sue', 'age' => 35],
            ['id' => 9, 'name' => 'Chris', 'age' => 26],
            ['id' => 6, 'name' => 'Joanna', 'age' => 35],
            ['id' => 5, 'name' => 'Chris', 'age' => 26],
            ['id' => 7, 'name' => 'Sue', 'age' => 17],
        ];
    
        $countByName = [];
        
        Stream::from($rowset)
            ->mapKey(Mappers::fieldValue('name'))
            ->call(static function ($_, string $name) use (&$countByName): void {
                $countByName[$name] = ($countByName[$name] ?? 0) + 1;
            })
            ->run();
    
        self::assertSame([
            'Sue' => 2,
            'Chris' => 2,
            'Joanna' => 1,
        ], $countByName);
    }
    
    public function test_scenario_46(): void
    {
        $rowset = [
            ['id' => 2, 'name' => 'Sue', 'age' => 35],
            ['id' => 9, 'name' => 'Chris', 'age' => 26],
            ['id' => 6, 'name' => 'Joanna', 'age' => 35],
            ['id' => 5, 'name' => 'Chris', 'age' => 26],
            ['id' => 7, 'name' => 'Sue', 'age' => 17],
        ];
    
        $countByName = [];
        
        $stream = Stream::from($rowset)
            ->extract('name')
            ->mapKV(static function (string $name) use (&$countByName): array {
                $countByName[$name] = ($countByName[$name] ?? 0) + 1;
                return [$name => $countByName[$name]];
            });
    
        self::assertSame([
            'Sue' => 2,
            'Chris' => 2,
            'Joanna' => 1,
        ], $stream->toArrayAssoc());
    }
    
    public function test_scenario_47(): void
    {
        $queue = Producers::queue([5,'b',4]);
        
        $result = Stream::from($queue)
            ->filter(static fn($v): bool => \is_int($v) || $v === \strtolower($v))
            ->mapWhen('is_int', static fn(int $v): int => $v - 2, 'strtoupper')
            ->filter(static fn($v): bool => \is_string($v) || $v > 0)
            ->call($queue)
            ->toArray();
        
        self::assertSame([3, 'B', 2, 1], $result);
    }
    
    public function test_scenario_48(): void
    {
        $rowset = [
            ['id' => 2, 'name' => 'Sue', 'age' => 35],
            ['id' => 9, 'name' => 'Chris', 'age' => 26],
            ['id' => 6, 'name' => 'Joanna', 'age' => 35],
            ['id' => 5, 'name' => 'Chris', 'age' => 26],
            ['id' => 7, 'name' => 'Sue', 'age' => 17],
        ];
        
        $result = Stream::from($rowset)
            ->fork(
                Discriminators::byField('name'),
                Stream::empty()->extract('age')->reduce(Reducers::average())
            )
            ->toArrayAssoc();
    
        self::assertSame([
            'Sue' => (35 + 17 ) / 2,
            'Chris' => (26 + 26) / 2,
            'Joanna' => 35,
        ], $result);
    }
    
    public function test_scenario_49(): void
    {
        $rowset = [
            ['id' => 2, 'name' => 'Sue', 'age' => 35],
            ['id' => 9, 'name' => 'Chris', 'age' => 26],
            ['id' => 6, 'name' => 'Joanna', 'age' => 35],
            ['id' => 5, 'name' => 'Chris', 'age' => 26],
            ['id' => 7, 'name' => 'Sue', 'age' => 17],
        ];
        
        $result = Stream::from($rowset)
            ->fork(
                Discriminators::byField('name'),
                Stream::empty()->extract('age')->reindex()->collect()
            )
            ->toArrayAssoc();
    
        self::assertSame([
            'Sue' => [35, 17],
            'Chris' => [26, 26],
            'Joanna' => [35],
        ], $result);
    }
    
    public function test_scenario_50(): void
    {
        $rowset = [
            ['id' => 2, 'name' => 'Sue', 'age' => 35],
            ['id' => 9, 'name' => 'Chris', 'age' => 26],
            ['id' => 6, 'name' => 'Joanna', 'age' => 35],
            ['id' => 5, 'name' => 'Chris', 'age' => 42],
            ['id' => 7, 'name' => 'Sue', 'age' => 17],
        ];
        
        $result = Stream::from($rowset)
            ->fork(
                Discriminators::byField('name'),
                Stream::empty()->extract('age')->sort()->limit(1)->collect(true)
            )
            ->map(Mappers::fieldValue(0))
            ->toArrayAssoc();
    
        self::assertSame([
            'Sue' => 17,
            'Chris' => 26,
            'Joanna' => 35,
        ], $result);
    }
    
    public function test_scenario_51(): void
    {
        $rowset = [
            ['id' => 2, 'name' => 'Sue', 'age' => 35],
            ['id' => 9, 'name' => 'Chris', 'age' => 26],
            ['id' => 6, 'name' => 'Joanna', 'age' => 35],
            ['id' => 5, 'name' => 'Chris', 'age' => 42],
            ['id' => 7, 'name' => 'Sue', 'age' => 17],
        ];
        
        $result = Stream::from($rowset)
            ->fork(
                Discriminators::byField('name'),
                Stream::empty()->extract('age')->collect()
            )
            ->map(Mappers::concat('|'))
            ->toArrayAssoc();
    
        self::assertSame([
            'Sue' => '35|17',
            'Chris' => '26|42',
            'Joanna' => '35',
        ], $result);
    }
    
    public function test_scenario_52(): void
    {
        $rowset = [
            ['id' => 2, 'name' => 'Sue', 'age' => 35],
            ['id' => 9, 'name' => 'Chris', 'age' => 12],
            ['id' => 6, 'name' => 'Joanna', 'age' => 17],
            ['id' => 5, 'name' => 'Chris', 'age' => 42],
            ['id' => 7, 'name' => 'Sue', 'age' => 16],
        ];
        
        $result = Stream::from($rowset)
            ->fork(
                Discriminators::byField('name'),
                Stream::empty()->extract('age')->find(Filters::greaterOrEqual(18))
            )
            ->notNull()
            ->toArrayAssoc();
    
        self::assertSame([
            'Sue' => 35,
            'Chris' => 42,
        ], $result);
    }
    
    public function test_scenario_53(): void
    {
        $rowset = [
            ['id' => 2, 'name' => 'Sue', 'age' => 35],
            ['id' => 9, 'name' => 'Chris', 'age' => 12],
            ['id' => 6, 'name' => 'Joanna', 'age' => 17],
            ['id' => 5, 'name' => 'Chris', 'age' => 42],
            ['id' => 7, 'name' => 'Sue', 'age' => 16],
        ];
        
        $result = Stream::from($rowset)
            ->fork(
                Discriminators::byField('name'),
                Stream::empty()->filterBy('age', Filters::greaterOrEqual(18))->isNotEmpty()
            )
            ->notEmpty()
            ->flip()
            ->toArray();
    
        self::assertSame([
            'Sue',
            'Chris',
        ], $result);
    }
    
    public function test_scenario_54(): void
    {
        $rowset = [
            ['id' => 7, 'name' => 'Sue', 'age' => 16],
            ['id' => 9, 'name' => 'Chris', 'age' => 12],
            ['id' => 6, 'name' => 'Joanna', 'age' => 17],
            ['id' => 5, 'name' => 'Chris', 'age' => 42],
            ['id' => 2, 'name' => 'Sue', 'age' => 35],
        ];
        
        $result = Stream::from($rowset)
            ->fork(
                Discriminators::byField('name'),
                Stream::empty()->extract('age')->has(Filters::greaterOrEqual(18))
            )
            ->toArrayAssoc();
    
        self::assertSame([
            'Sue' => true,
            'Chris' => true,
            'Joanna' => false,
        ], $result);
    }
    
    public function test_scenario_55(): void
    {
        $data = [
            1,
            4,  //series
            3,
            2,6,    //series
            5, 7,
            8,6,2,  //1st chunk
            9,
            4,2,4,6,8,  //series
            3,7,
            2,2,8,6,4,6,4,8,    //series
            3,3,1,
            4,6,2,  //2nd chunk
            1,
            2,2,4,6,    //3rd chunk
        ];
        
        $stream = Stream::from($data)
            ->accumulate(Filters::number()->isEven(), true)
            ->append('count', Reducers::count())
            ->chunk(3)
            ->map(static fn(array $chunk): array => Stream::from($chunk)->sortBy('count desc')->first()->get())
            ->remove('count');
            
        $expected = [
            [8, 6, 2],
            [2,2,8,6,4,6,4,8],
            [2,2,4,6],
        ];
        
        self::assertSame($expected, $stream->toArray());
    }
    
    public function test_scenario_56(): void
    {
        $data = [
            1,
            4,          //even
            3,
            2,6,        //even
            5,7,
            8,6,2,      //even
            9,
            4,2,4,6,8,  //even
            3,7,
            2,2,8,6,4,6,4,8,    //even
            3,3,1,
            4,6,2,      //even
            1,
            2,2,4,6,    //even
        ];
        
        $stream = Stream::from($data)
            ->accumulate(Filters::number()->isEven(), true)
            ->rsort(By::size())
            ->first();
        
        self::assertSame([2,2,8,6,4,6,4,8], $stream->get());
    }
    
    public function test_scenario_57(): void
    {
        $rowset = [
            ['id' => 2, 'name' => 'Sue', 'age' => 22],
            ['id' => 9, 'name' => 'Chris', 'age' => 17],
            ['id' => 6, 'name' => 'Joanna', 'age' => 15],
            ['id' => 5, 'name' => 'Chris', 'age' => 24],
            ['id' => 7, 'name' => 'Sue', 'age' => 18],
        ];
        
        $underage = Stream::from($rowset)
            ->reindexBy('id', true)
            ->accumulate(Filters::filterBy('age', Filters::lessOrEqual(18)))
            ->toArray();
        
        $expected = [
            [
                9 => ['name' => 'Chris', 'age' => 17],
                6 => ['name' => 'Joanna', 'age' => 15],
            ], [
                7 => ['name' => 'Sue', 'age' => 18],
            ],
        ];
        
        self::assertSame($expected, $underage);
    }
    
    public function test_scenario_59(): void
    {
        $data = [
            'ala oros j lul ee adstukuo guo',
            'kik jej dedafs byb',
            'rahgoo foo ly dabib cyc',
            'roo nooajej afs aa',
        ];

        $prototype = Stream::empty()->tokenize()->reduce(Reducers::longest());

        $stream = Stream::from($data)
            ->fork('\mb_strlen', $prototype)
            ->collect();

        $result = $stream->get();

        self::assertSame([
            30 => 'adstukuo',
            18 => 'nooajej',
            23 => 'rahgoo',
        ], $result);
    }
    
    public function test_scenario_60(): void
    {
        $data = [
            'ala oros j lul ee adstukuo guo',
            'roo nooajej afs aa',
            'rahgoo foo ly dabib cyc',
            'kik jej dedafs byb',
        ];
        
        $collection = Stream::from($data)->groupBy('\mb_strlen');
        
        $result = [];
        foreach ($collection as $length => $lines) {
            $result[$length] = Stream::from($lines)
                ->tokenize()
                ->reduce(Reducers::longest())
                ->get();
        }
        
        self::assertSame([
            30 => 'adstukuo',
            18 => 'nooajej',
            23 => 'rahgoo',
        ], $result);
    }
    
    public function test_scenario_61(): void
    {
        $data = [
            'ala oros j lul ee adstukuo guo',
            'kik jej dedafs byb',
            'rahgoo foo ly dabib cyc',
            'roo nooajej afs aa',
        ];
        
        $result = [];
        
        Stream::from($data)
            ->groupBy('\mb_strlen')
            ->stream()
            ->forEach(static function (array $lines, int $length) use (&$result): void {
                $result[$length] = Stream::from($lines)
                    ->tokenize()
                    ->reduce(Reducers::longest())
                    ->get();
            });
        
        self::assertSame([
            30 => 'adstukuo',
            18 => 'nooajej',
            23 => 'rahgoo',
        ], $result);
    }
    
    public function test_scenario_62(): void
    {
        $data = [
            'ala oros j lul ee adstukuo guo',
            'kik jej dedafs byb',
            'rahgoo foo ly dabib cyc',
            'roo nooajej afs aa',
        ];

        $stream = Stream::empty()->tokenize()->reduce(Reducers::longest());

        Stream::from($data)->feed($stream);

        self::assertSame('adstukuo', $stream->get());
    }
    
    public function test_scenario_63(): void
    {
        self::assertSame(
            $this->multiSortExpectedResult(),
            Stream::from($this->multiSortRowset())
                ->sortBy('sex', 'name', 'age')
                ->toArray()
        );
    }
    
    public function test_scenario_64(): void
    {
        //consecutive sort operations are independent!
        self::assertNotSame(
            $this->multiSortExpectedResult(),
            Stream::from($this->multiSortRowset())
                ->sort(static fn(array $a, array $b): int => $a['sex'] <=> $b['sex'])
                ->sort(static fn(array $a, array $b): int => $a['name'] <=> $b['name'])
                ->sort(static fn(array $a, array $b): int => $a['age'] <=> $b['age'])
                ->toArray()
        );
    }
    
    public function test_scenario_65(): void
    {
        self::assertSame(
            $this->multiSortExpectedResult(),
            Stream::from($this->multiSortRowset())
                ->sort(Comparators::multi(
                    static fn(array $a, array $b): int => $a['sex'] <=> $b['sex'],
                    static fn(array $a, array $b): int => $a['name'] <=> $b['name'],
                    static fn(array $a, array $b): int => $a['age'] <=> $b['age'],
                ))
                ->toArray()
        );
    }
    
    private function multiSortRowset(): array
    {
        return [
            ['id' => 7, 'name' => 'Sue', 'age' => 17, 'sex' => 'female'],
            ['id' => 2, 'name' => 'Kate', 'age' => 35, 'sex' => 'female'],
            ['id' => 9, 'name' => 'Chris', 'age' => 26, 'sex' => 'male'],
            ['id' => 6, 'name' => 'Joanna', 'age' => 30, 'sex' => 'female'],
            ['id' => 5, 'name' => 'Chris', 'age' => 20, 'sex' => 'male'],
            ['id' => 1, 'name' => 'Sue', 'age' => 20, 'sex' => 'female'],
            ['id' => 4, 'name' => 'Elie', 'age' => 25, 'sex' => 'female'],
            ['id' => 10, 'name' => 'Tom', 'age' => 35, 'sex' => 'male'],
            ['id' => 3, 'name' => 'Joanna', 'age' => 22, 'sex' => 'female'],
            ['id' => 8, 'name' => 'John', 'age' => 24, 'sex' => 'male'],
        ];
    }
    
    private function multiSortExpectedResult(): array
    {
        return [
            ['id' => 4, 'name' => 'Elie', 'age' => 25, 'sex' => 'female'],
            ['id' => 3, 'name' => 'Joanna', 'age' => 22, 'sex' => 'female'],
            ['id' => 6, 'name' => 'Joanna', 'age' => 30, 'sex' => 'female'],
            ['id' => 2, 'name' => 'Kate', 'age' => 35, 'sex' => 'female'],
            ['id' => 7, 'name' => 'Sue', 'age' => 17, 'sex' => 'female'],
            ['id' => 1, 'name' => 'Sue', 'age' => 20, 'sex' => 'female'],
            ['id' => 5, 'name' => 'Chris', 'age' => 20, 'sex' => 'male'],
            ['id' => 9, 'name' => 'Chris', 'age' => 26, 'sex' => 'male'],
            ['id' => 8, 'name' => 'John', 'age' => 24, 'sex' => 'male'],
            ['id' => 10, 'name' => 'Tom', 'age' => 35, 'sex' => 'male'],
        ];
    }
    
    public function test_scenario_66(): void
    {
        $result = Stream::from([4, 3, 2, 4, 5, 7, 6, 7, 8, 6, 5, 3, 3, 5, 2, 4, 1, 3, 5, 8, 9])
            ->accumulateUptrends()
            ->flat(1)
            ->toArray();
        
        self::assertSame([2, 4, 5, 7, 6, 7, 8, 3, 5, 2, 4, 1, 3, 5, 8, 9], $result);
    }
    
    public function test_scenario_67(): void
    {
        $result = Stream::from([4, 3, 2, 4, 5, 7, 6, 7, 8, 6, 5, 3, 3, 5, 2, 4, 1, 3, 5, 8, 9])
            ->accumulateUptrends()
            ->rsort(By::size())
            ->first();
        
        self::assertSame([16 => 1, 3, 5, 8, 9], $result->get());
        
        $result->transform('array_values');
        self::assertSame([1, 3, 5, 8, 9], $result->get());
    }
    
    public function test_scenario_68(): void
    {
        $result = Stream::from([
                4, 3, 2,
                4, 5, 7,
                6, 7, 8,
                6, 5, 3,
                3, 5, 2,
                4, 1, 3,
                5, 8, 9,
            ])
            ->fork(
                Discriminators::alternately(['first', 'second', 'third']),
                Stream::empty()->accumulateUptrends(true)->rsort('count')->first()
            );
        
        self::assertSame([
            'first' => [3, 4, 5],
            'second' => [3, 5, 7],
            'third' => [2, 7, 8],
        ], $result->toArrayAssoc());
    }
    
    public function test_scenario_69(): void
    {
        $data = [2, 3, 4, 3, 2, 4, 5, 7, 6, 7, 8, 6, 5, 3, 3, 5, 2, 4, 1, 3, 5, 5, 3, 2, 4, 4, 5, 8, 9];
        
        $result = Stream::from($data)
            ->onlyMinima()
            ->fork(Discriminators::byValue(), Reducers::count())
            ->rsort()
            ->first();
        
        self::assertSame([2 => 4], $result->toArrayAssoc());
    }
    
    public function test_scenario_70(): void
    {
        $data = [6, 4, 3, 2, 4, 5, 7, 6, 7, 8, 6, 5, 3, 3, 5, 2, 4, 1, 3, 5, 5, 3, 2, 4, 4, 5, 8, 9];
        
        $result = Stream::from($data)
            ->onlyMaxima(false)
            ->greaterOrEqual(5)
            ->toArray();
        
        self::assertSame([7, 8, 5], $result);
    }
    
    public function test_scenario_71(): void
    {
        $data = [
            6,
            4, 3, 2, 4,
            5, 7, 6, 7, 8, 6, 5,
            3, 3,
            5,
            2, 4, 1, 3,
            5, 5,
            3, 2, 4, 4,
            5, 8, 9
        ];
        
        $result = Stream::from($data)
            ->greaterOrEqual(5)
            ->onlyMaxima(false)
            ->toArray();
        
        self::assertSame([7, 8], $result);
    }
    
    public function test_scenario_72(): void
    {
        $data = [
            6,
            4, 3, 2, 4,
            5, 7, 6, 7, 8, 6, 5,
            3, 3,
            5,
            2, 4, 1, 3,
            5, 5,
            3, 2, 4, 4,
            5, 8, 9
        ];
        
        $result = Stream::from($data)
            ->greaterOrEqual(5)
            ->onlyMaxima()
            ->toArray();
        
        self::assertSame([6, 7, 8, 9], $result);
    }
    
    public function test_scenario_73(): void
    {
        $data = [6, 4, 3, 2, 4, 5, 7, 6, 7, 8, 6, 5, 3, 3, 5, 2, 4, 1, 3, 5, 5, 3, 2, 4, 4, 5, 8, 9];
        
        $result = Stream::from($data)
            ->onlyExtrema()
            ->toArray();
        
        self::assertSame([6, 2, 7, 6, 8, 5, 2, 4, 1, 2, 9], $result);
    }
    
    public function test_scenario_74(): void
    {
        $data = [6, 4, 3, 2, 4, 5, 7, 6, 7, 8, 6, 5, 3, 3, 5, 2, 4, 1, 3, 5, 5, 3, 2, 4, 4, 5, 8, 9];
        
        $result = Stream::from($data)
            ->fork(
                Discriminators::evenOdd(),
                Stream::empty()->onlyExtrema()->collect(true)
            )
            ->toArrayAssoc();
        
        self::assertSame([
            'even' => [6, 2, 8, 2, 4, 2, 8],
            'odd' => [3, 5, 1, 3, 9],
        ], $result);
    }
    
    public function test_scenario_75(): void
    {
        $data = [6, 4, 3, 2, 4, 5, 7, 6, 7, 8, 6, 5, 3, 3, 5, 2, 4, 1, 3, 5, 5, 3, 2, 4, 4, 5, 8, 9];
        
        $result = Stream::from($data)
            ->fork(
                Discriminators::evenOdd(),
                Stream::empty()
                    ->onlyExtrema()
                    ->gather()
                    ->append('min', Reducers::min())
                    ->append('max', Reducers::max())
                    ->extract(['min', 'max'])
                    ->first()
            )
            ->toArrayAssoc();
        
        self::assertSame([
            'even' => ['min' => 2, 'max' => 8],
            'odd' => ['min' => 1, 'max' => 9],
        ], $result);
    }
    
    public function test_scenario_76(): void
    {
        $data = [6, 4, 3, 2, 4, 5, 7, 6, 7, 8, 6, 5, 3, 3, 5, 2, 4, 1, 3, 5, 5, 3, 2, 4, 4, 5, 8, 9];
        
        $result = Stream::from($data)
            ->fork(
                Discriminators::evenOdd(),
                Stream::empty()
                    ->onlyExtrema()
                    ->gather()
                    ->map(static fn(array $values, $key): array => [
                        'min' => Mappers::getAdapter(Reducers::min())->map($values, $key),
                        'max' => Mappers::getAdapter(Reducers::max())->map($values, $key)
                    ])
                    ->first()
            )
            ->toArrayAssoc();
        
        self::assertSame([
            'even' => ['min' => 2, 'max' => 8],
            'odd' => ['min' => 1, 'max' => 9],
        ], $result);
    }
    
    public function test_scenario_77(): void
    {
        $data = [6, 4, 3, 2, 4, 5, 7, 6, 7, 8, 6, 5, 3, 3, 5, 2, 4, 1, 3, 5, 5, 3, 2, 4, 4, 5, 8, 9];
        
        $result = Stream::from($data)
            ->fork(
                Discriminators::evenOdd(),
                Stream::empty()->onlyExtrema()->reduce(Reducers::minMax())
            )
            ->toArrayAssoc();
        
        self::assertSame([
            'even' => ['min' => 2, 'max' => 8],
            'odd' => ['min' => 1, 'max' => 9],
        ], $result);
    }
    
    public function test_scenario_78(): void
    {
        $data = [6, 4, 3, 2, 4, 5, 7, 6, 7, 8, 6, 5, 3, 3, 5, 2, 4, 1, 3, 5, 5, 3, 2, 4, 4, 5, 8, 9];
        
        $result = Stream::from($data)
            ->fork(
                Discriminators::evenOdd(),
                Stream::empty()
                    ->onlyExtrema()
                    ->reduce([
                        'min' => Reducers::min(),
                        'max' => Reducers::max(),
                        'sum' => Reducers::sum(),
                        'cnt' => Reducers::count(),
                        'avg' => Reducers::average(2),
                    ])
            )
            ->toArrayAssoc();
        
        self::assertSame([
            'even' => [
                'min' => 2,
                'max' => 8,
                'sum' => 32,
                'cnt' => 7,
                'avg' => 4.57,
            ],
            'odd' => [
                'min' => 1,
                'max' => 9,
                'sum' => 21,
                'cnt' => 5,
                'avg' => 4.2,
            ],
        ], $result);
    }
    
    public function test_scenario_79(): void
    {
        $data = [6, 4, 3, 2, 4, 5, 7, 6, 7, 8, 6, 5, 3, 3, 5, 2, 4, 1, 3, 5, 5, 3, 2, 4, 4, 5, 8, 9];
        
        $result = Stream::from($data)
            ->omitReps()
            ->onlyExtrema(false)
            ->toArray();
        
        self::assertSame([2, 7, 6, 8, 3, 5, 2, 4, 1, 5, 2], $result);
    }
    
    public function test_scenario_80(): void
    {
        $data = [1, 2, 3, 4, 5, 6, 7, 8];
        
        $result = Stream::from($data)
            ->groupBy(Discriminators::evenOdd())
            ->stream()
            ->map(Reducers::getAdapter([
                'min' => 'min',
                'max' => 'max',
            ]))
            ->toArrayAssoc();
        
        self::assertSame([
            'odd' => [
                'min' => 1,
                'max' => 7,
            ],
            'even' => [
                'min' => 2,
                'max' => 8,
            ],
        ], $result);
    }
    
    public function test_scenario_81(): void
    {
        $data = ['The brown quick Python', 'jumps over', 'the lazy Panther'];
        $longest = '';
        
        $result = Stream::from($data)
            ->tokenize()
            ->call(
                static function (string $v) use (&$longest): void {
                    if (\mb_strlen($longest) < \mb_strlen($v)) {
                        $longest = $v;
                    }
                }
            )
            ->flatMap([
                'lowercase' => '\strtolower',
                'uppercase' => '\strtoupper',
                'original' => Mappers::value(),
                'longest' => Mappers::readFrom($longest),
                'key' => Mappers::key(),
            ])
            ->groupBy(Discriminators::byKey())
            ->toArray();
        
        $expected = [
            'lowercase' => ['the', 'brown', 'quick', 'python', 'jumps', 'over', 'the', 'lazy', 'panther'],
            'uppercase' => ['THE', 'BROWN', 'QUICK', 'PYTHON', 'JUMPS', 'OVER', 'THE', 'LAZY', 'PANTHER'],
            'original' => ['The', 'brown', 'quick', 'Python', 'jumps', 'over', 'the', 'lazy', 'Panther'],
            'longest' => ['The', 'brown', 'brown', 'Python', 'Python', 'Python', 'Python', 'Python', 'Panther'],
            'key' => [0, 1, 2, 3, 4, 5, 6, 7, 8],
        ];
        
        self::assertSame($expected, $result);
    }
    
    public function test_scenario_82(): void
    {
        $result = Stream::from(['The brown quick Python', 'jumps over', 'the lazy Panther'])
            ->map(static fn(string $v): array
                => \array_count_values(\str_split(\mb_strtolower(\str_replace(' ', '', $v))))
            )
            ->fold([], static function (array $result, array $current): array {
                foreach ($current as $index => $value) {
                    if (isset($result[$index])) {
                        $result[$index] += $value;
                    } else {
                        $result[$index] = $value;
                    }
                }
                
                return $result;
            })
            ->stream()
            ->omit(Filters::number()->eq(1))
            ->sort(By::both(Value::desc(), Key::asc()))
            ->toArrayAssoc();
        
        $expected = [
            'e' => 4, 'h' => 4, 't' => 4, 'n' => 3, 'o' => 3, 'p' => 3, 'r' => 3, 'a' => 2, 'u' => 2, 'y' => 2,
        ];
        
        self::assertSame($expected, $result);
    }
    
    public function test_scenario_83(): void
    {
        $result = Stream::from(['The brown quick Python', 'jumps over', 'the lazy Panther'])
            ->flatMap(static fn(string $v): array => \str_split(\mb_strtolower(\str_replace(' ', '', $v))))
            ->fork(Discriminators::byValue(), Reducers::count())
            ->omit(Filters::number()->eq(1))
            ->sort(By::both(Value::desc(), Key::asc()))
            ->toArrayAssoc();
        
        $expected = [
            'e' => 4, 'h' => 4, 't' => 4, 'n' => 3, 'o' => 3, 'p' => 3, 'r' => 3, 'a' => 2, 'u' => 2, 'y' => 2,
        ];
        
        self::assertSame($expected, $result);
    }
    
    public function test_scenario_84(): void
    {
        $result = Stream::from(['The brown quick Python', 'jumps over', 'the lazy Panther'])
            ->flatMap(static fn(string $v): array => \str_split(\mb_strtolower(\str_replace(' ', '', $v))))
            ->gather(true)
            ->map('\array_count_values')
            ->flatMap(Filters::number()->gt(1))
            ->sort(By::both(Value::desc(), Key::asc()))
            ->toArrayAssoc();
        
        $expected = [
            'e' => 4, 'h' => 4, 't' => 4, 'n' => 3, 'o' => 3, 'p' => 3, 'r' => 3, 'a' => 2, 'u' => 2, 'y' => 2,
        ];
        
        self::assertSame($expected, $result);
    }
    
    public function test_scenario_85(): void
    {
        $result = Stream::from(['The brown quick Python', 'jumps over', 'the lazy Panther'])
            ->flatMap(static fn(string $v): array => \str_split(\mb_strtolower(\str_replace(' ', '', $v))))
            ->reduce(Reducers::countUnique(), [])
            ->transform(Filters::number()->gt(1))
            ->stream()
            ->sort(By::both(Value::desc(), Key::asc()))
            ->toArrayAssoc();
        
        $expected = [
            'e' => 4, 'h' => 4, 't' => 4, 'n' => 3, 'o' => 3, 'p' => 3, 'r' => 3, 'a' => 2, 'u' => 2, 'y' => 2,
        ];
        
        self::assertSame($expected, $result);
    }
    
    public function test_scenario_86(): void
    {
        //Arrange
        $rows = [
            ['id' => 7, 'name' => 'Sue', 'age' => 17, 'sex' => 'female'],
            ['id' => 9, 'name' => 'Chris', 'age' => 26, 'sex' => 'male'],
            ['id' => 2, 'name' => 'Kate', 'age' => 35, 'sex' => 'female'],
            ['id' => 5, 'name' => 'Chris', 'age' => 20, 'sex' => 'male'],
            ['id' => 6, 'name' => 'Joanna', 'age' => 30, 'sex' => 'female'],
            ['id' => 10, 'name' => 'Tom', 'age' => 35, 'sex' => 'male'],
            ['id' => 1, 'name' => 'Sue', 'age' => 20, 'sex' => 'female'],
        ];
        
        $junkData = [
            4, $rows[0], 2.35, new \stdClass(), 8, 'foo', $rows[1], 4.16, true, 3, $rows[2], 5.22, 'bar', 5, $rows[3],
            false, 7, $rows[4], 3.94, 'zoll', 'con', $rows[5], true, 14.33, 'ara', $rows[6], new \stdClass(),
        ];
        
        $collectFloats = Collectors::values();
        $countBools = Consumers::counter();
        $sumInts = Reducers::sum();
        
        $countLetters = Stream::empty()
            ->flatMap('\str_split')
            ->reduce(Reducers::countUnique())
            ->transform('ksort');
        
        $rowsHandler = Stream::empty()
            ->filterBy('sex', 'female')
            ->filterBy('age', Filters::greaterOrEqual(18))
            ->extract('id')
            ->collectIn($idsOfAdultWomen = Collectors::values());
        
        $countObjects = Stream::empty()->count();
        
        //Act
        $countInts = Stream::from($junkData)
            ->dispatch(
                '\gettype',
                [
                    'integer' => $sumInts,
                    'string' => $countLetters,
                    'boolean' => $countBools,
                    'array' => $rowsHandler,
                    'double' => $collectFloats,
                    'object' => $countObjects,
                ]
            )
            ->onlyIntegers()
            ->count();
        
        //Assert
        self::assertSame(5, $countInts->get());
        self::assertSame(27, $sumInts->result());
        self::assertSame(3, $countBools->count());
        self::assertSame(2, $countObjects->get());
        self::assertSame([2.35, 4.16, 5.22, 3.94, 14.33], $collectFloats->toArray());
        self::assertSame([2, 6, 1], $idsOfAdultWomen->toArray());
        
        self::assertSame([
            'a' => 3, 'b' => 1, 'c' => 1, 'f' => 1, 'l' => 2, 'n' => 1, 'o' => 4, 'r' => 2, 'z' => 1,
        ], $countLetters->toArrayAssoc());
    }
    
    public function test_scenario_87(): void
    {
        $data = [2,2,5,4,1,6,8,7,8,3,5,2,3,5,4];
        
        $allEvenNumbers = Collectors::default();
        $countOddNumbers = Consumers::counter();
        
        $stream = Stream::from($data)
            ->fork(
                Discriminators::alternately(['first', 'second']),
                Stream::empty()->dispatch(
                    Discriminators::evenOdd(),
                    [
                        'even' => $allEvenNumbers,
                        'odd' => $countOddNumbers,
                    ]
                )
                ->reduce(Reducers::sum())
            );
        
        self::assertSame([
            'first' => 36,
            'second' => 29,
        ], $stream->toArrayAssoc());
        
        self::assertSame([0 => 2, 2, 3 => 4, 5 => 6, 8, 8 => 8, 11 => 2, 14 => 4], $allEvenNumbers->toArray());
        self::assertSame(7, $countOddNumbers->count());
    }
    
    public function test_scenario_88(): void
    {
        $namesAndIds = Stream::from($this->rowsetDataset())
            ->classifyBy('name')
            ->extract('id')
            ->group();
        
        self::assertSame([
            'Sue' => [7],
            'Kate' => [2],
            'Chris' => [9, 5, 1, 4, 11, 13],
            'Joanna' => [6],
            'Phil' => [10],
            'Mike' => [5],
            'Joe' => [2],
            'Sam' => [8],
            'Doris' => [3],
            'Nathaniel' => [12],
        ], $namesAndIds->toArray());
        
        $repeatedNames = $namesAndIds->stream()->filter(Filters::size()->gt(1))->toArrayAssoc();
        
        self::assertSame(['Chris' => [9, 5, 1, 4, 11, 13]], $repeatedNames);
    }
    
    public function test_scenario_89(): void
    {
        $isAdult = static fn(array $row): string => $row['age'] >= 18 ? 'adults' : 'kids';
        
        $result = Stream::from($this->rowsetDataset())
            ->limit(3)
            ->categorize($isAdult)
            ->fork(
                Discriminators::byKey(),
                Stream::empty()
                    ->map(Mappers::arrayColumn('id'))
                    ->map(Mappers::concat(','))
                    ->first()
            )
            ->toArrayAssoc();
        
        self::assertSame([
            'kids' => '7',
            'adults' => '2,9',
        ], $result);
    }
    
    public function test_scenario_90(): void
    {
        $result = Stream::from([3, 6, 5, 2, 1, 4])
            ->categorize(Discriminators::evenOdd(), true)
            ->sort(By::key())
            ->toArrayAssoc();
        
        self::assertSame([
            'even' => [6, 2, 4],
            'odd' => [3, 5, 1],
        ], $result);
    }
    
    public function test_scenario_91(): void
    {
        $data = [8, 2, 5, 4, 2, 9, 7, 5, 1, 6, 2, 8, 3, 2, 9, 7, 4, 1, 6, 2, 5, 6, 3, 4, 9];
        
        $result = Stream::from($data)->segregate(null, true)->toArray();
        
        self::assertSame([
            [1, 1],
            [2, 2, 2, 2, 2],
            [3, 3],
            [4, 4, 4],
            [5, 5, 5],
            [6, 6, 6],
            [7, 7],
            [8, 8],
            [9, 9, 9],
        ], $result);
    }
    
    public function test_scenario_92(): void
    {
        //Arrange
        $rows = [
            ['id' => 7, 'name' => 'Sue', 'age' => 17, 'sex' => 'female'],
            ['id' => 9, 'name' => 'Chris', 'age' => 26, 'sex' => 'male'],
            ['id' => 2, 'name' => 'Kate', 'age' => 35, 'sex' => 'female'],
            ['id' => 5, 'name' => 'Chris', 'age' => 20, 'sex' => 'male'],
            ['id' => 6, 'name' => 'Joanna', 'age' => 30, 'sex' => 'female'],
        ];
        
        $data = [
            [4, 'foo', true, $rows[0], 2.35, new \stdClass(), 'foo'],
            [8, 'bar', false, $rows[1], 4.16],
            [3, 'zoll', true, $rows[2], 5.22],
            [5, 'con', true, $rows[3], 3.94],
            [7, 'ara', false, $rows[4], 14.33, new \stdClass(), 'bar', 'this value will not be consumed'],
        ];
        
        $collectFloats = Collectors::values();
        $countBools = Consumers::counter();
        $sumInts = Reducers::sum();
        $extraParams = Memo::sequence();
        $lastThreeWords = Memo::sequence(3);
        
        $rowsHandler = Stream::empty()
            ->filterBy('sex', 'female')
            ->filterBy('age', Filters::greaterOrEqual(18))
            ->extract('id')
            ->collectIn($idsOfAdultWomen = Collectors::values());
        
        $countObjects = Stream::empty()->count();
        
        //Act
        $count = Stream::from($data)
            ->unzip(
                $sumInts,
                $lastThreeWords,
                $countBools,
                $rowsHandler,
                $collectFloats,
                $countObjects,
                $extraParams,
            )
            ->count();
        
        //Assert
        self::assertSame(5, $count->get());
        self::assertSame(27, $sumInts->result());
        self::assertSame(5, $countBools->count());
        self::assertSame(2, $countObjects->get());
        self::assertSame([2.35, 4.16, 5.22, 3.94, 14.33], $collectFloats->toArray());
        self::assertSame([2, 6], $idsOfAdultWomen->toArray());
        
        self::assertSame(['foo', 'bar'], $extraParams->getValues());
        self::assertSame([6, 6], $extraParams->getKeys());
        
        self::assertSame(['zoll', 'con', 'ara'], $lastThreeWords->getValues());
        self::assertSame([1, 1, 1], $lastThreeWords->getKeys());
    }
    
    public function test_scenario_93(): void
    {
        $data = [
            1, '4 - 0',
            2, '- - 0',
            3, '- 2 5 0',
            4, '7 - 3 - 2 0',
        ];
        
        $result = Stream::from($data)
            ->putIn($currentId)
            ->readNext()
            ->tokenize(' -0')
            ->castToInt()
            ->categorize(Discriminators::readFrom($currentId), true)
            ->toArrayAssoc();
        
        $expected = [1 => [4], 3 => [2, 5], 4 => [7, 3, 2]];
        
        self::assertSame($expected, $result);
    }
}