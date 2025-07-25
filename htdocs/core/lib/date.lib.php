<?php
/* Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2015 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2017      Ferran Marcet        <fmarcet@2byte.es>
 * Copyright (C) 2018-2024 Charlene Benke       <charlene@patas-monkey.com>
 * Copyright (C) 2024-2025	MDW					<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024      Frédéric France      <frederic.france@free.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *  \file		htdocs/core/lib/date.lib.php
 *  \brief		Set of function to manipulate dates
 */


/**
 *  Return an array with timezone values
 *
 *  @return     array<int<-11,13>,string>   Array with timezone values
 */
function get_tz_array()
{
	$tzarray = array(
		-11 => "Pacific/Pago_Pago",
		-10 => "Pacific/Honolulu",
		-9 => "America/Anchorage",
		-8 => "America/Los_Angeles",
		-7 => "America/Dawson_Creek",
		-6 => "America/Chicago",
		-5 => "America/Bogota",
		-4 => "America/Asuncion",
		-3 => "America/Araguaina",
		-2 => "America/Noronha",
		-1 => "Atlantic/Azores",
		0 => "Europe/London",
		1 => "Europe/Paris",
		2 => "Europe/Helsinki",
		3 => "Europe/Moscow",
		4 => "Asia/Dubai",
		5 => "Asia/Karachi",
		6 => "Indian/Chagos",
		7 => "Asia/Jakarta",
		8 => "Asia/Hong_Kong",
		9 => "Asia/Tokyo",
		10 => "Australia/Sydney",
		11 => "Pacific/Noumea",
		12 => "Pacific/Auckland",
		13 => "Pacific/Fakaofo",
		14 => "Pacific/Kiritimati"
	);
	return $tzarray;
}


/**
 * Return server timezone string
 *
 * @return string			PHP server timezone string ('Europe/Paris')
 */
function getServerTimeZoneString()
{
	return @date_default_timezone_get();
}

/**
 * Return server timezone int.
 *
 * @param	string	$refgmtdate		Reference period for timezone (timezone differs on winter and summer. May be 'now', 'winter' or 'summer')
 * @return 	float					An offset in hour (+1 for Europe/Paris on winter and +2 for Europe/Paris on summer). Note some countries use half and even quarter hours.
 */
function getServerTimeZoneInt($refgmtdate = 'now')
{
	if (method_exists('DateTimeZone', 'getOffset')) {
		// Method 1 (include daylight)
		$gmtnow = dol_now('gmt');
		$yearref = dol_print_date($gmtnow, '%Y');
		$monthref = dol_print_date($gmtnow, '%m');
		$dayref = dol_print_date($gmtnow, '%d');
		if ($refgmtdate == 'now') {
			$newrefgmtdate = $yearref.'-'.$monthref.'-'.$dayref;
		} elseif ($refgmtdate == 'summer') {
			$newrefgmtdate = $yearref.'-08-01';
		} else {
			$newrefgmtdate = $yearref.'-01-01';
		}
		$newrefgmtdate .= 'T00:00:00+00:00';
		$localtz = new DateTimeZone(getServerTimeZoneString());
		$localdt = new DateTime($newrefgmtdate, $localtz);
		$tmp = -1 * $localtz->getOffset($localdt);
		//print $refgmtdate.'='.$tmp;
	} else {
		$tmp = 0;
		dol_print_error(null, 'PHP version must be 5.3+');
	}
	$tz = round(($tmp < 0 ? 1 : -1) * abs($tmp / 3600));
	return $tz;
}


/**
 *  Add a delay to a date
 *
 *  @param      int			$time               Date timestamp
 *  @param      float		$duration_value     Value of delay to add
 *  @param      string		$duration_unit      Unit of added delay (d, m, y, w, h, i)
 *  @param      int<0,1>    $ruleforendofmonth  Change the behavior of PHP over data-interval, 0 or 1
 *  @return     int      			        	New timestamp
 *  @see convertSecondToTime(), convertTimeToSeconds()
 */
function dol_time_plus_duree($time, $duration_value, $duration_unit, $ruleforendofmonth = 0)
{
	if (empty($duration_value)) {
		return $time;
	}
	if ($duration_unit == 's') {
		return $time + (int) ($duration_value);
	}
	if ($duration_unit == 'i' || $duration_unit == 'mn' || $duration_unit == 'min') {
		return $time + (int) (60 * $duration_value);
	}
	if ($duration_unit == 'h') {
		return $time + (int) (3600 * $duration_value);
	}
	if ($duration_unit == 'w') {
		return $time + (int) (3600 * 24 * 7 * $duration_value);
	}

	$deltastring = 'P';

	$sub = false;

	if ($duration_value > 0) {
		$deltastring .= abs($duration_value);
		$sub = false;
	}
	if ($duration_value < 0) {
		$deltastring .= abs($duration_value);
		$sub = true;
	}
	if ($duration_unit == 'd') {
		$deltastring .= "D";
	}
	if ($duration_unit == 'm') {
		$deltastring .= "M";
	}
	if ($duration_unit == 'y') {
		$deltastring .= "Y";
	}

	$date = new DateTime();
	if (getDolGlobalString('MAIN_DATE_IN_MEMORY_ARE_GMT')) {
		$date->setTimezone(new DateTimeZone('UTC'));
	}
	$date->setTimestamp((int) $time);
	$interval = new DateInterval($deltastring);

	if ($sub) {
		$date->sub($interval);
	} else {
		$date->add($interval);
	}
	//Change the behavior of PHP over data-interval when the result of this function is Feb 29 (non-leap years), 30 or Feb 31 (so php returns March 1, 2 or 3 respectively)
	if ($ruleforendofmonth == 1 && $duration_unit == 'm') {
		$timeyear = (int) dol_print_date($time, '%Y');
		$timemonth = (int) dol_print_date($time, '%m');
		$timetotalmonths = (($timeyear * 12) + $timemonth);

		$monthsexpected = ($timetotalmonths + $duration_value);

		$newtime = $date->getTimestamp();

		$newtimeyear = (int) dol_print_date($newtime, '%Y');
		$newtimemonth = (int) dol_print_date($newtime, '%m');
		$newtimetotalmonths = (($newtimeyear * 12) +  $newtimemonth);

		if ($monthsexpected < $newtimetotalmonths) {
			$newtimehours = (int) dol_print_date($newtime, '%H');
			$newtimemins = (int) dol_print_date($newtime, '%M');
			$newtimesecs = (int) dol_print_date($newtime, '%S');

			$datelim = dol_mktime($newtimehours, $newtimemins, $newtimesecs, $newtimemonth, 1, $newtimeyear);
			$datelim -= (3600 * 24);

			$date->setTimestamp($datelim);
		}
	}
	return $date->getTimestamp();
}


/**
 * Convert hours and minutes into seconds
 *
 * @param      int		$iHours     	Hours
 * @param      int		$iMinutes   	Minutes
 * @param      int		$iSeconds   	Seconds
 * @return     int						Time into seconds
 * @see convertSecondToTime()
 */
function convertTime2Seconds($iHours = 0, $iMinutes = 0, $iSeconds = 0)
{
	$iResult = ((int) $iHours * 3600) + ((int) $iMinutes * 60) + (int) $iSeconds;
	return $iResult;
}


