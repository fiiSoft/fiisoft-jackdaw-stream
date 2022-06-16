<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Collector\Collectors;
use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Producer\Producers;
use FiiSoft\Jackdaw\Reducer\Reducers;
use FiiSoft\Jackdaw\Stream;
use FiiSoft\Jackdaw\StreamMaker;
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
            ->call(function () use (&$counter1) {
                ++$counter1;
            })
            ->limit(6)
            ->filter('is_int')
            ->call(function () use (&$counter2) {
                ++$counter2;
            })
            ->map(fn(int $x) => $x ** 2)
            ->omit(Filters::greaterThan(50))
            ->collectIn($buffer);
        
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
        
        $stream = StreamMaker::from(fn() => Stream::from($inputData)
            ->filter('is_int')
            ->limit(5)
            ->skip(2)
        );
        
        //when
        foreach ($stream as $key => $value) {
            $buffer1[$key] = $value;
        }
        
        //or
        $stream->collectIn($buffer2)->run();
        
        //or
        $buffer3 = $stream->toArray();
        
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
            ->chunk(2)
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
        $stream = StreamMaker::from(static fn() => Stream::from($inputData)->limit(7)->chunk(3));
    
        self::assertSame('[[1,2,3],[4,5,6],[7]]', $stream->limit(3)->toJson());
        self::assertSame('[[1,2,3],[4,5,6]]', $stream->limit(2)->toJson());
        
        self::assertSame('[[1,2,3],[4,5,6]]', $stream->filter(static fn($ch) => \count($ch) === 3)->toJson());
        self::assertSame('[[1,2,3],[4,5,6]]', $stream->filter(Filters::length()->eq(3))->toJson());
        
        self::assertSame('[[1,2,3],[4,5,6]]', $stream->limit(2)->filter(Filters::length()->eq(3))->toJson());
    }
    
    public function test_scenario_10(): void
    {
        $inputData = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5, 'f' => 6, 'g' => 7, 'h' => 8];
        $stream = Stream::from($inputData)
            ->limit(7) //without h=>8
            ->while(static fn($v) => $v <= 5) //it should stop on f=>6
            ->chunk(3);
        
        self::assertSame('[[1,2,3]]', $stream->toJson());
    }
    
    public function test_scenario_11(): void
    {
        $inputData = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5, 'f' => 6, 'g' => 7, 'h' => 8];
        $stream = Stream::from($inputData)
            ->while(static fn($v) => $v <= 5) //it should stop on f=>6
            ->sort(); //only five first should be passed to sort
        
        self::assertSame('1,2,3,4,5', $stream->toString());
    }
    
    public function test_scenario_12(): void
    {
        $inputData = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5, 'f' => 6, 'g' => 7, 'h' => 8];
        $stream = Stream::from($inputData)
            ->while(static fn($v) => $v <= 5) //it should stop on f=>6
            ->sort() //only five first should be passed to sort
            ->chunk(3); //it should produce two chunks: 3 and 2 elements
    
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
        
        self::assertSame([[2, 'Kate', 35], [6, 'Joanna']], $actual);
    }
    
    public function test_scenario_17(): void
    {
        $stream = Stream::from($this->rowsetDataset())
            ->filterBy('name', 'Chris')
            ->filterBy('age', Filters::number()->ge(30))
            ->flat()
            ->chunk(3);
        
        self::assertSame([[4, 'Chris', 30], [11, 'Chris', 42], [13, 'Chris', 62]], $stream->toArray());
    }
    
    public function test_scenario_18(): void
    {
        $stream = Stream::from($this->rowsetDataset())
            ->filterBy('name', 'Chris')
            ->filterBy('age', Filters::number()->ge(30))
            ->flat()
            ->chunk(3)
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
            ->chunk(3)
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
            ->chunk(3);
    
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
                ->collectIn($names = Collectors::default())
            )
            ->feed(Stream::empty()->extract('id')->collectIn($allIds = Collectors::default()))
            ->filterBy('name', 'Chris')
            ->filterBy('age', Filters::number()->ge(30))
            ->feed(Stream::empty()->extract('id')->collectIn($ids = Collectors::default()))
            ->flat()
            ->limit(8)
            ->without(['Chris'])
            ->skip(2)
            ->limit(2);
    
        self::assertSame([11, 42], $stream->toArray());
        self::assertSame([4, 11], $ids->getArrayCopy());
        self::assertSame(['Chris', 'Joanna', 'Joe', 'Kate', 'Mike'], $names->getArrayCopy());
        self::assertSame(11, Stream::from($allIds)->reduce('max')->get());
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
        $source = Stream::from(['v', -3, 'a', 5, 'f', 2, 'u', 0, 'b', -1])->limit(7)->feed($consumer);
        $source->run();
    
        self::assertSame(7, $consumer->get());
    }
    
    public function test_scenario_28()
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
    
    public function test_scenario_29()
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
            ->fold([], static function (array $result, array $data) {
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
        
        //when
        $result->run();
        
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
        
        self::assertSame(['foo', 123, 'bar', 456], $buffer->getArrayCopy());
    }
    
    public function test_scenario_40(): void
    {
        $buffer = Collectors::default();
    
        Stream::from(['foo', 123, 'bar', 456])
            ->feed(
                Stream::from(['z', 5, 'd'])
                    ->join(['a', 7, 'c'])
                    ->onlyStrings()
                    ->collectIn($buffer)
            )
            ->onlyIntegers()
            ->collectIn($buffer)
            ->run();
    
        self::assertSame(['foo', 123, 'bar', 456, 'z', 'd', 'a', 'c'], $buffer->getArrayCopy());
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
            ->groupBy(Discriminators::byKey())
            ->stream()
            ->map(Reducers::average())
            ->sort(null, Check::KEY)
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
            ->call(static function ($_, string $name) use (&$countByName) {
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
}