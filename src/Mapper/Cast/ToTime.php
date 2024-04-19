<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Cast;

use FiiSoft\Jackdaw\Mapper\Cast\ToTime\ToTimeFields;
use FiiSoft\Jackdaw\Mapper\Cast\ToTime\ToTimeSimple;
use FiiSoft\Jackdaw\Mapper\Exception\MapperExceptionFactory;
use FiiSoft\Jackdaw\Mapper\Internal\BaseMapper;

abstract class ToTime extends BaseMapper
{
    private ?string $format = null;
    private ?\DateTimeZone $timeZone = null;
    
    /**
     * @param array|string|int|null $fields
     * @param \DateTimeZone|string|null $inTimeZone
     */
    final public static function create(
        $fields = null,
        ?string $fromFormat = null,
        $inTimeZone = null
    ): self
    {
        if (\is_string($inTimeZone)) {
            $inTimeZone = new \DateTimeZone($inTimeZone);
        }
        
        $mapper = $fields === null ? new ToTimeSimple() : new ToTimeFields($fields);
        $mapper->setFormatAndTimeZone($fromFormat, $inTimeZone);
        
        return $mapper;
    }
    
    /**
     * @param \DateTimeInterface|string|int $time
     */
    final protected function cast($time): \DateTimeImmutable
    {
        if (\is_string($time) && $time !== '') {
            if ($this->format === null) {
                return new \DateTimeImmutable($time, $this->timeZone);
            }

            $dt = \DateTimeImmutable::createFromFormat($this->format, $time, $this->timeZone);
            if ($dt instanceof \DateTimeImmutable) {
                return $dt;
            }
            
            throw MapperExceptionFactory::cannotCreateTimeObjectFromString($time, $this->format);
        }
        
        if ($time instanceof \DateTime) {
            if ($this->timeZone === null) {
                return \DateTimeImmutable::createFromMutable($time);
            }

            $dt = \DateTimeImmutable::createFromMutable($time)->setTimezone($this->timeZone);
            if ($dt instanceof \DateTimeImmutable) {
                return $dt;
            }

            //@codeCoverageIgnoreStart
            throw MapperExceptionFactory::cannotCreateTimeObjectWithTimeZone($time, $this->timeZone);
            //@codeCoverageIgnoreEnd
        }
        
        if ($time instanceof \DateTimeImmutable) {
            if ($this->timeZone === null) {
                return $time;
            }

            $dt = $time->setTimezone($this->timeZone);
            if ($dt instanceof \DateTimeImmutable) {
                return $dt;
            }

            //@codeCoverageIgnoreStart
            throw MapperExceptionFactory::cannotCreateTimeObjectWithTimeZone($time, $this->timeZone);
            //@codeCoverageIgnoreEnd
        }
        
        if (\is_int($time)) {
            return new \DateTimeImmutable('@'.$time, $this->timeZone);
        }
        
        throw MapperExceptionFactory::cannotCreateTimeObjectFrom($time);
    }
    
    final protected function setFormatAndTimeZone(?string $format, ?\DateTimeZone $timeZone): void
    {
        $this->format = $format;
        $this->timeZone = $timeZone;
    }
}