/**	  	Return, in clear text, value of a number of seconds in days, hours and minutes.
 *      Can be used to show a duration.
 *
 *    	@param      int		$iSecond		Number of seconds
 *		@param		'all'|'allwithouthour'|'allhourmin'|'allhourminsec'|'allhour'|'fullhour'|'hour'|'min'|'sec'|'month'|'year'	$format		    Output format
 *                                                                                                                                              - 'all': total delay days hour:min like "2 days 12:30",
 *                                                                                                                                              - 'allwithouthour': total delay days without hour part like "2 days",
 *                                                                                                                                              - 'allhourmin': total delay with format hours:min like "60:30",
 *                                                                                                                                              - 'allhourminsec': total delay with format hours:min:sec like "60:30:10",
 *                                                                                                                                              - 'allhour': total delay hours without min/sec like "60:30",
 *                                                                                                                                              - 'fullhour': total delay hour decimal like "60.5" for 60:30,
 *                                                                                                                                              - 'hour': only hours part "12",
 *                                                                                                                                              - 'min': only minutes part "30",
 *                                                                                                                                              - 'sec': only seconds part,
 *                                                                                                                                              - 'month': only month part,
 *                                                                                                                                              - 'year': only year part);
 *      @param      int<1,86400>	$lengthOfDay    Length of day (default 86400 seconds for 1 day, 28800 for 8 hour)
 *      @param      int<1,7>	$lengthOfWeek   Length of week (default 7)
 *    	@return     string		 		 	Formatted text of duration
 * 	                                		Example: 0 return 00:00, 3600 return 1:00, 86400 return 1d, 90000 return 1 Day 01:00
 *      @see convertTime2Seconds()
 */
function convertSecondToTime($iSecond, $format = 'all', $lengthOfDay = 86400, $lengthOfWeek = 7)
{
	global $langs;

	if (empty($lengthOfDay)) {
		$lengthOfDay = 86400; // 1 day = 24 hours
	}
	if (empty($lengthOfWeek)) {
		$lengthOfWeek = 7; // 1 week = 7 days
	}
	$nbHbyDay = $lengthOfDay / 3600;

	$sTime = '';

	if ($format == 'all' || $format == 'allwithouthour' || $format == 'allhour' || $format == 'allhourmin' || $format == 'allhourminsec') {
		if ((int) $iSecond === 0) {
			return '0'; // This is to avoid having 0 return a 12:00 AM for en_US
		}

		$sDay = 0;
		$sWeek = 0;

		if ($iSecond >= $lengthOfDay) {
			for ($i = $iSecond; $i >= $lengthOfDay; $i -= $lengthOfDay) {
				$sDay++;
				$iSecond -= $lengthOfDay;
			}
			$dayTranslate = $langs->trans("Day");
			if ($iSecond >= ($lengthOfDay * 2)) {
				$dayTranslate = $langs->trans("Days");
			}
		}

		if ($lengthOfWeek < 7) {
			if ($sDay) {
				if ($sDay >= $lengthOfWeek) {
					$sWeek = (int) (($sDay - $sDay % $lengthOfWeek) / $lengthOfWeek);
					$sDay %= $lengthOfWeek;
					$weekTranslate = $langs->trans("DurationWeek");
					if ($sWeek >= 2) {
						$weekTranslate = $langs->trans("DurationWeeks");
					}
					$sTime .= $sWeek.' '.$weekTranslate.' ';
				}
			}
		}
		if ($sDay > 0) {
			$dayTranslate = $langs->trans("Day");
			if ($sDay > 1) {
				$dayTranslate = $langs->trans("Days");
			}
			$sTime .= $sDay.' '.$langs->trans("d").' ';
		}

		if ($format == 'all') {
			if ($iSecond || empty($sDay)) {
				$sTime .= dol_print_date($iSecond, 'hourduration', true);
			}
		} elseif ($format == 'allhourminsec') {
			return sprintf("%02d", ($sWeek * $lengthOfWeek * $nbHbyDay + $sDay * $nbHbyDay + (int) floor($iSecond / 3600))).':'.sprintf("%02d", ((int) floor(($iSecond % 3600) / 60))).':'.sprintf("%02d", ((int) ($iSecond % 60)));
		} elseif ($format == 'allhourmin') {
			return sprintf("%02d", ($sWeek * $lengthOfWeek * $nbHbyDay + $sDay * $nbHbyDay + (int) floor($iSecond / 3600))).':'.sprintf("%02d", ((int) floor(($iSecond % 3600) / 60)));
		} elseif ($format == 'allhour') {
			return sprintf("%02d", ($sWeek * $lengthOfWeek * $nbHbyDay + $sDay * $nbHbyDay + (int) floor($iSecond / 3600)));
		}
	} elseif ($format == 'hour') {	// only hour part
		$sTime = dol_print_date($iSecond, '%H', true);
	} elseif ($format == 'fullhour') {
		if (!empty($iSecond)) {
			$iSecond /= 3600;
		} else {
			$iSecond = 0;
		}
		$sTime = $iSecond;
	} elseif ($format == 'min') {	// only min part
		$sTime = dol_print_date($iSecond, '%M', true);
	} elseif ($format == 'sec') {	// only sec part
		$sTime = dol_print_date($iSecond, '%S', true);
	} elseif ($format == 'month') {	// only month part
		$sTime = dol_print_date($iSecond, '%m', true);
	} elseif ($format == 'year') {	// only year part
		$sTime = dol_print_date($iSecond, '%Y', true);
	}
	return trim((string) $sTime);
}


/**	  	Convert duration to hour
 *
 *    	@param      float	$duration_value		Duration value
 *    	@param      string	$duration_unit		Duration unit
 *      @return     float	$result
 *      @see measuringUnitString()
 */
function convertDurationtoHour($duration_value, $duration_unit)
{
	$result = 0;

	if ($duration_unit == 's') {
		$result = $duration_value / 3600;
	}
	if ($duration_unit == 'i' || $duration_unit == 'mn' || $duration_unit == 'min') {
		$result = $duration_value / 60;
	}
	if ($duration_unit == 'h') {
		$result = $duration_value;
	}
	if ($duration_unit == 'd') {
		$result = $duration_value * 24;
	}
	if ($duration_unit == 'w') {
		$result = $duration_value * 24 * 7;
	}
	if ($duration_unit == 'm') {
		$result = $duration_value * 730.484;
	}
	if ($duration_unit == 'y') {
		$result = $duration_value * 365 * 24;
	}

	return $result;
}

/**
 * Generate a SQL string to make a filter into a range (for second of date until last second of date).
 * This method allows to maje SQL request that will deal correctly the timezone of server.
 *
 * @param      string		$datefield			Name of SQL field where apply sql date filter
 * @param      int|string	$day_date			Day date (Can be 0 or '' for filter on a month)
 * @param      int|string	$month_date			Month date (Can be 0 or '' for filter on a year)
 * @param      int|string	$year_date			Year date
 * @param	   int      	$excludefirstand	Exclude first and
 * @param      bool|int<0,1>|'gmt'|'tzserver'|'tzref'|'tzuser'|'tzuserrel'	$gm		False or 0 or 'tzserver' = Input date fields are date info in the server TZ. True or 1 or 'gmt' = Input are date info in GMT TZ.
 *                                                                                  Note: In database, dates are always for the server TZ.
 * @return     string		$sqldate			String with SQL filter
 * @see forgeSQLFromUniversalSearchCriteria()
 * @see natural_search()
 */
