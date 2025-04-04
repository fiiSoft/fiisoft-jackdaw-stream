## What is it?

This library allows to perform stream operations on iterable data, similar to Java or Scala.  

It comes with tons of unique features not available elsewhere. Will you ever need them? No, never.  
 

### How to install?

Use composer (`fiisoft/jackdaw-stream`). Code is compatible with PHP >=7.4 (up to the newest version).

### How to start?

Typically it starts with Stream::from(...). For example:

```php
Stream::from(Producers::sequentialInt())
    ->mapKey(static fn(int $n): int => ($n % 3 === 0 ? 2 : 0) | ($n % 5 === 0 ? 1 : 0))
    ->map(static fn(int $n, int $k): string => [$n, 'Buzz', 'Fizz', 'Fizz Buzz'][$k].', ')
    ->forEach(STDOUT);
```

Let it flow!

### Documentation

Does not exist. Sorry.

Class Stream is the entry point to this code, so look at public methods of this class, and their typehints. Analyse examples and tests.

### Disclaimer

I can change anything in this library without warning, although I try to keep semantic versioning.

And as always: don't use it unless you're mentally strong enough to be immune to such bad code (and my bad English too).

