<?php


/**
 * Contains a timestamp that can be manipulated in various ways.
 *
 * Last Updated 12/9/2009
 * @author Alex Brombal
 * @copyright 2006
 * @version 1.2.0
 */
class DayTime implements dbInsertable {

	private $year;
	private $month;
	private $date;
	private $hour;
	private $minute;
	private $second;
	private $millisecond;

	public static $months = array(
		1 => 'Jan',
		2 => 'Feb',
		3 => 'Mar',
		4 => 'Apr',
		5 => 'May',
		6 => 'Jun',
		7 => 'Jul',
		8 => 'Aug',
		9 => 'Sep',
		10 => 'Oct',
		11 => 'Nov',
		12 => 'Dec'
	);

	const DATETIME_SQL = "Y-m-d H:i:s";
	const DATE_SQL = "Y-m-d";
	const DATE_SHORT = "M j, Y";
	const DATE_LONG = "F jS, Y";
	const DATE_SLASHES = "n/j/Y";
	const TIME_12 = "g:i a";

	const YEAR = 1;
	const MONTH = 2;
	const DAY = 3;
	const HOUR = 4;
	const MINUTE = 5;
	const SECOND = 6;

	const MON = 1;
	const TUE = 2;
	const WED = 3;
	const THU = 4;
	const FRI = 5;
	const SAT = 6;
	const SUN = 7;

	public function __construct($year_ts_date = null, $month = null, $date = null, $hour = null, $minute = null, $second = null, $millisecond = null) {
		if($year_ts_date instanceof DayTime) {
			$this->setTimestamp($year_ts_date->timestamp());
			return;
		} elseif(is_int($year_ts_date) || (string)(int)$year_ts_date == $year_ts_date) {
			if($month) {
				$this->year = $year_ts_date;
				$this->setMonth($month);
				$this->setDate($date);
				$this->hour = max(0, min(23, (int)$hour));
				$this->minute = max(0, min(59, (int)$minute));
				$this->second = max(0, min(59, (int)$second));
				$this->millisecond = max(0, min(999, (int)$millisecond));
				return;
			} else {
				$this->setTimestamp($year_ts_date);
				return;
			}
		} elseif(is_string($year_ts_date) && $time = strtotime($year_ts_date)) {
			$this->setTimestamp($time);
			return;
		} elseif(!$year_ts_date) {
			$this->setTimestamp(time());
			return;
		}
		throw new Exception("Invalid date");
	}

	/**
	 * Creates and returns new DayTime
	 *
	 * @param mixed $dateTime
	 * @return DayTime
	 */
	public static function create($year_ts_date = null, $month = null, $date = null, $hour = null, $minute = null, $second = null, $millisecond = null) {
		return new DayTime($year_ts_date, $month, $date, $hour, $minute, $second, $millisecond);
	}

	private function setString($string) {
		$timestamp = strtotime($string);
		if(!$timestamp) return;
		$this->setTimestamp($timestamp);
	}

	private function setTimestamp($timestamp) {
		$this->year = date("Y", $timestamp);
		$this->month = date("m", $timestamp);
		$this->date = date("d", $timestamp);
		$this->hour = date("H", $timestamp);
		$this->minute = date("i", $timestamp);
		$this->second = date("s", $timestamp);
		$this->millisecond = ($u = date("u", $timestamp)) != 'u' ? $u : null;
		return $this;
	}

	public function timestamp() {
		return mktime($this->hour, $this->minute, $this->second, $this->month, $this->date, $this->year);
	}

	public function add($type, $amount) {
		$date = clone $this;
		switch($type) {
			case self::YEAR:
				$date->year += $amount;
				break;
			case self::MONTH:
				$date->year += floor(($date->month + $amount - 1) / 12);
				$date->month = ($date->month + $amount) % 12;
				if($date->month <= 0) $date->month = 12 + $date->month;
				$date->setDate($date->date);
				break;
			case self::DAY:
				$julian = $date->julian() + $amount;
				$date->setString(jdtogregorian($julian) . $this->format(' g:i a'));
				break;
			case self::HOUR:
				$date->setTimestamp($date->timestamp() + (60 * 60 * $amount));
				break;
			case self::MINUTE:
				$date->setTimestamp($date->timestamp() + (60 * $amount));
				break;
			case self::SECOND:
				$date->setTimestamp($date->timestamp() + $amount);
				break;
		}
		return $date;
	}

	public function subtract($type, $amount) {
		return $this->add($type, $amount * -1);
	}