function dolSqlDateFilter($datefield, $day_date, $month_date, $year_date, $excludefirstand = 0, $gm = false)
{
	global $db;
	$sqldate = '';

	$day_date = intval($day_date);
	$month_date = intval($month_date);
	$year_date = intval($year_date);

	if ($month_date > 0) {
		if ($month_date > 12) {	// protection for bad value of month
			return " AND 1 = 2";
		}
		if ($year_date > 0 && empty($day_date)) {
			$sqldate .= ($excludefirstand ? "" : " AND ").$datefield." BETWEEN '".$db->idate(dol_get_first_day($year_date, $month_date, $gm));
			$sqldate .= "' AND '".$db->idate(dol_get_last_day($year_date, $month_date, $gm))."'";
		} elseif ($year_date > 0 && !empty($day_date)) {
			$sqldate .= ($excludefirstand ? "" : " AND ").$datefield." BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month_date, $day_date, $year_date, $gm));
			$sqldate .= "' AND '".$db->idate(dol_mktime(23, 59, 59, $month_date, $day_date, $year_date, $gm))."'";
		} else {
			// This case is not reliable on TZ, but we should not need it.
			$sqldate .= ($excludefirstand ? "" : " AND ")." date_format( ".$datefield.", '%c') = '".$db->escape((string) $month_date)."'";
		}
	} elseif ($year_date > 0) {
		$sqldate .= ($excludefirstand ? "" : " AND ").$datefield." BETWEEN '".$db->idate(dol_get_first_day($year_date, 1, $gm));
		$sqldate .= "' AND '".$db->idate(dol_get_last_day($year_date, 12, $gm))."'";
	}
	return $sqldate;
}

/**
 *	Convert a string date into a GM Timestamps date
 *	Warning: YYYY-MM-DDTHH:MM:SS+02:00 (RFC3339) is not supported. If parameter gm is 1, we will use no TZ, if not we will use TZ of server, not the one inside string.
 *
 *	@param	string		$string		Date in a string
 *				     		        YYYYMMDD
 *	                 				YYYYMMDDHHMMSS
 *									YYYYMMDDTHHMMSSZ
 *									YYYY-MM-DDTHH:MM:SSZ (RFC3339)
 *		                			DD/MM/YY or DD/MM/YYYY (deprecated)
 *		                			DD/MM/YY HH:MM:SS or DD/MM/YYYY HH:MM:SS (deprecated)
 *  @param  int<0,1>|'gmt'|'tzserver'|'tzref'|'tzuser'|'tzuserrel'|'dayrfc'	$gm		'gmt' or 1 =Input date is GM date,
 *                                                                                  'tzserver' or 0 =Input date is date using PHP server timezone
 *  @return	int						Date as a timestamp
 *		                			19700101020000 -> 7200 with gm=1
 *									19700101000000 -> 0 with gm=1
 *
 *  @see    dol_print_date(), dol_mktime(), dol_getdate()
 */
function dol_stringtotime($string, $gm = 1)
{
	$reg = array();
	// Convert date with format DD/MM/YYY HH:MM:SS. This part of code should not be used.
	if (preg_match('/^([0-9]+)\/([0-9]+)\/([0-9]+)\s?([0-9]+)?:?([0-9]+)?:?([0-9]+)?/i', $string, $reg)) {
		dol_syslog("dol_stringtotime call to function with deprecated parameter format", LOG_WARNING);
		// Date est au format 'DD/MM/YY' ou 'DD/MM/YY HH:MM:SS'
		// Date est au format 'DD/MM/YYYY' ou 'DD/MM/YYYY HH:MM:SS'
		$sday = (int) $reg[1];
		$smonth = (int) $reg[2];
		$syear = (int) $reg[3];
		$shour = (int) $reg[4];
		$smin = (int) $reg[5];
		$ssec = (int) $reg[6];
		if ($syear < 50) {
			$syear += 1900;
		}
		if ($syear >= 50 && $syear < 100) {
			$syear += 2000;
		}
		$string = sprintf("%04d%02d%02d%02d%02d%02d", $syear, $smonth, $sday, $shour, $smin, $ssec);
	} elseif (preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})Z$/i', $string, $reg)	// Convert date with format YYYY-MM-DDTHH:MM:SSZ (RFC3339)
		|| preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/i', $string, $reg)	// Convert date with format YYYY-MM-DD HH:MM:SS
		|| preg_match('/^([0-9]{4})([0-9]{2})([0-9]{2})T([0-9]{2})([0-9]{2})([0-9]{2})Z$/i', $string, $reg)		// Convert date with format YYYYMMDDTHHMMSSZ
	) {
		$syear = (int) $reg[1];
		$smonth = (int) $reg[2];
		$sday = (int) $reg[3];
		$shour = (int) $reg[4];
		$smin = (int) $reg[5];
		$ssec = (int) $reg[6];
		$string = sprintf("%04d%02d%02d%02d%02d%02d", $syear, $smonth, $sday, $shour, $smin, $ssec);
	}

	$string = preg_replace('/([^0-9])/i', '', $string);
	$tmp = $string.'000000';
	// Clean $gm
	if ($gm === 1) {
		$gm = 'gmt';
	} elseif (empty($gm) || $gm === 'tzserver') {
		$gm = 'tzserver';
	}

	$date = dol_mktime((int) substr($tmp, 8, 2), (int) substr($tmp, 10, 2), (int) substr($tmp, 12, 2), (int) substr($tmp, 4, 2), (int) substr($tmp, 6, 2), (int) substr($tmp, 0, 4), $gm);

	return $date;
}


/**
 *  Return previous day
 *
 *  @param      int			$day     	Day
 *  @param      int			$month   	Month
 *  @param      int			$year    	Year
 *  @return     array{year:int,month:int,day:int}	Previous year,month,day
 */
function dol_get_prev_day($day, $month, $year)
{
	$time = dol_mktime(12, 0, 0, $month, $day, $year, 1, 0);
	$time -= 24 * 60 * 60;
	$tmparray = dol_getdate($time, true);
	return array('year' => $tmparray['year'], 'month' => $tmparray['mon'], 'day' => $tmparray['mday']);
}

/**
 *  Return next day
 *
 *  @param      int			$day    	Day
 *  @param      int			$month  	Month
 *  @param      int			$year   	Year
 *  @return     array{year:int,month:int,day:int}	Next year,month,day
 */
function dol_get_next_day($day, $month, $year)
{
	$time = dol_mktime(12, 0, 0, $month, $day, $year, 1, 0);
	$time += 24 * 60 * 60;
	$tmparray = dol_getdate($time, true);
	return array('year' => $tmparray['year'], 'month' => $tmparray['mon'], 'day' => $tmparray['mday']);
}

/**
 *  Return previous month
 *
 *	@param		int			$month		Month
 *	@param		int			$year		Year
 *	@return		array{year:int,month:int}	Previous year,month
 */
function dol_get_prev_month($month, $year)
{
	if ($month == 1) {
		$prev_month = 12;
		$prev_year  = $year - 1;
	} else {
		$prev_month = $month - 1;
		$prev_year  = $year;
	}
	return array('year' => $prev_year, 'month' => $prev_month);
}

/**
 *  Return next month
 *
 *	@param		int			$month		Month
 *	@param		int			$year		Year
 *	@return		array{year:int,month:int}	Next year,month
 */
function dol_get_next_month($month, $year)
{
	if ($month == 12) {
		$next_month = 1;
		$next_year  = $year + 1;
	} else {
		$next_month = $month + 1;
		$next_year  = $year;
	}
	return array('year' => $next_year, 'month' => $next_month);
}

/**
 *  Return previous week
 *
 *  @param      int			$day     	Day
 *  @param      int			$week    	Week
 *  @param      int			$month   	Month
 *	@param		int			$year		Year
 *	@return		array{year:int,month:int,day:int}	Previous year,month,day
 */
function dol_get_prev_week($day, $week, $month, $year)
{
	$tmparray = dol_get_first_day_week($day, $month, $year);

	$time = dol_mktime(12, 0, 0, $month, $tmparray['first_day'], $year, 1, 0);
	$time -= 24 * 60 * 60 * 7;
	$tmparray = dol_getdate($time, true);
	return array('year' => $tmparray['year'], 'month' => $tmparray['mon'], 'day' => $tmparray['mday']);
}

