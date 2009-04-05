<?php
/**
 * functions from the Hx library. http://www.hx.net.au
 */

class hx {

	private static $cri;
	private static $cmp;

	/**
	 * Perform a SQL-style sort on a two-dimensional array.
	 * @param array $array The array to be sorted
	 * @param string $members Sort criteria, eg. "firstname, lastname ASC, age DESC"
	 * @param boolean $associative Keep keys matched up with their values. Default <b>true</b>
	 * @param boolean $natural Use natural comparision. Default <b>false</b>
	 */
	static function csort(&$array, $members, $associative=true, $natural=false) {
		hx::$cmp=$natural?'strnatcmp':'strcmp';
		hx::$cri=array();
		$sort=$associative?'uasort':'usort';
		$cris=preg_split('/\s*,\s*/',$members);
		foreach($cris as $i) if(preg_match('/^(\w+)(?:\s+([ad])\w*)?$/i',$i,$r)) hx::$cri[$r[1]]=(strtolower(count($r)>2?$r[2]:'')=='d');
		$sort($array,'hx::ccomp');
	}

	private static function ccomp($a, $b) {
		$ret=false;
		$cmp=hx::$cmp;
		foreach(hx::$cri as $k => $i)
			if(!$ret)
				$ret=$cmp(is_array($a)?$a[$k]:$a->$k,
					is_array($b)?$b[$k]:$b->$k) * ($i?-1:1);
		return $ret;
	}
}

?>
