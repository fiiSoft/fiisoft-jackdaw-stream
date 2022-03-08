<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Handler\Logger;

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
    
        return \date('[Y-m-d H:i:s] ').$message.', key: '.self::describe($key).', value: '.self::describe($value);
    }
    
    /**
     * @param mixed $value
     * @return string
     */
    private static function describe($value): string
    {
        if ($value === null) {
            return 'NULL';
        }
    
        if (\is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }
    
        if (\is_numeric($value)) {
            return (string) $value;
        }
    
        if (\is_string($value)) {
            return \mb_strlen($value) > 50 ? \mb_substr($value, 0, 47).'...' : $value;
        }
    
        if (\is_array($value)) {
            $desc = 'array of length: '.\count($value);
            
            $json = \json_encode($value);
            if ($json !== false) {
                if (\mb_strlen($json) > 50) {
                    $desc .= ' '.\mb_substr($json, 0, 47).'...';
                } else {
                    $desc .= ' '.$json;
                }
            }
            
            return $desc;
        }
    
        if (\is_object($value)) {
            return 'object of class: '.\get_class($value);
        }
    
        return \gettype($value);
    }
}