/**
 *  Return next week
 *
 *  @param      int			$day     	Day
 *  @param      int			$week    	Week
 *  @param      int			$month   	Month
 *	@param		int			$year		Year
 *	@return		array{year:int,month:int,day:int}		Next year,month,day
 */
function dol_get_next_week($day, $week, $month, $year)
{
	$tmparray = dol_get_first_day_week($day, $month, $year);

	$time = dol_mktime(12, 0, 0, $tmparray['first_month'], $tmparray['first_day'], $tmparray['first_year'], 1, 0);
	$time += 24 * 60 * 60 * 7;
	$tmparray = dol_getdate($time, true);

	return array('year' => $tmparray['year'], 'month' => $tmparray['mon'], 'day' => $tmparray['mday']);
}

/**
 *  Return GMT time for first day of a month or year
 *
 *	@param	int			$year		Year
 * 	@param	int			$month		Month
 *  @param	bool|int<0,1>|'gmt'|'tzserver'|'tzref'|'tzuser'|'tzuserrel'	$gm		False or 0 or 'tzserver' = Return date to compare with server TZ,
 *                                                                              True or 1 or 'gmt' to compare with GMT date.
 *                                                                              Example: dol_get_first_day(1970,1,false) will return -3600 with TZ+1, a dol_print_date on it will return 1970-01-01 00:00:00
 *                                                                              Example: dol_get_first_day(1970,1,true) will return 0 whatever is TZ, a dol_print_date on it will return 1970-01-01 00:00:00
 *  @return		int|''				Date as a timestamp, '' if error
 */
function dol_get_first_day($year, $month = 1, $gm = false)
{
	if ($year > 9999) {
		return '';
	}
	return dol_mktime(0, 0, 0, $month, 1, $year, $gm);
}


/**
 * Return GMT time for last day of a month or year.
 * Note: The timestamp contains last day and last hours (23:59:59)
 *
 *	@param		int			$year		Year
 * 	@param		int			$month		Month
 * 	@param		bool|int<0,1>|'gmt'|'tzserver'|'tzref'|'tzuser'|'tzuserrel'	$gm		False or 0 or 'tzserver' = Return date to compare with server TZ,
 *                                                                                  True or 1 or 'gmt' to compare with GMT date.
 *	@return		int|''					Date as a timestamp, '' if error
 */
function dol_get_last_day($year, $month = 12, $gm = false)
{
	if ($year > 9999) {
		return '';
	}
	if ($month == 12) {
		$month = 1;
		$year += 1;
	} else {
		$month += 1;
	}

	// On se deplace au debut du mois suivant, et on retire un jour
	$datelim = dol_mktime(23, 59, 59, $month, 1, $year, $gm);
	$datelim -= (3600 * 24);

	return $datelim;
}

/**
 *  Return GMT time for last hour of a given GMT date (it replaces hours, min and second part to 23:59:59)
 *
 *	@param		int			$date		Date GMT
 * 	@param		bool|int|string	$gm		False or 0 or 'tzserver' = Return date to compare with server TZ,
 * 										'gmt' to compare with GMT date.
 *  @return		int						Date for last hour of a given date
 */
function dol_get_last_hour($date, $gm = 'tzserver')
{
	$tmparray = dol_getdate($date, false, ($gm == 'gmt' ? 'gmt' : ''));
	return dol_mktime(23, 59, 59, $tmparray['mon'], $tmparray['mday'], $tmparray['year'], $gm);
}

/**
 *  Return GMT time for first hour of a given GMT date (it removes hours, min and second part)
 *
 *	@param		int			$date		Date GMT
 * 	@param		bool|int|string	$gm		False or 0 or 'tzserver' = Return date to compare with server TZ,
 * 										'gmt' to compare with GMT date.
 *  @return		int						Date for first hour of a given date
 */
function dol_get_first_hour($date, $gm = 'tzserver')
{
	$tmparray = dol_getdate($date, false, ($gm == 'gmt' ? 'gmt' : ''));
	return dol_mktime(0, 0, 0, $tmparray['mon'], $tmparray['mday'], $tmparray['year'], $gm);
}

/**	Return first day of week for a date. First day of week may be monday if option MAIN_START_WEEK is 1.
 *
 *	@param	int		$day		Day
 * 	@param	int		$month		Month
 *  @param	int		$year		Year
 *  @param	bool|int<0,1>|'gmt'|'tzserver'|'tzref'|'tzuser'|'tzuserrel'	$gm		False or 0 or 'tzserver' = Return date to compare with server TZ,
 *                                                                              True or 1 or 'gmt' to compare with GMT date.
 *	@return	array{year:int,month:int,week:string,first_day:int,first_month:int,first_year:int,prev_year:int,prev_month:int,prev_day:int}
 */
function dol_get_first_day_week($day, $month, $year, $gm = false)
{
	global $conf;

	//$day=2; $month=2; $year=2015;
	$date = dol_mktime(0, 0, 0, $month, $day, $year, $gm);

	//Checking conf of start week
	$start_week = (isset($conf->global->MAIN_START_WEEK) ? $conf->global->MAIN_START_WEEK : 1);

	$tmparray = dol_getdate($date, true); // detail of current day

	//Calculate days = offset from current day
	$days = $start_week - $tmparray['wday'];
	if ($days >= 1) {
		$days = 7 - $days;
	}
	$days = abs($days);
	$seconds = $days * 24 * 60 * 60;
	//print 'start_week='.$start_week.' tmparray[wday]='.$tmparray['wday'].' day offset='.$days.' seconds offset='.$seconds.'<br>';

	//Get first day of week
	$tmpdaytms = (int) date((string) $tmparray['0']) - $seconds; // $tmparray[0] is day of parameters
	$tmpday = idate("d", $tmpdaytms);

	//Check first day of week is in same month than current day or not
	if ($tmpday > $day) {
		$prev_month = $month - 1;
		$prev_year = $year;

		if ($prev_month == 0) {
			$prev_month = 12;
			$prev_year  = $year - 1;
		}
	} else {
		$prev_month = $month;
		$prev_year = $year;
	}
	$tmpmonth = $prev_month;
	$tmpyear = $prev_year;

	//Get first day of next week
	$tmptime = dol_mktime(12, 0, 0, $month, $tmpday, $year, 1, 0);
	$tmptime -= 24 * 60 * 60 * 7;
	$tmparray = dol_getdate($tmptime, true);
	$prev_day = $tmparray['mday'];

	//Check prev day of week is in same month than first day or not
	if ($prev_day > $tmpday) {
		$prev_month = $month - 1;
		$prev_year = $year;

		if ($prev_month == 0) {
			$prev_month = 12;
			$prev_year  = $year - 1;
		}
	}

	$week = date("W", dol_mktime(0, 0, 0, $tmpmonth, $tmpday, $tmpyear, $gm));

	return array('year' => $year, 'month' => $month, 'week' => $week, 'first_day' => $tmpday, 'first_month' => $tmpmonth, 'first_year' => $tmpyear, 'prev_year' => $prev_year, 'prev_month' => $prev_month, 'prev_day' => $prev_day);
}

/**
 *	Return the easter day in GMT time.
 *  This function replaces easter_date() that returns a date in local TZ.
 *
 *	@param	    int			$year     			Year
 *	@return   	int								GMT Date of easter day
 */
function getGMTEasterDatetime($year)
{
	$base = new DateTime("$year-03-21", new DateTimeZone("UTC"));
	$days = easter_days($year); // Return number of days between 21 march and easter day.
	$tmp = $base->add(new DateInterval("P{$days}D"));
	return $tmp->getTimestamp();
}

