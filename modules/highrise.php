<?php
/**
 * 	FatSync (c) 2009 Fatpublisher	http://fatpublisher.com.au
 *	Written by Neil E. Pearson		http://www.Hx.net.au
 *	This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
 *	This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *	You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/
class highrise extends fs_rest {

	protected $domain='highrisehq.com';
	protected $max_calls=35;

	public $tag='';

	function clients() {
		if($this->clients!==false) return $this->clients;
		$path='companies.xml';
		if($this->tag) $path.="?tag_id=".$this->tag_id();
		$d=$this->get($path);
		$this->clients=array();
		if(!($d['content'] instanceof SimpleXMLElement)) return false;
		$this->cache_records($d['content']);
		return $this->clients;
	}

	function client($id) {
		if($id instanceof SimpleXMLElement) $xml=$id;
		else {
			if($this->clients!==false) foreach($this->clients as $i) if($i->id==$id) return $i;
			$d=$this->get("companies/$id.xml");
			if(!($d['content'] instanceof SimpleXMLElement)) return false;
			$xml=$d['content'];
		}
		$ret=new fs_client($this);
		$ret->id=(integer) $xml->id;
		$ret->fields['name']=(string) $xml->name;
		$a=$xml->xpath('contact-data/addresses/address');
		if(count($a)) {
			$a=$a[0];
			$ret->fields['address']=sprintf("%s\n%s %s %s\n%s",$a->street,$a->city,$a->state,$a->zip,$a->country);
			$ret->address_id=$a->id;
		}
		return $ret;
	}

	function contacts($id) {
		if(array_key_exists($id, $this->contacts)) return $this->contacts[$id];
		$path="companies/$id/people.xml";
		if($this->tag) $path.="?tag_id=".$this->tag_id();
		$d=$this->get($path);
		if(!($d['content'] instanceof SimpleXMLElement)) return false;
		$this->contacts[$id]=array();
		$this->cache_records($d['content']);
		return $this->contacts[$id];
	}

	private function cache_records($xml) {
		foreach($xml->company as $c) $this->clients[]=$this->client($c);
		foreach($xml->person as $p) $this->contacts[$id][]=$this->contact($p);
		foreach($xml->record as $p)
			if($p->{'company-id'}) $this->contacts[(int)$p->{'company-id'}][]=$this->contact($p);
			else $this->clients[]=$this->client($p);
	}

	function contact($id) {
		if($id instanceof SimpleXMLElement) $xml=$id;
		else {
			foreach($this->contacts as $j) if(is_array($j)) foreach($j as $i) if($i->id==$id) return $i;
			$d=$this->get("people/$id.xml");
			if(!($d['content'] instanceof SimpleXMLElement)) return false;
			$xml=$d['content'];
		}
		$ret=new fs_contact($this);
		$ret->id=(integer) $xml->id;
		$ret->fields['firstname']=(string) $xml->{'first-name'};
		$ret->fields['lastname']=(string) $xml->{'last-name'};
		foreach($xml->xpath('contact-data/phone-numbers/phone-number') as $i) switch((string)$i->location) {
			case 'Work':	$ret->fields['phone']	=(string) $i->number; $ret->phone_id	=(integer)$i->id; break;
			case 'Mobile':	$ret->fields['mobile']	=(string) $i->number; $ret->mobile_id	=(integer)$i->id; break;
			case 'Fax':		$ret->fields['fax']		=(string) $i->number; $ret->fax_id		=(integer)$i->id; break;
		}
		$e=$xml->xpath('contact-data/email-addresses/email-address');
		if(count($e)) {
			$ret->fields['email']=(string) $e[0]->address;
			$ret->email_id=(integer) $e[0]->id;
		}
		$ret->company_id=(integer) $xml->{'company-id'};
		return $ret;
	}

	function delete(fs_record $record) {
		switch(get_class($record)) {
			case 'fs_client': return $this->del("companies/{$record->id}.xml");
			case 'fs_contact': return $this->del("people/{$record->id}.xml");
		}
	}

	function save(fs_record $record) {
		switch(get_class($record)) {
			case 'fs_client':
				$path="companies/{$record->id}.xml";
				$data=highrise::get_client_xml($record);
				$data->addChild('id',$record->id);
				$data->id->addAttribute('type','integer');
				if(isset($record->address_id)) {
					$data->{'contact-data'}->addresses->address->addChild('id',$record->address_id);
					$data->{'contact-data'}->addresses->address->id->addAttribute('type','integer');
				}
			break;
			case 'fs_contact':
				$path="people/{$record->id}.xml";
				$data=highrise::get_contact_xml($record);
				if($email=$data->xpath('contact-data/email-addresses/email-address')) {
					$email[0]->addChild('id',intval($record->email_id));
					$email[0]->id->addAttribute('type','integer');
				}
				foreach($data->xpath('contact-data/phone-numbers/phone-number') as $i) {
					$id='none';
					switch((string) $i->location) {
						case 'Work':$id='phone_id'; break;
						case 'Mobile':$id='mobile_id'; break;
						case 'Fax':$id='fax_id';
					}
					if(isset($record->$id)) {
						$i->addChild('id',$record->$id);
						$i->id->addAttribute('type','integer');
					}
				}
			break;
		}
		$result=$this->put($path, $data);
		if(!$result) return false;
		return $result['status']['code']==200;
	}

	function add(fs_record $record) {
		switch(get_class($record)) {
			case 'fs_client':
				$path='companies.xml';
				$data=highrise::get_client_xml($record);
				$result=$this->post($path, $data);
				if($result['status']['code']!=201) return false;
				$ret=new fs_client($this);
				$ret->fields=$record->fields;
				if(preg_match('`(\d+)(\.xml)?$`i',$result['headers']['Location'],$r)) $ret->id=$r[1];
				else $ret->id=0;
			break;
			case 'fs_contact':
				list(,$parent)=func_get_args();
				$path="people.xml";
				$data=highrise::get_contact_xml($record);
				$data->addChild('company-id',$parent);
				$data->{'company-id'}->addAttribute('type','integer');
				$result=$this->post($path,$data);
				if(!$result) return false;
				if($result['status']['code']!=201) return false;
				$ret=new fs_contact($this);
				$ret->fields=$record->fields;
				$ret->id=(string) $result['content']->id;
				$e=$result['content']->xpath('contact-data/email-addresses/email-address');
				$ret->email_id=(string) $e[0]->id;
				foreach($result['content']->xpath('contact-data/phone-numbers/phone-number') as $i) {
					$n=(string) $i->id;
					switch((string) $i->location) {
						case 'Work': $ret->phone_id=$n; break;
						case 'Fax': $ret->fax_id=$n; break;
						case 'Mobile': $ret->mobile_id=$n; break;
					}
				}
				$ret->company_id=$parent;
			break;
		}

		// add a tag

		if(strlen($this->tag)&&$ret->id) {
			$ch=$this->new_curl("parties/{$ret->id}/tags","");
			curl_setopt($ch,CURLOPT_POST,true);
			curl_setopt($ch,CURLOPT_POSTFIELDS, array('name'=>$this->tag));
			curl_exec($ch);
			curl_close($ch);
			$this->call_count++;
		}
		
		return $ret;
	}

	static private function get_contact_xml(fs_contact $record) {
		$ret=new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><person />');
		$ret->addChild('first-name',amp($record->fields['firstname']));
		$ret->addChild('last-name',amp($record->fields['lastname']));
		$cd=$ret->addChild('contact-data');
		$cd->addChild('email-addresses');
		$email=$cd->{'email-addresses'}->addChild('email-address');
		$email->addChild('location','Work');
		$email->addChild('address',amp($record->fields['email']));
		$ph=$cd->addChild('phone-numbers');
		foreach(array('Work'=>'phone','Mobile'=>'mobile','Fax'=>'fax') as $k=>$i) {
			$n=$ph->addChild('phone-number');
			$n->addChild('location',$k);
			$n->addChild('number',amp($record->fields[$i]));
		}
		if(isset($record->company_id)) {
			$ret->addChild('company-id',$record->company_id);
			$ret->{'company-id'}->addAttribute('type','integer');
		}
		return $ret;
	}

	static private function get_client_xml(fs_client $client) {
		$ret=new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><company />');
		$ret->addChild('name', amp($client->fields['name']));
		$cd=$ret->addChild('contact-data');
		$addrs=$cd->addChild('addresses');
		$addr=$addrs->addChild('address');
		$addr->addChild('location','Work');
		foreach(highrise::address_to_fields($client->fields['address']) as $k=>$i) $addr->addChild($k, amp($i));
		return $ret;
	}

	static private function address_to_fields($str) {
		$exp='`^[ \t]*(?P<street>.*)[ \t]*(?:[\r\n]+[ \t]*(?P<city>.*?)[ \t,]*(?P<pc1>\d{4,5})?[ \t]*(?P<state>'.
			'nsw|new south wales|act|australian capital territory|wa|western australia|qld|queensland|sa|south australia|'.
			'nt|northern territory|tas|tasmania|vic|victoria)?[ \t]*(?P<pc2>\d{4,5})?)?[ \t]*(?:[\r\n]+[ \t]*(?P<country>.*)\s*)?$`i';
		$ret=array();
		if(preg_match($exp,$str,$r)) {
			foreach($r as $k=>$i) if(!is_numeric($k)) {
				if($k=='pc1'||$k=='pc2') $ret['zip']=$i;
				else $ret[$k]=$i;
			}
		} else $ret['street']=$str;
		return $ret;
	}

	private function tag_id() {
		if(!$this->tag) return 0;
		foreach($GLOBALS['data_sources'] as $k=>$i) if($i===$this) $ds_k=$k;
		$local=&$GLOBALS['local']['data_sources'][$ds_k];
		if(!array_key_exists('tag_id', $local)) {
			$tags=$this->get('tags');
			$tag=$tags['content']->xpath('tag[name="'.$this->tag.'"]/id');
			if(!count($tag)) {
				trace("Highrise: tag {$this->tag} not found. Exiting.");
				save_and_exit();
			}
			$local['tag_id']=(integer) $tag[0];
		}
		return $local['tag_id'];
	}

}
















?>