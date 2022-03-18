<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Handler\Logger;

use FiiSoft\Jackdaw\Internal\Helper;

final class LogFormatter
{
    /**
     * @param \Throwable $error
     * @param mixed $value
     * @param string|int|mixed $key
     * @return string
     */
    public static function format(\Throwable $error, $value, $key): string
    {
        $message = 'Exception: '.\get_class($error);
        
        if (!empty($error->getMessage())) {
            $message .= ', message: ';
            
            if (!empty($error->getCode())) {
                $message .= '['.$error->getCode().'] ';
            }
            
            $message .= $error->getMessage();
        }
    
        return \date('[Y-m-d H:i:s] ').$message.', key: '.Helper::describe($key).', value: '.Helper::describe($value);
    }
}