/**
 *  Return the number of non working days including Friday, Saturday and Sunday (or not) between 2 dates in timestamp.
 *  Dates must be UTC with hour, min, sec to 0.
 *  Called by function num_open_day()
 *
 *  @param	int			$timestampStart		Timestamp start (UTC with hour, min, sec = 0)
 *  @param	int			$timestampEnd		Timestamp end (UTC with hour, min, sec = 0)
 *  @param	string		$country_code		Country code
 *  @param	int			$lastday			Last day is included, 0: no, 1:yes
 *  @param	int			$includesaturday	Include saturday as non working day (-1=use setup, 0=no, 1=yes)
 *  @param	int			$includesunday		Include sunday as non working day (-1=use setup, 0=no, 1=yes)
 *  @param	int			$includefriday		Include friday as non working day (-1=use setup, 0=no, 1=yes)
 *  @param	int			$includemonday		Include monday as non working day (-1=use setup, 0=no, 1=yes)
 *  @return	int|string						Number of non working days or error message string if error
 *  @see num_between_day(), num_open_day()
 */
function num_public_holiday($timestampStart, $timestampEnd, $country_code = '', $lastday = 0, $includesaturday = -1, $includesunday = -1, $includefriday = -1, $includemonday = -1)
{
	global $conf, $db, $mysoc;

	$nbFerie = 0;

	// Check to ensure we use correct parameters
	if (($timestampEnd - $timestampStart) % 86400 != 0) {
		return 'Error Dates must use same hours and must be GMT dates';
	}

	if (empty($country_code)) {
		$country_code = $mysoc->country_code;
	}
	if ($includemonday < 0) {
		$includemonday = getDolGlobalInt('MAIN_NON_WORKING_DAYS_INCLUDE_MONDAY', 0);
	}
	if ($includefriday < 0) {
		$includefriday = getDolGlobalInt('MAIN_NON_WORKING_DAYS_INCLUDE_FRIDAY', 0);
	}
	if ($includesaturday < 0) {
		$includesaturday = getDolGlobalInt('MAIN_NON_WORKING_DAYS_INCLUDE_SATURDAY', 1);
	}
	if ($includesunday < 0) {
		$includesunday = getDolGlobalInt('MAIN_NON_WORKING_DAYS_INCLUDE_SUNDAY', 1);
	}

	$country_id = dol_getIdFromCode($db, $country_code, 'c_country', 'code', 'rowid');

	if (empty($conf->cache['arrayOfActivePublicHolidays_'.$country_id])) {
		// Loop on public holiday defined into hrm_public_holiday for the day, month and year analyzed
		$tmpArrayOfPublicHolidays = array();
		$sql = "SELECT id, code, entity, fk_country, dayrule, year, month, day, active";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_hrm_public_holiday";
		$sql .= " WHERE active = 1 and fk_country IN (0".($country_id > 0 ? ", ".$country_id : 0).")";
		$sql .= " AND entity IN (0," .getEntity('holiday') .")";

		$resql = $db->query($sql);
		if ($resql) {
			$num_rows = $db->num_rows($resql);
			$i = 0;
			while ($i < $num_rows) {
				$obj = $db->fetch_object($resql);
				$tmpArrayOfPublicHolidays[$obj->id] = array('dayrule' => $obj->dayrule, 'year' => $obj->year, 'month' => $obj->month, 'day' => $obj->day);
				$i++;
			}
		} else {
			dol_syslog($db->lasterror(), LOG_ERR);
			return 'Error sql '.$db->lasterror();
		}

		//var_dump($tmpArrayOfPublicHolidays);
		$conf->cache['arrayOfActivePublicHolidays_'.$country_id] = $tmpArrayOfPublicHolidays;
	}

	$arrayOfPublicHolidays = $conf->cache['arrayOfActivePublicHolidays_'.$country_id];

	$i = 0;
	while ((($lastday == 0 && $timestampStart < $timestampEnd) || ($lastday && $timestampStart <= $timestampEnd))
		&& ($i < 50000)) {		// Loop end when equals (Test on i is a security loop to avoid infinite loop)
		$ferie = false;
		$specialdayrule = array();

		$jour  = (int) gmdate("d", $timestampStart);
		$mois  = (int) gmdate("m", $timestampStart);
		$annee = (int) gmdate("Y", $timestampStart);
		$jour_semaine = (int) gmdate("w", $timestampStart);		// sunday = 0, monday = 1, ...

		//print "jour=".$jour." month=".$mois." year=".$annee." includesaturday=".$includesaturday." includesunday=".$includesunday."\n";
		foreach ($arrayOfPublicHolidays as $entrypublicholiday) {
			if (!empty($entrypublicholiday['dayrule']) && $entrypublicholiday['dayrule'] != 'date') {		// For example 'easter', '...'
				$specialdayrule[$entrypublicholiday['dayrule']] = $entrypublicholiday['dayrule'];
			} else {
				$match = 1;
				if (!empty($entrypublicholiday['year']) && $entrypublicholiday['year'] != $annee) {
					$match = 0;
				}
				if ($entrypublicholiday['month'] != $mois) {
					$match = 0;
				}
				if ($entrypublicholiday['day'] != $jour) {
					$match = 0;
				}

				if ($match) {
					$ferie = true;
				}
			}

			$i++;
		}
		//var_dump($specialdayrule)."\n";
		//print "ferie=".$ferie."\n";

		if (!$ferie) {
			// Special dayrules
			if (in_array('easter', $specialdayrule)) {
				// Calculation for easter date
				$date_paques = getGMTEasterDatetime($annee);
				$jour_paques = gmdate("d", $date_paques);
				$mois_paques = gmdate("m", $date_paques);
				if ($jour_paques == $jour && $mois_paques == $mois) {
					$ferie = true;
				}
				// Easter (sunday)
			}

			if (in_array('eastermonday', $specialdayrule)) {
				// Calculation for the monday of easter date
				$date_paques = getGMTEasterDatetime($annee);
				//print 'PPP'.$date_paques.' '.dol_print_date($date_paques, 'dayhour', 'gmt')." ";
				$date_lundi_paques = $date_paques + (3600 * 24);
				$jour_lundi_paques = gmdate("d", $date_lundi_paques);
				$mois_lundi_paques = gmdate("m", $date_lundi_paques);
				if ($jour_lundi_paques == $jour && $mois_lundi_paques == $mois) {
					$ferie = true;
				}
				// Easter (monday)
				//print 'annee='.$annee.' $jour='.$jour.' $mois='.$mois.' $jour_lundi_paques='.$jour_lundi_paques.' $mois_lundi_paques='.$mois_lundi_paques."\n";
			}

			//Good Friday
			if (in_array('goodfriday', $specialdayrule)) {
				// Pulls the date of Easter
				$easter = getGMTEasterDatetime($annee);

				// Calculates the date of Good Friday based on Easter
				$date_good_friday  = $easter - (2 * 3600 * 24);
				$dom_good_friday   = gmdate("d", $date_good_friday);
				$month_good_friday = gmdate("m", $date_good_friday);

				if ($dom_good_friday == $jour && $month_good_friday == $mois) {
					$ferie = true;
				}
			}

			if (in_array('ascension', $specialdayrule)) {
				// Calcul du jour de l'ascension (39 days after easter day)
				$date_paques = getGMTEasterDatetime($annee);
				$date_ascension = $date_paques + (3600 * 24 * 39);
				$jour_ascension = gmdate("d", $date_ascension);
				$mois_ascension = gmdate("m", $date_ascension);
				if ($jour_ascension == $jour && $mois_ascension == $mois) {
					$ferie = true;
				}
				// Ascension (thursday)
			}

			if (in_array('pentecost', $specialdayrule)) {
				// Calculation of "Pentecote" (49 days after easter day)
				$date_paques = getGMTEasterDatetime($annee);
				$date_pentecote = $date_paques + (3600 * 24 * 49);
				$jour_pentecote = gmdate("d", $date_pentecote);
				$mois_pentecote = gmdate("m", $date_pentecote);
				if ($jour_pentecote == $jour && $mois_pentecote == $mois) {
					$ferie = true;
				}
				// "Pentecote" (sunday)
			}
			if (in_array('pentecotemonday', $specialdayrule)) {
				// Calculation of "Pentecote" (49 days after easter day)
				$date_paques = getGMTEasterDatetime($annee);
				$date_pentecote = $date_paques + (3600 * 24 * 50);
				$jour_pentecote = gmdate("d", $date_pentecote);
				$mois_pentecote = gmdate("m", $date_pentecote);
				if ($jour_pentecote == $jour && $mois_pentecote == $mois) {
					$ferie = true;
				}
				// "Pentecote" (monday)
			}

			if (in_array('viernessanto', $specialdayrule)) {
				// Viernes Santo
				$date_paques = getGMTEasterDatetime($annee);
				$date_viernes = $date_paques - (3600 * 24 * 2);
				$jour_viernes = gmdate("d", $date_viernes);
				$mois_viernes = gmdate("m", $date_viernes);
				if ($jour_viernes == $jour && $mois_viernes == $mois) {
					$ferie = true;
				}
				//Viernes Santo
			}

			if (in_array('fronleichnam', $specialdayrule)) {
				// Fronleichnam (60 days after easter sunday)
				$date_paques = getGMTEasterDatetime($annee);
				$date_fronleichnam = $date_paques + (3600 * 24 * 60);
				$jour_fronleichnam = gmdate("d", $date_fronleichnam);
				$mois_fronleichnam = gmdate("m", $date_fronleichnam);
				if ($jour_fronleichnam == $jour && $mois_fronleichnam == $mois) {
					$ferie = true;
				}
				// Fronleichnam
			}

			if (in_array('genevafast', $specialdayrule)) {
				// Geneva fast in Switzerland (Thursday after the first sunday in September)
				$date_1sunsept = strtotime('next thursday', strtotime('next sunday', mktime(0, 0, 0, 9, 1, $annee)));
				$jour_1sunsept = date("d", $date_1sunsept);
				$mois_1sunsept = date("m", $date_1sunsept);
				if ($jour_1sunsept == $jour && $mois_1sunsept == $mois) {
					$ferie = true;
				}
				// Geneva fast in Switzerland
			}
		}
		//print "ferie=".$ferie."\n";

		// If we have to include Friday, Saturday and Sunday
		if (!$ferie) {
			if ($includefriday || $includesaturday || $includesunday || $includemonday) {
				//Monday (1), Friday (5), Saturday (6) and Sunday (0)
				if ($includefriday && $jour_semaine == 5) {
					$ferie = true;
				}
				if ($includesaturday && $jour_semaine == 6) {
					$ferie = true;
				}
				if ($includesunday && $jour_semaine == 0) {
					$ferie = true;
				}
				if ($includemonday && $jour_semaine == 1) {
					$ferie = true;
				}
			}
		}
		//print "ferie=".$ferie."\n";

		// We increase the counter of non working day
		if ($ferie) {
			$nbFerie++;
		}

		// Increase number of days (on go up into loop)
		$timestampStart = dol_time_plus_duree($timestampStart, 1, 'd');
		//var_dump($jour.' '.$mois.' '.$annee.' '.$timestampStart);

		$i++;
	}

	//print "nbFerie=".$nbFerie."\n";
	return $nbFerie;
}

