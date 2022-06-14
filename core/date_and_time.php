<?php

namespace SKATES;


class DateTime extends \DateTime {
	
	private static $utcTimeZone;
	/**
	 * Singleton of UTC Time Zone
	 * 
	 * @return \DateTimeZone
	 */
	public static function getUTCTimeZone() {
		if (!self::$utcTimeZone)
			self::$utcTimeZone = new \DateTimeZone('UTC');
		return self::$utcTimeZone;
	}
	
	
	public function toDbFormat() {
		$tz = $this->getTimezone();
		$this->setTimezone(self::getUTCTimeZone());
		$r = $this->format(DB_DATETIME_FORMAT);
		$this->setTimezone($tz);
		return $r;
	}
	
	public function __toString() {
		$format = has_translation('skates.formats.datetime') ? __('skates.formats.datetime') : 'Y-m-d H:i:s';
		return $this->format($format);
	}
	
	public function add_seconds(int $interval) {
		$interval = new \DateInterval('PT'.$interval.'S');
		return parent::add($interval);
	}
	
	public function sub_seconds(int $interval) {
		$interval = new \DateInterval('PT'.$interval.'S');
		return parent::sub($interval);
	}
	
	public function compareTo(DateTime $other) {
		return $this->getTimestamp() - $other->getTimestamp();
	}
	
	public function equals(DateTime $other) {
		return $this->compareTo($other) === 0;
	}
	
	public function newerThan(DateTime $other) {
		return $this->compareTo($other) > 0;
	}
	
	public function newerThanOrEqual(DateTime $other) {
		return $this->compareTo($other) >= 0;
	}
	
	public function olderThan(DateTime $other) {
		return $this->compareTo($other) < 0;
	}
	
	public function olderThanOrEqual(DateTime $other) {
		return $this->compareTo($other) <= 0;
	}
	
	public function toDate(){
		$t = new Date();
		$t->setTimestamp($this->getTimestamp());
		$t->setTime(0,0,0);
		return $t;
	}
}


class Date extends DateTime {
	public function toDbFormat() {
		$tz = $this->getTimezone();
		$this->setTimezone(self::getUTCTimeZone());
		$r = $this->format(DB_DATE_FORMAT);
		$this->setTimezone($tz);
		return $r;
	}

	public function __toString() {
		$format = has_translation('skates.formats.date') ? __('skates.formats.date') : 'Y-m-d';
		return $this->format($format);
	}
	
	public function toDateTime() {
		$t = new DateTime();
		$t->setTimestamp($this->getTimestamp());
		return $t;
	}
}
