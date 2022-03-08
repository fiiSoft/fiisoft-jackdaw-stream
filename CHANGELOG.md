# Changelog

All important changes to `fiisoft-jackdaw-stream` will be documented in this file

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