/**
 *  Return the list of public holidays including Friday, Saturday and Sunday (or not) between 2 dates in timestamp.
 *  Dates must be UTC with hour, min, sec to 0.
 *  TODO Not used yet. Must be shared with num_public_holiday()
 *
 * @param 	int 		$timestampStart 	Timestamp start (UTC with hour, min, sec = 0)
 * @param 	int 		$timestampEnd 		Timestamp end (UTC with hour, min, sec = 0)
 * @param 	string 		$country_code 		Country code
 * @param 	int 		$lastday 			Last day is included, 0: no, 1:yes
 * @param 	int 		$excludesaturday 	Exclude saturday as non working day (-1=use setup, 0=no, 1=yes)
 * @param 	int 		$excludesunday 		Exclude sunday as non working day (-1=use setup, 0=no, 1=yes)
 * @param 	int 		$excludefriday 		Exclude friday as non working day (-1=use setup, 0=no, 1=yes)
 * @param 	int 		$excludemonday 		Exclude monday as non working day (-1=use setup, 0=no, 1=yes)
 * @return	string|int[]                    List of public holidays timestamps or error message string if error
 * @see num_public_holiday(), num_open_day()
 */
function listPublicHoliday($timestampStart, $timestampEnd, $country_code = '', $lastday = 0, $excludesaturday = -1, $excludesunday = -1, $excludefriday = -1, $excludemonday = -1)
{
	global $conf, $db, $mysoc;

	// Check to ensure we use correct parameters
	if (($timestampEnd - $timestampStart) % 86400 != 0) {
		return 'Error Dates must use same hours and must be GMT dates';
	}

	if (empty($country_code)) {
		$country_code = $mysoc->country_code;
	}
	if ($excludemonday < 0) {
		$excludemonday = getDolGlobalInt('MAIN_NON_WORKING_DAYS_INCLUDE_MONDAY', 0);
	}
	if ($excludefriday < 0) {
		$excludefriday = getDolGlobalInt('MAIN_NON_WORKING_DAYS_INCLUDE_FRIDAY', 0);
	}
	if ($excludesaturday < 0) {
		$excludesaturday = getDolGlobalInt('MAIN_NON_WORKING_DAYS_INCLUDE_SATURDAY', 1);
	}
	if ($excludesunday < 0) {
		$excludesunday = getDolGlobalInt('MAIN_NON_WORKING_DAYS_INCLUDE_SUNDAY', 1);
	}

	$country_id = dol_getIdFromCode($db, $country_code, 'c_country', 'code', 'rowid');

	if (empty($conf->cache['arrayOfActivePublicHolidays_' . $country_id])) {
		// Loop on public holiday defined into hrm_public_holiday for the day, month and year analyzed
		$tmpArrayOfPublicHolidays = array();
		$sql = "SELECT id, code, entity, fk_country, dayrule, year, month, day, active";
		$sql .= " FROM " . MAIN_DB_PREFIX . "c_hrm_public_holiday";
		$sql .= " WHERE active = 1 and fk_country IN (0" . ($country_id > 0 ? ", " . $country_id : 0) . ")";
		$sql .= " AND entity IN (0," . getEntity('holiday') . ")";

		$resql = $db->query($sql);
		if ($resql) {
			$num_rows = $db->num_rows($resql);
			$i = 0;
			while ($i < $num_rows) {
				$obj = $db->fetch_object($resql);
				$tmpArrayOfPublicHolidays[$obj->id] = array('dayrule' => $obj->dayrule, 'year' => $obj->year, 'month' => $obj->month, 'day' => $obj->day);
				$i++;
			}
		} else {
			dol_syslog($db->lasterror(), LOG_ERR);
			return 'Error sql ' . $db->lasterror();
		}

		//var_dump($tmpArrayOfPublicHolidays);
		$conf->cache['arrayOfActivePublicHolidays_' . $country_id] = $tmpArrayOfPublicHolidays;
	}

	$arrayOfPublicHolidays = $conf->cache['arrayOfActivePublicHolidays_' . $country_id];
	$listFeries = [];
	$i = 0;
	while ((($lastday == 0 && $timestampStart < $timestampEnd) || ($lastday && $timestampStart <= $timestampEnd))
		&& ($i < 50000)) {        // Loop end when equals (Test on i is a security loop to avoid infinite loop)
		$nonWorkingDay = false;
		$ferie = false;
		$specialdayrule = array();

		$jour = (int) gmdate("d", $timestampStart);
		$mois = (int) gmdate("m", $timestampStart);
		$annee = (int) gmdate("Y", $timestampStart);

		// If we have to exclude Friday, Saturday and Sunday
		if ($excludefriday || $excludesaturday || $excludesunday) {
			$jour_julien = unixtojd($timestampStart);
			$jour_semaine = jddayofweek($jour_julien, 0);
			if ($excludefriday) {                    //Friday (5), Saturday (6) and Sunday (0)
				if ($jour_semaine == 5) {
					$nonWorkingDay = true;
				}
			}
			if ($excludesaturday) {                    //Friday (5), Saturday (6) and Sunday (0)
				if ($jour_semaine == 6) {
					$nonWorkingDay = true;
				}
			}
			if ($excludesunday) {                    //Friday (5), Saturday (6) and Sunday (0)
				if ($jour_semaine == 0) {
					$nonWorkingDay = true;
				}
			}
		}
		//print "ferie=".$nonWorkingDay."\n";

		if (!$nonWorkingDay) {
			//print "jour=".$jour." month=".$mois." year=".$annee." includesaturday=".$excludesaturday." includesunday=".$excludesunday."\n";
			foreach ($arrayOfPublicHolidays as $entrypublicholiday) {
				if (!empty($entrypublicholiday['dayrule']) && $entrypublicholiday['dayrule'] != 'date') {        // For example 'easter', '...'
					$specialdayrule[$entrypublicholiday['dayrule']] = $entrypublicholiday['dayrule'];
				} else {
					$match = 1;
					if (!empty($entrypublicholiday['year']) && $entrypublicholiday['year'] != $annee) {
						$match = 0;
					}
					if ($entrypublicholiday['month'] != $mois) {
						$match = 0;
					}
					if ($entrypublicholiday['day'] != $jour) {
						$match = 0;
					}

					if ($match) {
						$ferie = true;
						$listFeries[] = $timestampStart;
					}
				}

				$i++;
			}
			//var_dump($specialdayrule)."\n";
			//print "ferie=".$nonWorkingDay."\n";
		}

		if (!$nonWorkingDay && !$ferie) {
			// Special dayrules
			if (in_array('easter', $specialdayrule)) {
				// Calculation for easter date
				$date_paques = getGMTEasterDatetime($annee);
				$jour_paques = gmdate("d", $date_paques);
				$mois_paques = gmdate("m", $date_paques);
				if ($jour_paques == $jour && $mois_paques == $mois) {
					$ferie = true;
					$listFeries[] = $timestampStart;
				}
				// Easter (sunday)
			}

			if (in_array('eastermonday', $specialdayrule)) {
				// Calculation for the monday of easter date
				$date_paques = getGMTEasterDatetime($annee);
				//print 'PPP'.$date_paques.' '.dol_print_date($date_paques, 'dayhour', 'gmt')." ";
				$date_lundi_paques = $date_paques + (3600 * 24);
				$jour_lundi_paques = gmdate("d", $date_lundi_paques);
				$mois_lundi_paques = gmdate("m", $date_lundi_paques);
				if ($jour_lundi_paques == $jour && $mois_lundi_paques == $mois) {
					$ferie = true;
					$listFeries[] = $timestampStart;
				}
				// Easter (monday)
				//print 'annee='.$annee.' $jour='.$jour.' $mois='.$mois.' $jour_lundi_paques='.$jour_lundi_paques.' $mois_lundi_paques='.$mois_lundi_paques."\n";
			}

			//Good Friday
			if (in_array('goodfriday', $specialdayrule)) {
				// Pulls the date of Easter
				$easter = getGMTEasterDatetime($annee);

				// Calculates the date of Good Friday based on Easter
				$date_good_friday = $easter - (2 * 3600 * 24);
				$dom_good_friday = gmdate("d", $date_good_friday);
				$month_good_friday = gmdate("m", $date_good_friday);

				if ($dom_good_friday == $jour && $month_good_friday == $mois) {
					$ferie = true;
					$listFeries[] = $timestampStart;
				}
			}

			if (in_array('ascension', $specialdayrule)) {
				// Calcul du jour de l'ascension (39 days after easter day)
				$date_paques = getGMTEasterDatetime($annee);
				$date_ascension = $date_paques + (3600 * 24 * 39);
				$jour_ascension = gmdate("d", $date_ascension);
				$mois_ascension = gmdate("m", $date_ascension);
				if ($jour_ascension == $jour && $mois_ascension == $mois) {
					$ferie = true;
					$listFeries[] = $timestampStart;
				}
				// Ascension (thursday)
			}

			if (in_array('pentecost', $specialdayrule)) {
				// Calculation of "Pentecote" (49 days after easter day)
				$date_paques = getGMTEasterDatetime($annee);
				$date_pentecote = $date_paques + (3600 * 24 * 49);
				$jour_pentecote = gmdate("d", $date_pentecote);
				$mois_pentecote = gmdate("m", $date_pentecote);
				if ($jour_pentecote == $jour && $mois_pentecote == $mois) {
					$ferie = true;
					$listFeries[] = $timestampStart;
				}
				// "Pentecote" (sunday)
			}

			if (in_array('pentecotemonday', $specialdayrule)) {
				// Calculation of "Pentecote" (49 days after easter day)
				$date_paques = getGMTEasterDatetime($annee);
				$date_pentecote = $date_paques + (3600 * 24 * 50);
				$jour_pentecote = gmdate("d", $date_pentecote);
				$mois_pentecote = gmdate("m", $date_pentecote);
				if ($jour_pentecote == $jour && $mois_pentecote == $mois) {
					$ferie = true;
					$listFeries[] = $timestampStart;
				}
				// "Pentecote" (monday)
			}

			if (in_array('viernessanto', $specialdayrule)) {
				// Viernes Santo
				$date_paques = getGMTEasterDatetime($annee);
				$date_viernes = $date_paques - (3600 * 24 * 2);
				$jour_viernes = gmdate("d", $date_viernes);
				$mois_viernes = gmdate("m", $date_viernes);
				if ($jour_viernes == $jour && $mois_viernes == $mois) {
					$ferie = true;
					$listFeries[] = $timestampStart;
				}
				//Viernes Santo
			}

			if (in_array('fronleichnam', $specialdayrule)) {
				// Fronleichnam (60 days after easter sunday)
				$date_paques = getGMTEasterDatetime($annee);
				$date_fronleichnam = $date_paques + (3600 * 24 * 60);
				$jour_fronleichnam = gmdate("d", $date_fronleichnam);
				$mois_fronleichnam = gmdate("m", $date_fronleichnam);
				if ($jour_fronleichnam == $jour && $mois_fronleichnam == $mois) {
					$ferie = true;
					$listFeries[] = $timestampStart;
				}
				// Fronleichnam
			}

			if (in_array('genevafast', $specialdayrule)) {
				// Geneva fast in Switzerland (Thursday after the first sunday in September)
				$date_1sunsept = strtotime('next thursday', strtotime('next sunday', mktime(0, 0, 0, 9, 1, $annee)));
				$jour_1sunsept = date("d", $date_1sunsept);
				$mois_1sunsept = date("m", $date_1sunsept);
				if ($jour_1sunsept == $jour && $mois_1sunsept == $mois) {
					$ferie = true;
					$listFeries[] = $timestampStart;
				}
				// Geneva fast in Switzerland
			}
		}
		//print "ferie=".$nonWorkingDay."\n";

		// Increase number of days (on go up into loop)
		$timestampStart = dol_time_plus_duree($timestampStart, 1, 'd');
		//var_dump($jour.' '.$mois.' '.$annee.' '.$timestampStart);

		$i++;
	}

	//print "nbFerie=".$nbFerie."\n";
	return $listFeries;
}

