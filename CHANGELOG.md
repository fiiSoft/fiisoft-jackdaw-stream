# Changelog

## 10.3.0

- SequenceMemo got two methods: getValues() and getKeys()  
- SequenceMemo can be used as a client of fork() 
- MemoWriter can be used as a client of dispatch() 

## 10.2.0

Executable::run() got flag for safe run.   
Some bugs fixed.

## 10.1.0

Stream got consume() method.

## 10.0.0

- added Matcher
- added Stream::mapBy()
- added Filters::readFrom()
- added Filters::wrapIntValue()
- added Filters::wrapMemoReader()
- added LastOperation::consume() 
- added Consumers::changeIntBy()
- added SequenceMemo::matches() 
- added SequenceMemo::inspect()
- added Producers::repeater() 
- added Producers::cyclic()
- SequencePredicate produced by matches() and inspect() can be used as Condition or Filter 
- SequenceMemo can be used as Mapper and Transformer
- Stream::chunk() and Stream::skip() are able to handle IntProvider 

#### Incompatible change:

- Filters::number()->isNumber() renamed to Filters::number()->isNumeric() 

## 9.0.0

- added Memo which duplicates functionality of Registry but is simpler to use
- Registry is now based on Memo
- operations readWhile() and readUntil() can trigger consumer after reading sequence
- added Mappers::reindexKeys()
- mapKV() was changed and does not support Mapper as value returned from passed callable; this is the only change that is backward incompatible 

## 8.1.0

- added some unique, experimental operations: readNext(), readMany(), readWhile(), readUntil()  
- added sugar-syntaxt operation putValueKeyIn()  
- added new concepts - IntValue, IntProvider   
- operation fork() can handle Reducer and IterableCollector directly (using Stream is not necessary in such cases)

Code seems to be compatible with PHP 8.4 (can run all tests).  

## 8.0.0

Changed behaviour of methods Counter::get() and Counter::count().  
Added ability to trigger processing of all parent streams by the last element in chain of execution.  
These changes are highly experimental and uncertain. 

## 7.4.0

A stream can be executed by directly calling methods from dependent streams passed to the feed() method.

## 7.3.0

- added Producers::dateTimeSeq() to stream sequence of \DateTimeImmutable objects.
- added Mappers::toTime() to cast values to \DateTimeImmutable
- added Mappers::formatTime() to cast \DateTimeInterface value into string
- added Filters::time()->isDay() and Filters::time()->isNotDay() to test time values against name of day
- added Discriminators::dayOfWeek()
- added Stream::castToTime() for convenient use of Mappers::toTime()

## 7.2.0

Added possibility to limit max number of elements collected in buckets by operation segregate().

## 7.1.0

Added the ability to compare key=>value pairs (treated as a coherent, single whole) in the uniqe() and omitReps() operations.

## 7.0.0

Many new features have been added and several errors fixed. Greatly increased speed in most use cases.  

Large portions of the code have been redesigned and rewritten, so overall they are completely incompatible with the earlier version.

## 6.3.0

Library ready for PHP 8.3

## 6.2.0

- added operations window() and everyNth()
- minor internal changes and improvements

## 6.1.0

Added new Stream methods: filterWhile(), filterUntil(), mapWhile(), mapUntil(), callWhile(), callUntil().

These "while" operations work in a similar way to other "while" functions already available. As long as the condition is met, the corresponding operation is performed, but the first time the condition is not met, this operation is removed from the call chain and is no longer executed (nor condition checked). Obviously, "until" operations work in the opposite way than "while".

## 6.0.0

Backward-incompatible changes:
- rewriten Registry module with some canges in API
- removed module Predicate with all classes and interfaces 
- removed various factory methods (use theirs getAdapter() instead): 
  - Producers::fromArray(), Producers::fromIterator(), Producers::fromArrayIterator(), Producers::fromResult(), Producers::fromCallable()
  - Conditions::generic(), Conditions::predicate(), Conditions::filter()
  - Discriminators::generic()
  - Comparators::generic()
  - Consumers::generic()
  - Reducers::generic()
  - Mappers::generic()
  - Filters::generic()

