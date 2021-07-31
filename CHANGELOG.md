# Changelog

All important changes to `fiisoft-jackdaw-stream` will be documented in this file

## 1.1.0 - incompatible changes!

- removed method Result::__toString
- removed method ResultItem::create
- added method StreamApi::collect
- added method StreamApi::aggregate
- method Result::toString() accepts param `string $separator = ','`
- class FiiSoft\Jackdaw\Collector\Collectors\Collect renamed to CollectIn
- composer.lock added to .gitignore

## 1.0.0

Initial release for PHP 7.0.

Probably full of hidden bugs.

Have fun!