/**
 *	Function to return number of days between two dates (date must be UTC date !)
 *  Example: 2012-01-01 2012-01-02 => 1 if lastday=0, 2 if lastday=1
 *
 *	@param	   int			$timestampStart     Timestamp start UTC
 *	@param	   int			$timestampEnd       Timestamp end UTC
 *	@param     int			$lastday            Last day is included, 0: no, 1:yes
 *	@return    int								Number of days
 *  @see num_public_holiday(), num_open_day()
 */
function num_between_day($timestampStart, $timestampEnd, $lastday = 0)
{
	if ($timestampStart <= $timestampEnd) {
		if ($lastday == 1) {
			$bit = 0;
		} else {
			$bit = 1;
		}
		$nbjours = (int) floor(($timestampEnd - $timestampStart) / (60 * 60 * 24)) + 1 - $bit;
	} else {
		$nbjours = 0;
	}
	//print ($timestampEnd - $timestampStart) - $lastday;
	return $nbjours;
}

/**
 *	Function to return number of working days (and text of units) between two dates (working days)
 *
 *	@param	   	int			$timestampStart     Timestamp for start date (date must be UTC to avoid calculation errors)
 *	@param	   	int			$timestampEnd       Timestamp for end date (date must be UTC to avoid calculation errors)
 *	@param     	int			$inhour             0: return number of days, 1: return number of hours
 *	@param		int			$lastday            We include last day, 0: no, 1:yes
 *  @param		int			$halfday			Tag to define half day when holiday start and end
 *  @param      string		$country_code       Country code (company country code if not defined)
 *	@return    	int|string						Number of days or hours or string if error
 *  @seealso num_between_day(), num_public_holiday()
 */