	public function compare($type, DayTime $date) {
		switch($type) {
			case self::YEAR: return $this->year - $date->year;
			case self::MONTH: return ($this->month - $date->month) + ($date->compare(self::YEAR, $this) * 12);
			case self::DAY: return $this->julian() - $date->julian();
			case self::HOUR: return floor(($this->timestamp() - $date->timestamp()) / (60 * 60));
			case self::MINUTE: return floor(($this->timestamp() - $date->timestamp()) / 60);
			case self::SECOND: return $this->timestamp() - $date->timestamp();
		}
	}

	public function getDate() { return (int)$this->date; }
	public function setDate($date) {
		if($date > ($days = DayTime::daysInMonth($this->month, $this->year))) $this->date = $days;
		else $this->date = max(1, $date);
		return $this;
	}

	public function getMonth() { return $this->month; }
	public function setMonth($month) {
		if(is_string($month) && (string)(int)$month != $month) {
			$this->month = date(DayTime::DATE_M_INT, strtotime("1 $month"));
		}
		else $this->month = max(1, min(12, (int)$month));
		return $this;
	}

	public function getYear() { return $this->year; }
	public function setYear($year) { $this->year = (int)$year; return $this; }

	public function getHour() { return $this->hour; }
	public function setHour($hour) { $this->hour = max(min(24, $hour), 1); return $this; }

	public function getMinute() { return $this->minute; }
	public function setMinute($minute) { $this->minute = max(min(59, $minute), 0); return $this; }

	public function getSecond() { return $this->second; }
	public function setSecond($second) { $this->second = max(min(59, $second), 0); return $this; }

	public static function isLeapYear($year) {
		if($year instanceof DayTime) $year = $year->getYear();
		return (bool)date('L', strtotime("$year-01-01 00:00:00"));
	}

	public function getWeekday($iso = false) { return (int)date($iso ? "N" : "w", $this->timestamp()); }

	public function format($format) {
		return date($format, $this->timestamp());
	}

	public function julian() {
		return gregoriantojd((int)$this->month, (int)$this->date, (int)$this->year);
	}

	public function getWeek() {
		$week = ceil(($this->getDate() + 7 - ($this->getWeekday())) / 7);
		return $week;
	}

	public function getWeekdayOccurance() {
		return ceil($this->getDate() / 7);
	}

	public static function isLastDayOfMonth($date, $month = null, $year = null) {
		if($date instanceof DayTime) {
			$year = $date->getYear();
			$month = $date->getMonth();
			$date = $date->getDate();
		}
		return ($date == self::daysInMonth($month, $year));
	}

	public static function daysInMonth($month, $year) {
		if(in_array($month, array(1, 3, 5, 7, 8, 10, 12))) return 31;
		if(in_array($month, array(4, 6, 9, 11))) return 30;
		if($month == 2) {
			if(DayTime::isLeapYear($year)) return 29;
			else return 28;
		}
	}

	public static function daysInMonthArray($month, $year)
	{
		$days = self::daysInMonth($month, $year);
		for($i = 1; $i <= $days; $i++)
			$arr[] = $i;
		return $arr;
	}

	public static function getLatest($date1, $date2) {
		if(!($date1 instanceof DayTime)) $date1 = new DayTime($date1);
		if(!($date2 instanceof DayTime)) $date2 = new DayTime($date2);
		if($date1->compare(DayTime::DAY, $date2) < 0) return $date2;
		else return $date1;
	}

	public static function getEarliest($date1, $date2) {
		if(!($date1 instanceof DayTime)) $date1 = new DayTime($date1);
		if(!($date2 instanceof DayTime)) $date2 = new DayTime($date2);
		if($date1->compare(DayTime::DAY, $date2) > 0) return $date2;
		else return $date1;
	}


	public static function getWeekdayDate($which, $weekday, $month, $year = null) {
		if($month instanceof DayTime) {
			$year = $month->getYear();
			$month = $month->getMonth();
		}
		$which = min(5, max(1, (int)$which)) - 1;
		$wdOf1st = (int)date("N", strtotime("$year-$month-01"));
		$firstWd = 1 + (($weekday + 7 - $wdOf1st) % 7);
		if($which <= 3) return ($which * 7) + $firstWd;
		else {
			if(($which * 7) + $firstWd <= DayTime::daysInMonth($month, $year)) return ($which * 7) + $firstWd;
			else return $firstWd + (7 * 3);
		}
	}


	public function __dbValue() {
		return $this->format(DayTime::DATETIME_SQL);
	}

	public static function __fromDbValue($value, $type = null) {
		try {
			return new DayTime($value);
		} catch(Exception $e) {
			return null;
		}
	}

}