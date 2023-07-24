<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Handler\Logger\LogFormatter;
use FiiSoft\Jackdaw\Handler\Logger\Loggers;
use FiiSoft\Jackdaw\Handler\OnError;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\NullOutput;

final class OnErrorHandlersTest extends TestCase
{
    public function test_method_log_returns_null(): void
    {
        $handler = OnError::log(Loggers::getAdapter(new NullLogger()));
        
        self::assertNull($handler->handle(new \RuntimeException('Fake error'), 1, 'foo'));
    }
    
    public function test_method_logAndSkip_returns_true(): void
    {
        $handler = OnError::logAndSkip(Loggers::getAdapter(new NullLogger()));
        
        self::assertTrue($handler->handle(new \RuntimeException('Fake error'), 1, 'foo'));
    }
    
    public function test_method_logAndAbort_returns_false(): void
    {
        $handler = OnError::logAndAbort(Loggers::getAdapter(new NullLogger()));
        
        self::assertFalse($handler->handle(new \RuntimeException('Fake error'), 1, 'foo'));
    }
    
    public function test_callable_error_handler_must_return_bool_or_null_and_can_accept_various_number_of_params(): void
    {
        $error = new \RuntimeException('Fake error');
        
        self::assertTrue(OnError::call(static fn(): bool => true)->handle($error, 1, 'foo'));
        self::assertTrue(OnError::call(static fn($error): bool => true)->handle($error, 1, 'foo'));
        self::assertTrue(OnError::call(static fn($error, $key): bool => true)->handle($error, 1, 'foo'));
        self::assertTrue(OnError::call(static fn($error, $key, $value): bool => true)->handle($error, 1, 'foo'));
    }
    
    public function test_GenericErrorHandler_throws_exception_when_callable_requires_invalid_number_of_params(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('ErrorHandler have to accept 0, 1, 2 or 3 arguments, but requires 4');
    
        OnError::call(static fn($a, $b, $c, $d): bool => true)->handle(new \RuntimeException('Fake error'), 1, 'foo');
    }
    
    public function test_OnError_handler_can_log_to_various_loggers(): void
    {
        $error = new \RuntimeException('Fake error');
        
        OnError::log(Loggers::getAdapter(new NullLogger()))->handle($error, 1, 'foo');
        OnError::log(Loggers::getAdapter(new NullOutput()))->handle($error, 1, 'foo');
        OnError::log(Loggers::getAdapter(new ConsoleLogger(new NullOutput())))->handle($error, 1, 'foo');
        
        self::assertTrue(true);
    }
    
    public function test_exception_is_thrown_when_provided_logger_is_not_supported(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param logger');
        
        OnError::log(Loggers::getAdapter('unknown logger'));
    }
    
    public function test_exception_can_be_simply_echo_to_stdout(): void
    {
        $handler = OnError::log(Loggers::simple());
        
        \ob_start();
        $handler->handle(new \RuntimeException('Fake error', 456), 1, 'foo');
        $output = \ob_get_clean();
        
        self::assertStringContainsString(
            'Exception: RuntimeException, message: [456] Fake error, key: 1, value: foo',
            $output
        );
    }
    
    /**
     * @dataProvider getDataForTestLogFormatterDescribesData
     */
    public function test_LogFormatter_describes_data($value, $key, $message): void
    {
        self::assertStringContainsString($message, LogFormatter::format(new \RuntimeException(), $value, $key));
    }
    
    public function getDataForTestLogFormatterDescribesData(): array
    {
        $longArray = [
            'qwertyuiopasdfghjklzxcvbnmqwertyuiopasdfghjklzxcvbnmqwertyuiopasdfghjklzxcvbnmqwertyuiopasdfghjklzxcvbnm'
        ];
        
        $json = '["qwertyuiopasdfghjklzxcvbnmqwertyuiopasdfghjkl...';
        
        return [
            //value, key, message
            [null, 1, 'Exception: RuntimeException, key: 1, value: NULL'],
            [true, 1, 'Exception: RuntimeException, key: 1, value: TRUE'],
            [false, 1, 'Exception: RuntimeException, key: 1, value: FALSE'],
            [[1,2,3], 1, 'Exception: RuntimeException, key: 1, value: array of length: 3 [1,2,3]'],
            [$longArray, 1, 'Exception: RuntimeException, key: 1, value: array of length: 1 '.$json],
            [new NullLogger(), 1, 'Exception: RuntimeException, key: 1, value: object of class: '.NullLogger::class],
            [\fopen('php://memory', 'rb+'), 1, 'Exception: RuntimeException, key: 1, value: resource'],
        ];
    }
    
    public function test_OnError_skip_handler(): void
    {
        self::assertTrue(OnError::skip()->handle(new \RuntimeException(), 1, 'a'));
    }
    
    public function test_OnError_abort_handler(): void
    {
        self::assertFalse(OnError::abort()->handle(new \RuntimeException(), 1, 'a'));
    }
}