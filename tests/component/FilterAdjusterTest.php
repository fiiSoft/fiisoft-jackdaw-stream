<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Filter\Adjuster\StringFilterAdjuster;
use FiiSoft\Jackdaw\Filter\Adjuster\UnwrapFilterAdjuster;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterAdjuster;
use FiiSoft\Jackdaw\Filter\FilterBy;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Filter\Logic\ConditionalFilter;
use FiiSoft\Jackdaw\Filter\Logic\FilterNOT;
use FiiSoft\Jackdaw\Filter\Number\Equal;
use FiiSoft\Jackdaw\Filter\Number\NumberFilterPicker;
use FiiSoft\Jackdaw\Filter\String\Contains\ValueContains;
use FiiSoft\Jackdaw\Filter\String\StringFilter;
use FiiSoft\Jackdaw\Filter\String\StringFilterPhony;
use FiiSoft\Jackdaw\Filter\String\StringFilterSingle;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Memo\Memo;
use FiiSoft\Jackdaw\Stream;
use FiiSoft\Jackdaw\ValueRef\IntNum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FilterAdjusterTest extends TestCase
{
    public function test_change_mode_of_StringFilterPhony(): void
    {
        $filter = Filters::startsWith('a')->and(Filters::endsWith('b'));
        
        $checkValue = $filter->checkValue();
        $checkKey = $filter->checkKey();
        $checkBoth = $filter->checkBoth();
        $checkAny = $filter->checkAny();
        
        self::assertTrue($checkValue->equals($filter));
        self::assertFalse($checkKey->equals($filter));
        self::assertFalse($checkBoth->equals($filter));
        self::assertFalse($checkAny->equals($filter));
        
        self::assertTrue($checkValue->isAllowed('ab', 'cd'));
        self::assertFalse($checkValue->isAllowed('cd', 'ab'));
        
        self::assertTrue($checkKey->isAllowed('cd', 'ab'));
        self::assertFalse($checkKey->isAllowed('ab', 'cd'));
        
        self::assertTrue($checkBoth->isAllowed('ab', 'ab'));
        self::assertFalse($checkBoth->isAllowed('ab', 'cd'));
        self::assertFalse($checkBoth->isAllowed('cd', 'ab'));
        self::assertFalse($checkBoth->isAllowed('cd', 'cd'));
        
        self::assertTrue($checkAny->isAllowed('ab', 'ab'));
        self::assertTrue($checkAny->isAllowed('ab', 'cd'));
        self::assertTrue($checkAny->isAllowed('cd', 'ab'));
        self::assertFalse($checkAny->isAllowed('cd', 'cd'));
    }
    
    /**
     * @dataProvider getDataForTestChangeBetweenIgnoreCaseAndCaseSensitiveOnStringFilter
     */
    #[DataProvider('getDataForTestChangeBetweenIgnoreCaseAndCaseSensitiveOnStringFilter')]
    public function test_change_between_ignoreCase_and_caseSensitive_on_string_filter(StringFilter $filter): void
    {
        self::assertFalse($filter->isCaseInsensitive());
        self::assertTrue($filter->equals($filter->caseSensitive()));
        
        $filter = $filter->ignoreCase();
        
        self::assertTrue($filter->isCaseInsensitive());
        self::assertTrue($filter->equals($filter->ignoreCase()));
        
        $filter = $filter->caseSensitive();
        
        self::assertFalse($filter->isCaseInsensitive());
        self::assertTrue($filter->equals($filter->caseSensitive()));
    }
    
    public static function getDataForTestChangeBetweenIgnoreCaseAndCaseSensitiveOnStringFilter(): iterable
    {
        yield 'InSet' => [Filters::string()->inSet(['a', 'b'])];
        
        $var = 'Afo';
        yield 'ReferenceStringFilter' => [Filters::readFrom($var)->string()->contains('f')];
    }
    
    public function test_adjust_inner_StringFilter_of_ConditionalFilter(): void
    {
        //given
        $caseSensitive = Filters::endsWith('o')
            ->and(ConditionalFilter::create(Filters::length()->eq(3), Filters::startsWith('a'), false));
        
        //when
        $caseInsensitive = $caseSensitive->ignoreCase();
        
        //then
        self::assertFalse($caseInsensitive->equals($caseSensitive));
        
        self::assertFalse($caseSensitive->isCaseInsensitive());
        self::assertTrue($caseInsensitive->isCaseInsensitive());
        
        self::assertFalse($caseSensitive->isAllowed('Afo'));
        self::assertTrue($caseInsensitive->isAllowed('Afo'));
        
        self::assertSame(Check::VALUE, $caseSensitive->getMode());
        self::assertSame(Check::VALUE, $caseInsensitive->getMode());
    }
    
    public function test_adjust_inner_StringFilter_of_FilterNOT(): void
    {
        //given
        $caseSensitive = Filters::string()->endsWith('o')
            ->and(Filters::startsWith('a')->negate())
            ->and(FakeStringFilter::create(Check::VALUE, 'afo')->negate());
        
        //when
        $caseInsensitive = $caseSensitive->ignoreCase();
        
        //then
        self::assertFalse($caseInsensitive->equals($caseSensitive));
        
        self::assertFalse($caseSensitive->isCaseInsensitive());
        self::assertTrue($caseInsensitive->isCaseInsensitive());
        
        self::assertTrue($caseSensitive->isAllowed('Afo'));
        self::assertFalse($caseInsensitive->isAllowed('Afo'));
        
        self::assertSame(Check::VALUE, $caseSensitive->getMode());
        self::assertSame(Check::VALUE, $caseInsensitive->getMode());
    }
    
    public function test_adjust_inner_StringFilter_of_XOR_filter(): void
    {
        //given
        $caseSensitive = Filters::endsWith('o')->xor(Filters::startsWith('a'));
        
        //when
        $caseInsensitive = $caseSensitive->ignoreCase();
        
        //then
        self::assertFalse($caseInsensitive->equals($caseSensitive));
        
        self::assertFalse($caseSensitive->isCaseInsensitive());
        self::assertTrue($caseInsensitive->isCaseInsensitive());
        
        self::assertTrue($caseSensitive->isAllowed('Afo'));
        self::assertFalse($caseInsensitive->isAllowed('Afo'));
        
        self::assertSame(Check::VALUE, $caseSensitive->getMode());
        self::assertSame(Check::VALUE, $caseInsensitive->getMode());
    }
    
    public function test_StringFilterAdjuster_does_nothing_when_StringFilter_does_not_have_to_be_adjusted(): void
    {
        $caseSensitive = Filters::string()->startsWith('a');
        self::assertTrue($caseSensitive->adjust(new StringFilterAdjuster(false))->equals($caseSensitive));
        
        $caseInsensitive = Filters::string()->startsWith('a', true);
        self::assertTrue($caseInsensitive->adjust(new StringFilterAdjuster(true))->equals($caseInsensitive));
    }
    
    public function test_filter_by_StringFilter_adjust_ignoreCase(): void
    {
        $str = Filters::string();
        
        $filter = $str->startsWith('a')
            ->and($str->endsWith('b'))
            ->and($str->contains('c'))
            ->and(Filters::length()->eq(3))
            ->caseSensitive();
        
        $stream = Stream::from(['AAC' => 4, 'aCB' => 2, 'cdB' => 5, 'Adb' => 8, 'AcvB' => 1, 'acb' => 3]);
        
        self::assertSame(['aCB' => 2, 'acb' => 3], $stream->filter($filter->ignoreCase()->checkKey())->toArrayAssoc());
    }
    
    public function test_filter_by_StringFilter_adjust_caseSensitive(): void
    {
        $str = Filters::string();
        
        $filter = $str->startsWith('a')
            ->and($str->endsWith('b'))
            ->and($str->contains('c'))
            ->and(Filters::length()->eq(3))
            ->ignoreCase();
        
        $stream = Stream::from(['AAC' => 4, 'aCB' => 2, 'cdB' => 5, 'Adb' => 8, 'AcvB' => 1, 'acb' => 3]);
        
        self::assertSame(['acb' => 3], $stream->filter($filter->caseSensitive()->checkKey())->toArrayAssoc());
    }
    
    public function test_filter_by_StringFilter_andNot(): void
    {
        $str = Filters::string();
        $filter = $str->startsWith('a')->andNot($str->contains('c'))->ignoreCase();
        
        $stream = Stream::from(['AAC' => 4, 'aCB' => 2, 'cdB' => 5, 'Adb' => 8, 'AcvB' => 1, 'acb' => 3]);
        
        self::assertSame(['Adb' => 8], $stream->filter($filter->checkKey())->toArrayAssoc());
    }
    
    public function test_filter_by_StringFilter_orNot(): void
    {
        $str = Filters::string();
        $filter = $str->startsWith('a')->orNot($str->contains('c'))->ignoreCase();
        
        $stream = Stream::from(['AAC' => 4, 'CBa' => 2, 'cdB' => 5, 'dbA' => 8, 'cAvB' => 1, 'bac' => 3]);
        
        self::assertSame(['AAC' => 4, 'dbA' => 8], $stream->filter($filter->checkKey())->toArrayAssoc());
    }
    
    public function test_filter_by_StringFilter_xnor(): void
    {
        $str = Filters::string();
        $filter = $str->startsWith('a')->xnor($str->contains('c'))->ignoreCase();
        
        $stream = Stream::from(['AAC' => 4, 'CBa' => 2, 'cdB' => 5, 'dbA' => 8, 'cAvB' => 1, 'bac' => 3]);
        
        self::assertSame(['AAC' => 4, 'dbA' => 8], $stream->filter($filter->checkKey())->toArrayAssoc());
    }
    
    public function test_filter_StringFilterPhony_andNot(): void
    {
        $str = Filters::string();
        $filter = $str->startsWith('a')->and($str->endsWith('c'))->andNot($str->contains('b'))->ignoreCase();
        
        $stream = Stream::from(['AAC' => 4, 'CBa' => 2, 'cdB' => 5, 'Abd' => 8, 'cAvB' => 1, 'bac' => 3]);
        
        self::assertSame(['AAC' => 4], $stream->filter($filter->checkKey())->toArrayAssoc());
    }
    
    public function test_filter_StringFilterPhony_or(): void
    {
        $str = Filters::string();
        $filter = $str->startsWith('a')->and($str->endsWith('c'))->or($str->contains('v'))->ignoreCase();
        
        $stream = Stream::from(['AAC' => 4, 'CBa' => 2, 'cdB' => 5, 'Abd' => 8, 'cAvB' => 1, 'bac' => 3]);
        
        self::assertSame(['AAC' => 4, 'cAvB' => 1], $stream->filter($filter->checkKey())->toArrayAssoc());
    }
    
    public function test_filter_StringFilterPhony_orNot(): void
    {
        $str = Filters::string();
        $filter = $str->startsWith('a')->and($str->endsWith('c'))
            ->orNot($str->contains('d')->or($str->contains('v')))
            ->ignoreCase();
        
        $stream = Stream::from(['AAC' => 4, 'CBa' => 2, 'cdB' => 5, 'Abd' => 8, 'cAvB' => 1, 'bac' => 3]);
        
        self::assertSame(['AAC' => 4, 'CBa' => 2, 'bac' => 3], $stream->filter($filter->checkKey())->toArrayAssoc());
    }
    
    public function test_filter_StringFilterPhony_xor(): void
    {
        $str = Filters::string();
        $filter = $str->startsWith('a')->and($str->endsWith('c'))
            ->xor($str->contains('d')->or($str->contains('v')))
            ->ignoreCase();
        
        $stream = Stream::from(['AAC' => 4, 'CBa' => 2, 'cdB' => 5, 'Abd' => 8, 'cAvB' => 1, 'bac' => 3]);
        
        self::assertSame(
            ['AAC' => 4, 'cdB' => 5, 'Abd' => 8, 'cAvB' => 1],
            $stream->filter($filter->checkKey())->toArrayAssoc()
        );
    }
    
    public function test_filter_StringFilterPhony_xnor_1(): void
    {
        $str = Filters::string();
        $filter = $str->startsWith('a')->and($str->endsWith('c'))
            ->xnor($str->contains('d')->or($str->contains('v')))
            ->ignoreCase();
        
        $stream = Stream::from(['AAC' => 4, 'CBa' => 2, 'cdB' => 5, 'Abd' => 8, 'cAvB' => 1, 'bac' => 3]);
        
        self::assertSame(
            ['CBa' => 2, 'bac' => 3],
            $stream->filter($filter->checkKey())->toArrayAssoc()
        );
    }
    
    public function test_filter_StringFilterPhony_adjust_without_any_changes(): void
    {
        $str = Filters::string();
        
        $filter = $str->startsWith('a')->and($str->endsWith('c'))
            ->xnor($str->contains('d')->or($str->contains('v')))
            ->ignoreCase()
            ->adjust(new StringFilterAdjuster(true));
        
        self::assertTrue($filter->isAllowed('CBa'));
        self::assertFalse($filter->isAllowed('AAC'));
    }
    
    /**
     * @dataProvider getDataForTestFilterSingleFilterHolderAdjust
     */
    #[DataProvider('getDataForTestFilterSingleFilterHolderAdjust')]
    public function test_filter_SingleFilterHolder_adjust(NumberFilterPicker $filterPicker): void
    {
        $filter = Filters::startsWith('a')->and($filterPicker->eq(-15));
        self::assertFalse($filter->isAllowed('AFO'));
        
        $filter = $filter->ignoreCase();
        self::assertFalse($filter->isAllowed('AFO'));
        
        $filter = $filter->adjust(new FakeValueEqualFilterReplacer());
        self::assertTrue($filter->isAllowed('AFO'));
    }
    
    public static function getDataForTestFilterSingleFilterHolderAdjust(): iterable
    {
        $var = 15;
        
        yield 'IntValueFilter' => [Filters::wrapIntValue(IntNum::readFrom($var))];
        yield 'MemoFilter' => [Filters::wrapMemoReader(Memo::value($var))->number()];
        yield 'RefereneFilter' => [Filters::readFrom($var)->number()];
    }
    
    public function test_FilterBy_adjust_inner_filter(): void
    {
        $arr = ['foo' => -15];
        
        $filter = Filters::isArray()->and(Filters::filterBy('foo', Filters::number()->eq(15)));
        self::assertFalse($filter->isAllowed($arr));
        
        $filter = $filter->adjust(new FakeValueEqualFilterReplacer());
        self::assertTrue($filter->isAllowed($arr));
    }
    
    public function test_FilterBy_adjust_itself(): void
    {
        $arr = ['foo' => 15, 'bar' => 3];
        
        $filter = Filters::isArray()->and(Filters::filterBy('bar', Filters::number()->eq(15)));
        self::assertFalse($filter->isAllowed($arr));
        
        $filter = $filter->adjust(new FakeFilterByAdjuster('foo'));
        self::assertTrue($filter->isAllowed($arr));
    }
    
    public function test_unwrap_deeply_nested_filter(): void
    {
        //given
        $filter = new StringFilterPhony(new StringFilterPhony(Filters::contains('a'), true), true);
        
        //when
        $filter = UnwrapFilterAdjuster::unwrap($filter);
        
        //then
        self::assertInstanceOf(ValueContains::class, $filter);
    }
}

