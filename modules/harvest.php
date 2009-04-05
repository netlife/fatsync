<?php
/**
 * 	FatSync (c) 2009 Fatpublisher	http://fatpublisher.com.au
 *	Written by Neil E. Pearson		http://www.Hx.net.au
 *	This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
 *	This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *	You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/
class harvest extends fs_rest {

	protected $domain='harvestapp.com';
	protected $max_calls=35;

	function clients() {
		if($this->clients!==false) return $this->clients;
		$d=$this->get('clients');
		if(!($d['content'] instanceof SimpleXMLElement)) return false;
		$this->clients=array();
		foreach($d['content']->client as $c) {
			$this->clients[]=$this->client($c);
		}
		return $this->clients;
	}

	function client($id) {
		if($id instanceof SimpleXMLElement) $xml=$id;
		else {
			if($this->clients!==false) foreach($this->clients as $i) if($i->id==$id) return $i;
			$d=$this->get("clients/$id");
			if(!($d['content'] instanceof SimpleXMLElement)) return false;
			$xml=$d['content'];
		}
		$ret=new fs_client($this);
		$ret->id=(integer) $xml->id;
		$ret->fields['name']=(string) $xml->name;
		$ret->fields['address']=(string) $xml->details;
		return $ret;
	}

	function contacts($id) {
		if(is_array($this->contacts)) if(array_key_exists($id, $this->contacts)) return $this->contacts[$id];
		$d=$this->get("clients/$id/contacts");
		if(!($d['content'] instanceof SimpleXMLElement)) return false;
		$this->contacts[$id]=array();
		foreach($d['content']->contact as $c) {
			//$this->contact_parent((integer)$c->id,$id);
			$this->contacts[$id][]=$this->contact($c);
		}
		return $this->contacts[$id];
	}

	function contact($id) {
		if($id instanceof SimpleXMLElement) $xml=$id;
		else {
			if(is_array($this->contacts)) foreach($this->contacts as $j) if(is_array($j)) foreach($j as $i) if($i->id==$id) return $i;
			//$pid=$this->contact_parent($id);
			//if(!$pid) return false;
			$d=$this->get("contacts/$id");
			if(!($d['content'] instanceof SimpleXMLElement)) return false;
			$xml=$d['content'];
		}
		$ret=new fs_contact($this);
		$ret->id=(integer) $xml->id;
		foreach(array(
			'email'			=>'email',
			'first-name'	=>'firstname',
			'last-name'		=>'lastname',
			'fax'			=>'fax',
			'phone-mobile'	=>'mobile',
			'phone-office'	=>'phone'
		) as $k=>$i) $ret->fields[$i]=(string) $xml->$k;
		return $ret;
	}

	function delete(fs_record $record) {
		switch(get_class($record)) {
			case 'fs_client': return $this->del("clients/{$record->id}");
			case 'fs_contact': return $this->del("contacts/{$record->id}");
		}
	}

	function save(fs_record $record) {
		switch(get_class($record)) {
			case 'fs_client':
				$path="clients/".$record->id;
				$data=harvest::get_client_xml($record);
			break;
			case 'fs_contact':
				$path="contacts/{$record->id}";
				$data=harvest::get_contact_xml($record);
			break;
		}
		$result=$this->put($path, $data);
		if(!$result) return false;
		return $result['status']['code']==200;
	}

	function add(fs_record $record) {
		switch(get_class($record)) {
			case 'fs_client':
				$data=harvest::get_client_xml($record);
				$path='clients';
				$result=$this->post($path, $data);
				if($result['status']['code']!=201) return false;
				$ret=new fs_client($this);
				$ret->fields=$record->fields;
				$ret->id=preg_replace('`^[^\d]+`','',$result['headers']['Location']);
				return $ret;
			case 'fs_contact':
				list(,$parent)=func_get_args();
				$path='contacts';
				$data=harvest::get_contact_xml($record);
				$data->addChild('client-id',$parent);
				$data->{'client-id'}->addAttribute('type','integer');
				$result=$this->post($path,$data);
				if(!$result) return false;
				if($result['status']['code']!=201) return false;
				$ret=new fs_contact($this);
				$ret->fields=$record->fields;
				$ret->id=preg_replace('`^[^\d]+`','',$result['headers']['Location']);
				return $ret;
		}
	}

	static private function get_contact_xml(fs_contact $contact) {
		$ret=new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><contact />');
		foreach(array(
			'email'=>'email',
			'firstname'=>'first-name',
			'lastname'=>'last-name',
			'phone'=>'phone-office',
			'mobile'=>'phone-mobile',
			'fax'=>'fax'
		) as $k=>$i) $ret->addChild($i,amp($contact->fields[$k]));
		return $ret;
	}

	static private function get_client_xml(fs_client $client) {
		$ret=new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><client />');
		$ret->addChild('name', amp($client->fields['name']));
		$ret->addChild('details', amp($client->fields['address']));
		return $ret;
	}

	/*private function contact_parent($id,$pid=-1) {
		foreach($GLOBALS['data_sources'] as $k=>$i) if($i==$this) $local=&$GLOBALS['local']['data_sources'][$k]['contacts'];
		if($pid>=0) $local[$id]=$pid;
		if(array_key_exists($id, $local)) return (integer) $local[$id];
		return 0;
	}*/

}
















?>