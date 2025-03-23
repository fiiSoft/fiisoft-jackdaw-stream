<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Filter\IdleFilter;
use FiiSoft\Jackdaw\Filter\Logic\OpAND\FilterANDAny;
use FiiSoft\Jackdaw\Filter\Logic\OpAND\Optim\TwoArgsAND;
use FiiSoft\Jackdaw\Filter\Logic\OpOR\FilterORBoth;
use FiiSoft\Jackdaw\Filter\Logic\OpOR\Optim\TwoArgsOR;
use FiiSoft\Jackdaw\Filter\Time\Day;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Memo\Inspector\SequenceIsEmpty;
use FiiSoft\Jackdaw\Memo\Inspector\SequenceIsFull;
use FiiSoft\Jackdaw\Memo\Memo;
use FiiSoft\Jackdaw\ValueRef\IntNum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FiltersBTest extends TestCase
{
    public function test_OR_value(): void
    {
        $filter = $this->filterOR();
        
        //true: 2,5, false: 3,4
        self::assertTrue($filter->isAllowed(2, 3));
        self::assertFalse($filter->isAllowed(3, 2));
        self::assertFalse($filter->isAllowed(4, 2));
        self::assertTrue($filter->isAllowed(5, 3));
    }
    
    public function test_NOT_OR_value(): void
    {
        $filter = $this->filterNotOR();
        
        //true: 3,4, false: 2,5
        self::assertFalse($filter->isAllowed(2, 3));
        self::assertTrue($filter->isAllowed(3, 2));
        self::assertTrue($filter->isAllowed(4, 2));
        self::assertFalse($filter->isAllowed(5, 3));
    }
    
    public function test_OR_key(): void
    {
        $filter = $this->filterOR(Check::KEY);
        
        //true: 2,5, false: 3,4
        self::assertTrue($filter->isAllowed(3, 2));
        self::assertFalse($filter->isAllowed(2, 3));
        self::assertFalse($filter->isAllowed(2, 4));
        self::assertTrue($filter->isAllowed(3, 5));
    }
    
    public function test_NOT_OR_key(): void
    {
        $filter = $this->filterNotOR(Check::KEY);
        
        //true: 3,4, false: 2,5
        self::assertFalse($filter->isAllowed(3, 2));
        self::assertTrue($filter->isAllowed(2, 3));
        self::assertTrue($filter->isAllowed(2, 4));
        self::assertFalse($filter->isAllowed(3, 5));
    }
    
    public function test_OR_both(): void
    {
        //true: 2,5, false: 3,4
        $filter = $this->filterOR(Check::BOTH);
        
        self::assertFalse($filter->isAllowed(3, 2));
        self::assertFalse($filter->isAllowed(4, 2));
        self::assertFalse($filter->isAllowed(2, 3));
        self::assertFalse($filter->isAllowed(2, 4));
        self::assertFalse($filter->isAllowed(5, 3));
        self::assertFalse($filter->isAllowed(3, 5));
        self::assertFalse($filter->isAllowed(3, 4));
        
        self::assertTrue($filter->isAllowed(2, 2));
        self::assertTrue($filter->isAllowed(5, 2));
        self::assertTrue($filter->isAllowed(2, 5));
    }
    
    public function test_NOT_OR_both(): void
    {
        //true: 3,4, false: 2,5
        $filter = $this->filterNotOR(Check::BOTH);
        
        self::assertFalse($filter->isAllowed(2, 2));
        self::assertFalse($filter->isAllowed(5, 2));
        self::assertFalse($filter->isAllowed(2, 5));

        self::assertTrue($filter->isAllowed(2, 3));
        self::assertTrue($filter->isAllowed(2, 4));
        self::assertTrue($filter->isAllowed(3, 2));
        self::assertTrue($filter->isAllowed(4, 2));
        self::assertTrue($filter->isAllowed(5, 3));
        self::assertTrue($filter->isAllowed(3, 5));
        self::assertTrue($filter->isAllowed(3, 4));
    }
    
    public function test_OR_any(): void
    {
        //true: 2,5, false: 3,4
        $filter = $this->filterOR(Check::ANY);
        
        self::assertTrue($filter->isAllowed(2, 2));
        self::assertTrue($filter->isAllowed(3, 2));
        self::assertTrue($filter->isAllowed(4, 2));
        self::assertTrue($filter->isAllowed(5, 2));
        
        self::assertTrue($filter->isAllowed(2, 3));
        self::assertFalse($filter->isAllowed(3, 3));
        self::assertFalse($filter->isAllowed(4, 3));
        self::assertTrue($filter->isAllowed(5, 3));
        
        self::assertTrue($filter->isAllowed(2, 4));
        self::assertFalse($filter->isAllowed(3, 4));
        self::assertFalse($filter->isAllowed(4, 4));
        self::assertTrue($filter->isAllowed(5, 4));
        
        self::assertTrue($filter->isAllowed(2, 5));
        self::assertTrue($filter->isAllowed(3, 5));
        self::assertTrue($filter->isAllowed(4, 5));
        self::assertTrue($filter->isAllowed(5, 5));
    }
    
    public function test_NOT_OR_any(): void
    {
        //true: 3,4, false: 2,5
        $filter = $this->filterNotOR(Check::ANY);
        
        self::assertFalse($filter->isAllowed(2, 2));
        self::assertFalse($filter->isAllowed(3, 2));
        self::assertFalse($filter->isAllowed(4, 2));
        self::assertFalse($filter->isAllowed(5, 2));
        
        self::assertFalse($filter->isAllowed(2, 3));
        self::assertTrue($filter->isAllowed(3, 3));
        self::assertTrue($filter->isAllowed(4, 3));
        self::assertFalse($filter->isAllowed(5, 3));
        
        self::assertFalse($filter->isAllowed(2, 4));
        self::assertTrue($filter->isAllowed(3, 4));
        self::assertTrue($filter->isAllowed(4, 4));
        self::assertFalse($filter->isAllowed(5, 4));
        
        self::assertFalse($filter->isAllowed(2, 5));
        self::assertFalse($filter->isAllowed(3, 5));
        self::assertFalse($filter->isAllowed(4, 5));
        self::assertFalse($filter->isAllowed(5, 5));
    }
    
    public function test_AND_value(): void
    {
        $filter = $this->filterAND();
        
        //true: 3,4, false: 2,5
        self::assertFalse($filter->isAllowed(2, 3));
        self::assertTrue($filter->isAllowed(3, 2));
        self::assertTrue($filter->isAllowed(4, 2));
        self::assertFalse($filter->isAllowed(5, 3));
    }
    
    public function test_NOT_AND_value(): void
    {
        $filter = $this->filterNotAND();
        
        //true: 2,5, false: 3,4
        self::assertTrue($filter->isAllowed(2, 3));
        self::assertFalse($filter->isAllowed(3, 2));
        self::assertFalse($filter->isAllowed(4, 2));
        self::assertTrue($filter->isAllowed(5, 3));
    }
    
    public function test_AND_key(): void
    {
        $filter = $this->filterAND(Check::KEY);
        
        //true: 3,4, false: 2,5
        self::assertFalse($filter->isAllowed(3, 2));
        self::assertTrue($filter->isAllowed(2, 3));
        self::assertTrue($filter->isAllowed(2, 4));
        self::assertFalse($filter->isAllowed(3, 5));
    }
    
    public function test_NOT_AND_key(): void
    {
        $filter = $this->filterNotAND(Check::KEY);
        
        //true: 2,5, false: 3,4
        self::assertTrue($filter->isAllowed(3, 2));
        self::assertFalse($filter->isAllowed(2, 3));
        self::assertFalse($filter->isAllowed(2, 4));
        self::assertTrue($filter->isAllowed(3, 5));
    }
    
    public function test_AND_both(): void
    {
        //true: 3,4, false: 2,5
        $filter = $this->filterAND(Check::BOTH);
        
        self::assertFalse($filter->isAllowed(2, 2));
        self::assertFalse($filter->isAllowed(3, 2));
        self::assertFalse($filter->isAllowed(4, 2));
        self::assertFalse($filter->isAllowed(5, 2));
        self::assertFalse($filter->isAllowed(2, 3));
        self::assertFalse($filter->isAllowed(2, 4));
        self::assertFalse($filter->isAllowed(2, 5));
        self::assertFalse($filter->isAllowed(5, 3));
        self::assertFalse($filter->isAllowed(3, 5));

        self::assertTrue($filter->isAllowed(3, 4));
        self::assertTrue($filter->isAllowed(4, 3));
    }
    
    public function test_NOT_AND_both(): void
    {
        //true: 2,5, false: 3,4
        $filter = $this->filterNotAND(Check::BOTH);
        
        self::assertTrue($filter->isAllowed(2, 2));
        self::assertTrue($filter->isAllowed(3, 2));
        self::assertTrue($filter->isAllowed(4, 2));
        self::assertTrue($filter->isAllowed(5, 2));
        self::assertTrue($filter->isAllowed(2, 3));
        self::assertTrue($filter->isAllowed(2, 4));
        self::assertTrue($filter->isAllowed(2, 5));
        self::assertTrue($filter->isAllowed(5, 3));
        self::assertTrue($filter->isAllowed(3, 5));

        self::assertFalse($filter->isAllowed(3, 4));
        self::assertFalse($filter->isAllowed(4, 3));
    }
    
    public function test_AND_any(): void
    {
        //true: 3,4, false: 2,5
        $filter = $this->filterAND(Check::ANY);
        
        self::assertFalse($filter->isAllowed(2, 2));
        self::assertTrue($filter->isAllowed(3, 2));
        self::assertTrue($filter->isAllowed(4, 2));
        self::assertFalse($filter->isAllowed(5, 2));
        
        self::assertTrue($filter->isAllowed(2, 3));
        self::assertTrue($filter->isAllowed(3, 3));
        self::assertTrue($filter->isAllowed(4, 3));
        self::assertTrue($filter->isAllowed(5, 3));
        
        self::assertTrue($filter->isAllowed(2, 4));
        self::assertTrue($filter->isAllowed(3, 4));
        self::assertTrue($filter->isAllowed(4, 4));
        self::assertTrue($filter->isAllowed(5, 4));
        
        self::assertFalse($filter->isAllowed(2, 5));
        self::assertTrue($filter->isAllowed(3, 5));
        self::assertTrue($filter->isAllowed(4, 5));
        self::assertFalse($filter->isAllowed(5, 5));
    }
    
    public function test_NOT_AND_any(): void
    {
        //true: 2,5, false: 3,4
        $filter = $this->filterNotAND(Check::ANY);
        
        self::assertTrue($filter->isAllowed(2, 2));
        self::assertFalse($filter->isAllowed(3, 2));
        self::assertFalse($filter->isAllowed(4, 2));
        self::assertTrue($filter->isAllowed(5, 2));
        
        self::assertFalse($filter->isAllowed(2, 3));
        self::assertFalse($filter->isAllowed(3, 3));
        self::assertFalse($filter->isAllowed(4, 3));
        self::assertFalse($filter->isAllowed(5, 3));
        
        self::assertFalse($filter->isAllowed(2, 4));
        self::assertFalse($filter->isAllowed(3, 4));
        self::assertFalse($filter->isAllowed(4, 4));
        self::assertFalse($filter->isAllowed(5, 4));
        
        self::assertTrue($filter->isAllowed(2, 5));
        self::assertFalse($filter->isAllowed(3, 5));
        self::assertFalse($filter->isAllowed(4, 5));
        self::assertTrue($filter->isAllowed(5, 5));
    }
    
    public function test_XOR_value(): void
    {
        //v>=3 xor v>4
        $filter = $this->filterXOR();
        
        self::assertFalse($filter->isAllowed(2, 3));
        self::assertTrue($filter->isAllowed(3, 2));
        self::assertTrue($filter->isAllowed(4, 2));
        self::assertFalse($filter->isAllowed(5, 3));
    }
    
    public function test_NOT_XOR_value(): void
    {
        //v>=3 xnor v>4
        $filter = $this->filterNotXOR();
        
        self::assertTrue($filter->isAllowed(2, 3));
        self::assertFalse($filter->isAllowed(3, 2));
        self::assertFalse($filter->isAllowed(4, 2));
        self::assertTrue($filter->isAllowed(5, 3));
    }
    
    public function test_XOR_key(): void
    {
        //k>=3 xor k>4
        $filter = $this->filterXOR(Check::KEY);
        
        self::assertFalse($filter->isAllowed(3, 2));
        self::assertTrue($filter->isAllowed(2, 3));
        self::assertTrue($filter->isAllowed(2, 4));
        self::assertFalse($filter->isAllowed(3, 5));
    }
    
    public function test_NOT_XOR_key(): void
    {
        //k>=3 xnor k>4
        $filter = $this->filterNotXOR(Check::KEY);
        
        self::assertTrue($filter->isAllowed(3, 2));
        self::assertFalse($filter->isAllowed(2, 3));
        self::assertFalse($filter->isAllowed(2, 4));
        self::assertTrue($filter->isAllowed(3, 5));
    }
    
    public function test_XOR_both(): void
    {
        //(v>=3 xor v>4) && (k>=3 xor k>4)
        $filter = $this->filterXOR(Check::BOTH);
        
        self::assertFalse($filter->isAllowed(2, 2));
        self::assertFalse($filter->isAllowed(3, 2));
        self::assertFalse($filter->isAllowed(4, 2));
        self::assertFalse($filter->isAllowed(5, 2));
        self::assertFalse($filter->isAllowed(2, 3));
        self::assertFalse($filter->isAllowed(2, 4));
        self::assertFalse($filter->isAllowed(2, 5));
        self::assertFalse($filter->isAllowed(5, 3));
        self::assertFalse($filter->isAllowed(3, 5));

        self::assertTrue($filter->isAllowed(3, 4));
        self::assertTrue($filter->isAllowed(4, 3));
    }
    
    public function test_NOT_XOR_both(): void
    {
        //(v>=3 xnor v>4) || (k>=3 xnor k>4)
        $filter = $this->filterNotXOR(Check::BOTH);
        
        self::assertTrue($filter->isAllowed(2, 2));
        self::assertTrue($filter->isAllowed(3, 2));
        self::assertTrue($filter->isAllowed(4, 2));
        self::assertTrue($filter->isAllowed(5, 2));
        self::assertTrue($filter->isAllowed(2, 3));
        self::assertTrue($filter->isAllowed(2, 4));
        self::assertTrue($filter->isAllowed(2, 5));
        self::assertTrue($filter->isAllowed(5, 3));
        self::assertTrue($filter->isAllowed(3, 5));

        self::assertFalse($filter->isAllowed(3, 4));
        self::assertFalse($filter->isAllowed(4, 3));
    }
    
    public function test_XOR_any(): void
    {
        //(v>=3 xor v>4) || (k>=3 xor k>4)
        $filter = $this->filterXOR(Check::ANY);
        
        self::assertFalse($filter->isAllowed(2, 2));
        self::assertTrue($filter->isAllowed(3, 2));
        self::assertTrue($filter->isAllowed(4, 2));
        self::assertFalse($filter->isAllowed(5, 2));
        
        self::assertTrue($filter->isAllowed(2, 3));
        self::assertTrue($filter->isAllowed(3, 3));
        self::assertTrue($filter->isAllowed(4, 3));
        self::assertTrue($filter->isAllowed(5, 3));
        
        self::assertTrue($filter->isAllowed(2, 4));
        self::assertTrue($filter->isAllowed(3, 4));
        self::assertTrue($filter->isAllowed(4, 4));
        self::assertTrue($filter->isAllowed(5, 4));
        
        self::assertFalse($filter->isAllowed(2, 5));
        self::assertTrue($filter->isAllowed(3, 5));
        self::assertTrue($filter->isAllowed(4, 5));
        self::assertFalse($filter->isAllowed(5, 5));
    }
    
    public function test_NOT_XOR_any(): void
    {
        //(v>=3 xnor v>4) && (k>=3 xnor k>4)
        $filter = $this->filterNotXOR(Check::ANY);
        
        self::assertTrue($filter->isAllowed(2, 2));
        self::assertFalse($filter->isAllowed(3, 2));
        self::assertFalse($filter->isAllowed(4, 2));
        self::assertTrue($filter->isAllowed(5, 2));
        
        self::assertFalse($filter->isAllowed(2, 3));
        self::assertFalse($filter->isAllowed(3, 3));
        self::assertFalse($filter->isAllowed(4, 3));
        self::assertFalse($filter->isAllowed(5, 3));
        
        self::assertFalse($filter->isAllowed(2, 4));
        self::assertFalse($filter->isAllowed(3, 4));
        self::assertFalse($filter->isAllowed(4, 4));
        self::assertFalse($filter->isAllowed(5, 4));
        
        self::assertTrue($filter->isAllowed(2, 5));
        self::assertFalse($filter->isAllowed(3, 5));
        self::assertFalse($filter->isAllowed(4, 5));
        self::assertTrue($filter->isAllowed(5, 5));
    }
    
    /**
     * @dataProvider allModes
     */
    #[DataProvider('allModes')]
    public function test_negation_of_filters_should_always_gets_opposite_result(int $mode): void
    {
        $and = $this->filterAND($mode);
        $or = $this->filterOR($mode);
        $xor = $this->filterXOR($mode);
        $xnor = $this->filterXNOR($mode);
        
        $notAnd = $and->negate();
        $notOr = $or->negate();
        $notXor = $xor->negate();
        $notXnor = $xnor->negate();
        
        for ($key = 2; $key <= 5; ++$key) {
            for ($value = 2; $value <= 5; ++$value) {
                $message = 'key_'.$key.'_value_'.$value.'_mode_'.$mode;
                
                self::assertNotSame($or->isAllowed($value, $key), $notOr->isAllowed($value, $key), $message);
                self::assertNotSame($and->isAllowed($value, $key), $notAnd->isAllowed($value, $key), $message);
                self::assertNotSame($xor->isAllowed($value, $key), $notXor->isAllowed($value, $key), $message);
                self::assertNotSame($xnor->isAllowed($value, $key), $notXnor->isAllowed($value, $key), $message);
            }
        }
    }
    
    public function test_XNOR_filter_and_its_negation_in_BOTH_mode(): void
    {
        //(v>=3 xnor v>4) && (k>=3 xnor k>4)
        $xnor = $this->filterXNOR(Check::BOTH);
        self::assertFalse($xnor->isAllowed(3, 2));
        
        //(v>=3 xor v>4) || (k>=3 xor k>4)
        $notXnor = $xnor->negate();
        self::assertTrue($notXnor->isAllowed(3, 2));
    }
    
    public function test_AND_filter_and_its_negation_in_BOTH_mode(): void
    {
        //(v>=3 && v<=4) && (k>=3 && k<=4)
        $and = $this->filterAND(Check::BOTH);
        
        //(v<3 || v>4) || (k<3 || k>4)
        $notAnd = $and->negate();
        
        self::assertFalse($and->isAllowed(3, 2));
        self::assertTrue($notAnd->isAllowed(3, 2));
    }
    
    public function test_OR_filter_and_its_negation_in_BOTH_mode(): void
    {
        //(v<3 || v>4) && (k<3 || k>4)
        $or = $this->filterOR(Check::BOTH);
        
        //(v>=3 && v<=4) || (k>=3 && k<=4)
        $notOr = $or->negate();
        
        self::assertFalse($or->isAllowed(3, 2));
        self::assertTrue($notOr->isAllowed(3, 2));
    }
    
    public function test_AND_in_BOTH_mode_with_negation(): void
    {
        $and = $this->filterAND(Check::BOTH);
        self::assertInstanceOf(TwoArgsAND::class, $and);
        self::assertSame(Check::BOTH, $and->getMode());
        
        self::assertFalse($and->isAllowed(3, 2));
        self::assertTrue($and->isAllowed(4, 3));
        
        self::assertSame($and, $and->inMode(Check::BOTH));
        
        $notAnd = $and->negate();
        self::assertInstanceOf(TwoArgsOR::class, $notAnd);
        self::assertSame(Check::ANY, $notAnd->getMode());
        
        self::assertTrue($notAnd->isAllowed(3, 2));
        self::assertFalse($notAnd->isAllowed(4, 3));
    }
    
    public function test_OR_in_BOTH_mode_with_negation(): void
    {
        $or = $this->filterOR(Check::BOTH);
        self::assertInstanceOf(FilterORBoth::class, $or);
        self::assertSame(Check::BOTH, $or->getMode());
        
        self::assertFalse($or->isAllowed(3, 2));
        self::assertTrue($or->isAllowed(2, 5));
        
        self::assertSame($or, $or->inMode(Check::BOTH));
        
        $notOr = $or->negate();
        self::assertInstanceOf(FilterANDAny::class, $notOr);
        self::assertSame(Check::ANY, $notOr->getMode());
        
        self::assertTrue($notOr->isAllowed(3, 2));
        self::assertFalse($notOr->isAllowed(2, 5));
    }
    
    public function test_filter_onlyWith_allow_nulls(): void
    {
        $this->examineOnlyWithFilter(Filters::onlyWith('a', true), ['a' => null], 'foo');
    }
    
    public function test_filter_onlyWith_disallow_nulls(): void
    {
        $this->examineOnlyWithFilter(Filters::onlyWith('a'), ['a' => 1], 'foo');
    }
    
    private function examineOnlyWithFilter(Filter $filter, $good, $wrong): void
    {
        $value = $filter->checkValue();
        $valueNot = $value->negate();
      
        $mode = 'value';
        $filters = ['positive' => ['value' => $value], 'negative' => ['value' => $valueNot]];
        $patterns = $this->prepareExpectedResults();
        
        foreach (['positive', 'negative'] as $posneg) {
            /* @var $filter Filter */
            $filter = $filters[$posneg][$mode];
            
            /* @var $pattern array */
            $pattern = $patterns[$posneg][$mode];
            
            $case = $posneg.'_'.$mode;
            self::assertSame($pattern[0], $filter->isAllowed($good, $good), $case.'_0');
            self::assertSame($pattern[1], $filter->isAllowed($good, $wrong), $case.'_1');
            self::assertSame($pattern[2], $filter->isAllowed($wrong, $good), $case.'_2');
            self::assertSame($pattern[3], $filter->isAllowed($wrong, $wrong), $case.'_3');
        }
    }
    
    /**
     * @dataProvider allModes
     */
    #[DataProvider('allModes')]
    public function test_double_negation_of_AND_should_give_initial_filter(int $mode): void
    {
        $filter = $this->filterAND($mode);
        
        self::assertTrue($filter->equals($filter->negate()->negate()));
    }
    
    /**
     * @dataProvider allModes
     */
    #[DataProvider('allModes')]
    public function test_double_negation_of_OR_should_give_initial_filter(int $mode): void
    {
        $filter = $this->filterOR($mode);
        
        self::assertTrue($filter->equals($filter->negate()->negate()));
    }
    
    public static function allModes(): array
    {
        return [[Check::VALUE], [Check::KEY], [Check::BOTH], [Check::ANY]];
    }
    
    private function filterAND(int $mode = Check::VALUE): Filter
    {
        return Filters::AND(Filters::greaterOrEqual(3), Filters::lessOrEqual(4))->inMode($mode);
    }
    
    private function filterOR(int $mode = Check::VALUE): Filter
    {
        return Filters::OR(Filters::lessThan(3), Filters::greaterThan(4))->inMode($mode);
    }
    
    private function filterNotAND(int $mode = Check::VALUE): Filter
    {
        return $this->filterNOT($this->filterAND($mode));
    }
    
    private function filterNotOR(int $mode = Check::VALUE): Filter
    {
        return $this->filterNOT($this->filterOR($mode));
    }
    
    private function filterXOR(int $mode = Check::VALUE): Filter
    {
        return Filters::XOR(Filters::greaterOrEqual(3), Filters::greaterThan(4))->inMode($mode);
    }
    
    private function filterNotXOR(int $mode = Check::VALUE): Filter
    {
        return $this->filterNOT($this->filterXOR($mode));
    }
    
    private function filterXNOR(int $mode = Check::VALUE): Filter
    {
        return Filters::XNOR(Filters::greaterOrEqual(3), Filters::greaterThan(4))->inMode($mode);
    }
    
    private function filterNotXNOR(int $mode = Check::VALUE): Filter
    {
        return $this->filterNOT($this->filterXNOR($mode));
    }
    
    private function filterNOT(Filter $filter): Filter
    {
        return Filters::NOT($filter);
    }
    
    public function test_filters_provides_fluent_interface_to_build_compound_nested_filters(): void
    {
        $n = Filters::number(Check::BOTH);
        $s = Filters::string(Check::ANY);
        
        $isValidNumber = $n->isInt()->and($n->ge(3)->and($n->le(4)));
        $isValidString = $s->isString()->and($s->startsWith('foo')->or($s->endsWith('bar')));
        
        $filter = $isValidNumber->or($isValidString)->checkValue();
        
        self::assertFalse($filter->isAllowed(2, 2));
        self::assertTrue($filter->isAllowed(3, 2));
        self::assertTrue($filter->isAllowed(4, 2));
        self::assertFalse($filter->isAllowed(5, 2));
        
        self::assertTrue($filter->isAllowed('foozoe', 2));
        self::assertTrue($filter->isAllowed('joebar', 2));
        self::assertFalse($filter->isAllowed('zoejoe', 2));
    }
    
    public function test_filter_isBool(): void
    {
        $this->examineFilter(Filters::isBool(), true, 1);
    }
    
    public function test_filter_isInt(): void
    {
        $this->examineFilter(Filters::isInt(), 1, 'foo');
    }
    
    public function test_filter_isFloat(): void
    {
        $this->examineFilter(Filters::isFloat(), 1.0, 5);
    }
    
    public function test_filter_isString(): void
    {
        $this->examineFilter(Filters::isString(), 'foo', 1);
    }
    
    public function test_filter_isDateTime(): void
    {
        $this->examineFilter(Filters::isDateTime(), 'now', 'foo');
    }
    
    public function test_filter_isCountable(): void
    {
        $this->examineFilter(Filters::size()->isCountable(), ['a'], 5);
    }
    
    public function test_filter_isEmpty(): void
    {
        $this->examineFilter(Filters::isEmpty(), '', 'foo');
    }
    
    public function test_filter_isNull(): void
    {
        $this->examineFilter(Filters::isNull(), null, 'foo');
    }
    
    public function test_filter_isNumeric(): void
    {
        $this->examineFilter(Filters::isNumeric(), '25.0', 'foo');
    }
    
    public function test_filter_notEmpty(): void
    {
        $this->examineFilter(Filters::notEmpty(), '25.0', '');
    }
    
    public function test_filter_isArray(): void
    {
        $this->examineFilter(Filters::isArray(), [], '');
    }
    
    public function test_filter_notNull(): void
    {
        $this->examineFilter(Filters::notNull(), '', null);
    }
    
    public function test_filter_time_is(): void
    {
        $time = 'tomorrow midnight';
        $wrongTime = '2020-02-02 12:00:00';
        
        $this->examineFilter(Filters::time()->is($time), $time, $wrongTime);
    }
    
    public function test_filter_time_isNot(): void
    {
        $time = '2015-05-05 12:12:12';
        $wrongTime = '2020-02-02 12:00:00';
        
        $this->examineFilter(Filters::time()->isNot($time), $wrongTime, $time);
    }
    
    public function test_filter_time_before(): void
    {
        [$d1, $d2] = $this->dates();
        
        $this->examineFilter(Filters::time()->before($d2), $d1, $d2);
    }
    
    public function test_filter_time_from(): void
    {
        [, $d2, $d3] = $this->dates();
        
        $this->examineFilter(Filters::time()->from($d3), $d3, $d2);
    }
    
    public function test_filter_time_after(): void
    {
        [, $d2, $d3] = $this->dates();
        
        $this->examineFilter(Filters::time()->after($d2), $d3, $d2);
    }
    
    public function test_filter_time_until(): void
    {
        [$d1, $d2, $d3] = $this->dates();
        
        $this->examineFilter(Filters::time()->until($d2), $d1, $d3);
    }
    
    public function test_filter_time_between(): void
    {
        [, $d2, $d3, $d4] = $this->dates();
        
        $this->examineFilter(Filters::time()->between($d2, $d3), $d2, $d4);
    }
    
    public function test_filter_time_outside(): void
    {
        [$d1, $d2, $d3] = $this->dates();
        
        $this->examineFilter(Filters::time()->outside($d2, $d3), $d1, $d3);
    }
    
    public function test_filter_time_inside(): void
    {
        [$d1, $d2, $d3, $d4] = $this->dates();
        
        $this->examineFilter(Filters::time()->inside($d2, $d4), $d3, $d1);
    }
    
    public function test_filter_time_notInside(): void
    {
        [$d1, $d2, $d3, $d4] = $this->dates();
        
        $this->examineFilter(Filters::time()->notInside($d2, $d4), $d1, $d3);
    }
    
    public function test_filter_time_inSet(): void
    {
        [$d1, $d2, $d3, $d4] = $this->dates();
        
        $this->examineFilter(Filters::time()->inSet([$d1, $d3, $d4]), $d3, $d2);
    }
    
    public function test_filter_time_notInSet(): void
    {
        [$d1, $d2, $d3, $d4] = $this->dates();
        
        $this->examineFilter(Filters::time()->notInSet([$d1, $d3, $d4]), $d2, $d3);
    }
    
    private function dates(): array
    {
        $d1 = new \DateTime('2024-01-31 15:00:00');
        $d2 = '2024-01-31 15:30:00';
        $d3 = '2024-01-31 16:00:00';
        $d4 = new \DateTimeImmutable('2024-01-31 17:30:00');
        $d5 = '2024-01-31 18:00:00';
        
        return [$d1, $d2, $d3, $d4, $d5];
    }
    
    public function test_filter_string_contains_case_sensitive(): void
    {
        $this->examineFilter(Filters::string()->contains('foo'), 'aaafooaaa', 'aaaFOOaaa');
    }
    
    public function test_filter_string_contains_case_insensitive(): void
    {
        $this->examineFilter(Filters::contains('foo', true), 'aaaFOOaaa', 'aaabaraaa');
    }
    
    public function test_filter_string_notContains_case_sensitive(): void
    {
        $this->examineFilter(Filters::string()->notContains('foo'), 'aaaFOOaaa', 'aaafooaaa');
    }
    
    public function test_filter_string_notContains_case_insensitive(): void
    {
        $this->examineFilter(Filters::string()->notContains('foo', true), 'aaabaraaa', 'aaaFOOaaa');
    }
    
    public function test_filter_string_endsWith_case_sensitive(): void
    {
        $this->examineFilter(Filters::endsWith('foo'), 'aaafoo', 'aaaFOO');
    }
    
    public function test_filter_string_endsWith_case_insensitive(): void
    {
        $this->examineFilter(Filters::string()->endsWith('foo', true), 'aaaFOO', 'aaabar');
    }
    
    public function test_filter_string_notEndsWith_case_sensitive(): void
    {
        $this->examineFilter(Filters::string()->notEndsWith('foo'), 'aaaFOO', 'aaafoo');
    }
    
    public function test_filter_string_notEndsWith_case_insensitive(): void
    {
        $this->examineFilter(Filters::string()->notEndsWith('foo', true), 'aaabar', 'aaaFOO');
    }
    
    public function test_filter_string_inSet_case_sensitive(): void
    {
        $this->examineFilter(Filters::string()->inSet(['aaa', 'foo', 'bbb']), 'foo', 'FOO');
    }
    
    public function test_filter_string_inSet_case_insensitive(): void
    {
        $this->examineFilter(Filters::string()->inSet(['aaa', 'foo', 'bbb'], true), 'Foo', 1);
    }
    
    public function test_filter_string_notInSet_case_sensitive(): void
    {
        $this->examineFilter(Filters::string()->notInSet(['aaa', 'foo', 'bbb']), 'FOO', 'foo');
    }
    
    public function test_filter_string_notInSet_case_insensitive(): void
    {
        $this->examineFilter(Filters::string()->notInSet(['aaa', 'foo', 'bbb'], true), 'zoo', 'Foo');
    }
    
    public function test_filter_string_is_case_sensitive(): void
    {
        $this->examineFilter(Filters::string()->is('foo'), 'foo', 'FOO');
    }
    
    public function test_filter_string_is_case_insensitive(): void
    {
        $this->examineFilter(Filters::string()->is('foo', true), 'Foo', 'zoo');
    }
    
    public function test_filter_string_isNot_case_sensitive(): void
    {
        $this->examineFilter(Filters::string()->isNot('foo'), 'FOO', 'foo');
    }
    
    public function test_filter_string_isNot_case_insensitive(): void
    {
        $this->examineFilter(Filters::string()->isNot('foo', true), 'zoo', 'Foo');
    }
    
    public function test_filter_string_startsWith_case_sensitive(): void
    {
        $this->examineFilter(Filters::startsWith('foo'), 'foo', 'FOO');
    }
    
    public function test_filter_string_startsWith_case_insensitive(): void
    {
        $this->examineFilter(Filters::string()->startsWith('foo', true), 'Foo', 'zoo');
    }
    
    public function test_filter_string_notStartsWith_case_sensitive(): void
    {
        $this->examineFilter(Filters::string()->notStartsWith('foo'), 'FOO', 'foo');
    }
    
    public function test_filter_string_notStartsWith_case_insensitive(): void
    {
        $this->examineFilter(Filters::string()->notStartsWith('foo', true), 'zoo', 'Foo');
    }
    
    public function test_filter_size_count_equal(): void
    {
        $this->examineFilter(Filters::size()->eq(1), ['a'], []);
    }
    
    public function test_filter_size_count_notEqual(): void
    {
        $this->examineFilter(Filters::size()->ne(1), [], ['a']);
    }
    
    public function test_filter_size_count_lessThan(): void
    {
        $this->examineFilter(Filters::size()->lt(1), [], ['a']);
    }
    
    public function test_filter_size_count_lessOrEqual(): void
    {
        $this->examineFilter(Filters::size()->le(1), ['a'], [1, 2]);
    }
    
    public function test_filter_size_count_greaterThan(): void
    {
        $this->examineFilter(Filters::size()->gt(0), ['a'], []);
    }
    
    public function test_filter_size_count_greaterOrEqual(): void
    {
        $this->examineFilter(Filters::size()->ge(1), ['a'], []);
    }
    
    public function test_filter_size_count_between(): void
    {
        $this->examineFilter(Filters::size()->between(1, 2), [1], [1,2,3]);
    }
    
    public function test_filter_size_count_outside(): void
    {
        $this->examineFilter(Filters::size()->outside(1, 2), [1,2,3], [1,2]);
    }
    
    public function test_filter_size_count_inside(): void
    {
        $this->examineFilter(Filters::size()->inside(1, 3), [1,2], [1,2,3]);
    }
    
    public function test_filter_size_count_notInside(): void
    {
        $this->examineFilter(Filters::size()->notInside(1, 3), [1,2,3], [1,2]);
    }
    
    public function test_filter_size_length_equal(): void
    {
        $this->examineFilter(Filters::length()->eq(1), 'a', 'bb');
    }
    
    public function test_filter_size_length_notEqual(): void
    {
        $this->examineFilter(Filters::length()->ne(1), 'bb', 'a');
    }
    
    public function test_filter_size_length_lessThan(): void
    {
        $this->examineFilter(Filters::length()->lt(2), 'a', 'bb');
    }
    
    public function test_filter_size_length_lessOrEqual(): void
    {
        $this->examineFilter(Filters::length()->le(1), 'a', 'ab');
    }
    
    public function test_filter_size_length_greaterThan(): void
    {
        $this->examineFilter(Filters::length()->gt(1), 'bb', 'a');
    }
    
    public function test_filter_size_length_greaterOrEqual(): void
    {
        $this->examineFilter(Filters::length()->ge(2), 'aa', 'b');
    }
    
    public function test_filter_size_length_between(): void
    {
        $this->examineFilter(Filters::length()->between(1, 2), 'a', 'abc');
    }
    
    public function test_filter_size_length_outside(): void
    {
        $this->examineFilter(Filters::length()->outside(1, 2), 'abc', 'ab');
    }
    
    public function test_filter_size_length_inside(): void
    {
        $this->examineFilter(Filters::length()->inside(1, 3), 'ab', 'abc');
    }
    
    public function test_filter_size_length_notInside(): void
    {
        $this->examineFilter(Filters::length()->notInside(1, 3), 'abc', 'ab');
    }
    
    public function test_filter_equal(): void
    {
        $this->examineFilter(Filters::equal(3), '3', 5);
    }
    
    public function test_filter_notEqual(): void
    {
        $this->examineFilter(Filters::notEqual(3), 5, '3');
    }
    
    public function test_filter_same(): void
    {
        $this->examineFilter(Filters::same(3), 3, '3');
    }
    
    public function test_filter_notSame(): void
    {
        $this->examineFilter(Filters::notSame(3), '3', 3);
    }
    
    public function test_filter_number_between(): void
    {
        $this->examineFilter(Filters::number()->between(2, 3), 2, 1);
    }
    
    public function test_filter_number_outside(): void
    {
        $this->examineFilter(Filters::number()->outside(2, 3), 1, 3);
    }
    
    public function test_filter_number_equal(): void
    {
        $this->examineFilter(Filters::number()->eq(1), 1, 2);
    }
    
    public function test_filter_number_notEqual(): void
    {
        $this->examineFilter(Filters::number()->ne(1), 2, 1);
    }
    
    public function test_filter_number_greaterOrEqual(): void
    {
        $this->examineFilter(Filters::number()->ge(2), 2, 1);
    }
    
    public function test_filter_number_lessThan(): void
    {
        $this->examineFilter(Filters::number()->lt(2), 1, 2);
    }
    
    public function test_filter_number_greaterThan(): void
    {
        $this->examineFilter(Filters::number()->gt(1), 2, 1);
    }
    
    public function test_filter_number_lessOrEqual(): void
    {
        $this->examineFilter(Filters::number()->le(1), 1, 2);
    }
    
    public function test_filter_number_inside(): void
    {
        $this->examineFilter(Filters::number()->inside(1, 2), 1.5, 1);
    }
    
    public function test_filter_number_notInside(): void
    {
        $this->examineFilter(Filters::number()->notInside(1, 2), 1, 1.5);
    }
    
    public function test_filter_number_isEven(): void
    {
        $this->examineFilter(Filters::number()->isEven(), 4, 3);
    }
    
    public function test_filter_number_isOdd(): void
    {
        $this->examineFilter(Filters::number()->isOdd(), 3, 4);
    }
    
    public function test_filter_onlyIn_ints(): void
    {
        $this->examineFilter(Filters::onlyIn([1, 3]), 3, 2);
    }
    
    public function test_filter_onlyIn_strings(): void
    {
        $this->examineFilter(Filters::onlyIn(['a', 'b']), 'a', 'c');
    }
    
    public function test_filter_onlyIn_others(): void
    {
        $this->examineFilter(Filters::onlyIn([1.0, 2.0]), 1.0, 1.5);
    }
    
    public function test_filter_onlyIn_mixed(): void
    {
        $this->examineFilter(Filters::onlyIn([1, '1', 1.0, '2']), 1, 2);
        $this->examineFilter(Filters::onlyIn([1, '1', 1.0, '2']), '1', 2);
        $this->examineFilter(Filters::onlyIn([1, '1', 1.0, '2']), 1.0, 2);
    }
    
    public function test_filter_one_arg_callable(): void
    {
        $this->examineFilter(Filters::getAdapter(static fn($val): bool => $val === 1), 1, 2);
    }
    
    public function test_isDay_all_variations(): void
    {
        $this->examineFilter(Filters::time()->isDay(Day::FRI), '2024-04-19', '2024-04-20');
    }
    
    public function test_isNotDay_all_variations(): void
    {
        $this->examineFilter(Filters::time()->isNotDay(Day::FRI), '2024-04-20', '2024-04-19');
    }
    
    private function examineFilter(Filter $filter, $good, $wrong): void
    {
        foreach ($this->testParams($filter, $good, $wrong) as $case => $testParam) {
            /* @var $filter Filter */
            /* @var $pattern array */
            [$filter, $pattern, $good, $wrong] = $testParam;
            
            self::assertSame($pattern[0], $filter->isAllowed($good, $good), $case.'_0');
            self::assertSame($pattern[1], $filter->isAllowed($good, $wrong), $case.'_1');
            self::assertSame($pattern[2], $filter->isAllowed($wrong, $good), $case.'_2');
            self::assertSame($pattern[3], $filter->isAllowed($wrong, $wrong), $case.'_3');
        }
    }
    
    private function testParams(Filter $filter, $good, $wrong): iterable
    {
        $filters = $this->prepareFiltersForTest($filter);
        $patterns = $this->prepareExpectedResults();
        
        foreach (['positive', 'negative'] as $posneg) {
            foreach (['value', 'key', 'both', 'any'] as $mode) {
                yield $posneg.'_'.$mode => [
                    $filters[$posneg][$mode],
                    $patterns[$posneg][$mode],
                    $good,
                    $wrong,
                ];
            }
        }
    }
    
    private function prepareExpectedResults(): array
    {
        return [
            'positive' => [
                'value' => [true, true, false, false],
                'key' => [true, false, true, false],
                'both' => [true, false, false, false],
                'any' => [true, true, true, false],
            ],
            'negative' => [
                'value' => [false, false, true, true],
                'key' => [false, true, false, true],
                'both' => [false, false, false, true],
                'any' => [false, true, true, true],
            ],
        ];
    }
    
    private function prepareFiltersForTest(Filter $filter): array
    {
        $value = $filter->checkValue();
        $key = $value->checkKey();
        $both = $key->checkBoth();
        $any = $both->checkAny();
        
        $notValue = Filters::NOT($filter)->checkValue();
        $notKey = $notValue->checkKey();
        $notBoth = $notKey->checkBoth();
        $notAny = $notBoth->checkAny();
        
        return [
            'positive' => ['value' => $value, 'key' => $key, 'both' => $both, 'any' => $any],
            'negative' => ['value' => $notValue, 'key' => $notKey, 'both' => $notBoth, 'any' => $notAny],
        ];
    }
    
    public function test_example_of_string_filter_bot_not_contains_case_insensitive(): void
    {
        $filter = Filters::string()->not()->contains('foo')->ignoreCase()->checkBoth();
        
        self::assertTrue($filter->isAllowed('barbar', 'zoezoe'));
        
        self::assertFalse($filter->isAllowed('barfoObar', 'zoezoe'));
        self::assertFalse($filter->isAllowed('barbar', 'zoeFoozoe'));
        self::assertFalse($filter->isAllowed('barfOobar', 'zoefoozoe'));
    }
    
    public function test_example_of_string_filter_any_not_contains_case_sensitive(): void
    {
        $filter = Filters::string()->not()->contains('foo')->caseSensitive()->checkAny();
        
        self::assertTrue($filter->isAllowed('barFOObar', 'zoefoozoe'));
        self::assertTrue($filter->isAllowed('zoefoozoe', 'barFOObar'));
        self::assertTrue($filter->isAllowed('zoeFOOzoe', 'barFOObar'));
        
        self::assertFalse($filter->isAllowed('barfoobar', 'zoefoozoe'));
    }
    
    public function test_TimeFilter_provides_isDateTime_filter(): void
    {
        $filter = Filters::time()->isDateTime();
        
        self::assertTrue($filter->isAllowed('now'));
        self::assertFalse($filter->isAllowed('then'));
    }
    
    public function test_number_between_equal_values(): void
    {
        $filter = Filters::number()->between(1, 1);
        
        self::assertFalse($filter->isAllowed(0));
        self::assertTrue($filter->isAllowed(1));
        self::assertFalse($filter->isAllowed(2));
    }
    
    public function test_number_outside_equal_values(): void
    {
        $filter = Filters::number()->outside(1, 1);
        
        self::assertTrue($filter->isAllowed(0));
        self::assertFalse($filter->isAllowed(1));
        self::assertTrue($filter->isAllowed(2));
    }
    
    public function test_onlyIn_with_empty_arguments_returns_false_always(): void
    {
        self::assertEquals(IdleFilter::false(), Filters::onlyIn([]));
        self::assertEquals(IdleFilter::false(Check::KEY), Filters::onlyIn([], Check::KEY));
    }
    
    public function test_zero_arg_callable_filter_with_constant_responses(): void
    {
        $filter = Filters::getAdapter(static fn(): bool => true);
        self::assertTrue($filter->isAllowed(1));
        
        $value = $filter->checkValue();
        $key = $value->checkKey();
        $both = $key->checkBoth();
        $any = $both->checkAny();
        
        self::assertSame($filter, $value);
        self::assertSame($filter, $key);
        self::assertSame($filter, $both);
        self::assertSame($filter, $any);
        
        $notValue = $value->negate();
        $notKey = $key->negate();
        $notBoth = $both->negate();
        $notAny = $any->negate();
        
        self::assertFalse($notValue->isAllowed(1));
        self::assertFalse($notKey->isAllowed(1));
        self::assertFalse($notBoth->isAllowed(1));
        self::assertFalse($notAny->isAllowed(1));
        
        self::assertSame($notKey, $notKey->checkValue());
        self::assertSame($notValue, $notValue->checkKey());
        self::assertSame($notAny, $notAny->checkBoth());
        self::assertSame($notBoth, $notBoth->checkAny());
        
        self::assertTrue($notValue->negate()->isAllowed(1));
        self::assertTrue($notKey->negate()->isAllowed(1));
        self::assertTrue($notBoth->negate()->isAllowed(1));
        self::assertTrue($notAny->negate()->isAllowed(1));
    }
    
    public function test_zero_arg_callable_filter_with_changing_responses(): void
    {
        $responses = [true, true, false, true];
        
        $filter = Filters::getAdapter(static function () use (&$responses) {
            return empty($responses) ? false : \array_shift($responses);
        });
        
        self::assertTrue($filter->isAllowed(1));
        self::assertTrue($filter->isAllowed(1));
        self::assertFalse($filter->isAllowed(1));
        self::assertTrue($filter->isAllowed(1));
        self::assertFalse($filter->isAllowed(1));
    }
    
    public function test_negation_of_zero_arg_callable_filter_with_changing_responses(): void
    {
        $responses = [true, true, false, true];
        
        $filter = Filters::getAdapter(static function () use (&$responses) {
            return empty($responses) ? false : \array_shift($responses);
        })->negate();
        
        self::assertFalse($filter->isAllowed(1));
        self::assertFalse($filter->isAllowed(1));
        self::assertTrue($filter->isAllowed(1));
        self::assertFalse($filter->isAllowed(1));
        self::assertTrue($filter->isAllowed(1));
    }
    
    public function test_two_args_callable_filter(): void
    {
        $filter = Filters::getAdapter(static fn($val, $key): bool => $val === 1 || $key === 1);
        
        self::assertTrue($filter->isAllowed(1, 2));
        self::assertTrue($filter->isAllowed(2, 1));
        self::assertTrue($filter->isAllowed(1, 1));
        self::assertFalse($filter->isAllowed(2, 2));
        
        self::assertSame($filter, $filter->checkValue());
        self::assertSame($filter, $filter->checkKey());
        self::assertSame($filter, $filter->checkBoth());
        self::assertSame($filter, $filter->checkAny());
    }
    
    public function test_negation_of_two_args_callable_filter(): void
    {
        $filter = Filters::NOT(static fn($val, $key): bool => $val === 1 || $key === 1);
        
        self::assertFalse($filter->isAllowed(1, 2));
        self::assertFalse($filter->isAllowed(2, 1));
        self::assertFalse($filter->isAllowed(1, 1));
        self::assertTrue($filter->isAllowed(2, 2));
    }
    
    public function test_adapter_of_SequenceInspector_is_insensitive_to_the_mode_change(): void
    {
        $filter = Filters::getAdapter(Memo::sequence()->inspect(static fn(): bool => true));
        
        self::assertSame($filter, $filter->checkAny());
        self::assertSame($filter, $filter->checkBoth());
        self::assertSame($filter, $filter->checkKey());
        self::assertSame($filter, $filter->checkValue());
    }
    
    public function test_negation_of_SequenceInspector_filter_adapter(): void
    {
        $sequence = Memo::sequence(1);
        
        $isEmpty = Filters::getAdapter($sequence->inspect(new SequenceIsEmpty()));
        $isFull = Filters::getAdapter($sequence->inspect(new SequenceIsFull()));
        
        self::assertTrue($isEmpty->isAllowed('a', 'g'));
        self::assertFalse($isFull->isAllowed('a', 'g'));
        
        self::assertFalse($isEmpty->negate()->isAllowed('a', 'g'));
        self::assertTrue($isFull->negate()->isAllowed('a', 'g'));
        
        $sequence->write(1, 0);
        
        self::assertFalse($isEmpty->isAllowed('a', 'g'));
        self::assertTrue($isFull->isAllowed('a', 'g'));
        
        self::assertTrue($isEmpty->negate()->isAllowed('a', 'g'));
        self::assertFalse($isFull->negate()->isAllowed('a', 'g'));
    }
    
    public function test_filter_variable_does_not_relay_on_mode(): void
    {
        $var = 3;
        $filter = Filters::readFrom($var)->is()->same(1);
        
        self::assertSame($filter, $filter->checkValue());
        self::assertSame($filter, $filter->checkKey());
        self::assertSame($filter, $filter->checkAny());
        self::assertSame($filter, $filter->checkBoth());
    }
    
    public function test_filter_variable_reference_is(): void
    {
        $var = 3;
        $filter = Filters::readFrom($var)->is();
        
        self::assertTrue($filter->same(3)->isAllowed('a'));
        self::assertFalse($filter->same(2)->isAllowed('a'));
        
        self::assertTrue($filter->notSame(2)->isAllowed('a'));
        self::assertFalse($filter->notSame(3)->isAllowed('a'));
        
        self::assertTrue($filter->equal('3')->isAllowed('a'));
        self::assertFalse($filter->equal('2')->isAllowed('a'));
        
        self::assertTrue($filter->notEqual('2')->isAllowed('a'));
        self::assertFalse($filter->notEqual('3')->isAllowed('a'));
    }
    
    public function test_filter_variable_reference_length(): void
    {
        $var = 'foo';
        $filter = Filters::readFrom($var)->length();
        
        self::assertTrue($filter->isString()->isAllowed('a'));
        self::assertFalse($filter->isString()->negate()->isAllowed('a'));
        
        self::assertTrue($filter->eq(3)->isAllowed('a'));
        self::assertFalse($filter->eq(2)->isAllowed('a'));
        
        self::assertFalse($filter->not()->eq(3)->isAllowed('a'));
        self::assertTrue($filter->not()->eq(2)->isAllowed('a'));
        
        self::assertTrue($filter->ne(2)->isAllowed('a'));
        self::assertFalse($filter->ne(3)->isAllowed('a'));
        
        self::assertTrue($filter->le(4)->isAllowed('a'));
        self::assertFalse($filter->le(2)->isAllowed('a'));
        
        self::assertTrue($filter->ge(2)->isAllowed('a'));
        self::assertFalse($filter->ge(4)->isAllowed('a'));
        
        self::assertTrue($filter->lt(4)->isAllowed('a'));
        self::assertFalse($filter->lt(3)->isAllowed('a'));
        
        self::assertTrue($filter->gt(2)->isAllowed('a'));
        self::assertFalse($filter->gt(3)->isAllowed('a'));
        
        self::assertTrue($filter->inside(2, 4)->isAllowed('a'));
        self::assertFalse($filter->inside(3, 4)->isAllowed('a'));
        
        self::assertTrue($filter->outside(4, 8)->isAllowed('a'));
        self::assertFalse($filter->outside(2, 4)->isAllowed('a'));
        
        self::assertTrue($filter->between(3, 4)->isAllowed('a'));
        self::assertFalse($filter->between(4, 5)->isAllowed('a'));
        
        self::assertTrue($filter->notInside(3, 4)->isAllowed('a'));
        self::assertFalse($filter->notInside(2, 4)->isAllowed('a'));
    }
    
    public function test_filter_variable_reference_size(): void
    {
        $var = ['a', 'b', 'c'];
        $filter = Filters::readFrom($var)->size();
        
        self::assertTrue($filter->isCountable()->isAllowed('a'));
        self::assertFalse($filter->isCountable()->negate()->isAllowed('a'));
        
        self::assertTrue($filter->eq(3)->isAllowed('a'));
        self::assertFalse($filter->eq(2)->isAllowed('a'));
        
        self::assertFalse($filter->not()->eq(3)->isAllowed('a'));
        self::assertTrue($filter->not()->eq(2)->isAllowed('a'));
        
        self::assertTrue($filter->ne(2)->isAllowed('a'));
        self::assertFalse($filter->ne(3)->isAllowed('a'));
        
        self::assertTrue($filter->le(4)->isAllowed('a'));
        self::assertFalse($filter->le(2)->isAllowed('a'));
        
        self::assertTrue($filter->ge(2)->isAllowed('a'));
        self::assertFalse($filter->ge(4)->isAllowed('a'));
        
        self::assertTrue($filter->lt(4)->isAllowed('a'));
        self::assertFalse($filter->lt(3)->isAllowed('a'));
        
        self::assertTrue($filter->gt(2)->isAllowed('a'));
        self::assertFalse($filter->gt(3)->isAllowed('a'));
        
        self::assertTrue($filter->inside(2, 4)->isAllowed('a'));
        self::assertFalse($filter->inside(3, 4)->isAllowed('a'));
        
        self::assertTrue($filter->outside(4, 8)->isAllowed('a'));
        self::assertFalse($filter->outside(2, 4)->isAllowed('a'));
        
        self::assertTrue($filter->between(3, 4)->isAllowed('a'));
        self::assertFalse($filter->between(4, 5)->isAllowed('a'));
        
        self::assertTrue($filter->notInside(3, 4)->isAllowed('a'));
        self::assertFalse($filter->notInside(2, 4)->isAllowed('a'));
    }
    
    public function test_filter_variable_reference_number(): void
    {
        $var = 3;
        $filter = Filters::readFrom($var)->number();
        
        self::assertTrue($filter->isNumeric()->isAllowed('a'));
        self::assertTrue($filter->isInt()->isAllowed('a'));
        self::assertFalse($filter->isFloat()->isAllowed('a'));
        
        self::assertTrue($filter->isOdd()->isAllowed('a'));
        self::assertFalse($filter->isEven()->isAllowed('a'));
        
        self::assertFalse($filter->not()->isOdd()->isAllowed('a'));
        self::assertTrue($filter->not()->isEven()->isAllowed('a'));
        
        self::assertFalse($filter->isOdd()->negate()->isAllowed('a'));
        self::assertTrue($filter->isEven()->negate()->isAllowed('a'));
        
        self::assertTrue($filter->not()->isOdd()->negate()->isAllowed('a'));
        self::assertFalse($filter->not()->isEven()->negate()->isAllowed('a'));
        
        self::assertTrue($filter->eq(3)->isAllowed('a'));
        self::assertFalse($filter->eq(2)->isAllowed('a'));
        
        self::assertTrue($filter->ne(2)->isAllowed('a'));
        self::assertFalse($filter->ne(3)->isAllowed('a'));
        
        self::assertTrue($filter->le(4)->isAllowed('a'));
        self::assertFalse($filter->le(2)->isAllowed('a'));
        
        self::assertTrue($filter->ge(2)->isAllowed('a'));
        self::assertFalse($filter->ge(4)->isAllowed('a'));
        
        self::assertTrue($filter->lt(4)->isAllowed('a'));
        self::assertFalse($filter->lt(3)->isAllowed('a'));
        
        self::assertTrue($filter->gt(2)->isAllowed('a'));
        self::assertFalse($filter->gt(3)->isAllowed('a'));
        
        self::assertTrue($filter->inside(2, 4)->isAllowed('a'));
        self::assertFalse($filter->inside(3, 4)->isAllowed('a'));
        
        self::assertTrue($filter->outside(4, 8)->isAllowed('a'));
        self::assertFalse($filter->outside(2, 4)->isAllowed('a'));
        
        self::assertTrue($filter->between(3, 4)->isAllowed('a'));
        self::assertFalse($filter->between(4, 5)->isAllowed('a'));
        
        self::assertTrue($filter->notInside(3, 4)->isAllowed('a'));
        self::assertFalse($filter->notInside(2, 4)->isAllowed('a'));
    }
    
    public function test_filter_variable_reference_string(): void
    {
        $var = 'zoe';
        $filter = Filters::readFrom($var)->string();
        
        self::assertTrue($filter->isString()->isAllowed('a'));
        
        self::assertTrue($filter->is('zoe')->isAllowed('a'));
        self::assertFalse($filter->is('bar')->isAllowed('a'));
        
        self::assertTrue($filter->isNot('bar')->isAllowed('a'));
        self::assertFalse($filter->isNot('zoe')->isAllowed('a'));
        
        self::assertTrue($filter->startsWith('zo')->isAllowed('a'));
        self::assertFalse($filter->startsWith('oe')->isAllowed('a'));
        
        self::assertTrue($filter->notStartsWith('oe')->isAllowed('a'));
        self::assertFalse($filter->notStartsWith('zo')->isAllowed('a'));
        
        self::assertTrue($filter->endsWith('oe')->isAllowed('a'));
        self::assertFalse($filter->endsWith('zo')->isAllowed('a'));
        
        self::assertTrue($filter->notEndsWith('zo')->isAllowed('a'));
        self::assertFalse($filter->notEndsWith('oe')->isAllowed('a'));
        
        self::assertTrue($filter->notEndsWith('zo')->isAllowed('a'));
        self::assertFalse($filter->notEndsWith('oe')->isAllowed('a'));
        
        self::assertTrue($filter->contains('o')->isAllowed('a'));
        self::assertFalse($filter->contains('x')->isAllowed('a'));
        
        self::assertTrue($filter->notContains('x')->isAllowed('a'));
        self::assertFalse($filter->notContains('o')->isAllowed('a'));
        
        self::assertTrue($filter->inSet(['foo', 'zoe', 'bar'])->isAllowed('a'));
        self::assertFalse($filter->inSet(['foo', 'lam', 'bar'])->isAllowed('a'));
        
        self::assertTrue($filter->notInSet(['foo', 'lam', 'bar'])->isAllowed('a'));
        self::assertFalse($filter->notInSet(['foo', 'zoe', 'bar'])->isAllowed('a'));
        
        self::assertTrue($filter->length()->eq(3)->isAllowed('a'));
        self::assertFalse($filter->not()->length()->eq(3)->isAllowed('a'));
    }
    
    public function test_filter_variable_reference_time(): void
    {
        $var = '2025-02-10 11:30:00';
        $filter = Filters::readFrom($var)->time();
        
        self::assertTrue($filter->isDateTime()->isAllowed('a'));
        self::assertFalse($filter->not()->isDateTime()->isAllowed('a'));
        self::assertFalse($filter->isDateTime()->negate()->isAllowed('a'));
        self::assertTrue($filter->not()->isDateTime()->negate()->isAllowed('a'));
        
        self::assertTrue($filter->is($var)->isAllowed('a'));
        self::assertFalse($filter->is('2010-01-01 15:52:35')->isAllowed('a'));
        
        self::assertFalse($filter->isNot($var)->isAllowed('a'));
        self::assertTrue($filter->isNot('2010-01-01 15:52:35')->isAllowed('a'));
        
        self::assertTrue($filter->before('2025-02-10 11:30:01')->isAllowed('a'));
        self::assertFalse($filter->before('2025-02-10 11:29:59')->isAllowed('a'));
        
        self::assertTrue($filter->after('2025-02-10 11:29:59')->isAllowed('a'));
        self::assertFalse($filter->after('2025-02-10 11:30:01')->isAllowed('a'));
        
        self::assertTrue($filter->from('2025-02-10 11:30:00')->isAllowed('a'));
        self::assertFalse($filter->from('2025-02-10 11:30:01')->isAllowed('a'));
        
        self::assertTrue($filter->until('2025-02-10 11:30:00')->isAllowed('a'));
        self::assertFalse($filter->until('2025-02-10 11:29:59')->isAllowed('a'));
        
        self::assertTrue($filter->between('2025-02-10 11:30:00', '2025-02-10 11:30:01')->isAllowed('a'));
        self::assertFalse($filter->between('2025-02-10 11:30:01', '2025-02-10 11:30:02')->isAllowed('a'));
        
        self::assertTrue($filter->outside('2025-02-10 11:30:01', '2025-02-10 11:30:02')->isAllowed('a'));
        self::assertFalse($filter->outside('2025-02-10 11:30:00', '2025-02-10 11:30:02')->isAllowed('a'));
        
        self::assertTrue($filter->inside('2025-02-10 11:29:59', '2025-02-10 11:30:01')->isAllowed('a'));
        self::assertFalse($filter->inside('2025-02-10 11:30:00', '2025-02-10 11:30:01')->isAllowed('a'));
        
        self::assertTrue($filter->notInside('2025-02-10 11:30:01', '2025-02-10 11:30:02')->isAllowed('a'));
        self::assertFalse($filter->notInside('2025-02-10 11:29:59', '2025-02-10 11:30:01')->isAllowed('a'));
        
        self::assertTrue($filter->isDay(Day::MON)->isAllowed('a'));
        self::assertFalse($filter->isDay(Day::TUE)->isAllowed('a'));
        
        self::assertTrue($filter->isNotDay(Day::TUE)->isAllowed('a'));
        self::assertFalse($filter->isNotDay(Day::MON)->isAllowed('a'));
        
        self::assertTrue($filter->inSet(['2025-02-10 11:30:00', '2025-02-10 11:30:01'])->isAllowed('a'));
        self::assertFalse($filter->inSet(['2025-02-10 11:29:59', '2025-02-10 11:30:01'])->isAllowed('a'));
        
        self::assertTrue($filter->notInSet(['2025-02-10 11:29:59', '2025-02-10 11:30:01'])->isAllowed('a'));
        self::assertFalse($filter->notInSet(['2025-02-10 11:30:00', '2025-02-10 11:30:01'])->isAllowed('a'));
    }
    
    public function test_filter_variable_reference_type(): void
    {
        $str = 'foo';
        self::assertTrue(Filters::readFrom($str)->type()->isString()->isAllowed('a'));
        self::assertFalse(Filters::readFrom($str)->type()->isInt()->isAllowed('a'));
        
        $int = 4;
        self::assertTrue(Filters::readFrom($int)->type()->isInt()->isAllowed('a'));
        self::assertFalse(Filters::readFrom($int)->type()->isFloat()->isAllowed('a'));
        
        $float = 2.0;
        self::assertTrue(Filters::readFrom($float)->type()->isFloat()->isAllowed('a'));
        self::assertFalse(Filters::readFrom($float)->type()->isBool()->isAllowed('a'));
        
        $bool = true;
        self::assertTrue(Filters::readFrom($bool)->type()->isBool()->isAllowed('a'));
        self::assertFalse(Filters::readFrom($bool)->type()->isArray()->isAllowed('a'));
        
        $arr = [];
        self::assertTrue(Filters::readFrom($arr)->type()->isArray()->isAllowed('a'));
        self::assertFalse(Filters::readFrom($arr)->type()->isDateTime()->isAllowed('a'));
        self::assertTrue(Filters::readFrom($arr)->type()->isEmpty()->isAllowed('a'));
        self::assertFalse(Filters::readFrom($arr)->type()->notEmpty()->isAllowed('a'));
        
        $date = new \DateTime();
        self::assertTrue(Filters::readFrom($date)->type()->isDateTime()->isAllowed('a'));
        self::assertFalse(Filters::readFrom($date)->type()->isCountable()->isAllowed('a'));
        
        $cnt = [5];
        self::assertTrue(Filters::readFrom($cnt)->type()->isCountable()->isAllowed('a'));
        self::assertFalse(Filters::readFrom($cnt)->type()->isEmpty()->isAllowed('a'));
        self::assertTrue(Filters::readFrom($cnt)->type()->notEmpty()->isAllowed('a'));
        
        $num = '5.5';
        self::assertTrue(Filters::readFrom($num)->type()->isNumeric()->isAllowed('a'));
        self::assertFalse(Filters::readFrom($num)->type()->isNull()->isAllowed('a'));
        self::assertTrue(Filters::readFrom($num)->type()->notNull()->isAllowed('a'));
        
        $nil = null;
        self::assertTrue(Filters::readFrom($nil)->type()->isNull()->isAllowed('a'));
        self::assertFalse(Filters::readFrom($nil)->type()->notNull()->isAllowed('a'));
        
        self::assertFalse(Filters::readFrom($nil)->type()->not()->isNull()->isAllowed('a'));
        self::assertTrue(Filters::readFrom($nil)->type()->not()->notNull()->isAllowed('a'));
    }
    
    public function test_filter_built_from_IntVal(): void
    {
        $filter = Filters::wrapIntValue(IntNum::constant(5));
        
        self::assertTrue($filter->isInt()->isAllowed('a'));
        self::assertTrue($filter->isNumeric()->isAllowed('a'));
        self::assertFalse($filter->isFloat()->isAllowed('a'));
        
        self::assertTrue($filter->isOdd()->isAllowed('a'));
        self::assertFalse($filter->isEven()->isAllowed('a'));
        
        self::assertTrue($filter->eq(5)->isAllowed('a'));
        self::assertFalse($filter->eq(4)->isAllowed('a'));
        
        self::assertFalse($filter->not()->eq(5)->isAllowed('a'));
        self::assertTrue($filter->not()->eq(4)->isAllowed('a'));
        
        self::assertFalse($filter->eq(5)->negate()->isAllowed('a'));
        self::assertTrue($filter->eq(4)->negate()->isAllowed('a'));
        
        self::assertTrue($filter->not()->eq(5)->negate()->isAllowed('a'));
        self::assertFalse($filter->not()->eq(4)->negate()->isAllowed('a'));
        
        self::assertTrue($filter->ne(4)->isAllowed('a'));
        self::assertFalse($filter->ne(5)->isAllowed('a'));
        
        self::assertTrue($filter->ge(5)->isAllowed('a'));
        self::assertFalse($filter->ge(6)->isAllowed('a'));
        
        self::assertTrue($filter->gt(4)->isAllowed('a'));
        self::assertFalse($filter->gt(5)->isAllowed('a'));
        
        self::assertTrue($filter->le(5)->isAllowed('a'));
        self::assertFalse($filter->le(4)->isAllowed('a'));
        
        self::assertTrue($filter->lt(6)->isAllowed('a'));
        self::assertFalse($filter->lt(5)->isAllowed('a'));
        
        self::assertTrue($filter->between(5, 6)->isAllowed('a'));
        self::assertFalse($filter->between(6, 7)->isAllowed('a'));
        
        self::assertTrue($filter->outside(6, 7)->isAllowed('a'));
        self::assertFalse($filter->outside(5, 6)->isAllowed('a'));
        
        self::assertTrue($filter->inside(4, 6)->isAllowed('a'));
        self::assertFalse($filter->inside(5, 6)->isAllowed('a'));
        
        self::assertTrue($filter->notInside(5, 6)->isAllowed('a'));
        self::assertFalse($filter->notInside(4, 6)->isAllowed('a'));
    }
    
    public function test_filter_built_from_MemoReader_int(): void
    {
        $memoFilter = Filters::wrapMemoReader(Memo::value(5));
        
        $typeFilter = $memoFilter->type();
        
        self::assertTrue($typeFilter->isInt()->isAllowed('a'));
        self::assertTrue($typeFilter->isNumeric()->isAllowed('a'));
        self::assertFalse($typeFilter->isFloat()->isAllowed('a'));
        self::assertFalse($typeFilter->isString()->isAllowed('a'));
        
        $numberFilter = $memoFilter->number();
        
        self::assertTrue($numberFilter->isOdd()->isAllowed('a'));
        self::assertFalse($numberFilter->isEven()->isAllowed('a'));
        
        self::assertTrue($numberFilter->eq(5)->isAllowed('a'));
        self::assertFalse($numberFilter->eq(4)->isAllowed('a'));
        
        self::assertFalse($numberFilter->not()->eq(5)->isAllowed('a'));
        self::assertTrue($numberFilter->not()->eq(4)->isAllowed('a'));
        
        self::assertFalse($numberFilter->eq(5)->negate()->isAllowed('a'));
        self::assertTrue($numberFilter->eq(4)->negate()->isAllowed('a'));
        
        self::assertTrue($numberFilter->not()->eq(5)->negate()->isAllowed('a'));
        self::assertFalse($numberFilter->not()->eq(4)->negate()->isAllowed('a'));
        
        self::assertTrue($numberFilter->ne(4)->isAllowed('a'));
        self::assertFalse($numberFilter->ne(5)->isAllowed('a'));
        
        self::assertTrue($numberFilter->ge(5)->isAllowed('a'));
        self::assertFalse($numberFilter->ge(6)->isAllowed('a'));
        
        self::assertTrue($numberFilter->gt(4)->isAllowed('a'));
        self::assertFalse($numberFilter->gt(5)->isAllowed('a'));
        
        self::assertTrue($numberFilter->le(5)->isAllowed('a'));
        self::assertFalse($numberFilter->le(4)->isAllowed('a'));
        
        self::assertTrue($numberFilter->lt(6)->isAllowed('a'));
        self::assertFalse($numberFilter->lt(5)->isAllowed('a'));
        
        self::assertTrue($numberFilter->between(5, 6)->isAllowed('a'));
        self::assertFalse($numberFilter->between(6, 7)->isAllowed('a'));
        
        self::assertTrue($numberFilter->outside(6, 7)->isAllowed('a'));
        self::assertFalse($numberFilter->outside(5, 6)->isAllowed('a'));
        
        self::assertTrue($numberFilter->inside(4, 6)->isAllowed('a'));
        self::assertFalse($numberFilter->inside(5, 6)->isAllowed('a'));
        
        self::assertTrue($numberFilter->notInside(5, 6)->isAllowed('a'));
        self::assertFalse($numberFilter->notInside(4, 6)->isAllowed('a'));
    }
    
    public function test_multiple_consequtive_negations_of_FilterPicker(): void
    {
        $var = 'foo';
        $filter = Filters::readFrom($var);
        
        self::assertTrue($filter->is()->same('foo')->isAllowed('a'));
        
        self::assertFalse($filter->is()->same('foo')->negate()->isAllowed('a'));
        self::assertTrue($filter->is()->same('foo')->negate()->negate()->isAllowed('a'));
        
        self::assertFalse($filter->is()->not()->same('foo')->isAllowed('a'));
        self::assertTrue($filter->is()->not()->same('foo')->negate()->isAllowed('a'));
        
        self::assertFalse($filter->is()->not()->not()->same('foo')->negate()->isAllowed('a'));
        self::assertTrue($filter->is()->not()->not()->same('foo')->negate()->negate()->isAllowed('a'));
        
        self::assertFalse($filter->not()->is()->same('foo')->isAllowed('a'));
        self::assertTrue($filter->not()->is()->not()->same('foo')->isAllowed('a'));
        self::assertTrue($filter->not()->is()->same('foo')->negate()->isAllowed('a'));
        self::assertFalse($filter->not()->is()->not()->same('foo')->negate()->isAllowed('a'));
        self::assertFalse($filter->not()->is()->not()->same('foo')->negate()->isAllowed('a'));
        self::assertTrue($filter->not()->not()->is()->not()->same('foo')->negate()->isAllowed('a'));
        self::assertTrue($filter->not()->not()->is()->not()->same('foo')->negate()->isAllowed('a'));
        self::assertFalse($filter->not()->not()->is()->not()->not()->same('foo')->negate()->isAllowed('a'));
        self::assertFalse($filter->not()->not()->is()->not()->not()->same('foo')->negate()->isAllowed('a'));
        self::assertTrue($filter->not()->not()->is()->not()->not()->same('foo')->negate()->negate()->isAllowed('a'));
        self::assertTrue($filter->not()->not()->is()->not()->not()->same('foo')->negate()->negate()->isAllowed('a'));
    }
}