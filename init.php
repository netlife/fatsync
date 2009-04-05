<?php
/**
 * 	FatSync (c) 2009 Fatpublisher	http://fatpublisher.com.au
 *	Written by Neil E. Pearson		http://www.Hx.net.au
 *	This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
 *	This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *	You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/
// set maximum execution time to 6 minutes
set_time_limit(360);

// set content type to text, to improve appearance of log/trace actions in browsers
header('Content-Type: text/plain');

// include all files in these folders. The order is important to facilitate inheritance.

$include_folders=array(
	'lib',
	'record_types',
	'modules'
);

foreach($include_folders as $folder) {
	$files=scandir($folder);
	foreach($files as $file) {
		if(substr($file,-4)=='.php') include_once("$folder/$file");
	}
}

/**
 * The following variable will contain two or more objects. The should be classes which inherit
 * from the fs_data_source abstract class. The objects determine how the script connects to its
 * data sources, as defined in config.php
 *
 * @global array $data_sources
 */
$data_sources=array();

include('config.php');

// local data

define('LOCAL_DATA_FILE','local.data');

if(is_file(LOCAL_DATA_FILE)) {

	// load from file
	$local=unserialize(gzuncompress(file_get_contents(LOCAL_DATA_FILE)));

	// make sure loaded data matches config
	if(count($local['data_sources'])!=count($data_sources)) die("Local data does not match config file");
	foreach($data_sources as $k=>$i) if($local['data_sources'][$k]['class']!=get_class($i)) die("Local data does not match config file");

} else {

// data file does not exist; initialize local structure
	$local=array('data_sources'=>array(),'matches'=>array('contacts'=>array(),'clients'=>array()));
	foreach($data_sources as $k=>$i) {
		$local['data_sources'][$k]=array(
			'class'=>get_class($i),
		//	'clients'=>array(),
		//	'contacts'=>array()
		);
	}
}

/**
 * function to save local data and kill script
 */
function save_and_exit() {
	file_put_contents(LOCAL_DATA_FILE, gzcompress(serialize($GLOBALS['local'])));
	exit;
}

?>