New features:
 - Registry\RegWriter can be used as Consumer
 - Registry\RegReader can be used as Producer and Discriminator
 - variable can be used as Producer through reference thanks to factory method Producers::readFrom()
 - added mapper Increment via Mappers::increment() and Mappers::decrement()

## 5.0.0

Several significant changes incompatible with previous version: 
 - changed arguments of methods: omitReps(), sort(), rsort(), best(), worst(), unique(), segregate(), accumulateUptrends(), accumulateDowntrends(), onlyMaxima(), onlyMinima(), onlyExtrema()
 - methods increasingValues() and decreasingValues() renamed to increasingTrend() and decreasingTrend() respectively; their arguments have also been changed
 - changed arguments in factory method Producers::randomUuid()
 - removed methods Comparators::valueAscKeyDesc() and Comparators::valueDescKeyAsc()
 - method Comparators::sortBy() renamed to Comparators::fields()
 - new method added to Mapper

New features:
 - added possibility to convenient use of symfony/uid as generator for UIDs
 - added possibility to use Stream, Producer, \Generator and \Traversable as Mapper 
 - greatly improved speed of operation unique() when a custom Comparator is provided
 - several other features related to sorting and comparison operations

## 4.2.0

Library has been adapted to be fully compatible with PHP >= 8.0 (up to 8.2).

If you have PHP > 7.4 you should be able to run `composer update` and everything should work with the newest packages, although I've found some of them trigger some deprecations. Anyway, everything seems to be working fine.

Special thanks for Pol Dellaiera (@drupol on Github) for his pull request which gave me the motivation to make the code compatible with PHP 8.2

## 4.1.0

- added method unzip()
- added possibility to use array as Collector (by reference)

## 4.0.0

- removed method chunkAssoc()
- changed the order of default arguments of several methods (those with $reindex parameter)
- changed criteria of what arguments for methods groupBy() and fork() are valid
- changed default behavior of groupBy() method when ByKey discriminator is used - it's now reindexed numerically by default
- changed methods while() and until() - they work now like conditional limit(). The behaviour of collectWhile(), collectUntil(), gatherWhile() and gatherUntil() has changed, as well as all other methods in combination with while() or until() in the pipeline.
- significantly changed and fixed how unique() works
- Discriminators::getAdapter does not accept non-callable string or int as key in array-accessible values 
- added operations: accumulateUptrends(), accumulateDowntrends(), onlyMaxima(), onlyMinima(), onlyExtrema(), omitReps(), findMax(), dispatch(), classify(), classifyBy(), categorize(), categorizeBy(), putIn(), storeIn(), segregate(), forkBy(), increasingValues(), decreasingValues(), group(), unpackTuple(), zip(), countIn()
- fixed bugs

## 3.0.0

Many changes not compatible with the previous version, many bugs fixed. Significant parts of the code have been rewritten and the API changed. Still PHP 7.4
The previous version has been abandoned.

- added new methods, e.g. fork()
- removed StreamApi
- rewritten StreamMaker - will be re-created in the future
- for some methods the parameters they accept have been changed, e.g. first(), last()
- the default behavior of retaining or re-indexing keys has been changed for many operations 
- redesigned and reorganized core class structure

## 2.17.0

- added QueueProducer

## 2.16.0

- added method StreamApi::chunkBy
- added method StreamApi::accumulate
- added method StreamApi::separateBy
- added reducer Count

## 2.15.0

- added method StreamApi::reindexBy
- added two special mappers Key and Value
- added special map operation StreamApi::mapKV

