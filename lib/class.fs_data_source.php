<?php
/**
 * 	FatSync (c) 2009 Fatpublisher	http://fatpublisher.com.au
 *	Written by Neil E. Pearson		http://www.Hx.net.au
 *	This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
 *	This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *	You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/
/**
 * This abstract class defines how data source classes should be structured.
 */

abstract class fs_data_source {

	/**
	 * @return array All clients in this data source (fs_client)
	 */
	abstract protected function clients();

	/**
	 * @param integer $id Unique ID
	 * @return fs_client A specific client
	 */
	abstract protected function client($client_id);

	/**
	 * @param integer $client_id Parent client ID
	 * @return array All of this client's contacts
	 */
	abstract protected function contacts($client_id);

	/**
	 * @param integer $contact_id Unique ID
	 * @return fs_contact A specific contact
	 */
	abstract protected function contact($contact_id);

	/**
	 * Save one of this data source's records
	 * @param fs_record $record The record to be saved.
	 */
	abstract protected function save(fs_record $record);

	/**
	 * Delete one of this data source's records
	 * @param fs_record $record The record to be deleted.
	 */
	abstract protected function delete(fs_record $record);

	/**
	 * Add a new record to this data source.
	 * @param fs_record $record The record to be added. Can belong to another data source.
	 * @return fs_record The newly created record.
	 */
	abstract public function add(fs_record $record);

	/**
	 * @var integer Maximum number of calls to the api in a single session. Zero for unlimited.
	 */
	protected $max_calls=0;

	/**
	 * @var integer Number of calls made so far. Should be incremented on each call.
	 */
	public $call_count=0;

	/**
	 *
	 * @return boolean Returns <b>true</b> if this data source has reached its call limit.
	 */
	public function max_calls() {
		return $this->max_calls>0 && $this->call_count>=$this->max_calls;
	}

	/**
	 * @var boolean If true, new records added to this data source will be copied to other data sources.
	 */
	public $sync_additions=false;

	/**
	 * @var boolean If true, changes made to records in this data source will be reflected in other data sources.
	 */
	public $sync_updates=false;

	/**
	 * @var boolean If true, records deleted from this data source will also be deleted from other data sources.
	 */
	public $sync_deletions=false;

	/**
	 * @var array Internal clients cache
	 */
	protected $clients=false;

	/**
	 * @var array Internal contacts cache
	 */
	protected $contacts=array();

}

?>