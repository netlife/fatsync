<?php
/**
 * 	FatSync (c) 2009 Fatpublisher	http://fatpublisher.com.au
 *	Written by Neil E. Pearson		http://www.Hx.net.au
 *	This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
 *	This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *	You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/
abstract class fs_record {

	/**
	 *
	 * @param fs_data_source $data_source Data source with which this record is associated.
	 */
	public function __construct(fs_data_source $data_source) {
		$this->data_source=$data_source;
	}

	/**
	 * @var integer Unique ID
	 */
	var $id;
	/**
	 * @var array Data Fields
	 */
	var $fields;
	/**
	 * @var fs_data_source Data source with which this record is associated. Necessary for save() function to work.
	 */
	private $data_source;

	/**
	 * Save this record
	 * @return mixed
	 */
	public function save() {
		return $this->data_source->save($this);
	}

	/**
	 * Delete this record
	 * @return mixed
	 */
	public function delete() {
		return $this->data_source->delete($this);
	}

	/**
	 * Generate an MD5 hash based on this record's fields.
	 * @param string $compare_to Optionally compare the generated hash to this string, and return a boolean.
	 * @return mixed The generated hash, or if $compare_to is specified, a boolean result of a string comparison.
	 */
	public function hash($compare_to=false) {
		ksort($this->fields);
		$hash=md5(serialize($this->fields));
		if($compare_to===false) return $hash;
		return $compare_to==$hash;
	}

}

?>