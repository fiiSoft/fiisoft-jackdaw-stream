<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Collector\Collectors;
use FiiSoft\Jackdaw\Comparator\Sorting\By;
use FiiSoft\Jackdaw\Comparator\Sorting\Key;
use FiiSoft\Jackdaw\Comparator\Sorting\Sorting;
use FiiSoft\Jackdaw\Comparator\Sorting\Value;
use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Handler\OnError;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Memo\Memo;
use FiiSoft\Jackdaw\Producer\Producers;
use FiiSoft\Jackdaw\Reducer\Reducers;
use FiiSoft\Jackdaw\Registry\Registry;
use FiiSoft\Jackdaw\Stream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StreamFTest extends TestCase
{
    public function test_increasing(): void
    {
        $this->performTest009(false);
    }
    
    public function test_increasing_with_onerror_handler(): void
    {
        $this->performTest009(true);
    }
    
    private function performTest009(bool $onError): void
    {
        $stream = Stream::from([4, 2, 3, 4, 1, 2, 5, 3, 4])->increasingTrend();
        
        if ($onError) {
            $stream->onError(OnError::abort());
        }
        
        self::assertSame([4, 4, 5], $stream->toArray());
    }
    
    public function test_omitReps(): void
    {
        $this->performTest010(false);
    }
    
    public function test_omitReps_with_onerror_handler(): void
    {
        $this->performTest010(true);
    }
    
    private function performTest010(bool $onError): void
    {
        $stream = Stream::from([3, 2, 2, 4, 4, 1, 2, 1, 1, 1, 5, 5, 2, 2])->omitReps();
        
        if ($onError) {
            $stream->onError(OnError::abort());
        }
        
        self::assertSame([3, 2, 4, 1, 2, 1, 5, 2], $stream->toArray());
    }
    
    public function test_iterate_stream_as_array_with_filter(): void
    {
        $data = ['d' => 3, 'a' => 1, 'c' => 2, 'e' => 4, 'g' => 3, 'b' => 2, 'f' => 1, 'i' => 5];
        
        self::assertSame(
            ['a' => 1, 'c' => 2, 'b' => 2, 'f' => 1],
            \iterator_to_array(Stream::from($data)->lessThan(3))
        );
    }
    
    public function test_reindex(): void
    {
        self::assertSame(
            [2 => 'a', 4 => 'b', 6 => 'c'],
            Stream::from([5 => 'a', 'b', 'c'])->reindex(2, 2)->toArrayAssoc()
        );
    }
    
    public function test_remember(): void
    {
        $this->performTest011(false);
    }
    
    public function test_remember_with_onerror_handler(): void
    {
        $this->performTest011(true);
    }
    
    private function performTest011(bool $onError): void
    {
        $registry = Registry::new();
        
        $stream = Stream::from(['e', 'a', 'b'])->remember($registry->value('foo'));
        
        if ($onError) {
            $stream->onError(OnError::abort());
        }
        
        $stream->run();
        
        self::assertSame('b', $registry->get('foo'));
    }
    
    public function test_call_one(): void
    {
        Stream::from(['a', 'v', 'c'])->call($counter = Consumers::counter())->run();
        
        self::assertSame(3, $counter->get());
    }
    
    public function test_call_many(): void
    {
        $this->performTest012(false);
    }
    
    public function test_call_many_with_onerror_handler(): void
    {
        $this->performTest012(true);
    }
    
    private function performTest012(bool $onError): void
    {
        $data = [5, 2, 4, 1, 3];
        $value = null;
        
        $stream = Stream::from($data)
            ->call(Consumers::sendValueTo($value))
            ->call(static function () use (&$value, &$data) {
                self::assertSame(\array_shift($data), $value);
            });
        
        if ($onError) {
            $stream->onError(OnError::abort());
        }
        
        $stream->run();
        
        self::assertSame(3, $value);
    }
    
    public function test_callMax(): void
    {
        $this->performTest013(false);
    }
    
    public function test_callMax_with_onerror_handler(): void
    {
        $this->performTest013(true);
    }
    
    private function performTest013(bool $onError): void
    {
        $stream = Stream::from(['a', 'b', 'c', 'd'])->callMax(2, Consumers::sendValueTo($value));
        
        if ($onError) {
            $stream->onError(OnError::abort());
        }
        
        $stream->run();
        
        self::assertSame('b', $value);
    }
    
    public function test_callWhen(): void
    {
        $this->performTest014(false);
    }
    
    public function test_callWhen_with_else_consumer_and_onerror_handler(): void
    {
        $this->performTest014(true);
    }
    
    private function performTest014(bool $onError): void
    {
        $countStrings = Consumers::counter();
        $countInts = Consumers::counter();
        
        $stream = Stream::from(['a', 1, 2, 'c', 3])->callWhen('is_string', $countStrings, $countInts);
        
        if ($onError) {
            $stream->onError(OnError::abort());
        }
        
        $stream->run();
        
        self::assertSame(3, $countInts->get());
        self::assertSame(2, $countStrings->get());
    }
    
    public function test_callWhile(): void
    {
        $this->performTest015(false);
    }
    
    public function test_callWhile_with_onerror_handler(): void
    {
        $this->performTest015(true);
    }
    
    private function performTest015(bool $onError): void
    {
        $numOfIntsAtTheBeginning = Consumers::counter();
        
        $stream = Stream::from([3, 2, 'a', 1, 2, 'b'])->callWhile('is_int', $numOfIntsAtTheBeginning);
        
        if ($onError) {
            $stream->onError(OnError::abort());
        }
        
        $stream->run();
        
        self::assertSame(2, $numOfIntsAtTheBeginning->get());
    }
    
    public function test_callUntil(): void
    {
        $this->performTest016(false);
    }

    public function test_callUntil_with_onerror_handler(): void
    {
        $this->performTest016(true);
    }
    
    private function performTest016(bool $onError): void
    {
        $numOfIntsAtTheBeginning = Consumers::counter();
        
        $stream = Stream::from([3, 2, 'a', 1, 2, 'b'])->callUntil('is_string', $numOfIntsAtTheBeginning);
        
        if ($onError) {
            $stream->onError(OnError::abort());
        }
        
        $stream->run();
        
        self::assertSame(2, $numOfIntsAtTheBeginning->get());
    }
    
    public function test_skipWhile(): void
    {
        $this->performTest017(false);
    }
    
    public function test_skipWhile_with_onerror_handler(): void
    {
        $this->performTest017(true);
    }
    
    private function performTest017(bool $onError): void
    {
        $stream = Stream::from([3, 2, 'a', 1, 2, 'b'])->skipWhile('is_int');
        
        if ($onError) {
            $stream->onError(OnError::abort());
        }
        
        self::assertSame(['a', 1, 2, 'b'], $stream->toArray());
    }
    
    public function test_skipUntil(): void
    {
        $this->performTest018(false);
    }
    
    public function test_skipUntil_with_onerror_handler(): void
    {
        $this->performTest018(true);
    }
    
    private function performTest018(bool $onError): void
    {
        $stream = Stream::from([3, 2, 'a', 1, 2, 'b'])->skipUntil('is_string');
        
        if ($onError) {
            $stream->onError(OnError::abort());
        }
        
        self::assertSame(['a', 1, 2, 'b'], $stream->toArray());
    }
    
    public function test_makeTuple(): void
    {
        $this->performTest019(false);
    }
    
    public function test_makeTuple_with_onerror_handler(): void
    {
        $this->performTest019(true);
    }
    
    private function performTest019(bool $onError): void
    {
        $stream = Stream::from(['a' => 1, 'b' => 2, 'c' => 3])->makeTuple();
        
        if ($onError) {
            $stream->onError(OnError::abort());
        }
        
        self::assertSame([['a', 1], ['b', 2], ['c', 3]], $stream->toArray());
    }
    
    public function test_makeTuple_assoc(): void
    {
        $this->performTest020(false);
    }
    
    public function test_makeTuple_assoc_with_onerror_handler(): void
    {
        $this->performTest020(true);
    }
    
    private function performTest020(bool $onError): void
    {
        $stream = Stream::from(['a' => 1, 'b' => 2, 'c' => 3])->makeTuple(true);
        
        if ($onError) {
            $stream->onError(OnError::abort());
        }
        
        self::assertSame(
            [['key' => 'a', 'value' => 1], ['key' => 'b', 'value' => 2], ['key' => 'c', 'value' => 3]],
            $stream->toArrayAssoc()
        );
    }
    
    public function test_unpackTuple(): void
    {
        $this->performTest021(false);
    }
    
    public function test_unpackTuple_with_onerror_handler(): void
    {
        $this->performTest021(true);
    }
    
    private function performTest021(bool $onError): void
    {
        $stream = Stream::from([['a', 1], ['b', 2], ['c', 3]])->unpackTuple();
        
        if ($onError) {
            $stream->onError(OnError::abort());
        }
        
        self::assertSame(['a' => 1, 'b' => 2, 'c' => 3], $stream->toArrayAssoc());
    }
    
    public function test_unpackTuple_assoc(): void
    {
        $this->performTest022(false);
    }
    
    public function test_unpackTuple_assoc_with_onerror_handler(): void
    {
        $this->performTest022(true);
    }
    
    private function performTest022(bool $onError): void
    {
        $stream = Stream::from(
            [['key' => 'a', 'value' => 1], ['key' => 'b', 'value' => 2], ['key' => 'c', 'value' => 3]]
        )->unpackTuple(true);
        
        if ($onError) {
            $stream->onError(OnError::abort());
        }
        
        self::assertSame(['a' => 1, 'b' => 2, 'c' => 3], $stream->toArrayAssoc());
    }
    
    public function test_unique(): void
    {
        self::assertSame([2, 3, 1], Stream::from([2, 3, 1, 2, 3, 2, 3])->unique()->toArray());
    }
    
    public function test_until(): void
    {
        self::assertSame(
            [3, 2, 4],
            Stream::from([5, 3, 2, 4, 5, 'a', 1, 2, 'b'])->until('is_string')->lessOrEqual(4)->toArray()
        );
    }
    
    public function test_while(): void
    {
        self::assertSame(
            [3, 2, 4],
            Stream::from([5, 3, 2, 4, 5, 'a', 1, 2, 'b'])->while('is_int')->lessOrEqual(4)->toArray()
        );
    }
    
    public function test_zip(): void
    {
        $this->performTest023(false);
    }
    
    public function test_zip_with_onerror_handler(): void
    {
        $this->performTest023(true);
    }
    
    private function performTest023(bool $onError): void
    {
        $stream = Stream::from([1, 2, 3, 4])->zip(['a', 'b', 'c']);
        
        if ($onError) {
            $stream->onError(OnError::abort());
        }
        
        self::assertSame([[1, 'a'], [2, 'b'], [3, 'c'], [4, null]], $stream->toArray());
    }
    
    public function test_zip_empty_string(): void
    {
        $this->performTest024(false);
    }

    public function test_zip_empty_string_with_onerror_handler(): void
    {
        $this->performTest024(true);
    }
    
    private function performTest024(bool $onError): void
    {
        $stream = Stream::from([1, 2, 3, 4])->zip();
        
        if ($onError) {
            $stream->onError(OnError::abort());
        }
        
        self::assertSame([[1], [2], [3], [4]], $stream->toArray());
    }
    
    public function test_reduce(): void
    {
        self::assertSame(12, Stream::from([5, 2, 1, 4])->reduce(Reducers::sum())->get());
    }
    
    public function test_fold(): void
    {
        self::assertSame(7, Stream::from([5, 2, 1, 4])->fold(-5, Reducers::sum())->get());
    }
    
    public function test_collect(): void
    {
        self::assertSame([2, 3, 1], Stream::from([2, 3, 1])->collect()->get());
    }
    
    public function test_collectKeys(): void
    {
        self::assertSame([2, 3, 1], Stream::from([2 => 'a', 3 => 'b', 1 => 'c'])->collectKeys()->get());
    }
    
    public function test_count(): void
    {
        self::assertSame(3, Stream::from([2, 3, 1])->count()->get());
    }
    
    public function test_find(): void
    {
        self::assertSame('a', Stream::from([5, 3, 2, 4, 5, 'a', 1, 2, 'b'])->find('is_string')->get());
    }
    
    public function test_first(): void
    {
        self::assertSame('a', Stream::from([5, 3, 2, 4, 5, 'a', 1, 2, 'b'])->filter('is_string')->first()->get());
    }
    
    public function test_has(): void
    {
        self::assertTrue(Stream::from([5, 3, 2, 4, 5, 'a', 1, 2, 'b'])->has('is_string')->get());
    }
    
    public function test_hasEvery(): void
    {
        self::assertTrue(Stream::from([3, 5, 2, 4, 8, 1, 7, 6])->hasEvery([2, 5, 7])->get());
    }
    
    public function test_hasOnly(): void
    {
        self::assertFalse(Stream::from([5, 2, 4, 8, 1, 7, 6])->hasOnly([2, 5, 7])->get());
    }
    
    public function test_isEmpty(): void
    {
        self::assertTrue(Stream::empty()->isEmpty()->get());
        self::assertFalse(Stream::from(['a'])->isEmpty()->get());
    }
    
    public function test_last(): void
    {
        self::assertSame('e', Stream::from(['a', 2, 'b', 1, 'e'])->last()->get());
    }
    
    public function test_unzip_with_fork_in_consumer(): void
    {
        $rowset = [
            ['id' => 2, 'age' => 22, 'sex' => 'm'],
            ['id' => 9, 'age' => 17, 'sex' => 'f'],
            ['id' => 6, 'age' => 15, 'sex' => 'm'],
            ['id' => 5, 'age' => 24, 'sex' => 'f'],
            ['id' => 7, 'age' => 18, 'sex' => 'm'],
        ];
        
        $ids = [];
        $avgAge = Reducers::average(2);
        $count = Stream::empty()->fork(Discriminators::byValue(), Reducers::count())->collect();
        
        Stream::from($rowset)->unzip(Collectors::array($ids, false), $avgAge, $count)->run();
        
        self::assertSame([2, 9, 6, 5, 7], $ids);
        self::assertSame((22 + 17 + 15 + 24 + 18) / 5, $avgAge->result());
        self::assertSame([
            'm' => 3,
            'f' => 2,
        ], $count->toArrayAssoc());
    }
    
    public function test_unzip_collector_preserve_keys_with(): void
    {
        $data = [
            ['a' => 2, 'f' => 22],
            ['b' => 9, 'g' => 17],
            ['c' => 6, 'h' => 15],
            ['d' => 5, 'i' => 24],
            ['e' => 7, 'j' => 30],
        ];
        
        $first = [];
        Stream::from($data)->unzip(Collectors::array($first))->run();
        
        self::assertSame(['a' => 2, 'b' => 9, 'c' => 6, 'd' => 5, 'e' => 7], $first);
    }
    
    public function test_unzip_collector_preserve_keys_with_stream_and_onerror_handler(): void
    {
        $data = [
            ['a' => 2, 'f' => 22],
            ['b' => 9, 'g' => 17],
            ['c' => 6, 'h' => 15],
            ['d' => 5, 'i' => 24],
            ['e' => 7, 'j' => 30],
        ];
        
        $second = Stream::empty()->greaterThan(20)->limit(2)->collect();
        
        Stream::from($data)
            ->onError(OnError::skip())
            ->unzip(Consumers::idle(), $second)
            ->run();
        
        self::assertSame(['f' => 22, 'i' => 24], $second->toArrayAssoc());
    }
    
    public function test_unzip_with_onerror_handler(): void
    {
        $rowset = [
            ['id' => 2, 'age' => 22, 'sex' => 'm'],
            ['id' => 9, 'age' => 17, 'sex' => 'f'],
            ['id' => 6, 'age' => 15, 'sex' => 'm'],
            ['id' => 5, 'age' => 24, 'sex' => 'f'],
            ['id' => 7, 'age' => 18, 'sex' => 'm'],
        ];
        
        $ids = [];
        $avgAge = Reducers::average(2);
        $count = Stream::empty()->fork(Discriminators::byValue(), Reducers::count())->collect();
        
        Stream::from($rowset)
            ->onError(OnError::skip())
            ->unzip(Collectors::array($ids, false), $avgAge, $count)
            ->run();
        
        self::assertSame([2, 9, 6, 5, 7], $ids);
        self::assertSame((22 + 17 + 15 + 24 + 18) / 5, $avgAge->result());
        self::assertSame([
            'm' => 3,
            'f' => 2,
        ], $count->toArrayAssoc());
    }
    
    public function test_dispatch(): void
    {
        $sumIntegers = Reducers::sum();
        $countStrings = Reducers::count();
        
        Stream::from(['a', 3, 'b', 2, 'c'])
            ->dispatch('gettype', [
                'integer' => $sumIntegers,
                'string' => $countStrings,
            ])
            ->run();
        
        self::assertSame(5, $sumIntegers->result());
        self::assertSame(3, $countStrings->result());
    }
    
    public function test_sort(): void
    {
        self::assertSame(['a', 'e', 'g', 'n'], Stream::from(['g', 'a', 'n', 'e'])->sort()->toArray());
    }
    
    public function test_sort_map_with_onerror_handler(): void
    {
        self::assertSame(
            ['A', 'E', 'G', 'N'],
            Stream::from(['g', 'a', 'n', 'e'])->onError(OnError::skip())->sort()->map('strtoupper')->toArray()
        );
    }
    
    public function test_map(): void
    {
        self::assertSame([8, 5, 6], Stream::from([5, 2, 3])->map(Mappers::increment(3))->toArray());
    }
    
    public function test_limit(): void
    {
        self::assertSame([5, 2, 3], Stream::from([5, 2, 3, 7, 1, 4])->limit(3)->toArray());
    }
    
    public function test_limit_0_with_onerror_handler(): void
    {
        self::assertSame([], Stream::from([5, 2, 3, 7, 1, 4])->onError(OnError::skip())->limit(0)->toArray());
    }
    
    public function test_sortLimited(): void
    {
        self::assertSame([1, 2, 3], Stream::from([7, 2, 4, 9, 3, 5, 1, 6, 8])->sort()->limit(3)->toArray());
    }
    
    public function test_sortLimited_one_element_empty_string(): void
    {
        self::assertSame([], Stream::empty()->best(1)->toArray());
    }
    
    public function test_sortLimited_one_element_with_onerror_handler(): void
    {
        self::assertSame(
            [6 => 1],
            Stream::from([7, 2, 4, 9, 3, 5, 1, 6, 8])->onError(OnError::skip())->best(1)->toArrayAssoc()
        );
    }
    
    public function test_sortLimited_on_empty_string_with_onerror_handler(): void
    {
        self::assertSame([], Stream::empty()->onError(OnError::skip())->best(5)->toArray());
    }
    
    public function test_fork_sortLimited(): void
    {
        $actual = Stream::from([6, 2, 5, 1, 3, 7, 4, 9, 2])
            ->fork(
                Discriminators::evenOdd(),
                Stream::empty()->best(3)->collect()
            )
            ->toArrayAssoc();
        
        self::assertSame([
            'even' => [1 => 2, 8 => 2, 6 => 4],
            'odd' => [3 => 1, 4 => 3, 2 => 5],
        ], $actual);
    }
    
    public function test_tail_sortLimited_with_onerror_handler(): void
    {
        $actual = Stream::from(['q','w','e','r','t','y','u','i','o','p','a','s','d','f','g','h','j','k','l','z','x'])
            ->onError(OnError::skip())
            ->tail(10)
            ->best(3)
            ->toArrayAssoc();
        
        self::assertSame([12 => 'd', 13 => 'f', 14 => 'g'], $actual);
    }
    
    public function test_sort_sortLimited_with_onerror_handler(): void
    {
        $actual = Stream::from(['q','w','e','r','t','r','e','w','q','t'])
            ->onError(OnError::skip())
            ->sort(By::keyDesc())
            ->sort(static function (string $v1, string $v2, int $k1, int $k2): int {
                if (($k1 & 1) === 1) {
                    if (($k2 & 1) === 1) {
                        return $v1 <=> $v2 ?: $k1 <=> $k2;
                    }
                    
                    return -1;
                }
                
                if (($k2 & 1) === 0) {
                    return ($v2 <=> $v1 ?: $k2 <=> $k1);
                }
                
                return 1;
            })
            ->limit(20)
            ->toArrayAssoc();
        
        self::assertSame([
            3 => 'r',
            5 => 'r',
            9 => 't',
            1 => 'w',
            7 => 'w',
            4 => 't',
            8 => 'q',
            0 => 'q',
            6 => 'e',
            2 => 'e',
        ], $actual);
    }
    
    public function test_sort_tail_sortLimited_with_onerror_handler(): void
    {
        $actual = Stream::from(['q','w','e','r','t','r','e','w','q','t'])
            ->onError(OnError::skip())
            ->sort(By::bothAsc())
            ->tail(8)
            ->best(6, static function (string $v1, string $v2, int $k1, int $k2): int {
                if (($k1 & 1) === 1) {
                    if (($k2 & 1) === 1) {
                        return $v1 <=> $v2 ?: $k1 <=> $k2;
                    }

                    return -1;
                }

                if (($k2 & 1) === 0) {
                    return ($v2 <=> $v1 ?: $k2 <=> $k1);
                }

                return 1;
            })
            ->toArrayAssoc();
        
        self::assertSame([
            3 => 'r',
            5 => 'r',
            9 => 't',
            1 => 'w',
            7 => 'w',
            4 => 't',
        ], $actual);
    }
    
    public function test_shuffle_tail_sortLimited_with_onerror_handler(): void
    {
        $actual = Stream::from(['q','w','e','r','t','r','e','w','q','t'])
            ->onError(OnError::skip())
            ->shuffle()
            ->tail(10)
            ->best(6, static function (string $v1, string $v2, int $k1, int $k2): int {
                if (($k1 & 1) === 1) {
                    if (($k2 & 1) === 1) {
                        return $v1 <=> $v2 ?: $k1 <=> $k2;
                    }

                    return -1;
                }

                if (($k2 & 1) === 0) {
                    return ($v2 <=> $v1 ?: $k2 <=> $k1);
                }

                return 1;
            })
            ->toArrayAssoc();
        
        self::assertSame([
            3 => 'r',
            5 => 'r',
            9 => 't',
            1 => 'w',
            7 => 'w',
            4 => 't',
        ], $actual);
    }
    
    public function test_accumulate(): void
    {
        self::assertSame(
            [['a', 'b'], [3 => 'c', 'd']],
            Stream::from(['a', 'b', 1, 'c', 'd'])->accumulate('is_string')->toArrayAssoc()
        );
    }
    
    public function test_chunk(): void
    {
        self::assertSame([[2, 5], [2 => 1, 4], [4 => 3]], Stream::from([2, 5, 1, 4, 3])->chunk(2)->toArray());
    }
    
    public function test_chunk_one_reindex_onerror_handlers(): void
    {
        self::assertSame([[2], [5]], Stream::from([2, 5])->onError(OnError::skip())->chunk(1, true)->toArray());
    }
    
    public function test_chunk_one_keep_keys(): void
    {
        $this->performTest025(false);
    }
    
    public function test_chunk_one_keep_keys_onerror_handlers(): void
    {
        $this->performTest025(true);
    }
    
    private function performTest025(bool $onError): void
    {
        $stream = Stream::from([2, 5])->chunk(1);
        
        if ($onError) {
            $stream->onError(OnError::abort());
        }
        
        self::assertSame([[0 => 2], [1 => 5]], $stream->toArray());
    }
    
    public function test_chunk_many_reindex_onerror_handlers(): void
    {
        self::assertSame(
            [[2, 5], [1, 3]],
            Stream::from([2, 5, 1, 3])->onError(OnError::skip())->chunk(2, true)->toArray()
        );
    }
    
    public function test_chunk_many_keep_keys_onerror_handlers(): void
    {
        self::assertSame(
            [[2, 5], [2 => 1, 3]],
            Stream::from([2, 5, 1, 3])->onError(OnError::skip())->chunk(2)->toArray()
        );
    }
    
    public function test_chunkBy(): void
    {
        $actual = Stream::from(['A', 'B', 'c', 'd', 'e', 'F', 'G', 'H', 'i', 'j'])
            ->chunkBy('\ctype_upper', true)
            ->toArray();
        
        self::assertSame([
            ['A', 'B'],
            ['c', 'd', 'e'],
            ['F', 'G', 'H'],
            ['i', 'j'],
        ], $actual);
    }
    
    public function test_chunkBy_reindex_keys_with_onerror_handler(): void
    {
        $actual = Stream::from(['a', 'b', 1, 'c', 2, 3, 'd', 'e'])
            ->onError(OnError::skip())
            ->chunkBy('is_string', true)
            ->toArray();
        
        self::assertSame([
            ['a', 'b'],
            [1],
            ['c'],
            [2, 3],
            ['d', 'e'],
        ], $actual);
    }
    
    public function test_chunkBy_preserve_keys_with_onerror_handler(): void
    {
        $actual = Stream::from(['a', 'b', 1, 'c', 2, 3, 'd', 'e'])
            ->onError(OnError::skip())
            ->chunkBy('is_string')
            ->toArray();
        
        self::assertSame([
            ['a', 'b'],
            [2 => 1],
            [3 => 'c'],
            [4 => 2, 3],
            [6 => 'd', 'e'],
        ], $actual);
    }
    
    public function test_classify_with_onerror_handler(): void
    {
        $actual = Stream::from(['a', 1, 'b', 'c', 2, 3])
            ->onError(OnError::skip())
            ->classify('is_string')
            ->toArrayAssoc();
            
        self::assertSame([1 => 'c', 0 => 3], $actual);
    }
    
    public function test_countIn_with_onerror_handler(): void
    {
        Stream::from([8, 2, 5, 1, 3])->onError(OnError::skip())->countIn($count)->run();
        
        self::assertSame(5, $count);
    }
    
    public function test_mapWhen(): void
    {
        self::assertSame(
            ['a', 'b', 1, 'c', 0, 3],
            Stream::from(['a', 'b', 3, 'c', 2, 5])->mapWhen('is_int', Mappers::decrement(2))->toArrayAssoc()
        );
    }
    
    public function test_mapWhen_with_onerror_handler(): void
    {
        $actual = Stream::from(['a', 'b', 3, 'c', 2, 5])
            ->onError(OnError::skip())
            ->mapWhen('is_int', Mappers::decrement(2), 'strtoupper')
            ->toArrayAssoc();
        
        self::assertSame(['A', 'B', 1, 'C', 0, 3], $actual);
    }
    
    public function test_mapFieldWhen(): void
    {
        $this->performTest026(false);
    }
    
    public function test_mapFieldWhen_with_onerror_handler(): void
    {
        $this->performTest026(true);
    }
    
    private function performTest026(bool $onError): void
    {
        $rowset = [
            ['id' => 1, 'temp' => '---'],
            ['id' => 2, 'temp' => '15.4'],
            ['id' => 3, 'temp' => '22.3'],
            ['id' => 4, 'temp' => '---'],
        ];
        
        $stream = Stream::from($rowset)
            ->mapFieldWhen(
                'temp',
                Filters::same('---'),
                Mappers::simple(null),
                Mappers::toFloat(),
            );
        
        if ($onError) {
            $stream->onError(OnError::abort());
        }
        
        self::assertSame([
            ['id' => 1, 'temp' => null],
            ['id' => 2, 'temp' => 15.4],
            ['id' => 3, 'temp' => 22.3],
            ['id' => 4, 'temp' => null],
        ], $stream->toArray());
    }
    
    public function test_extrema(): void
    {
        $data = [6, 4, 3, 2, 4, 5, 7, 6, 7, 8, 6, 5, 3, 3, 5, 2, 4, 1, 3, 5, 5, 3, 2, 4, 4, 5, 8, 9];
        
        self::assertSame([6, 2, 7, 6, 8, 5, 2, 4, 1, 2, 9], Stream::from($data)->onlyExtrema()->toArray());
    }
    
    public function test_flat(): void
    {
        self::assertSame(
            [3, 2, 5, 8, 7, 6, 2, 4, 1],
            Stream::from([3, [2, [5, 8], [7], 6, 2, [4, 1]]])->flat()->toArray()
        );
    }
    
    public function test_gather(): void
    {
        self::assertSame(
            [['a', 2 => 'b', 'c', 6 => 'd']],
            Stream::from(['a', 3, 'b', 'c', 1, 5, 'd'])->onlyStrings()->gather()->toArray()
        );
    }
    
    public function test_categorize(): void
    {
        $this->performTest027(false);
    }
    
    public function test_categorize_with_onerror_handler(): void
    {
        $this->performTest027(true);
    }
    
    private function performTest027(bool $onError): void
    {
        $stream = Stream::from([3, 6, 5, 2, 1, 4])->categorize(Discriminators::evenOdd(), true);
        
        if ($onError) {
            $stream->onError(OnError::abort());
        }
        
        self::assertSame([
            'odd' => [3, 5, 1],
            'even' => [6, 2, 4],
        ], $stream->toArrayAssoc());
    }
    
    public function test_categorize_with_onerror_handler_and_DataCollector(): void
    {
        $actual = Stream::from([3, 6, 5, 2, 1, 4])
            ->onError(OnError::skip())
            ->categorize(Discriminators::evenOdd(), true)
            ->tail(1);
        
        self::assertSame([
            'even' => [6, 2, 4],
        ], $actual->toArrayAssoc());
    }
    
    public function test_mapKey(): void
    {
        $actual = Stream::from(['a' => 1, 'b' => 2])
            ->mapKey(static fn($_, string $key): string => \strtoupper($key))
            ->toArrayAssoc();
        
        self::assertSame(['A' => 1, 'B' => 2], $actual);
    }
    
    public function test_mapKeyValue(): void
    {
        $actual = Stream::from(['a' => 1, 'b' => 2])
            ->mapKV(static fn(int $v, string $k): array => [\strtoupper($k) => $v * 2])
            ->toArrayAssoc();
        
        self::assertSame(['A' => 2, 'B' => 4], $actual);
    }
    
    public function test_mapKeyValue_OneArg_with_onerror_handler(): void
    {
        $actual = Stream::from(['a' => 1, 'b' => 2])
            ->onError(OnError::skip())
            ->mapKV(static fn(int $v): array => [$v => $v * 2])
            ->toArrayAssoc();
        
        self::assertSame([1 => 2, 2 => 4], $actual);
    }
    
    public function test_mapKeyValue_ZeroArg(): void
    {
        $data = new \InfiniteIterator(new \ArrayIterator([5 => 'e', 3 => 'f']));
        $data->rewind();
        
        $mapper = function () use ($data): array {
            try {
                return [$data->key() => $data->current()];
            } finally {
                $data->next();
            }
        };
        
        $actual = Stream::from(['a' => 1, 'b' => 2])
            ->mapKV($mapper)
            ->toArrayAssoc();
        
        self::assertSame([5 => 'e', 3 => 'f'], $actual);
    }

    public function test_mapKeyValue_ZeroArg_with_Memo_as_value(): void
    {
        $key = Memo::key();
        
        $actual = Stream::from(['a' => 1, 'b' => 3])
            ->countIn($itemNumber)
            ->remember($key)
            ->mapKV(static function () use ($key, &$itemNumber): array {
                return [$itemNumber => $key->read()];
            })
            ->toArrayAssoc();
        
        self::assertSame([1 => 'a', 'b'], $actual);
    }
    
    public function test_mapKeyValue_ZeroArg_with_onerror_handler(): void
    {
        $data = new \InfiniteIterator(new \ArrayIterator([5 => 'e', 3 => 'f']));
        $data->rewind();
        
        $mapper = function () use ($data): array {
            try {
                return [$data->key() => $data->current()];
            } finally {
                $data->next();
            }
        };
        
        $actual = Stream::from(['a' => 1, 'b' => 2])
            ->onError(OnError::skip())
            ->mapKV($mapper)
            ->toArrayAssoc();
        
        self::assertSame([5 => 'e', 3 => 'f'], $actual);
    }

    public function test_mapKeyValue_ZeroArg_with_Memo_as_value_and_onerror_handler(): void
    {
        $key = Memo::key();
        
        $actual = Stream::from(['a' => 1, 'b' => 3])
            ->onError(OnError::skip())
            ->countIn($itemNumber)
            ->remember($key)
            ->mapKV(static function () use ($key, &$itemNumber): array {
                return [$itemNumber => $key->read()];
            })
            ->toArrayAssoc();
        
        self::assertSame([1 => 'a', 'b'], $actual);
    }
    
    public function test_mapMany(): void
    {
        self::assertSame(
            [25, 4, 1, 9],
            Stream::from(['5', 2, '1', 3])->castToInt()->map(static fn(int $v): int => $v ** 2)->toArray()
        );
    }
    
    public function test_mapWhile(): void
    {
        $this->performTest028(false);
    }
    
    public function test_mapWhile_with_onerror_handler(): void
    {
        $this->performTest028(true);
    }
    
    private function performTest028(bool $onError): void
    {
        $stream = Stream::from([3, 2, 1, 0, 4, 5, 6])
            ->mapWhile(Filters::greaterThan(0), static fn(int $v): int => $v * 2);
        
        if ($onError) {
            $stream->onError(OnError::abort());
        }
        
        self::assertSame([6, 4, 2, 0, 4, 5, 6], $stream->toArray());
    }
    
    public function test_mapUntil(): void
    {
        $this->performTest029(false);
    }
    
    public function test_mapUntil_with_onerror_handler(): void
    {
        $this->performTest029(true);
    }
    
    private function performTest029(bool $onError): void
    {
        $stream = Stream::from([3, 2, 1, 0, 4, 5, 6])
            ->mapUntil(Filters::lessOrEqual(0), static fn(int $v): int => $v * 2);
        
        if ($onError) {
            $stream->onError(OnError::abort());
        }
        
        self::assertSame([6, 4, 2, 0, 4, 5, 6], $stream->toArray());
    }
    
    public function test_maxima(): void
    {
        $this->performTest030(false);
    }
    
    public function test_maxima_with_onerror_handler(): void
    {
        $this->performTest030(true);
    }
    
    private function performTest030(bool $onError): void
    {
        $stream = Stream::from([6, 4, 3, 2, 4, 5, 7, 6, 7, 8, 6, 5, 3, 3, 5, 2, 4, 1, 3, 5, 5, 3, 2, 4, 4, 5, 8, 9])
            ->onlyMaxima();
        
        if ($onError) {
            $stream->onError(OnError::skip());
        }
        
        self::assertSame([6, 7, 8, 5, 4, 9], $stream->toArray());
    }
    
    public function test_reverse(): void
    {
        $this->performTest031(false);
    }
    
    public function test_tail_reverse_with_onerror_handler(): void
    {
        $this->performTest031(true);
    }
    
    private function performTest031(bool $onError): void
    {
        $stream = Stream::from(['e', 'a', 'h', 'q', 'f'])->tail(3)->reverse();
        
        if ($onError) {
            $stream->onError(OnError::skip());
        }
        
        self::assertSame([4 => 'f', 3 => 'q', 2 => 'h'], $stream->toArrayAssoc());
    }
    
    public function test_reverse_empty_string_with_onerror_handler(): void
    {
        self::assertSame([], Stream::empty()->onError(OnError::skip())->reverse()->toArray());
    }
    
    public function test_scan(): void
    {
        $this->performTest032(false);
    }
    
    public function test_scan_with_onerror_handler(): void
    {
        $this->performTest032(true);
    }
    
    private function performTest032(bool $onError): void
    {
        $stream = Stream::from([3, 4, 5])->scan(0, static fn(int $acc, int $curr): int => $acc + $curr);
        
        if ($onError) {
            $stream->onError(OnError::skip());
        }
        
        self::assertSame([0, 3, 7, 12], $stream->toArray());
    }
    
    public function test_shuffle_chunked(): void
    {
        $this->performTest033(false);
    }
    
    public function test_shuffle_chunked_with_onerror_handler(): void
    {
        $this->performTest033(true);
    }
    
    private function performTest033(bool $onError): void
    {
        $stream = Stream::from([1, 2, 3, 4, 5, 6, 7, 8])->shuffle(3);
        
        if ($onError) {
            $stream->onError(OnError::skip());
        }
        
        $actual = $stream->toArray();
        
        $slice = \array_slice($actual, 0, 3);
        \sort($slice);
        self::assertSame([1, 2, 3], $slice);
        
        $slice = \array_slice($actual, 3, 3);
        \sort($slice);
        self::assertSame([4, 5, 6], $slice);
        
        $slice = \array_slice($actual, 6, 3);
        \sort($slice);
        self::assertSame([7, 8], $slice);
    }
    
    public function test_shuffle_all(): void
    {
        $data = [1, 2, 3, 4, 5, 6, 7, 8];
        
        $actual = Stream::from($data)->shuffle()->toArray();
        
        \sort($actual);
        self::assertSame($data, $actual);
    }
    
    public function test_shuffle_all_empty_stream_with_onerror_handler(): void
    {
        self::assertEmpty(Stream::empty()->onError(OnError::skip())->shuffle()->toArray());
    }
    
    public function test_tail(): void
    {
        self::assertSame([4 => 4, 1, 5], Stream::from([3, 6, 2, 8, 4, 1, 5])->tail(3)->toArrayAssoc());
    }
    
    public function test_tail_empty_string_with_onerror_handler(): void
    {
        self::assertSame([], Stream::empty()->onError(OnError::skip())->tail(5)->toArray());
    }
    
    public function test_tail_collect(): void
    {
        self::assertSame([2 => 3, 4, 5], Stream::from([1, 2, 3, 4, 5])->tail(3)->collect()->get());
    }
    
    public function test_tail_sort_with_onerror_handler(): void
    {
        $actual = Stream::from([8, 2, 5, 1, 7, 2, 5, 3, 6, 4, 2])
            ->onError(OnError::skip())
            ->tail(6)
            ->sort()
            ->toArrayAssoc();
        
        self::assertSame([5 => 2, 10 => 2, 7 => 3, 9 => 4, 6 => 5, 8 => 6], $actual);
    }
    
    public function test_shuffle_tail_call_with_onerror_handler(): void
    {
        $collector = Collectors::default();
        
        Stream::from([8, 2, 5, 1, 4, 3, 7, 9, 6])
            ->onError(OnError::skip())
            ->shuffle()
            ->tail(5)
            ->collectIn($collector)
            ->run();
        
        self::assertCount(5, $collector);
        
        foreach ($collector as $value) {
            self::assertTrue($value >= 1 && $value <= 9);
        }
        
        self::assertCount(5, \array_unique($collector->toArray(), \SORT_REGULAR));
    }
    
    public function test_tokenize(): void
    {
        self::assertSame('abcdef', Stream::from(['a b', 'c d e', 'f'])->tokenize()->toString(''));
    }
    
    public function test_window(): void
    {
        $this->performTest034(false);
    }
    
    public function test_window_with_onerror_handler(): void
    {
        $this->performTest034(true);
    }
    
    private function performTest034(bool $onError): void
    {
        $stream = Stream::from(['a', 'b', 'c', 'd', 'e', 'f'])->window(3, 2);
        
        if ($onError) {
            $stream->onError(OnError::skip());
        }
        
        self::assertSame([
            [0 => 'a', 'b', 'c'],
            [2 => 'c', 'd', 'e'],
            [4 => 'e', 'f'],
        ], $stream->toArrayAssoc());
    }
    
    public function test_accumulateUptrends_reindex_keys(): void
    {
        $this->performTest035(false);
    }
    
    public function test_accumulateUptrends_reindex_keys_with_onerror_handler(): void
    {
        $this->performTest035(true);
    }
    
    private function performTest035(bool $onError): void
    {
        $stream = Stream::from([4, 3, 2, 4, 5, 7, 6, 7, 8, 6, 5, 3, 3, 5, 2, 4, 1, 3, 5, 8, 9])
            ->accumulateUptrends(true);
        
        if ($onError) {
            $stream->onError(OnError::skip());
        }
        
        self::assertSame([
            [2, 4, 5, 7],
            [6, 7, 8],
            [3, 5],
            [2, 4],
            [1, 3, 5, 8, 9]
        ], $stream->toArray());
    }
    
    public function test_accumulateUptrends_keep_keys(): void
    {
        $this->performTest036(false);
    }

    public function test_accumulateUptrends_keep_keys_with_onerror_handler(): void
    {
        $this->performTest036(true);
    }
    
    private function performTest036(bool $onError): void
    {
        $stream = Stream::from([4, 3, 2, 4, 5, 7, 6, 7, 8, 6, 5, 3, 3, 5, 2, 4, 1, 3, 5, 8, 9])->accumulateUptrends();
        
        if ($onError) {
            $stream->onError(OnError::skip());
        }
        
        self::assertSame([
            [2 => 2, 4, 5, 7],
            [6 => 6, 7, 8],
            [12 => 3, 5],
            [14 => 2, 4],
            [16 => 1, 3, 5, 8, 9]
        ], $stream->toArray());
    }
    
    public function test_segregate(): void
    {
        $data = [1, 5, 2, 3, 2, 7, 4, 1, 6, 3, 5, 2, 4, 1, 7, 2, 3, 2, 6, 3, 2, 5, 8, 4];
        
        self::assertSame([
            [0 => 1, 7 => 1, 13 => 1],
            [2 => 2, 4 => 2, 11 => 2, 15 => 2, 17 => 2, 20 => 2],
            [3 => 3, 9 => 3, 16 => 3, 19 => 3],
        ], Stream::from($data)->segregate(3)->toArray());
    }
    
    public function test_segregate_empty_string(): void
    {
        self::assertSame([], Stream::empty()->segregate()->toArrayAssoc());
    }
    
    public function test_segregate_empty_string_with_onerror_handler(): void
    {
        self::assertSame([], Stream::empty()->onError(OnError::skip())->segregate()->toArrayAssoc());
    }
    
    public function test_segregate_with_onerror_handler(): void
    {
        $actual = Stream::from([6, 1, 2, 5, 3, 3, 4, 3, 7, 0, 2, 3, 1, 5, 8, 2, 1, 5, 0, 3, 9, 2, 4])
            ->onError(OnError::skip())
            ->segregate(4, true)
            ->toArray();
        
        self::assertSame([
            [0, 0],
            [1, 1, 1],
            [2, 2, 2, 2],
            [3, 3, 3, 3, 3],
        ], $actual);
    }
    
    public function test_segregate_one_bucket_with_onerror_handler(): void
    {
        $actual = Stream::from([6, 1, 2, 5, 3, 3, 4, 3, 7, 0, 2, 3, 1, 5, 8, 2, 1, 5, 0, 3, 9, 2, 4])
            ->onError(OnError::skip())
            ->segregate(1, true)
            ->toArray();
        
        self::assertSame([
            [0, 0],
        ], $actual);
    }
    
    public function test_tail_segregate_with_onerror_handler(): void
    {
        $actual = Stream::from([7, 2, 5, 1, 3, 5, 2, 6, 9, 4, 2, 6, 3, 4, 2, 1])
            ->onError(OnError::skip())
            ->tail(10)
            ->segregate(2)
            ->toArray();
        
        self::assertSame([
            [15 => 1],
            [6 => 2, 10 => 2, 14 => 2],
        ], $actual);
    }
    
    public function test_categorize_segregate_with_onerror_handler(): void
    {
        $actual = Stream::from([7, 2, 5, 1, 3, 5, 2, 6])
            ->onError(OnError::skip())
            ->categorize(Discriminators::evenOdd())
            ->segregate(2)
            ->toArray();
        
        self::assertSame([
            [
                'even' => [
                    1 => 2,
                    6 => 2,
                    7 => 6,
                ]
            ], [
                'odd' => [
                    0 => 7,
                    2 => 5,
                    3 => 1,
                    4 => 3,
                    5 => 5,
                ],
            ],
        ], $actual);
    }
    
    public function test_shuffle_segregate_reindex_keys_with_onerror_handler(): void
    {
        $actual = Stream::from([7, 2, 5, 1, 3, 5, 2, 6, 9, 4, 2, 6, 3, 4, 2, 1])
            ->onError(OnError::skip())
            ->shuffle()
            ->segregate(null, true)
            ->toArrayAssoc();
        
        self::assertSame([
            [1, 1],
            [2, 2, 2, 2],
            [3, 3],
            [4, 4],
            [5, 5],
            [6, 6],
            [7],
            [9],
        ], $actual);
    }
    
    /**
     * @dataProvider getDataForTestSortSegregateComparision
     */
    #[DataProvider('getDataForTestSortSegregateComparision')]
    public function test_sort_segregate_comparision(?Sorting $sorting, array $data, bool $areTheSame): void
    {
        $result1 = Stream::from($data)->sort($sorting)->segregate()->toArrayAssoc();
        $result2 = Stream::from($data)->segregate()->toArrayAssoc();
        
        if ($areTheSame) {
            self::assertSame($result1, $result2);
        } else {
            self::assertNotSame($result1, $result2);
        }
    }
    
    public static function getDataForTestSortSegregateComparision(): array
    {
        $data = [
            'f' => 7, 'b' => 2, 'k' => 5, 'c' => 1, 'm' => 3, 'h' => 5, 'a' => 2, 'j' => 6,
            'o' => 9, 'e' => 4, 'p' => 2, 'g' => 6, 'l' => 3, 'd' => 4, 'n' => 2, 'i' => 1,
        ];
        
        $byLengthDifferent = [
            'z' => 'aaa', 'y' => 'nn', 'a' => 'rrrr', 'd' => 'd', 'e' => 'uuuuu', 't' => 'kkk', 's' => 'ff',
            'm' => 'h', 'k' => 'rrrrr', 'q' => 'ttt', 'b' => 'jjjj', 'h' => 'aaaa', 'o' => 'eeeee', 'g' => 'gggg',
        ];
        
        $byLengthTheSame = [
            'z' => 'aaa', 'y' => 'aa', 'a' => 'aaaa', 'd' => 'a', 'e' => 'aaaaa', 't' => 'aaa', 's' => 'aa',
            'm' => 'a', 'k' => 'aaaaa', 'q' => 'aaa', 'b' => 'aaaa', 'h' => 'aaaa', 'o' => 'aaaaa', 'g' => 'aaaa',
        ];
        
        $byFields = [
            ['x' => 6], ['x' => 3], ['x' => 8], ['x' => 0], ['x' => 0], ['x' => 0], ['x' => 0], ['x' => 0],
            ['x' => 0], ['x' => 0], ['x' => 0], ['x' => 0], ['x' => 0], ['x' => 0], ['x' => 0], ['x' => 0],
        ];
        
        if (\PHP_MAJOR_VERSION === 8) {
            $areTheSame = true;
        } else {
            $areTheSame = false;
        }
        
        //sorting, data, expected result
        return [
            //the same
            [null, $data, true],
            [By::valueAsc(), $data, true],
            [By::valueDesc(), $data, true],
            [By::length(), $byLengthDifferent, true],
            [By::lengthDesc(), $byLengthDifferent, true],
            [By::lengthAsc(), $byLengthTheSame, true],
            [By::fieldsAsc(['x']), $byFields, true],
            [By::fieldsDesc(['x']), $byFields, true],
            //different
            [By::keyAsc(), $data, false],
            [By::keyDesc(), $data, false],
            [By::assocAsc(), $data, false],
            [By::assocDesc(), $data, false],
            [By::length(true), $byLengthTheSame, $areTheSame],
            [By::assocAsc(), $data, false],
            [By::assocDesc(), $data, false],
            [By::bothAsc(), $data, false],
            [By::bothDesc(), $data, false],
            [By::both(Key::asc(), Value::desc()), $data, false],
            [By::both(Key::desc(), Value::asc()), $data, false],
            [By::both(Value::desc(), Key::asc()), $data, false],
            [By::both(Value::asc(), Key::desc()), $data, false],
        ];
    }
    
    public function test_fork(): void
    {
        $prototype = Stream::empty()->reduce(Reducers::concat());
        
        self::assertSame([
            true => 'ABC',
            false => 'abc',
        ], Stream::from(['A', 'a', 'B', 'b', 'C', 'c'])->fork('ctype_upper', $prototype)->toArrayAssoc());
    }
    
    public function test_fork_with_onerror_handler_and_reducer(): void
    {
        $stream = Stream::from(['A', 'a', 'B', 'b', 'C', 'c'])
            ->onError(OnError::skip())
            ->fork('ctype_upper', Reducers::concat());
        
        self::assertSame([
            true => 'ABC',
            false => 'abc',
        ], $stream->toArrayAssoc());
    }
    
    public function test_Fork_with_onerror_handler_and_collector_with_keys_preserved(): void
    {
        $result = Stream::from(['a' => 1, 2 => 'b', 'c' => 3])
            ->onError(OnError::abort())
            ->fork(Discriminators::alternately(['foo', 'bar']), Collectors::default())
            ->toArrayAssoc();
        
        self::assertSame([
            'foo' => ['a' => 1, 'c' => 3],
            'bar' => [2 => 'b'],
        ], $result);
    }
    
    public function test_Fork_with_onerror_handler_and_collector_with_keys_reindexed(): void
    {
        $result = Stream::from(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4])
            ->onError(OnError::abort())
            ->fork(Filters::number()->isOdd(), Collectors::values())
            ->toArrayAssoc();
        
        self::assertSame([
            1 => [1, 3],
            0 => [2, 4],
        ], $result);
    }
    
    public function test_Fork_with_onerror_handler_and_Stream_with_boolean_discriminator(): void
    {
        $result = Stream::from(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4])
            ->onError(OnError::abort())
            ->fork(Filters::number()->isOdd(), Stream::empty()->flip()->collect(true))
            ->toArrayAssoc();
        
        self::assertSame([
            1 => ['a', 'c'],
            0 => ['b', 'd'],
        ], $result);
    }
    
    public function test_feed(): void
    {
        $data = [1, 2, 3, 4, 5];
        
        $three = Stream::empty()->limit(3)->collect();
        $stream = Stream::from($data)->feed($three);
        
        self::assertSame($data, $stream->toArrayAssoc());
        self::assertSame([1, 2, 3], $three->get());
    }
    
    public function test_feedMany(): void
    {
        $this->performTest037(false);
    }
    
    public function test_feedMany_with_onerror_handler(): void
    {
        $this->performTest037(true);
    }
    
    private function performTest037(bool $onError): void
    {
        $data = [1, 2, 3, 4, 5];
        
        $firstThree = Stream::empty()->limit(3)->collect();
        $lastThree = Stream::empty()->tail(3)->collect();
        $stream = Stream::from($data)->feed($firstThree, $lastThree);
        
        if ($onError) {
            $stream->onError(OnError::skip());
        }
        
        self::assertSame($data, $stream->toArrayAssoc());
        self::assertSame([1, 2, 3], $firstThree->get());
        self::assertSame([2 => 3, 4, 5], $lastThree->get());
    }
    
    public function test_final_operation_can_be_iterated(): void
    {
        $strings = Stream::of('a', 1, 'b', 2, 'c', 3)->onlyStrings()->collect();
        
        self::assertSame(['a', 2 => 'b', 4 => 'c'], \iterator_to_array($strings));
        self::assertSame(['a', 2 => 'b', 4 => 'c'], \iterator_to_array($strings));
    }
    
    public function test_groupBy_reindex_keys_with_onerror_handler(): void
    {
        $grouped = Stream::from([5, 'h', 3, 'e', 8, 'a'])
            ->onError(OnError::skip())
            ->groupBy(Discriminators::yesNo('is_string', 'strings', 'integers'), true);
        
        self::assertSame([5, 3, 8], $grouped->get('integers')->get());
        self::assertSame(['h', 'e', 'a'], $grouped->get('strings')->get());
    }
    
    public function test_groupBy_keep_keys_with_onerror_handler(): void
    {
        $grouped = Stream::from([5, 'h', 3, 'e', 8, 'a'])
            ->onError(OnError::skip())
            ->groupBy(Discriminators::yesNo('is_string', 'strings', 'integers'));
        
        self::assertSame([0 => 5, 2 => 3, 4 => 8], $grouped->get('integers')->get());
        self::assertSame([1 => 'h', 3 => 'e', 5 => 'a'], $grouped->get('strings')->get());
    }
    
    public function test_reverse_groupBy_with_onerror_handler(): void
    {
        $grouped = Stream::from([5, 'h', 3, 'e', 8, 'a'])
            ->onError(OnError::skip())
            ->reverse()
            ->groupBy(Discriminators::yesNo('is_string', 'strings', 'integers'));
        
        self::assertSame([4 => 8, 2 => 3, 0 => 5], $grouped->get('integers')->get());
        self::assertSame([5 => 'a', 3 => 'e', 1 => 'h'], $grouped->get('strings')->get());
    }
    
    public function test_filterBy(): void
    {
        $rowset = [
            ['id' => 4, 'age' => 15],
            ['id' => 6, 'age' => 21],
            ['id' => 2, 'age' => 13],
            ['id' => 3, 'age' => 19],
        ];
        
        self::assertSame([
            ['id' => 6, 'age' => 21],
            ['id' => 3, 'age' => 19],
        ], Stream::from($rowset)->filterBy('age', Filters::greaterOrEqual(18))->toArray());
    }
    
    public function test_iterate_stream_with_onError_handler_and_uncaught_exception(): void
    {
        $stream = Stream::from(['a', 'b', 'c', 'd'])
            ->map('strtoupper')
            ->call(static function ($value, $key) {
                if ($key === 3) {
                    throw new \RuntimeException('Error');
                }
            })
            ->onError(OnError::call(static fn() => null))
            ->onFinish(static function (): void {
                self::fail('OnFinish handler should not be fired');
            });
        
        try {
            $result = [];
            foreach ($stream as $key => $value) {
                $result[$key] = $value;
            }
        } catch (\RuntimeException $exception) {
            self::assertEquals(new \RuntimeException('Error'), $exception);
        }
        
        self::assertSame(['A', 'B', 'C'], $result);
    }
    
    public function test_iterate_stream_with_onError_handler_and_onFinish_handler(): void
    {
        $finishHandlerFired = false;
        
        $stream = Stream::from(['a', 'b', 'c', 'd'])
            ->map('strtoupper')
            ->call(static function ($value, $key) {
                if ($key === 2) {
                    throw new \RuntimeException('Error');
                }
            })
            ->onError(OnError::skip())
            ->onFinish(static function () use (&$finishHandlerFired) {
                $finishHandlerFired = true;
            });
        
        self::assertSame(['A', 'B', 3 => 'D'], \iterator_to_array($stream));
        self::assertTrue($finishHandlerFired);
    }
    
    public function test_result_can_be_use_as_producer(): void
    {
        $integers = Stream::from([1, 'a', 2, 'b', 3, 'c'])->onlyIntegers()->collect();
        
        self::assertSame([1, 2, 3], Stream::from($integers)->reindex()->toArray());
    }
    
    public function test_result_can_create_stream(): void
    {
        $integers = Stream::from([1, 'a', 2, 'b', 3, 'c'])->onlyIntegers()->collect();
        
        self::assertSame([1, 2, 3], $integers->stream()->reindex()->toArray());
    }
    
    public function test_feed_operation_with_error_handler(): void
    {
        $collector = Stream::empty()->skip(2)->limit(4)->collect(true);
        
        Stream::from([1, 2])->join([7, 8], ['a', 'b', 'c', 'd'])->feed($collector)->onError(OnError::skip());
        
        self::assertSame(
            [7, 8, 'a', 'b'],
            $collector->get()
        );
    }
    
    public function test_stack_filterBy(): void
    {
        $rowset = [
            ['id' => 1, 'age' => 15, 'sex' => 'f'],
            ['id' => 2, 'age' => 21, 'sex' => 'm'],
            ['id' => 3, 'age' => 14, 'sex' => 'm'],
            ['id' => 4, 'age' => 18, 'sex' => 'f'],
        ];
        
        $actual = Stream::from($rowset)
            ->filterBy('age', Filters::greaterOrEqual(18))
            ->filterBy('sex', 'm');
        
        self::assertSame([
            ['id' => 2, 'age' => 21, 'sex' => 'm'],
        ], $actual->toArray());
    }
    
    public function test_generic_discriminator_zero_args(): void
    {
        $keys = ['a', 'b', 'c'];
        
        $actual = Stream::from([3, 5, 2])
            ->classify(static function () use (&$keys) {
                return \array_shift($keys);
            });
        
        self::assertSame(['a' => 3, 'b' => 5, 'c' => 2], $actual->toArrayAssoc());
    }
    
    public function test_iterate_Registry_as_producer_with_onerror_handler(): void
    {
        $values = [3, 2, 5];
        
        $regEntry = Registry::new()->entry(Check::VALUE);
        $regEntry->set(\array_shift($values));
        
        $actual = [];
        foreach (Stream::from($regEntry)->onError(OnError::skip()) as $key => $value) {
            $actual[$key] = $value;
            $regEntry->set(\array_shift($values));
        }
        
        self::assertSame([3, 2, 5], $actual);
    }
    
    public function test_iterate_Reference_as_producer_with_onerror_handler(): void
    {
        $values = [3, 2, 5];
        $current = \array_shift($values);
        $producer = Producers::readFrom($current);
        
        $actual = [];
        foreach ($producer->stream()->onError(OnError::skip()) as $key => $value) {
            $actual[$key] = $value;
            $current = \array_shift($values);
        }
        
        self::assertSame([3, 2, 5], $actual);
    }
    
    public function test_iterate_ArrayIterator_with_onerror_handler(): void
    {
        self::assertSame(
            ['a', 'b'],
            \iterator_to_array(Stream::from(new \ArrayIterator(['a', 'b']))->onError(OnError::skip()))
        );
    }
    
    public function test_Flatteren_with_onerror_handler(): void
    {
        self::assertSame(
            ['a', 'b', 'c', 'd'],
            Stream::from(['a', ['b', ['c', ['d']]]])->flat()->onError(OnError::skip())->toArray()
        );
    }
    
    public function test_combine_two_arrays_with_onerror_handler(): void
    {
        self::assertSame(
            ['a' => 1, 'b' => 2, 'c' => 3],
            Producers::combinedFrom(['a', 'b', 'c'], [1, 2, 3])->stream()->onError(OnError::skip())->toArrayAssoc()
        );
    }
    
    public function test_combined(): void
    {
        $this->performTest038(false);
    }
    
    public function test_combined_with_onerror_handler(): void
    {
        $this->performTest038(true);
    }
    
    private function performTest038(bool $onError): void
    {
        $stream = Producers::combinedFrom(['a', 'b', 'c'], new \ArrayIterator([1, 2, 3]))->stream();
        
        if ($onError) {
            $stream->onError(OnError::skip());
        }
        
        self::assertSame(['a' => 1, 'b' => 2, 'c' => 3], $stream->toArrayAssoc());
    }
    
    public function test_Segregate_Reindex_default_with_onerror_handler(): void
    {
        self::assertSame([
            [1 => 1, 3 => 1],
            [2 => 2, 4 => 2],
            [0 => 3],
        ], Stream::from([3, 1, 2, 1, 2])->segregate()->reindex()->onError(OnError::skip())->toArrayAssoc());
    }
    
    public function test_Segregate_Reindex_default_with_onerror_handler_getLast(): void
    {
        self::assertSame(
            [0 => 3],
            Stream::from([3, 1, 2, 1, 2])->segregate()->reindex()->onError(OnError::skip())->last()->get()
        );
    }
    
    public function test_ReadMany_between_filters(): void
    {
        $data = ['a', 'B', 'c', 'D', 'e', 'F', 'g', 'H', 'i', 'J', 'k', 'L', 'm', 'N', 'o'];
        $big = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q'];
        
        self::assertSame('BDFHJLN', Stream::from($data)->onlyStrings()->readMany(1)->only($big)->toString(''));
        self::assertSame('BFHLN', Stream::from($data)->onlyStrings()->readMany(2)->only($big)->toString(''));
        self::assertSame('BDFHJLN', Stream::from($data)->onlyStrings()->readMany(3)->only($big)->toString(''));
        self::assertSame('BDHJLN', Stream::from($data)->onlyStrings()->readMany(4)->only($big)->toString(''));
    }
    
    public function test_sortLimited_with_one_item(): void
    {
        self::assertSame([3 => 'a'], Stream::from([3 => 'a'])->best(100)->toArrayAssoc());
    }
    
    public function test_filter_null(): void
    {
        self::assertSame([1, 2, 3], Stream::from([1, 2, null, 3])->omit(null)->toArray());
    }
    
    public function test_filterBy_with_null(): void
    {
        $rows = [['foo' => 'a'], ['foo' => null]];
        
        self::assertSame([['foo' => null]], Stream::from($rows)->filterBy('foo', null)->toArray());
    }
}