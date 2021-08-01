# Changelog

All important changes to `fiisoft-jackdaw-stream` will be documented in this file

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