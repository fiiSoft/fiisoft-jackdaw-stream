<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Registry\Registry;
use PHPUnit\Framework\TestCase;

final class ConsumersTest extends TestCase
{
    public function test_getAdapter_throws_exception_on_invalid_argument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param consumer - int 15');
        
        Consumers::getAdapter(15);
    }
    
    public function test_GenericConsumer_can_call_callable_with_one_argument(): void
    {
        $collector = [];
        
        Consumers::getAdapter(static function ($v) use (&$collector): void {
            $collector[] = $v;
        })->consume(15, 2);
        
        self::assertSame([15], $collector);
    }
    
    public function test_GenericConsumer_throws_exception_when_callable_accepts_wrong_number_of_params(): void
    {
        $this->expectException(\LogicException::class);
        
        Consumers::getAdapter(static fn($a,$b,$c): bool => true)->consume(2, 1);
    }
    
    /**
     * @dataProvider getDataForTestPrinterConsumerSimplyEchoOutputToStdout
     *
     * @param int $mode
     * @param string $expectedOutput
     * @return void
     */
    public function test_Printer_consumer_simply_echo_output_to_stdout(int $mode, string $expectedOutput): void
    {
        $consumer = Consumers::printer($mode);
        
        \ob_start();
        $consumer->consume('aaa', 1);
        $output = \ob_get_clean();
        
        self::assertSame($expectedOutput, $output);
    }
    
    public static function getDataForTestPrinterConsumerSimplyEchoOutputToStdout(): array
    {
        return [
            //mode, expected output
            [Check::VALUE, 'value: aaa'."\n"],
            [Check::KEY, 'key: 1'."\n"],
            [Check::BOTH, 'key: 1, value: aaa'."\n"],
            [Check::ANY, 'key: 1, value: aaa'."\n"],
        ];
    }
    
    public function test_ResourceWriter_throws_exception_when_param_is_not_resource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param resource');
        
        Consumers::resource('this is not resource');
    }
    
    public function test_ResourceWriter_can_write_value(): void
    {
        //given
        $resource = \fopen('php://memory', 'rb+');
        $consumer = Consumers::getAdapter($resource);
        
        //when
        $consumer->consume('foo', 1);
        
        //then
        \rewind($resource);
        self::assertSame('foo', \fgets($resource));
    }
    
    public function test_ResourceWriter_can_write_key(): void
    {
        //given
        $resource = \fopen('php://memory', 'rb+');
        $consumer = Consumers::resource($resource, Check::KEY);
        
        //when
        $consumer->consume('foo', 1);
        
        //then
        \rewind($resource);
        self::assertSame('1', \fgets($resource));
    }
    
    public function test_ResourceWriter_can_write_both_key_and_value_in_predefined_way(): void
    {
        //given
        $resource = \fopen('php://memory', 'rb+');
        $consumer = Consumers::resource($resource, Check::BOTH);
        
        //when
        $consumer->consume('foo', 1);
        
        //then
        \rewind($resource);
        self::assertSame('1:foo', \fgets($resource));
    }
    
    public function test_Sleeper_throws_exception_when_param_microseconds_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param microseconds');
        
        Consumers::usleep(-1);
    }
    
    public function test_Sleeper_can_sleep(): void
    {
        $waitTime = 50_000;
        
        $start = \microtime(true);
        Consumers::usleep($waitTime)->consume(1, 1);
        $stop = \microtime(true);
        
        self::assertTrue(($stop - $start) * 1_000_000 >= $waitTime);
    }
    
    public function test_StdoutWriter_allows_to_write_value(): void
    {
        \ob_start();
        Consumers::stdout('')->consume('foo', 1);
        $output = \ob_get_clean();
        
        self::assertSame('foo', $output);
    }
    
    public function test_StdoutWriter_allows_to_write_key(): void
    {
        \ob_start();
        Consumers::stdout('', Check::KEY)->consume('foo', 1);
        $output = \ob_get_clean();
        
        self::assertSame('1', $output);
    }
    
    public function test_StdoutWriter_allows_to_write_both_value_and_key_in_predefined_way(): void
    {
        \ob_start();
        Consumers::stdout('', Check::BOTH)->consume('foo', 1);
        $output = \ob_get_clean();
        
        self::assertSame('1:foo', $output);
    }
    
    public function test_Registry_can_be_used_as_Consumer(): void
    {
        //given
        $reg = Registry::new();
        $consumer = Consumers::getAdapter($reg->value('foo'));
        
        //when
        $consumer->consume(123, 'zoo');
        
        //then
        self::assertSame(123, $reg->read('foo')->read());
    }
}