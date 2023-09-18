## What is it?

Let's wrap native PHP array operations into stream-like wrappers, similar to Java and Scala. And... here we are! 

Of course, it's not as fast as clean PHP - and will never be, because it's impossible - but is fast enough. 

Previously was here: ~~this is the fastest library of its kind in complex scenarios which I've found so far~~ - but it's not so simple actually. Memory consumption and execution speed vary greatly not only from the type of data and the type of operations performed on it, but also from version of PHP, version of libraries and even if you run it as a native process or in a docker with the same version of PHP!. So, it depends.

Is it bugged? I hope not. But even so, it's still great and fun to play. 

### How to start?

It all starts with Stream::from(...). For example:

```php
Stream::from(Producers::sequentialInt())
    ->mapKey(static fn(int $n): int => ($n % 3 === 0 ? 2 : 0) | ($n % 5 === 0 ? 1 : 0))
    ->map(static fn(int $n, int $k): string => [$n, 'Buzz', 'Fizz', 'Fizz Buzz'][$k].', ')
    ->call(Consumers::usleep(100_000)) //slow down a bit
    ->forEach(STDOUT);
```

Let it flow!

### Disclaimer

I can change anything in this library without warning, although I'm trying to keep semantic versioning.

And as always: don't use it unless you're mentally strong enough to be immune to such bad code (and my bad English too). 