function num_open_day($timestampStart, $timestampEnd, $inhour = 0, $lastday = 0, $halfday = 0, $country_code = '')
{
	global $langs, $mysoc;

	if (empty($country_code)) {
		$country_code = $mysoc->country_code;
	}

	dol_syslog('num_open_day timestampStart='.$timestampStart.' timestampEnd='.$timestampEnd.' bit='.$lastday.' country_code='.$country_code);

	// Check parameters
	if (!is_int($timestampStart) && !is_float($timestampStart)) {
		return 'ErrorBadParameter_num_open_day';
	}
	if (!is_int($timestampEnd) && !is_float($timestampEnd)) {
		return 'ErrorBadParameter_num_open_day';
	}

	//print 'num_open_day timestampStart='.$timestampStart.' timestampEnd='.$timestampEnd.' bit='.$lastday;
	if ($timestampStart < $timestampEnd) {
		$numdays = num_between_day($timestampStart, $timestampEnd, $lastday);

		$numholidays = num_public_holiday($timestampStart, $timestampEnd, $country_code, $lastday);
		$nbOpenDay = ($numdays - $numholidays);
		if ($inhour == 1 && $nbOpenDay <= 3) {
			$nbOpenDay *= 24;
		}
		return $nbOpenDay - (($inhour == 1 ? 12 : 0.5) * abs($halfday));
	} elseif ($timestampStart == $timestampEnd) {
		$numholidays = 0;
		if ($lastday) {
			$numholidays = num_public_holiday($timestampStart, $timestampEnd, $country_code, $lastday);
			if ($numholidays == 1) {
				return 0;
			}
		}

		$nbOpenDay = $lastday;

		if ($inhour == 1) {
			$nbOpenDay *= 24;
		}
		return $nbOpenDay - (($inhour == 1 ? 12 : 0.5) * abs($halfday));
	} else {
		return $langs->trans("Error");
	}
}



/**
 *	Return array of translated months or selected month.
 *  This replace old function monthArrayOrSelected.
 *
 *	@param	Translate	$outputlangs	Object langs
 *  @param	int			$short			0=Return long label, 1=Return short label
 *	@return array<int<1,12>,string>		String of months in normal or short string format
 */
function monthArray($outputlangs, $short = 0)
{
	$montharray = array(
		1  => $outputlangs->trans("Month01"),
		2  => $outputlangs->trans("Month02"),
		3  => $outputlangs->trans("Month03"),
		4  => $outputlangs->trans("Month04"),
		5  => $outputlangs->trans("Month05"),
		6  => $outputlangs->trans("Month06"),
		7  => $outputlangs->trans("Month07"),
		8  => $outputlangs->trans("Month08"),
		9  => $outputlangs->trans("Month09"),
		10 => $outputlangs->trans("Month10"),
		11 => $outputlangs->trans("Month11"),
		12 => $outputlangs->trans("Month12")
	);

	if (!empty($short)) {
		$montharray = array(
			1  => $outputlangs->trans("MonthShort01"),
			2  => $outputlangs->trans("MonthShort02"),
			3  => $outputlangs->trans("MonthShort03"),
			4  => $outputlangs->trans("MonthShort04"),
			5  => $outputlangs->trans("MonthShort05"),
			6  => $outputlangs->trans("MonthShort06"),
			7  => $outputlangs->trans("MonthShort07"),
			8  => $outputlangs->trans("MonthShort08"),
			9  => $outputlangs->trans("MonthShort09"),
			10 => $outputlangs->trans("MonthShort10"),
			11 => $outputlangs->trans("MonthShort11"),
			12 => $outputlangs->trans("MonthShort12")
			);
	}

	return $montharray;
}

/**
 *	Return array of week numbers.
 *
 *	@param	int 		$month			Month number
 *  @param	int			$year			Year number
 *	@return string[]					Week numbers (week 1 is '01')
 */
function getWeekNumbersOfMonth($month, $year)
{
	$nb_days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
	$TWeek = array();
	for ($day = 1; $day < $nb_days; $day++) {
		$week_number = getWeekNumber($day, $month, $year);
		$TWeek[$week_number] = $week_number;
	}
	return $TWeek;
}

/**
 *	Return array of first day of weeks.
 *
 *	@param	string[] 	$TWeek			array of week numbers we want (week 1 must be '01')
 *  @param	int			$year			Year number
 *	@return string[]					First day of each week in entry (day 1 is '01')
 */
function getFirstDayOfEachWeek($TWeek, $year)
{
	$TFirstDayOfWeek = array();
	foreach ($TWeek as $weekNb) {
		if (in_array('01', $TWeek) && in_array('52', $TWeek) && $weekNb == '01') {
			$year++; //Si on a la 1re semaine et la semaine 52 c'est qu'on change d'année
		}
		$TFirstDayOfWeek[$weekNb] = date('d', strtotime($year.'W'.$weekNb));
	}
	return $TFirstDayOfWeek;
}

/**
 *	Return array of last day of weeks.
 *
 *	@param	string[] 	$TWeek			array of week numbers
 *  @param	int			$year			Year number
 *	@return string[]					Last day of week (day 1 is '01')
 */
function getLastDayOfEachWeek($TWeek, $year)
{
	$TLastDayOfWeek = array();
	foreach ($TWeek as $weekNb) {
		$TLastDayOfWeek[$weekNb] = date('d', strtotime($year.'W'.$weekNb.'+6 days'));
	}
	return $TLastDayOfWeek;
}

/**
 *	Return week number.
 *
 *	@param	int 		$day			Day number
 *	@param	int 		$month			Month number
 *  @param	int			$year			Year number
 *	@return string						Week number as two digits (week 1 is '01')
 */
function getWeekNumber($day, $month, $year)
{
	$date = new DateTime($year.'-'.$month.'-'.$day);
	$week = $date->format("W");
	return $week;
}
