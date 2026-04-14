<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Producer\Generator\Flattener;
use FiiSoft\Jackdaw\Stream;
use PHPUnit\Framework\TestCase;

final class ApiRegressionTest extends TestCase
{
    public function test_AssertionFailed(): void
    {
        try {
            Stream::from([1])->assert('is_string')->run();
            
            self::fail('AssertionFailed not thrown');
        } catch (\Exception $e) {
            self::assertInstanceOf(\RuntimeException::class, $e);
            self::assertInstanceOf('\FiiSoft\Jackdaw\Operation\Special\Assert\AssertionFailed', $e);
        }
    }
    
    public function test_BaseStreamCollection(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Internal\Collection\BaseStreamCollection',
            'extends' => [
                '\FiiSoft\Jackdaw\Internal\Destroyable',
                '\Iterator',
            ],
            'methods' => [
                'create' => [
                    'static' => true,
                    'type' => 'self',
                    'params' => ['\FiiSoft\Jackdaw\Operation\Terminating\GroupBy', 'array']
                ],
                'get' => [
                    'type' => '\FiiSoft\Jackdaw\Internal\ResultApi',
                    'params' => ['string|int|bool']
                ],
                'stream' => '\FiiSoft\Jackdaw\Stream',
                'toJson' => [
                    'type' => 'string',
                    'params' => [['type' => '?int', 'def' => null]]
                ],
                'current' => '\FiiSoft\Jackdaw\Internal\ResultApi',
                'valid' => 'bool',
                'key'
            ], [
                'methods' => ['classifiers', 'toArray'],
                'type' => 'array'
            ], [
                'methods' => ['next', 'rewind', 'destroy'],
                'type' => 'void'
            ]
        ]);
    }
    
    public function test_By(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Comparator\Sorting\By',
            'allStatic' => true,
            'defaultType' => '\FiiSoft\Jackdaw\Comparator\Sorting\Sorting',
            [
                'methods' => ['size', 'length'],
                'params' => [['type' => 'bool', 'def' => false]],
            ], [
                'methods' => ['fieldsAsc', 'fieldsDesc'],
                'params' => ['array'],
            ], [
                'methods' => ['fields'],
                'params' => ['array', ['type' => 'bool', 'def' => false]],
            ], [
                'methods' => [
                    'valueAsc', 'valueDesc', 'keyAsc', 'keyDesc', 'assocAsc', 'assocDesc', 'bothAsc', 'bothDesc'
                ],
                'params' => [['type' => 'ComparatorReady|callable|null', 'def' => null]],
            ], [
                'methods' => ['value', 'key', 'assoc'],
                'params' => [
                    ['type' => 'ComparatorReady|callable|null', 'def' => null],
                    ['type' => 'bool', 'def' => false]
                ],
            ], [
                'methods' => ['both'],
                'params' => [
                    '\FiiSoft\Jackdaw\Comparator\Sorting\Sorting',
                    '\FiiSoft\Jackdaw\Comparator\Sorting\Sorting',
                ],
            ], [
                'methods' => ['sizeAsc', 'sizeDesc', 'lengthAsc', 'lengthDesc'],
            ]
        ]);
    }
    
    public function test_Check(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Internal\Check',
            'isInterface' => true,
            'constants' => [
                'VALUE' => 1,
                'KEY' => 2,
                'BOTH' => 3,
                'ANY' => 4,
            ],
        ]);
    }
    
    public function test_Collector(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Collector\Collector',
            'isInterface' => true,
            'extends' => '\FiiSoft\Jackdaw\Operation\Internal\DispatchReady',
            'defaultType' => 'void',
            'methods' => [
                'set' => ['string|int', 'any'],
                'add' => ['any'],
                'canPreserveKeys' => 'bool',
                'allowKeys' => ['?bool'],
            ],
        ]);
    }
    
    public function test_Collectors(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Collector\Collectors',
            'allStatic' => true,
            'defaultType' => '\FiiSoft\Jackdaw\Collector\IterableCollector',
            'methods' => [
                'values',
                'getAdapter' => [
                    'type' => '\FiiSoft\Jackdaw\Collector\Collector',
                    'params' => [
                        'Collector|\ArrayAccess<K,M>|\SplHeap<M>|\SplPriorityQueue<int, M>',
                        ['type' => '?bool', 'def' => null]
                    ],
                ],
                'iterable' => [
                    'IterableCollector|\ArrayIterator<K,M>|\ArrayObject<K,M>|\SplFixedArray<M>',
                    ['type' => '?bool', 'def' => null]
                ],
                'default' => [['type' => 'bool', 'def' => true]],
                'array' => [
                    ['type' => 'array', 'ref' => true],
                    ['type' => 'bool', 'def' => true],
                ],
                'wrapSplPriorityQueue' => [
                    'type' => '\FiiSoft\Jackdaw\Collector\Adapter\SplPriorityQueueAdapter',
                    'params' => ['\SplPriorityQueue', ['type' => 'bool', 'def' => true]],
                ],
            ],
        ]);
    }
    
    public function test_Comparator(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Comparator\Comparator',
            'isInterface' => true,
            'extends' => '\FiiSoft\Jackdaw\Comparator\ComparisonSpec',
            'defaultType' => 'int',
            'methods' => [
                'mode',
                'comparator' => '?\FiiSoft\Jackdaw\Comparator\Comparator',
                'compare' => ['any', 'any'],
                'compareAssoc' => ['any', 'any', 'any', 'any'],
            ],
        ]);
    }
    
    public function test_Comparators(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Comparator\Comparators',
            'allStatic' => true,
            'defaultType' => '\FiiSoft\Jackdaw\Comparator\Comparator',
            'methods' => [
                'prepare' => ['ComparatorReady|callable|null'],
                'getAdapter' => [
                    'params' => ['ComparatorReady|callable|null'],
                    'type' => '?\FiiSoft\Jackdaw\Comparator\Comparator',
                ],
                'fields' => ['array'],
                'multi' => [
                    'type' => '\FiiSoft\Jackdaw\Comparator\Basic\MultiComparator',
                    'params' => [['type' => 'ComparatorReady|callable', 'var' => true]],
                ],
            ], [
                'methods' => ['default', 'reverse', 'size', 'length'],
            ],
        ]);
    }
    
    public function test_ComparisonSpec(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Comparator\ComparisonSpec',
            'isInterface' => true,
            'extends' => '\FiiSoft\Jackdaw\Comparator\ComparatorReady',
            'methods' => [
                'mode' => 'int',
                'comparator' => '?\FiiSoft\Jackdaw\Comparator\Comparator',
            ],
        ]);
    }
    
    public function test_Consumer(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Consumer\Consumer',
            'isInterface' => true,
            'extends' => [
                '\FiiSoft\Jackdaw\Internal\StreamBuilder',
                '\FiiSoft\Jackdaw\Consumer\ConsumerReady',
                '\FiiSoft\Jackdaw\Operation\Internal\DispatchReady',
            ],
            'methods' => [
                'consume' => ['type' => 'void', 'params' => ['any', 'any']],
                'buildStream' => ['type' => 'iterable', 'params' => ['iterable']]
            ]
        ]);
    }
    
    public function test_Consumers(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Consumer\Consumers',
            'allStatic' => true,
            'defaultType' => '\FiiSoft\Jackdaw\Consumer\Consumer',
            'methods' => [
                'idle',
                'counter' => '\FiiSoft\Jackdaw\Consumer\Counter',
                'getAdapter' => ['ConsumerReady|callable|resource'],
                'printer' => [['type' => 'int', 'def' => Check::BOTH]],
                'stdout' => [
                    ['type' => 'string', 'def' => \PHP_EOL],
                    ['type' => 'int', 'def' => Check::VALUE],
                ],
                'resource' => ['resource', ['type' => 'int', 'def' => Check::VALUE]],
                'usleep' => ['int'],
                'sendValueTo' => [['type' => 'any', 'ref' => true]],
                'sendKeyTo' => [['type' => 'any', 'ref' => true]],
                'sendValueKeyTo' => [
                    ['type' => 'any', 'ref' => true],
                    ['type' => 'any', 'ref' => true]
                ],
                'changeIntBy' => [
                    ['type' => '?int', 'ref' => true],
                    'IntProvider|\Traversable<int>|iterable<int>|callable|int'
                ],
                'byArgs' => ['callable']
            ]
        ]);
    }
    
    public function test_Counter(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Consumer\Counter',
            'isInterface' => true,
            'extends' => '\FiiSoft\Jackdaw\Consumer\Consumer',
            'methods' => [
                'get' => 'int',
                'consume' => ['type' => 'void', 'params' => ['any', 'any'],],
                'buildStream' => ['type' => 'iterable', 'params' => ['iterable'],]
            ]
        ]);
    }
    
    public function test_CountFilterPicker(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Filter\Size\Count\CountFilterPicker',
            'isInterface' => true,
            'defaultType' => '\FiiSoft\Jackdaw\Filter\Filter',
            'methods' => [
                'isCountable',
                'not' => '\FiiSoft\Jackdaw\Filter\Size\Count\CountFilterPicker',
            ], [
                'methods' => ['eq', 'ne', 'le', 'ge', 'lt', 'gt'],
                'params' => ['int'],
            ], [
                'methods' => ['inside', 'notInside', 'between', 'outside'],
                'params' => ['int', 'int'],
            ],
        ]);
    }
    
    public function test_Discriminator(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Discriminator\Discriminator',
            'isInterface' => true,
            'extends' => [
                '\FiiSoft\Jackdaw\Mapper\MapperReady',
                '\FiiSoft\Jackdaw\Comparator\ComparatorReady',
                '\FiiSoft\Jackdaw\Discriminator\DiscriminatorReady',
            ],
            'methods' => [
                'classify' => [
                    'params' => ['any', ['type' => 'any', 'def' => null]],
                    'type' => 'string|int|bool'
                ]
            ],
        ]);
    }
    
    public function test_Discriminators(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Discriminator\Discriminators',
            'allStatic' => true,
            'defaultType' => '\FiiSoft\Jackdaw\Discriminator\Discriminator',
            'methods' => [
                'getAdapter' => ['DiscriminatorReady|callable|array<string|int>'],
                'prepare' => ['DiscriminatorReady|callable|array<string|int>|string|int'],
                'evenOdd' => [['type' => 'int', 'def' => Check::VALUE]],
                'byField' => [
                    'string|int',
                    ['type' => 'string|int|null', 'def' => null],
                ],
                'alternately' => ['array'],
                'yesNo' => [
                    'DiscriminatorReady|callable|array<string|int>|string|int',
                    ['type' => 'string|int', 'def' => 'yes'],
                    ['type' => 'string|int', 'def' => 'no'],
                ],
                'readFrom' => [['type' => 'any', 'ref' => true]]
            ], [
                'methods' => ['byKey', 'byValue', 'dayOfWeek'],
            ],
        ]);
    }
    
    public function test_ErrorHandler(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Handler\ErrorHandler',
            'isInterface' => true,
            'methods' => [
                'handle' => ['type' => '?bool', 'params' => ['\Throwable', 'string|int', 'any']]
            ]
        ]);
    }
    
    public function test_ErrorLogger(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Handler\Logger\ErrorLogger',
            'isInterface' => true,
            'methods' => [
                'log' => ['type' => 'void', 'params' => ['\Throwable', 'any', 'string|int|mixed']]
            ]
        ]);
    }
    
    public function test_Executable(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Internal\Executable',
            'isInterface' => true,
            'methods' => [
                'run' => [
                    'type' => 'void',
                    'params' => [['type' => 'bool', 'def' => false]]
                ]
            ]
        ]);
    }
    
    public function test_Filter(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Filter\Filter',
            'isInterface' => true,
            'extends' => [
                '\FiiSoft\Jackdaw\Internal\StreamBuilder',
                '\FiiSoft\Jackdaw\Filter\FilterReady',
                '\FiiSoft\Jackdaw\Mapper\MapperReady',
                '\FiiSoft\Jackdaw\Discriminator\DiscriminatorReady',
                '\FiiSoft\Jackdaw\Comparator\ComparatorReady',
                '\FiiSoft\Jackdaw\Transformer\TransformerReady',
            ],
            'defaultType' => 'self',
            'methods' => [
                'buildStream' => ['type' => 'iterable', 'params' => ['iterable']],
                'isAllowed' => ['type' => 'bool', 'params' => ['any', ['type' => 'any', 'def' => null]]],
                'getMode' => '?int',
                'inMode' => ['?int'],
                'equals' => ['type' => 'bool', 'params' => ['\FiiSoft\Jackdaw\Filter\Filter']],
                'adjust' => ['\FiiSoft\Jackdaw\Filter\FilterAdjuster']
            ], [
                'methods' => ['checkValue', 'checkKey', 'checkBoth', 'checkAny', 'negate']
            ], [
                'methods' => ['and', 'andNot', 'or', 'orNot', 'xor', 'xnor'],
                'params' => ['FilterReady|callable|array<string|int, mixed>|scalar']
            ]
        ]);
    }
    
    public function test_FilterPicker(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Filter\ValRef\FilterPicker',
            'methods' => [
                'string' => '\FiiSoft\Jackdaw\Filter\String\StringFilterPicker',
                'length' => '\FiiSoft\Jackdaw\Filter\Size\Length\LengthFilterPicker',
                'size' => '\FiiSoft\Jackdaw\Filter\Size\Count\CountFilterPicker',
                'time' => '\FiiSoft\Jackdaw\Filter\Time\TimeFilterPicker',
                'number' => '\FiiSoft\Jackdaw\Filter\Number\NumberFilterPicker',
                'type' => '\FiiSoft\Jackdaw\Filter\CheckType\TypeFilterPicker',
                'is' => '\FiiSoft\Jackdaw\Filter\Simple\SimpleFilterPicker',
                'not' => 'self',
            ]
        ]);
    }
    
    public function test_Filters(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Filter\Filters',
            'defaultType' => '\FiiSoft\Jackdaw\Filter\Filter',
            'allStatic' => true,
            'methods' => [
                'getAdapter' => [
                    'FilterReady|callable|array<string|int, mixed>|scalar',
                    ['type' => '?int', 'def' => null]
                ],
                'time' => [
                    'type' => '\FiiSoft\Jackdaw\Filter\Time\TimeFilterPicker',
                    'params' => [['type' => '?int', 'def' => null]]
                ],
                'string' => [
                    'type' => '\FiiSoft\Jackdaw\Filter\String\StringFilterPicker',
                    'params' => [['type' => '?int', 'def' => null]]
                ],
                'size' => [
                    'type' => '\FiiSoft\Jackdaw\Filter\Size\Count\CountFilterPicker',
                    'params' => [['type' => '?int', 'def' => null]]
                ],
                'length' => [
                    'type' => '\FiiSoft\Jackdaw\Filter\Size\Length\LengthFilterPicker',
                    'params' => [['type' => '?int', 'def' => null]]
                ],
                'number' => [
                    'type' => '\FiiSoft\Jackdaw\Filter\Number\NumberFilterPicker',
                    'params' => [['type' => '?int', 'def' => null]]
                ],
                'type' => [
                    'type' => '\FiiSoft\Jackdaw\Filter\CheckType\TypeFilterPicker',
                    'params' => [['type' => '?int', 'def' => null]]
                ],
                'onlyIn' => ['array', ['type' => '?int', 'def' => null]],
                'onlyWith' => ['array<string|int>|string|int', ['type' => 'bool', 'def' => false]],
                'filterBy' => ['string|int', 'FilterReady|callable|array<string|int, mixed>|scalar'],
                'hasField' => ['string|int'],
                'readFrom' => [
                    'type' => '\FiiSoft\Jackdaw\Filter\ValRef\FilterPicker',
                    'params' => [['type' => 'array<mixed>|object|scalar|null', 'ref' => true]]
                ],
                'wrapIntValue' => [
                    'type' => '\FiiSoft\Jackdaw\Filter\Number\NumberFilterPicker',
                    'params' => ['\FiiSoft\Jackdaw\ValueRef\IntValue']
                ],
                'wrapMemoReader' => [
                    'type' => '\FiiSoft\Jackdaw\Filter\ValRef\FilterPicker',
                    'params' => ['\FiiSoft\Jackdaw\Memo\MemoReader']
                ],
                'byArgs' => ['callable'],
                'keyIs' => ['any'],
                'NOT' => ['FilterReady|callable|array<string|int, mixed>|scalar'],
            ], [
                'methods' => [
                    'isEmpty', 'notEmpty', 'isDateTime', 'isCountable', 'isNull', 'notNull', 'isInt', 'isNumeric',
                    'isString', 'isBool', 'isFloat', 'isArray'
                ],
                'params' => [['type' => '?int', 'def' => null]]
            ], [
                'methods' => ['contains', 'startsWith', 'endsWith'],
                'type' => '\FiiSoft\Jackdaw\Filter\String\StringFilter',
                'params' => ['string', ['type' => 'bool', 'def' => false]]
            ], [
                'methods' => ['greaterThan', 'greaterOrEqual', 'lessThan', 'lessOrEqual'],
                'params' => ['float|int', ['type' => '?int', 'def' => null]]
            ], [
                'methods' => ['equal', 'notEqual', 'same', 'notSame'],
                'params' => ['any', ['type' => '?int', 'def' => null]]
            ], [
                'methods' => ['AND', 'OR'],
                'params' => [['type' => 'FilterReady|callable|array<string|int, mixed>|scalar', 'var' => true]]
            ], [
                'methods' => ['XOR', 'XNOR'],
                'params' => [
                    'FilterReady|callable|array<string|int, mixed>|scalar',
                    'FilterReady|callable|array<string|int, mixed>|scalar'
                ]
            ]
        ]);
    }
    
    public function test_Flattener(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Producer\Generator\Flattener',
            'extends' => '\FiiSoft\Jackdaw\Producer\Tech\BaseProducer',
            'constants' => ['MAX_LEVEL' => Flattener::MAX_LEVEL],
            'methods' => [
                'getIterator' => '\Generator',
                'maxLevel' => 'int',
                'increaseLevel' => ['type' => 'void', 'params' => ['int']],
                'isLevel' => ['type' => 'bool', 'params' => ['int']],
                'setLevel' => ['type' => 'self', 'params' => ['int']],
                'setIterable' => ['type' => 'self', 'params' => ['iterable']],
                'stream' => '\FiiSoft\Jackdaw\Stream',
            ], [
                'methods' => ['decreaseLevel', 'destroy'],
                'type' => 'void',
            ]
        ]);
    }
    
    public function test_FullMemo(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Memo\FullMemo',
            'isInterface' => true,
            'extends' => '\FiiSoft\Jackdaw\Memo\MemoWriter',
            'methods' => [
                'write' => ['type' => 'void', 'params' => ['any', 'any']]
            ], [
                'methods' => ['value', 'key', 'tuple'],
                'type' => '\FiiSoft\Jackdaw\Memo\MemoReader'
            ]
        ]);
    }
    
    public function test_IdleForkHandler(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Operation\Collecting\Fork\Adapter\IdleForkHandler',
            'extends' => [
                '\FiiSoft\Jackdaw\Operation\Collecting\Fork\ForkHandler',
                '\FiiSoft\Jackdaw\Operation\Internal\ForkReady',
            ],
            'methods' => [
                'create' => '\FiiSoft\Jackdaw\Operation\Collecting\Fork\ForkHandler',
                'prepare' => 'void',
                'accept' => [
                    'type' => 'void',
                    'params' => ['any', 'any']
                ],
                'isEmpty' => 'bool',
                'result',
                'destroy' => 'void',
            ]
        ]);
    }
    
    public function test_IntNum(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\ValueRef\IntNum',
            'defaultType' => '\FiiSoft\Jackdaw\ValueRef\IntValue',
            'allStatic' => true,
            'methods' => [
                'getAdapter' => ['IntProvider|\Traversable<int>|iterable<int>|callable|int'],
                'addArgs' => ['\FiiSoft\Jackdaw\ValueRef\IntValue', '\FiiSoft\Jackdaw\ValueRef\IntValue'],
                'constant' => ['int'],
                'readFrom' => [['type' => '?int', 'ref' => true]],
                'infinitely' => ['iterable'],
                'consecutive' => ['iterable', ['type' => 'bool', 'def' => false]]
            ]
        ]);
    }
    
    public function test_IntValue(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\ValueRef\IntValue',
            'isInterface' => true,
            'extends' => '\FiiSoft\Jackdaw\ValueRef\IntProvider',
            'methods' => [
                'int' => 'int',
                'isConstant' => 'bool',
                'equals' => [
                    'type' => 'bool',
                    'params' => ['\FiiSoft\Jackdaw\ValueRef\IntValue'],
                ]
            ]
        ]);
    }
    
    public function test_IterableCollector(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Collector\IterableCollector',
            'isInterface' => true,
            'extends' => [
                '\FiiSoft\Jackdaw\Collector\Collector',
                '\FiiSoft\Jackdaw\Operation\Internal\ForkReady',
                '\Traversable',
                '\Countable'
            ],
            'methods' => [
                'add' => ['type' => 'void', 'params' => ['any']],
                'allowKeys' => ['type' => 'void', 'params' => ['?bool']],
                'canPreserveKeys' => 'bool',
                'clear' => 'void',
                'count',
                'set' => ['type' => 'void', 'params' => ['string|int', 'any']],
                'stream' => '\FiiSoft\Jackdaw\Stream',
                'toArray' => 'array',
                'toJson' => ['type' => 'string', 'params' => [['type' => '?int', 'def' => null]]],
                'toString' => ['type' => 'string', 'params' => [['type' => 'string', 'def' => ',']]],
            ]
        ]);
    }
    
    public function test_LastOperation(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Operation\LastOperation',
            'extends' => [
                '\FiiSoft\Jackdaw\Internal\StreamPipe',
                '\FiiSoft\Jackdaw\Internal\ResultApi',
                '\FiiSoft\Jackdaw\Operation\Internal\ForkReady',
                '\FiiSoft\Jackdaw\Operation\Internal\DispatchReady',
            ],
            'methods' => [
                'found' => 'bool',
                'notFound' => 'bool',
                'get',
                'transform' => ['type' => 'self', 'params' => ['TransformerReady|callable|null']],
                'getOrElse' => ['callable|mixed|null'],
                'key',
                'tuple' => 'array',
                'call' => ['type' => 'void', 'params' => ['ConsumerReady|callable|resource']],
                'stream' => '\FiiSoft\Jackdaw\Stream',
                'toString' => ['type' => 'string', 'params' => [['type' => 'string', 'def' => ',']]],
                'toJson' => [
                    'type' => 'string',
                    'params' => [['type' => '?int', 'def' => null], ['type' => 'bool', 'def' => false]]
                ],
                'toJsonAssoc' => ['type' => 'string', 'params' => [['type' => '?int', 'def' => null]]],
                'toArray' => ['type' => 'array', 'params' => [['type' => 'bool', 'def' => false]]],
                'toArrayAssoc' => 'array',
                'count',
                'destroy' => 'void',
                'getIterator',
                'wrap' => [
                    'type' => '\FiiSoft\Jackdaw\Operation\LastOperation',
                    'params' => ['ProducerReady|\Traversable<mixed>|resource|callable|iterable<mixed>|string']
                ],
                'consume' => [
                    'type' => 'void',
                    'params' => ['ProducerReady|\Traversable<mixed>|resource|callable|iterable<mixed>|string']
                ]
            ]
        ]);
    }
    
    public function test_LengthFilterPicker(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Filter\Size\Length\LengthFilterPicker',
            'isInterface' => true,
            'defaultType' => '\FiiSoft\Jackdaw\Filter\Filter',
            'methods' => [
                'isString',
                'not' => '\FiiSoft\Jackdaw\Filter\Size\Length\LengthFilterPicker',
            ], [
                'methods' => ['eq', 'ne', 'le', 'ge', 'lt', 'gt'],
                'params' => ['int']
            ], [
                'methods' => ['inside', 'outside', 'between', 'notInside'],
                'params' => ['int', 'int']
            ]
        ]);
    }
    
    public function test_Mapper(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Mapper\Mapper',
            'isInterface' => true,
            'extends' => [
                '\FiiSoft\Jackdaw\Mapper\MapperReady',
                '\FiiSoft\Jackdaw\Discriminator\DiscriminatorReady',
                '\FiiSoft\Jackdaw\Transformer\TransformerReady',
                '\FiiSoft\Jackdaw\Internal\StreamBuilder',
            ],
            'methods' => [
                'buildStream' => ['type' => 'iterable', 'params' => ['iterable']],
                'map' => ['any', ['type' => 'any', 'def' => null]],
                'makeKeyMapper' => '\FiiSoft\Jackdaw\Mapper\Mapper',
            ], [
                'methods' => ['mergeWith', 'equals'],
                'type' => 'bool',
                'params' => ['\FiiSoft\Jackdaw\Mapper\Mapper']
            ]
        ]);
    }
    
    public function test_Mappers(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Mapper\Mappers',
            'allStatic' => true,
            'defaultType' => '\FiiSoft\Jackdaw\Mapper\Mapper',
            'methods' => [
                'toTime' => [
                    ['type' => 'array<string|int>|string|int|null', 'def' => null],
                    ['type' => '?string', 'def' => null],
                    ['type' => '\DateTimeZone|string|null', 'def' => null],
                ],
                'toArray' => [['type' => 'bool', 'def' => false]],
                'concat' => [['type' => 'string', 'def' => '']],
                'split' => [['type' => 'string', 'def' => ' ']],
                'replace' => ['string[]|string', 'string[]|string'],
                'extract' => ['array<string|int>|string|int', ['type' => 'any', 'def' => null]],
                'simple' => ['any'],
                'remove' => ['array<string|int>|string|int'],
                'jsonEncode' => [['type' => '?int', 'def' => null]],
                'jsonDecode' => [['type' => '?int', 'def' => null], ['type' => 'bool', 'def' => true]],
                'moveTo' => ['string|int', ['type' => 'string|int|null', 'def' => null]],
                'mapField' => ['string|int', 'MapperReady|callable|iterable|mixed'],
                'round' => [['type' => 'int', 'def' => 2]],
                'tokenize' => [['type' => 'string', 'def' => ' ']],
                'trim' => [['type' => 'string', 'def' => " \t\n\r\0\x0B"]],
                'fieldValue' => ['string|int'],
                'readFrom' => [['type' => 'any', 'ref' => true]],
                'arrayColumn' => ['string|int|null', ['type' => 'string|int|null', 'def' => null]],
                'formatTime' => [['type' => 'string', 'def' => 'Y-m-d H:i:s']],
                'reindexKeys' => [['type' => 'int', 'def' => 0], ['type' => 'int', 'def' => 1]],
                'byArgs' => ['callable'],
                'slice' => ['int', ['type' => '?int', 'def' => null], ['type' => 'bool', 'def' => false]],
            ], [
                'methods' => ['getAdapter', 'forEach'],
                'params' => ['MapperReady|callable|iterable|mixed']
            ], [
                'methods' => ['toInt', 'toString', 'toFloat', 'toBool'],
                'params' => [['type' => 'array<string|int>|string|int|null', 'def' => null]]
            ], [
                'methods' => ['append', 'complete'],
                'params' => ['string|int', 'MapperReady|callable|iterable|mixed']
            ], [
                'methods' => ['increment', 'decrement'],
                'params' => [['type' => 'int', 'def' => 1]]
            ], [
                'methods' => ['remap', 'reorderKeys'],
                'params' => ['array']
            ], [
                'methods' => ['skip', 'limit'],
                'params' => ['int', ['type' => 'bool', 'def' => false]]
            ], [
                'methods' => ['shuffle', 'reverse', 'value', 'key']
            ]
        ]);
    }
    
    public function test_Matcher(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Matcher\Matcher',
            'isInterface' => true,
            'defaultType' => 'bool',
            'methods' => [
                'matches' => ['any', 'any', ['type' => 'any', 'def' => null], ['type' => 'any', 'def' => null]],
                'equals' => ['\FiiSoft\Jackdaw\Matcher\Matcher']
            ]
        ]);
    }
    
    public function test_Memo(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Memo\Memo',
            'allStatic' => true,
            'methods' => [
                'full' => '\FiiSoft\Jackdaw\Memo\FullMemo',
                'sequence' => [
                    'type' => '\FiiSoft\Jackdaw\Memo\SequenceMemo',
                    'params' => [['type' => '?int', 'def' => null]]
                ]
            ], [
                'methods' => ['value', 'key'],
                'type' => '\FiiSoft\Jackdaw\Memo\SingleMemo',
                'params' => [['type' => 'any', 'def' => null]]
            ]
        ]);
    }
    
    public function test_MemoReader(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Memo\MemoReader',
            'isInterface' => true,
            'extends' => [
                '\FiiSoft\Jackdaw\ValueRef\IntProvider',
                '\FiiSoft\Jackdaw\Mapper\MapperReady',
                '\FiiSoft\Jackdaw\Discriminator\DiscriminatorReady',
                '\FiiSoft\Jackdaw\Producer\ProducerReady',
            ],
            'methods' => [
                'read',
                'equals' => ['type' => 'bool', 'params' => ['\FiiSoft\Jackdaw\Memo\MemoReader']]
            ]
        ]);
    }
    
    public function test_MemoWriter(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Memo\MemoWriter',
            'isInterface' => true,
            'extends' => [
                '\FiiSoft\Jackdaw\Consumer\ConsumerReady',
                '\FiiSoft\Jackdaw\Operation\Internal\DispatchReady',
            ],
            'methods' => [
                'write' => ['type' => 'void', 'params' => ['any', 'any']]
            ]
        ]);
    }
    
    public function test_MultiComparator(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Comparator\Basic\MultiComparator',
            'extends' => '\FiiSoft\Jackdaw\Comparator\Basic\BaseComparator',
            'methods' => [
                'compare' => ['type' => 'int', 'params' => ['any', 'any']],
                'compareAssoc' => ['type' => 'int', 'params' => ['any', 'any', 'any', 'any']],
                'addComparators' => ['type' => 'void', 'params' => ['array<ComparatorReady|callable|null>']],
                'comparator' => '\FiiSoft\Jackdaw\Comparator\Comparator',
                'mode' => 'int',
            ]
        ]);
    }
    
    public function test_NumberFilterPicker(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Filter\Number\NumberFilterPicker',
            'isInterface' => true,
            'defaultType' => '\FiiSoft\Jackdaw\Filter\Filter',
            'methods' => [
                'not' => '\FiiSoft\Jackdaw\Filter\Number\NumberFilterPicker',
            ], [
                'methods' => ['isNumeric', 'isFloat', 'isInt', 'isEven', 'isOdd'],
            ], [
                'methods' => ['le', 'ge', 'lt', 'gt', 'eq', 'ne'],
                'params' => ['float|int']
            ], [
                'methods' => ['between', 'outside', 'inside', 'notInside'],
                'params' => ['float|int', 'float|int']
            ]
        ]);
    }
    
    public function test_OnError(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Handler\OnError',
            'allStatic' => true,
            'defaultType' => '\FiiSoft\Jackdaw\Handler\ErrorHandler',
            'methods' => [
                'skip',
                'abort',
                'call' => ['callable']
            ], [
                'methods' => ['logAndSkip', 'logAndAbort', 'log'],
                'params' => ['ErrorLogger|ConsoleLogger|OutputInterface|LoggerInterface']
            ]
        ]);
    }
    
    public function test_Producer(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Producer\Producer',
            'isInterface' => true,
            'extends' => [
                '\FiiSoft\Jackdaw\Internal\Destroyable',
                '\FiiSoft\Jackdaw\Producer\ProducerReady',
                '\FiiSoft\Jackdaw\Mapper\MapperReady',
                '\IteratorAggregate',
            ],
            'methods' => [
                'stream' => '\FiiSoft\Jackdaw\Stream',
                'destroy' => 'void',
                'getIterator'
            ]
        ]);
    }
    
    public function test_Producers(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Producer\Producers',
            'allStatic' => true,
            'defaultType' => '\FiiSoft\Jackdaw\Producer\Producer',
            'methods' => [
                'from' => ['array<ProducerReady|resource|callable|iterable<string|int, mixed>|object|scalar>'],
                'prepare' => [
                    'type' => 'array',
                    'params' => ['array<ProducerReady|resource|callable|iterable<string|int, mixed>|object|scalar>']
                ],
                'getAdapter' => ['ProducerReady|\Traversable<mixed>|resource|callable|iterable<mixed>|string'],
                'multiSourced' => [[
                    'type' => 'ProducerReady|resource|callable|iterable<string|int, mixed>|string',
                    'var' => true
                ]],
                'fromPDOStatement' => ['\PDOStatement', ['type' => '?int', 'def' => null]],
                'combinedFrom' => [
                    'ProducerReady|\Traversable<mixed>|resource|callable|iterable<int, mixed>|string',
                    'ProducerReady|\Traversable<mixed>|resource|callable|iterable<int, mixed>|string'
                ],
                'randomInt' => [
                    ['type' => 'int', 'def' => 1],
                    ['type' => 'int', 'def' => \PHP_INT_MAX],
                    ['type' => 'int', 'def' => \PHP_INT_MAX]
                ],
                'sequentialInt' => [
                    ['type' => 'int', 'def' => 1],
                    ['type' => 'int', 'def' => 1],
                    ['type' => 'int', 'def' => \PHP_INT_MAX]
                ],
                'randomString' => [
                    'int',
                    ['type' => '?int', 'def' => null],
                    ['type' => 'int', 'def' => \PHP_INT_MAX],
                    ['type' => '?string', 'def' => null],
                ],
                'uuidFrom' => [
                    '\FiiSoft\Jackdaw\Producer\Generator\Uuid\UuidGenerator',
                    ['type' => 'int', 'def' => \PHP_INT_MAX],
                ],
                'randomUuid' => [['type' => 'int', 'def' => \PHP_INT_MAX]],
                'uuidV4' => [['type' => 'int', 'def' => \PHP_INT_MAX]],
                'collatz' => [['type' => '?int', 'def' => null]],
                'resource' => [
                    'resource|string',
                    ['type' => 'bool', 'def' => false],
                    ['type' => '?int', 'def' => null]
                ],
                'tokenizer' => [
                    'type' => '\FiiSoft\Jackdaw\Producer\Generator\Tokenizer',
                    'params' => ['string', ['type' => 'string', 'def' => '']]
                ],
                'flattener' => [
                    'type' => '\FiiSoft\Jackdaw\Producer\Generator\Flattener',
                    'params' => [
                        ['type' => 'iterable', 'def' => []],
                        ['type' => 'int', 'def' => 0],
                    ]
                ],
                'queue' => [
                    'type' => '\FiiSoft\Jackdaw\Producer\QueueProducer',
                    'params' => [['type' => 'array', 'def' => []]],
                ],
                'readFrom' => [['type' => 'any', 'ref' => true]],
                'dateTimeSeq' => [
                    ['type' => '\DateTimeInterface|string|int|null', 'def' => null],
                    ['type' => '\DateInterval|string|null', 'def' => null],
                    ['type' => '\DateTimeInterface|string|int|null', 'def' => null],
                    ['type' => '?int', 'def' => null]
                ],
                'repeater' => ['any', ['type' => 'int', 'def' => \PHP_INT_MAX],],
                'cyclic' => [
                    'array',
                    ['type' => 'bool', 'def' => false],
                    ['type' => 'int', 'def' => \PHP_INT_MAX],
                ],
            ]
        ]);
    }
    
    public function test_QueueProducer(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Producer\QueueProducer',
            'defaultType' => 'self',
            'extends' => [
                '\FiiSoft\Jackdaw\Producer\Tech\BaseProducer',
                '\FiiSoft\Jackdaw\Consumer\Consumer',
            ],
            'methods' => [
                'getIterator' => '\Generator',
                'appendMany' => ['array'],
                'prependMany' => ['array', ['type' => 'bool', 'def' => false]],
                'consume' => ['type' => 'void', 'params' => ['any', 'any']],
                'buildStream' => ['type' => 'iterable', 'params' => ['iterable']],
                'destroy' => 'void',
                'stream' => '\FiiSoft\Jackdaw\Stream',
            ], [
                'methods' => ['append', 'prepend'],
                'params' => ['any', ['type' => 'any', 'def' => null]]
            ]
        ]);
    }
    
    public function test_Reducer(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Reducer\Reducer',
            'isInterface' => true,
            'extends' => [
                '\FiiSoft\Jackdaw\Consumer\ConsumerReady',
                '\FiiSoft\Jackdaw\Mapper\MapperReady',
                '\FiiSoft\Jackdaw\Operation\Internal\DispatchReady',
                '\FiiSoft\Jackdaw\Operation\Internal\ForkReady',
                '\FiiSoft\Jackdaw\Transformer\TransformerReady',
            ],
            'methods' => [
                'consume' => ['type' => 'void', 'params' => ['any']],
                'hasResult' => 'bool',
                'reset' => 'void',
                'result',
            ]
        ]);
    }
    
    public function test_Reducers(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Reducer\Reducers',
            'allStatic' => true,
            'defaultType' => '\FiiSoft\Jackdaw\Reducer\Reducer',
            'methods' => [
                'getAdapter' => ['Reducer|callable|array<Reducer|callable>'],
                'generic' => ['callable'],
                'concat' => [['type' => 'string', 'def' => '']],
                'countUnique' => [['type' => 'DiscriminatorReady|callable|array<string|int>|null', 'def' => null]],
            ], [
                'methods' => ['sum', 'product', 'min', 'max', 'minMax', 'longest', 'shortest', 'count']
            ], [
                'methods' => ['average', 'basicStats'],
                'params' => [['type' => '?int', 'def' => null]]
            ]
        ]);
    }
    
    public function test_Registry(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Registry\Registry',
            'methods' => [
                'valueKey' => [
                    'type' => '\FiiSoft\Jackdaw\Registry\ValueKeyWriter',
                    'params' => [
                        ['type' => 'string', 'def' => 'value'],
                        ['type' => 'string', 'def' => 'key'],
                    ]
                ],
                'read' => [
                    'type' => '\FiiSoft\Jackdaw\Registry\RegReader',
                    'params' => ['string', ['type' => 'any', 'def' => null]]
                ],
                'get' => ['string', ['type' => 'any', 'def' => null]],
                'set' => ['type' => 'self', 'params' => ['string', 'any']],
                'entry' => [
                    'type' => '\FiiSoft\Jackdaw\Registry\RegEntry',
                    'params' => ['int', ['type' => 'any', 'def' => null]]
                ]
            ], [
                'methods' => ['shared', 'new'],
                'type' => 'self',
                'static' => true
            ], [
                'methods' => ['value', 'key'],
                'type' => '\FiiSoft\Jackdaw\Registry\RegWriter',
                'params' => ['string']
            ]
        ]);
    }
    
    public function test_RegReader(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Registry\RegReader',
            'isInterface' => true,
            'extends' => '\FiiSoft\Jackdaw\Memo\MemoReader',
            'methods' => [
                'read',
                'equals' => [
                    'type' => 'bool',
                    'params' => ['\FiiSoft\Jackdaw\Memo\MemoReader']
                ]
            ]
        ]);
    }
    
    public function test_RegWriter(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Registry\RegWriter',
            'isInterface' => true,
            'extends' => '\FiiSoft\Jackdaw\Memo\MemoWriter',
            'methods' => [
                'set' => ['type' => 'void', 'params' => ['any']],
                'write' => ['type' => 'void', 'params' => ['any', 'any']]
            ]
        ]);
    }
    
    public function test_ResultApi(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Internal\ResultApi',
            'isInterface' => true,
            'extends' => [
                '\FiiSoft\Jackdaw\Producer\ProducerReady',
                '\FiiSoft\Jackdaw\Mapper\MapperReady',
                '\FiiSoft\Jackdaw\Internal\Destroyable',
                '\Countable',
                '\IteratorAggregate',
            ],
            'methods' => [
                'found' => 'bool',
                'notFound' => 'bool',
                'get',
                'transform' => ['type' => 'self', 'params' => ['TransformerReady|callable|null']],
                'getOrElse' => ['callable|mixed|null'],
                'key',
                'tuple' => 'array',
                'call' => ['type' => 'void', 'params' => ['ConsumerReady|callable|resource']],
                'stream' => '\FiiSoft\Jackdaw\Stream',
                'toString' => ['type' => 'string', 'params' => [['type' => 'string', 'def' => ',']]],
                'toJson' => [
                    'type' => 'string',
                    'params' => [['type' => '?int', 'def' => null], ['type' => 'bool', 'def' => false]]
                ],
                'toJsonAssoc' => ['type' => 'string', 'params' => [['type' => '?int', 'def' => null]]],
                'toArray' => ['type' => 'array', 'params' => [['type' => 'bool', 'def' => false]]],
                'toArrayAssoc' => 'array',
                'count',
                'destroy' => 'void',
                'getIterator'
            ]
        ]);
    }
    
    public function test_SequenceInspector(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Memo\SequenceInspector',
            'isInterface' => true,
            'defaultType' => 'bool',
            'methods' => [
                'inspect' => ['\FiiSoft\Jackdaw\Memo\SequenceMemo'],
                'equals' => ['\FiiSoft\Jackdaw\Memo\SequenceInspector']
            ]
        ]);
    }
    
    public function test_SequenceMemo(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Memo\SequenceMemo',
            'isInterface' => true,
            'extends' => [
                '\FiiSoft\Jackdaw\Memo\MemoWriter',
                '\FiiSoft\Jackdaw\Operation\Internal\ForkReady',
                '\FiiSoft\Jackdaw\Producer\ProducerReady',
                '\FiiSoft\Jackdaw\Mapper\MapperReady',
                '\FiiSoft\Jackdaw\Transformer\TransformerReady',
                '\FiiSoft\Jackdaw\Internal\Destroyable',
                '\IteratorAggregate'
            ],
            'methods' => [
                'getIterator',
                'fold' => ['any', 'callable'],
                'reduce' => ['Reducer|callable'],
                'inspect' => [
                    'type' => '\FiiSoft\Jackdaw\Memo\SequencePredicate',
                    'params' => ['SequenceInspector|callable(SequenceMemo): bool']
                ],
                'matches' => [
                    'type' => '\FiiSoft\Jackdaw\Memo\SequencePredicate',
                    'params' => ['array', ['type' => 'Matcher|callable|null', 'def' => null]]
                ],
                'write' => [
                    'type' => 'void',
                    'params' => ['any', 'any']
                ],
                'stream' => '\FiiSoft\Jackdaw\Stream',
                'count' => 'int',
            ], [
                'methods' => ['get', 'remove'],
                'type' => '\FiiSoft\Jackdaw\Memo\Entry',
                'params' => ['int']
            ], [
                'methods' => ['key', 'value', 'tuple', 'pair'],
                'type' => '\FiiSoft\Jackdaw\Memo\MemoReader',
                'params' => ['int']
            ], [
                'methods' => ['valueOf', 'keyOf'],
                'params' => ['int']
            ], [
                'methods' => ['isFull', 'isEmpty'],
                'type' => 'bool'
            ], [
                'methods' => ['clear', 'destroy'],
                'type' => 'void'
            ], [
                'methods' => ['toArray', 'getValues', 'getKeys'],
                'type' => 'array'
            ]
        ]);
    }
    
    public function test_SequencePredicate(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Memo\SequencePredicate',
            'isInterface' => true,
            'extends' => '\FiiSoft\Jackdaw\Filter\FilterReady',
            'methods' => [
                'evaluate' => 'bool',
                'equals' => ['type' => 'bool', 'params' => ['\FiiSoft\Jackdaw\Memo\SequencePredicate']]
            ]
        ]);
    }
    
    public function test_SingleMemo(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Memo\SingleMemo',
            'isInterface' => true,
            'extends' => [
                '\FiiSoft\Jackdaw\Memo\MemoWriter',
                '\FiiSoft\Jackdaw\Memo\MemoReader',
            ],
            'methods' => [
                'read',
                'equals' => ['type' => 'bool', 'params' => ['\FiiSoft\Jackdaw\Memo\MemoReader']],
                'write' => ['type' => 'void', 'params' => ['any', 'any']],
            ]
        ]);
    }
    
    public function test_SplPriorityQueueAdapter(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Collector\Adapter\SplPriorityQueueAdapter',
            'extends' => '\FiiSoft\Jackdaw\Collector\BaseCollector',
            'methods' => [
                'set' => ['type' => 'void', 'params' => ['any', 'any']],
                'add' => ['type' => 'void', 'params' => ['any']],
                'getPriority' => 'int',
                'setPriority' => ['type' => 'void', 'params' => ['int']],
                'canPreserveKeys' => 'bool',
                'allowKeys' => ['type' => 'void', 'params' => ['?bool']],
            ], [
                'methods' => ['increasePriority', 'decreasePriority'],
                'type' => 'void',
                'params' => [['type' => 'int', 'def' => 1]]
            ]
        ]);
    }
    
    public function test_Stream(): void
    {
        $pBoolFalse = ['type' => 'bool', 'def' => false];
        $pBoolNull = ['type' => '?bool', 'def' => null];
        $pCollector = 'Collector|\ArrayAccess<string|int, mixed>|\SplHeap<mixed>|\SplPriorityQueue<int, mixed>';
        $pComparison = ['type' => 'ComparatorReady|callable|null', 'def' => null];
        $pConsumer = 'ConsumerReady|callable|resource';
        $pConsumerNull = ['type' => 'ConsumerReady|callable|resource|null', 'def' => null];
        $pDiscriminatorShort = 'DiscriminatorReady|callable|array<string|int>';
        $pDiscriminatorLong = 'DiscriminatorReady|callable|array<string|int>|string|int';
        $pElseMapper = ['type' => 'MapperReady|callable|iterable|mixed|null', 'def' => null];
        $pField = 'string|int';
        $pFields = 'array<string|int>|string|int';
        $pFieldsNull = ['type' => 'array<string|int>|string|int|null', 'def' => null];
        $pFilter = 'FilterReady|callable|array<string|int, mixed>|scalar';
        $pForkReady = '\FiiSoft\Jackdaw\Operation\Internal\ForkReady';
        $pInt0 = ['type' => 'int', 'def' => 0];
        $pInt1 = ['type' => 'int', 'def' => 1];
        $pIntNull = ['type' => '?int', 'def' => null];
        $pIntProvider = 'IntProvider|\Traversable<int>|iterable<int>|callable|int';
        $pMapper = 'MapperReady|callable|iterable|mixed';
        $pMappers = 'array<string|int, MapperReady|callable|iterable|mixed>';
        $pModeValue = ['type' => 'int', 'def' => Check::VALUE];
        $pProducer = 'ProducerReady|\Traversable<mixed>|resource|callable|iterable<mixed>|string';
        $pReducer = 'Reducer|callable';
        $tLastOperation = '\FiiSoft\Jackdaw\Operation\LastOperation';
        
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Stream',
            'extends' => [
                '\FiiSoft\Jackdaw\Internal\State\StreamSource',
                '\FiiSoft\Jackdaw\Producer\ProducerReady',
                '\FiiSoft\Jackdaw\Operation\Internal\DispatchReady',
                '\FiiSoft\Jackdaw\Internal\Executable',
                '\FiiSoft\Jackdaw\Internal\Destroyable',
                '\IteratorAggregate',
            ],
            'defaultType' => '\FiiSoft\Jackdaw\Stream',
            'methods' => [
                'call' => [['type' => $pConsumer, 'var' => true]],
                'callMax' => ['int', $pConsumer],
                'callOnce' => [$pConsumer],
                'callWhen' => [$pFilter, $pConsumer, $pConsumerNull],
                'castToTime' => [
                    $pFieldsNull,
                    ['type' => '?string', 'def' => null],
                    ['type' => '\DateTimeZone|string|null', 'def' => null],
                ],
                'categorizeBy' => [$pField, $pBoolNull],
                'classify' => [$pDiscriminatorShort],
                'collect' => ['type' => $tLastOperation, 'params' => [$pBoolFalse]],
                'collectIn' => [$pCollector, $pBoolNull],
                'collectKeysIn' => [$pCollector],
                'consume' => ['type' => 'void', 'params' => [$pProducer]],
                'countIn' => [['type' => '?int', 'ref' => true]],
                'destroy' => ['type' => 'void'],
                'empty' => ['static' => true],
                'extract' => [$pFields, ['type' => 'any', 'def' => null]],
                'feed' => [['type' => '\FiiSoft\Jackdaw\Internal\StreamPipe', 'var' => true]],
                'findMax' => ['type' => $tLastOperation, 'params' => ['int', $pFilter, $pIntNull]],
                'flat' => [$pInt0],
                'flatMap' => [$pMapper, $pInt0],
                'fold' => ['type' => $tLastOperation, 'params' => ['any', $pReducer]],
                'forEach' => ['type' => 'void', 'params' => [['type' => $pConsumer, 'var' => true]]],
                'fork' => [$pDiscriminatorShort, $pForkReady],
                'forkBy' => [$pField, $pForkReady],
                'forkByKey' => [$pForkReady],
                'forkMatch' => [
                    $pDiscriminatorShort,
                    'array<string|int, ForkReady>',
                    ['type' => '?\FiiSoft\Jackdaw\Operation\Internal\ForkReady', 'def' => null]
                ],
                'from' => ['static' => true, 'params' => [$pProducer]],
                'getIterator' => '\Iterator',
                'group' => [
                    'type' => '\FiiSoft\Jackdaw\Internal\Collection\BaseStreamCollection',
                    'params' => [$pBoolNull]
                ],
                'groupBy' => [
                    'type' => '\FiiSoft\Jackdaw\Internal\Collection\BaseStreamCollection',
                    'params' => [$pDiscriminatorLong, $pBoolNull]
                ],
                'join' => [[
                    'type' => 'ProducerReady|resource|callable|iterable<string|int, mixed>|string',
                    'var' => true
                ]],
                'loop' => ['type' => '\FiiSoft\Jackdaw\Internal\Executable', 'params' => [$pBoolFalse]],
                'mapBy' => [$pDiscriminatorShort, $pMappers],
                'mapByKey' => [$pMappers],
                'mapFieldWhen' => [$pField, $pFilter, $pMapper, $pElseMapper],
                'mapWhen' => [$pFilter, $pMapper, $pElseMapper],
                'of' => [
                    'static' => true,
                    'params' => [[
                        'type' => 'ProducerReady|\Traversable<mixed>|resource|callable|iterable<mixed>|object|scalar',
                        'var' => true
                    ]]
                ],
                'onError' => ['ErrorHandler|callable', $pBoolFalse],
                'onlyWith' => [$pFields, $pBoolFalse],
                'prototype' => [
                    'static' => true,
                    'params' => [[
                        'type' => 'ProducerReady|\Traversable<mixed>|resource|callable|iterable<mixed>|string|null',
                        'def' => null
                    ]]
                ],
                'putIn' => [['type' => 'any', 'ref' => true], $pModeValue],
                'putValueKeyIn' => [['type' => 'any', 'ref' => true], ['type' => 'any', 'ref' => true]],
                'readNext' => [['type' => $pIntProvider, 'def' => 1]],
                'reduce' => [
                    'type' => $tLastOperation,
                    'params' => [
                        'Reducer|callable|array<Reducer|callable>',
                        ['type' => 'callable|mixed|null', 'def' => null]
                    ]
                ],
                'reindex' => [$pInt0, $pInt1],
                'reindexBy' => [$pField, $pBoolFalse],
                'remember' => ['\FiiSoft\Jackdaw\Memo\MemoWriter'],
                'remove' => [['type' => $pFields, 'var' => true]],
                'rename' => [$pField, $pField],
                'route' => [$pFilter, '\FiiSoft\Jackdaw\Operation\Internal\DispatchReady'],
                'run' => ['type' => 'void', 'params' => [$pBoolFalse]],
                'scan' => ['any', $pReducer],
                'segregate' => [$pIntNull, $pBoolFalse, $pComparison, $pIntNull],
                'shuffle' => [$pIntNull],
                'skip' => ['IntProvider|callable|int'],
                'sortBy' => [['type' => $pField, 'var' => true]],
                'storeIn' => [
                    ['type' => '\ArrayAccess<string|int, mixed>|array<string|int, mixed>', 'ref' => true],
                    $pBoolFalse
                ],
                'toArray' => ['type' => 'array', 'params' => [$pBoolFalse]],
                'toArrayAssoc' => 'array',
                'toJson' => ['type' => 'string', 'params' => [$pIntNull, $pBoolFalse]],
                'toJsonAssoc' => ['type' => 'string', 'params' => [$pIntNull]],
                'toString' => ['type' => 'string', 'params' => [['type' => 'string', 'def' => ',']]],
                'unzip' => [['type' => '\FiiSoft\Jackdaw\Operation\Internal\DispatchReady', 'var' => true]],
                'window' => ['int', $pInt1, $pBoolFalse],
                'wrap' => [$pProducer],
                'zip' => [[
                    'type' => 'array<ProducerReady|resource|callable|iterable<string|int, mixed>|scalar>',
                    'var' => true
                ]],
            ], [
                'methods' => ['accumulate', 'gatherUntil', 'gatherWhile', 'separateBy'],
                'params' => [$pFilter, $pBoolFalse, $pIntNull]
            ], [
                'methods' => ['accumulateDowntrends', 'accumulateUptrends'],
                'params' => [$pBoolFalse, $pComparison]
            ], [
                'methods' => ['aggregate', 'remap', 'reorder'],
                'params' => ['array<string|int>']
            ], [
                'methods' => ['append', 'complete', 'mapField'],
                'params' => [$pField, $pMapper]
            ], [
                'methods' => [
                    'assert', 'extractWhen', 'filter', 'omit', 'removeWhen',
                    'skipUntil', 'skipWhile', 'until', 'while'
                ],
                'params' => [$pFilter, $pIntNull]
            ], [
                'methods' => ['best', 'worst'],
                'params' => ['int', $pComparison]
            ], [
                'methods' => ['callArgs', 'filterArgs', 'iterate', 'mapArgs', 'mapKV'],
                'params' => ['callable']
            ], [
                'methods' => ['callUntil', 'callWhile'],
                'params' => [$pFilter, $pConsumer]
            ], [
                'methods' => ['castToBool', 'castToFloat', 'castToInt', 'castToString'],
                'params' => [$pFieldsNull]
            ], [
                'methods' => ['categorize', 'chunkBy'],
                'params' => [$pDiscriminatorLong, $pBoolNull]
            ], [
                'methods' => ['categorizeByKey', 'chunkByKey'],
                'params' => [$pBoolNull]
            ], [
                'methods' => ['chunk', 'readMany'],
                'params' => [$pIntProvider, $pBoolFalse]
            ], [
                'methods' => ['classifyBy', 'moveTo'],
                'params' => [$pField, ['type' => 'string|int|null', 'def' => null]]
            ], [
                'methods' => ['collectKeys', 'collectValues', 'count', 'first', 'isEmpty', 'isNotEmpty', 'last'],
                'type' => $tLastOperation,
            ], [
                'methods' => ['concat', 'split', 'tokenize'],
                'params' => [['type' => 'string', 'def' => ' ']]
            ], [
                'methods' => ['decreasingTrend', 'increasingTrend', 'omitReps', 'rsort', 'sort', 'unique'],
                'params' => [$pComparison]
            ], [
                'methods' => ['dispatch', 'switch'],
                'params' => [$pDiscriminatorShort, 'DispatchReady[]']
            ], [
                'methods' => ['everyNth', 'limit', 'skipNth', 'tail'],
                'params' => ['int']
            ], [
                'methods' => ['filterBy', 'omitBy'],
                'params' => [$pField, $pFilter]
            ], [
                'methods' => ['filterKey', 'omitKey'],
                'params' => [$pFilter]
            ], [
                'methods' => ['filterUntil', 'filterWhen', 'filterWhile', 'omitWhen'],
                'params' => [$pFilter, $pFilter, $pIntNull]
            ], [
                'methods' => ['firstOrElse', 'lastOrElse'],
                'type' => $tLastOperation,
                'params' => ['callable|mixed|null']
            ], [
                'methods' => ['flip', 'reverse', 'trim', 'cache'],
            ], [
                'methods' => ['gather', 'makeTuple', 'unpackTuple'],
                'params' => [$pBoolFalse]
            ], [
                'methods' => ['greaterOrEqual', 'greaterThan', 'lessOrEqual', 'lessThan'],
                'params' => ['float|int', $pModeValue]
            ], [
                'methods' => ['collectUntil', 'collectWhile', 'find', 'has'],
                'type' => $tLastOperation,
                'params' => [$pFilter, $pIntNull]
            ], [
                'methods' => ['hasAny', 'hasEvery', 'hasOnly'],
                'type' => $tLastOperation,
                'params' => ['array', $pModeValue]
            ], [
                'methods' => ['map', 'mapKey'],
                'params' => [$pMapper]
            ], [
                'methods' => ['mapUntil', 'mapWhile'],
                'params' => [$pFilter, $pMapper]
            ], [
                'methods' => ['notEmpty', 'notNull', 'onlyIntegers', 'onlyNumeric', 'onlyStrings'],
                'params' => [$pModeValue]
            ], [
                'methods' => ['onFinish', 'onSuccess'],
                'params' => ['callable', $pBoolFalse]
            ], [
                'methods' => ['only', 'without'],
                'params' => ['array', $pModeValue]
            ], [
                'methods' => ['onlyExtrema', 'onlyMaxima', 'onlyMinima'],
                'params' => [['type' => 'bool', 'def' => true], $pComparison]
            ], [
                'methods' => ['readUntil', 'readWhile'],
                'params' => [$pFilter, $pIntNull, $pBoolFalse, $pConsumerNull]
            ]
        ]);
    }
    
    public function test_StringFilter(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Filter\String\StringFilter',
            'isInterface' => true,
            'extends' => '\FiiSoft\Jackdaw\Filter\Filter',
            'defaultType' => 'self',
            'methods' => [
                'inMode' => ['?int'],
                'isCaseInsensitive' => 'bool',
                'adjust' => ['\FiiSoft\Jackdaw\Filter\FilterAdjuster'],
                'buildStream' => ['type' => 'iterable', 'params' => ['iterable']],
                'equals' => ['type' => 'bool', 'params' => ['\FiiSoft\Jackdaw\Filter\Filter']],
                'getMode' => '?int',
                'isAllowed' => ['type' => 'bool', 'params' => ['any', ['type' => 'any', 'def' => null]]],
            ], [
                'methods' => [
                    'checkValue', 'checkKey', 'checkBoth', 'checkAny', 'negate', 'ignoreCase', 'caseSensitive'
                ]
            ], [
                'methods' => ['and', 'andNot', 'or', 'orNot', 'xor', 'xnor'],
                'params' => ['FilterReady|callable|array<string|int, mixed>|scalar']
            ]
        ]);
    }
    
    public function test_StringFilterPicker(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Filter\String\StringFilterPicker',
            'isInterface' => true,
            'defaultType' => '\FiiSoft\Jackdaw\Filter\String\StringFilter',
            'methods' => [
                'isString' => '\FiiSoft\Jackdaw\Filter\Filter',
                'length' => '\FiiSoft\Jackdaw\Filter\Size\Length\LengthFilterPicker',
                'not' => '\FiiSoft\Jackdaw\Filter\String\StringFilterPicker',
            ], [
                'methods' => [
                    'is', 'isNot', 'startsWith', 'notStartsWith', 'endsWith', 'notEndsWith', 'contains', 'notContains'
                ],
                'params' => ['string', ['type' => 'bool', 'def' => false]]
            ], [
                'methods' => ['inSet', 'notInSet'],
                'params' => ['array', ['type' => 'bool', 'def' => false]]
            ]
        ]);
    }
    
    public function test_TimeFilterPicker(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Filter\Time\TimeFilterPicker',
            'isInterface' => true,
            'defaultType' => '\FiiSoft\Jackdaw\Filter\Filter',
            'methods' => [
                'isDateTime',
                'not' => '\FiiSoft\Jackdaw\Filter\Time\TimeFilterPicker',
            ], [
                'methods' => ['isDay', 'isNotDay'],
                'params' => [['type' => 'string', 'var' => true]],
            ], [
                'methods' => ['is', 'isNot', 'from', 'until', 'before', 'after'],
                'params' => ['\DateTimeInterface|string']
            ], [
                'methods' => ['inside', 'notInside', 'between', 'outside'],
                'params' => ['\DateTimeInterface|string', '\DateTimeInterface|string']
            ], [
                'methods' => ['inSet', 'notInSet'],
                'params' => ['array<\DateTimeInterface|string>']
            ]
        ]);
    }
    
    public function test_Tokenizer(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Producer\Generator\Tokenizer',
            'extends' => '\FiiSoft\Jackdaw\Producer\Tech\BaseProducer',
            'methods' => [
                'getIterator' => ['type' => '\Generator'],
                'restartWith' => ['type' => 'self', 'params' => ['string', ['type' => '?string', 'def' => null]]],
                'keepIndex' => 'void',
                'destroy' => 'void',
                'stream' => '\FiiSoft\Jackdaw\Stream',
            ],
        ]);
    }
    
    public function test_Transformer(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Transformer\Transformer',
            'isInterface' => true,
            'extends' => '\FiiSoft\Jackdaw\Transformer\TransformerReady',
            'methods' => [
                'transform' => ['any', 'string|int']
            ]
        ]);
    }
    
    public function test_TypeFilterPicker(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Filter\CheckType\TypeFilterPicker',
            'isInterface' => true,
            'methods' => [
                'not' => '\FiiSoft\Jackdaw\Filter\CheckType\TypeFilterPicker'
            ], [
                'methods' => [
                    'isNull', 'notNull', 'isEmpty', 'notEmpty', 'isInt', 'isNumeric', 'isString', 'isBool', 'isFloat',
                    'isArray', 'isCountable', 'isDateTime',
                ],
                'type' => '\FiiSoft\Jackdaw\Filter\Filter'
            ]
        ]);
    }
    
    public function test_UuidGenerator(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Producer\Generator\Uuid\UuidGenerator',
            'isInterface' => true,
            'extends' => '\FiiSoft\Jackdaw\Producer\ProducerReady',
            'methods' => [
                'create' => 'string'
            ]
        ]);
    }
    
    public function test_ValueKeyWriter(): void
    {
        $this->examinePublishedApi([
            'sut' => '\FiiSoft\Jackdaw\Registry\ValueKeyWriter',
            'isInterface' => true,
            'extends' => '\FiiSoft\Jackdaw\Registry\RegWriter',
            'methods' => [
                'set' => ['type' => 'void', 'params' => ['any']],
                'write' => ['type' => 'void', 'params' => ['any', 'any']],
            ], [
                'methods' => ['value', 'key'],
                'type' => '\FiiSoft\Jackdaw\Registry\RegReader'
            ]
        ]);
    }
    
    
    
    private function examinePublishedApi(array $specification): void
    {
        $refl = new \ReflectionClass($specification['sut']);
        unset($specification['sut']);
        
        $specification = $this->prepareSpecification($specification);
        
        //check extends
        self::assertSame($specification['isInterface'], $refl->isInterface(), 'Checking is interface');
        
        if (!empty($specification['extends'])) {
            if (!$specification['isInterface']) {
                $parentClass = $refl->getParentClass();
                if ($parentClass !== false) {
                    $key = \array_search(
                        $parentClass->getName(),
                        \array_map(static fn(string $cls): string => \ltrim($cls, '\\'), $specification['extends']),
                        true
                    );
                    
                    if ($key !== false) {
                        unset($specification['extends'][$key]);
                    } else {
                        self::fail('Parent class '.$parentClass->getName().' not found on extends list');
                    }
                }
            }
            
            foreach ($specification['extends'] as $requiredAbstract) {
                self::assertTrue(
                    $refl->implementsInterface($requiredAbstract),
                    'Checking if interface '.$requiredAbstract.' is implemented'
                );
            }
        }
        
        unset($specification['extends'], $specification['isInterface']);
        
        //check constants
        $constants = $specification['constants'];
        unset($specification['constants']);
        
        self::assertCount(\count($constants), $refl->getConstants(), 'Checking number of constants');
        self::assertSame($constants, $refl->getConstants(), 'Checking constants');
        
        //check methods
        $publicMethods = $this->getPublicMethods($refl);
        $expectedNumOfMethods = $this->countNumOfMethods($specification);
        
        if (\count($publicMethods) !== $expectedNumOfMethods) {
            self::assertSame(
                $this->extratNamesOfMethod($specification),
                $this->getNamesOfMethod($publicMethods),
                'Checking public methods'
            );
        }
        
        $numOfCheckedMethods = 0;
        
        foreach ($publicMethods as $method) {
            $spec = $this->findSpecForMethod($method, $specification);
            if (empty($spec)) {
                continue;
            }

            $message = 'Checking method '.$method->getName();
            
            self::assertTrue($method->isPublic(), $message);
            self::assertSame($spec['static'], $method->isStatic(), $message);
            
            //check method's return type
            if (isset($spec['type'])) {
                if (\mb_strpos($spec['type'], '|')) {
                    self::assertNull($method->getReturnType(), $message);
                    self::assertSame($spec['type'], $this->getReturnDocComment($method));
                } else {
                    self::assertNotNull($method->getReturnType(), $message);
                    
                    $methodType = $spec['type'];
                    
                    if (\strncmp($methodType, '?', 1) === 0) {
                        $methodType = \substr($methodType, 1);
                        $allowsNull = true;
                    } else {
                        $allowsNull = false;
                    }
                    
                    self::assertSame($allowsNull, $method->getReturnType()->allowsNull(), $message);
                    self::assertSame(\ltrim($methodType, '\\'), $method->getReturnType()->getName(), $message);
                }
            } else {
                self::assertNull($method->getReturnType(), $message);
            }
            
            self::assertSame(\count($spec['params']), $method->getNumberOfParameters(), $message);
            
            //check method's parameters
            foreach ($spec['params'] as $num => $param) {
                $methodParam = $method->getParameters()[$num];
                $message = 'Checking param '.$methodParam->getName().' of method '.$method->getName();
                
                self::assertSame(!empty($param['var']), $methodParam->isVariadic(), $message);
                
                if (isset($param['type'])) {
                    $paramType = $param['type'];
                    $allowsNull = true;
                    
                    $isTypeArray = \mb_strpos($paramType, 'array<') === 0 || \mb_strpos($paramType, '[]') !== false;
                    if ($isTypeArray || $paramType === 'resource' || \mb_strpos($paramType, '|')) {
                        if ($isTypeArray) {
                            if ($methodParam->getType() !== null) {
                                self::assertSame('array', $methodParam->getType()->getName(), $message);
                                $allowsNull = false;
                            }
                        } else {
                            self::assertNull($methodParam->getType(), $message);
                        }
                        
                        self::assertSame($paramType, $this->getParamDocComment($method, $num), $message);
                    } else {
                        if (\strncmp($paramType, '?', 1) === 0) {
                            $paramType = \substr($paramType, 1);
                        } elseif ($paramType !== 'any') {
                            $allowsNull = false;
                        }
                        
                        if ($paramType === 'any') {
                            self::assertNull($methodParam->getType(), $message);
                        } else {
                            self::assertNotNull($methodParam->getType(), $message);
                            self::assertSame(\ltrim($paramType, '\\'), $methodParam->getType()->getName(), $message);
                        }
                    }
                    
                    self::assertSame($allowsNull, $methodParam->allowsNull(), $message);
                } else {
                    self::assertNull($methodParam->getType(), $message);
                }
                
                if ($param['ref'] ?? false) {
                    self::assertTrue($methodParam->isPassedByReference());
                }
                
                self::assertSame(
                    \array_key_exists('def', $param),
                    $methodParam->isDefaultValueAvailable(),
                    $message
                );
                
                if (\array_key_exists('def', $param)) {
                    self::assertSame($param['def'], $methodParam->getDefaultValue(), $message);
                }
            }
            
            ++$numOfCheckedMethods;
        }
        
        self::assertSame($expectedNumOfMethods, $numOfCheckedMethods);
    }
    
    /**
     * @param \ReflectionMethod[] $methods
     */
    private function getNamesOfMethod(array $methods): array
    {
        $names = \array_map(static fn(\ReflectionMethod $method): string => $method->getName(), $methods);
        \sort($names, \SORT_REGULAR);
        
        return $names;
    }
    
    private function extratNamesOfMethod(array $specification): array
    {
        $names = [];
        
        foreach ($specification as $spec) {
            foreach ($spec['methods'] as $method) {
                $names[] = $method;
            }
        }
        
        \sort($names, \SORT_REGULAR);
        
        return $names;
    }
    
    private function findSpecForMethod(\ReflectionMethod $method, array $specification): array
    {
        foreach ($specification as $spec) {
            if (\in_array($method->getName(), $spec['methods'], true)) {
                return $spec;
            }
        }
        
        return [];
    }
    
    /**
     * @return \ReflectionMethod[]
     */
    private function getPublicMethods(\ReflectionClass $refl): array
    {
        return \array_filter(
            $refl->getMethods(),
            static fn(\ReflectionMethod $method): bool => !$method->isConstructor() && $method->isPublic()
        );
    }
    
    private function getParamDocComment(\ReflectionMethod $method, int $paramNum): string
    {
        $nameOfParam = $method->getParameters()[$paramNum]->getName();
        $pattern = '/@param (?P<paramComm>[\p{L}\s\[\]:\(\),<>\\\|]+) (\.\.\.)?\$'.$nameOfParam.'/u';
        
        if (\preg_match($pattern, $method->getDocComment() ?: '', $matches)) {
            return $matches['paramComm'];
        }
        
        return '';
    }
    
    private function getReturnDocComment(\ReflectionMethod $method): string
    {
        if (\preg_match('/@return (?P<returnComm>[\p{L},<>\\\|]+)\s/u', $method->getDocComment() ?: '', $matches)) {
            return $matches['returnComm'];
        }
        
        return '';
    }
    
    private function prepareSpecification(array $specification): array
    {
        if (isset($specification['allStatic'])) {
            $isStatic = $specification['allStatic'];
            unset($specification['allStatic']);
        } else {
            $isStatic = false;
        }
        
        if (isset($specification['defaultType'])) {
            $defaultType = $specification['defaultType'];
            unset($specification['defaultType']);
        } else {
            $defaultType = null;
        }
        
        if (isset($specification['isInterface'])) {
            $isInterface = $specification['isInterface'];
            unset($specification['isInterface']);
        } else {
            $isInterface = false;
        }
        
        if (isset($specification['extends'])) {
            if (\is_array($specification['extends'])) {
                $extends = $specification['extends'];
            } else {
                $extends = [$specification['extends']];
            }
            unset($specification['extends']);
        } else {
            $extends = [];
        }
        
        if (isset($specification['constants'])) {
            $constants = $specification['constants'];
            unset($specification['constants']);
        } else {
            $constants = [];
        }
        
        if (isset($specification['methods'])) {
            $methods = $specification['methods'];
            unset($specification['methods']);
            
            foreach ($methods as $methodName => $methodSpec) {
                if (\is_string($methodSpec)) {
                    if (\is_int($methodName)) {
                        $methodName = $methodSpec;
                        $methodSpec = [];
                    } elseif (\is_string($methodName)) {
                        $methodSpec = ['type' => $methodSpec];
                    }
                } elseif (\is_array($methodSpec)
                    && !isset($methodSpec['params'])
                    && !isset($methodSpec['type'])
                    && !isset($methodSpec['static'])
                ) {
                    $methodSpec['params'] = $methodSpec;
                }
                
                $specification[] = [
                    'methods' => [$methodName],
                    'type' => $methodSpec['type'] ?? $defaultType,
                    'params' => $methodSpec['params'] ?? [],
                    'static' => $methodSpec['static'] ?? $isStatic,
                ];
            }
        }
        
        foreach ($specification as &$spec) {
            if (!isset($spec['static'])) {
                $spec['static'] = $isStatic;
            }
            
            if (!isset($spec['type'])) {
                $spec['type'] = $defaultType;
            }
            
            if (isset($spec['params'])) {
                foreach ($spec['params'] as $key => $param) {
                    if (\is_string($param)) {
                        if ($param === 'any') {
                            $spec['params'][$key] = [];
                        } else {
                            $spec['params'][$key] = ['type' => $param];
                        }
                    }
                }
            } else {
                $spec['params'] = [];
            }
        }
        
        unset($spec);
        
        $specification['isInterface'] = $isInterface;
        $specification['constants'] = $constants;
        $specification['extends'] = $extends;
        
        return $specification;
    }
    
    private function countNumOfMethods(array $specifications): int
    {
        return \array_sum(\array_map(static fn(array $spec): int => \count($spec['methods']), $specifications));
    }
}
