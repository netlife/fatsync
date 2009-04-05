<?php
/**
 * 	FatSync (c) 2009 Fatpublisher	http://fatpublisher.com.au
 *	Written by Neil E. Pearson		http://www.Hx.net.au
 *	This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
 *	This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *	You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/
/**
 * This function returns a non-zero if any of the data sources have
 * reached their call limits. Bit 0 = data source 0, etc.
 * @return integer
 */
function max_calls() {
	$ret=0;
	foreach($GLOBALS['data_sources'] as $k=>$i) if($i->max_calls()) $ret|=1<<$k;
	return $ret;
}

/**
 * If max_calls has been reached on any data sources, this function will
 * report it, save local data, and exit.
 */
function check_max_calls() {
	if($max=max_calls()) {
		trace("Reached call limit on data source(s) (bitmask $max). Exiting.");
		save_and_exit();
	}
}

/**
 * Trace (log) to output and sync.log file.
 * @param string $str String to be logged
 */
function trace($str) {
	$txt=sprintf("[%s] %s\n",date('r'),trim($str));
	echo $txt;
	$logfile=fopen('sync.log', 'a');
	fwrite($logfile, $txt);
	fclose($logfile);
}

/**
 * Convert & to &amp;. Used to fix SimpleXML bug.
 * @param string $str Unescaped data
 * @return string Escaped data
 */
function amp($str) {
	return str_replace('&','&amp;',$str);
}

?>