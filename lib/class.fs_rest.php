<?php
/**
 * 	FatSync (c) 2009 Fatpublisher	http://fatpublisher.com.au
 *	Written by Neil E. Pearson		http://www.Hx.net.au
 *	This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
 *	This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *	You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/
abstract class fs_rest extends fs_data_source {
	/**
	 * @var string Domain of data source server. Should be specified by the data source class.
	 */
	protected $domain;
	/**
	 * @var string Subdomain. Default is none.
	 */
	var $subdomain;
	/**
	 * @var string Username
	 */
	var $username;
	/**
	 * @var string Password
	 */
	var $password;
	/**
	 * @var boolean Use SSL. Default is <b>false</b>
	 */
	var $ssl=false;

	/**
	 * Create a new CURL handler with default headers.
	 * @param string $path Path of current (sub)domain to use as url. Do no include leading slash (eg. "clients/45.xml").
	 * @return resource_id A new CURL handler.
	 */
	protected function new_curl($path, $content_type='application/xml') {
		$host=$this->domain;
		if($this->subdomain) $host=$this->subdomain.'.'.$host;
		$protocol=$this->ssl?'https':'http';
		$ret=curl_init("$protocol://$host/$path");
		curl_setopt_array($ret, array(
			CURLOPT_HEADER			=> true,
			CURLOPT_RETURNTRANSFER	=> true,
			CURLOPT_USERPWD			=> $this->username.':'.$this->password,
			CURLOPT_HTTPAUTH		=> CURLAUTH_BASIC,
			CURLOPT_TIMEOUT			=> 10
		));
		if($this->ssl) curl_setopt_array($ret, array(
			CURLOPT_SSL_VERIFYHOST	=> false,
			CURLOPT_SSL_VERIFYPEER	=> false
		));
		$headers=array(
			'Accept: application/xml',
			'User-Agent: FatSync',
			'Expect:'
		);
		if($content_type) $headers[]='Content-Type: '.$content_type;
		curl_setopt($ret, CURLOPT_HTTPHEADER, $headers);
		return $ret;
	}

	/**
	 * Perform an HTTP GET
	 * @param string $path Path to get, eg. companies/2153.xml
	 * @return array Array containing status, header and content elements. XML content is automatically converted to SimpleXML
	 */
	public function get($path) {

		// create a new curl object
		$c=$this->new_curl($path);

		// get a response
		$data=curl_exec($c);

		// return false if curl reports an error
		if(curl_errno($c)) return false;
		curl_close($c);
		$this->call_count++;

		return fs_rest::process_response($data);

	}

	/**
	 * Perform an HTTP PUT
	 * @param string $path Path to put, eg. companies/2153.xml
	 * @param mixed $data String or SimpleXMLElement containing data to put
	 * @return array Array containing status, header and content elements. XML content is automatically converted to SimpleXML
	 */
	protected function put($path, $data) {

		// create a new curl object
		$c=$this->new_curl($path);

		// set mode to PUT
		curl_setopt($c, CURLOPT_PUT, true);

		// add put data
		if($data instanceof SimpleXMLElement) $data=$data->asXML();
		$file=tmpfile();
		fwrite($file, $data);
		fseek($file, 0);
		curl_setopt($c, CURLOPT_INFILE, $file);
		curl_setopt($c, CURLOPT_INFILESIZE, strlen($data));

		// get a response
		$data=curl_exec($c);

		// close temp file
		fclose($file);

		// return false if curl reports an error
		if(curl_errno($c)) return false;
		curl_close($c);
		$this->call_count++;

		return fs_rest::process_response($data);

	}

	/**
	 * Perform an HTTP POST
	 * @param string $path Path to post, eg. companies/2153.xml
	 * @param mixed $data String or SimpleXMLElement containing data to post
	 * @return array Array containing status, header and content elements. XML content is automatically converted to SimpleXML
	 */
	protected function post($path, $data) {

		// create a new curl object
		$c=$this->new_curl($path);

		// set mode to POST
		curl_setopt($c, CURLOPT_POST, true);

		// add post data
		if($data instanceof SimpleXMLElement) $data=$data->asXML();
		curl_setopt($c, CURLOPT_POSTFIELDS, $data);

		// get a response
		$data=curl_exec($c);

		// return false if curl reports an error
		if(curl_errno($c)) return false;
		curl_close($c);
		$this->call_count++;

		return fs_rest::process_response($data);

	}

	/**
	 * Perform an HTTP DELETE
	 * @param string $path Path to delete, eg. companies/2153.xml
	 * @return array Array containing status, header and content elements. XML content is automatically converted to SimpleXML
	 */
	protected function del($path) {

		// create a new curl object
		$c=$this->new_curl($path);

		// set mode to DELETE
		curl_setopt($c, CURLOPT_CUSTOMREQUEST, 'DELETE');

		// get a response
		$data=curl_exec($c);

		// return false if curl reports an error
		if(curl_errno($c)) return false;
		curl_close($c);
		$this->call_count++;

		return fs_rest::process_response($data);


	}

	/**
	 * Split a CURL HTTP response into status, header and content elements
	 * @param string $data Raw HTTP response from CURL
	 * @return array Array containing status, header and content elements. XML content is automatically converted to SimpleXML
	 */
	static function process_response($data) {

		// get response status
		while(preg_match('`^HTTP/\d\.\d\s(\d+)\s*(.*?)[ \t]*[\r\n]+`i',$data,$s)) {
			$status=array('code'=>$s[1],'desc'=>$s[2]);
			$data=substr($data,strlen($s[0]));
		}

		// if status is not 2xx, return status.
		//if(!preg_match('`^2\d\d$`',$status['code'])) return $status;

		// get headers from response data and turn them into an associative array
		$headers=array();
		while(preg_match('`^\s*([-.\w\s]+)\s*:\s*(.+?)[ \t]*[\r\n]+`',$data,$r)) {
			$headers[$r[1]]=$r[2];
			$data=substr($data,strlen($r[0]));
		}

		// fish out the XML data

		if(preg_match('`^\s*<.*>\s*$`s',$data)) $data=new SimpleXMLElement(trim($data));

		// return the status, headers and content

		return array(
			'status'=>$status,
			'headers'=>$headers,
			'content'=>$data
		);

	}

}

?>