final class FakeFilterByAdjuster implements FilterAdjuster
{
    private string $field;
    
    public function __construct(string $field) {
        $this->field = $field;
    }
    
    public function adjust(Filter $filter): Filter
    {
        if ($filter instanceof FilterBy) {
            $refl = new \ReflectionObject($filter);
            $prop = $refl->getProperty('filter');
            $prop->setAccessible(true);
            
            return Filters::filterBy($this->field, $prop->getValue($filter));
        }
        
        return $filter;
    }
}

final class FakeValueEqualFilterReplacer implements FilterAdjuster
{
    public function adjust(Filter $filter): Filter
    {
        if ($filter instanceof Equal\ValueEqual)
        {
            $refl = new \ReflectionObject($filter);
            
            $prop = $refl->getProperty('number');
            $prop->setAccessible(true);
            $number = $prop->getValue($filter);
            
            $refl = new \ReflectionClass(FakeValueEqualFilter::class);
            $replacement = $refl->newInstanceWithoutConstructor();
            
            $prop = $refl->getProperty('number');
            $prop->setAccessible(true);
            $prop->setValue($replacement, $number);
            
            $prop = $refl->getProperty('mode');
            $prop->setAccessible(true);
            $prop->setValue($replacement, $filter->getMode());
            
            return $replacement;
        }
        
        return $filter;
    }
}

final class FakeValueEqualFilter extends Equal
{
    public function isAllowed($value, $key = null): bool {
        return \abs($value) === \abs($this->number);
    }
    
    public function buildStream(iterable $stream): iterable {
        return $stream;
    }
}

final class FakeStringFilter extends StringFilterSingle
{
    public static function create(int $mode, string $value, bool $ignoreCase = false): self {
        return new self($mode, $value, $ignoreCase);
    }
    
    protected function compareCaseInsensitive(iterable $stream): iterable {
        return $stream;
    }
    
    protected function compareCaseSensitive(iterable $stream): iterable {
        return $stream;
    }
    
    public function negate(): StringFilter {
        $refl = new \ReflectionClass(FilterNOT::class);
        
        $prop = $refl->getProperty('filter');
        $prop->setAccessible(true);
        
        $filterNOT = $refl->newInstanceWithoutConstructor();
        $prop->setValue($filterNOT, $this);
        
        return new StringFilterPhony($filterNOT, $this->ignoreCase);
    }
    
    public function isAllowed($value, $key = null): bool {
        return ($this->ignoreCase ? \strtolower($value) : $value) === $this->value;
    }
}