[//]: # (- added method StreamApi::moveFieldToKey)

## 2.14.0

- added method Producer::stream() so now every Prodcer can return Stream directly
- operation map can use Predicate in similar way like Filter

## 2.13.0

- added possibility to transform iterable values by Filter passed to operation map

## 2.12.0

- method stream added to ResultApi
- ResultApi extends \Countable

## 2.11.0 - incompatible changes!

- method StreamApi::feed can now accept many streams at once
- added operations collectWhile and collectUntil
- added operations gatherWhile and gatherUntil
- added operation makeTuple 
- added mapper Shuffle to shuffle arrays and strings
- behaviour of Result modified for methods toArray and toJson
- changed in how operation Gather works 

## 2.10.0

- optional parameters added to StreamApi methods reindex and shuffle

## 2.9.0

- Result can be use as Producer for Stream
- method StreamApi::join can accept many arguments at once
- added method StreamApi::gather

## 2.8.0 - incompatible changes!

- removed param limit from StreamApi::sortBy
- behaviour of feed streams considerably changed - looped streams are now able to feed themselves with first value from theirs starting producer 
- method StreamApi::sortBy can now accept integers
- added optional param fetchMode to Producers::fromPDOStatement
- added methods: omitBy, rename, remap, extractWhen, removeWhen to StreamApi
- method StreamApi::loop accepts optional param bool to autostart iteration

## 2.7.0

- added methods assert, trim to StreamApi
- added optional param key to method StreamApi::moveTo
- added new mappers: tokenize, trim

## 2.6.0 - incompatible changes!

- changed first argument of Consumers::stdout
- renamed method Filters::equal to Filters::same
- added methods to StreamApi: concat, tokenize, loop
- added new group of filters to test strings
- added new filters for integers
- added new producers: Tokenizer, Flattener
- huge modifications and refactoring
- some mechanics redesigned and rewritten
- performance optimisations

## 2.5.0

Added:

- new consumers to help test and develop this library
- consumer to write directly to any writable resource (including STDOUT)
- discriminator which returns key of current element
- some new filters
- some new mappers
- some new reducers
- some new producers (ability to read text from any readable resource, including STDIN)
- new methods to Result class to transform result got from stream or call callable when no result is available
- new type Transformer, to transform data provided by Result
- new methods to StreamApi: mapField, mapFieldWhen, castToFloat, castToString, castToBool

Also:
- performance improved
- some bugs fixed
- many new tests wrote
- signature of some StreamApi methods changed

## 2.4.0

- added ErrorHandler and ErrorLogger
- added methods onError, onSuccess, onFinish to StreamApi
- some changes to improve performance
- the version for PHP 7.0 has been dropped and will no longer be maintained

## 2.3.0 - incompatible changes!

- operation SortLimited rewritten
- operation Unique rewritten 
- operation Tail rewritten
- Tail operation no longer accepts 0 as an argument
- method stream() added to StreamCollection
- 
## 2.2.0 - incompatible changes!

- method StreamApi::sortBy accepts last integer param as limit
- method Reducer::consume changed, key is passed as second argument
- added method StreamApi::moveTo
- added method StreamApi::best
- added method StreamApi::worst
- many other changes

## 2.1.0 - incompatible changes!

- removed method Result::__toString
- removed method ResultItem::create
- added method StreamApi::collect
- added method StreamApi::aggregate
- added method StreamApi::complete
- added method StreamApi::onlyWith
- added method StreamApi::callOnce
- added method StreamApi::callMax
- added method StreamApi::callWhen
- added method StreamApi::mapWhen
- method Result::toString() accepts param `string $separator = ','`
- many changes in methods of Result: toJson, toJsonAssoc, toArray, toArrayAssoc - they accept default parameters as StreamApi and work different for array-results
- identical methods from StreamApi and Result moved to ResultCaster 
- class FiiSoft\Jackdaw\Collector\Collectors\Collect renamed to CollectIn
- composer.lock added to .gitignore

## 2.0.0

Version for PHP 7.4, developed on branch php74

## 1.0.0

Initial release for PHP 7.0.

Probably full of hidden bugs.

Have fun!