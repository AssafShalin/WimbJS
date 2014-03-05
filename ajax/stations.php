<?php
	header('Content-Type: application/json; charset=utf-8');

	function getResponse($request_array)
	{
		$query = "";
		foreach($request_array as $key=>$val)
		{
			$query.= 'stop_code eq '. $val;
			if($key+1 < count($request_array)) $query.= ' or ';
		}
		$query = str_replace(' ', '%20', $query);
		$url = 'http://wimb.azure-mobile.net/tables/AllStops?$filter=(' . $query . ')';
		$curl = curl_init($url);
		
		curl_setopt_array($curl, array(
			CURLOPT_HTTPHEADER => array("X-ZUMO-APPLICATION: oaISskurhJtituuYtszOvQypeIAGeE85"),
		    CURLOPT_RETURNTRANSFER => 1,
		    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; rv:27.0) Gecko/20100101 Firefox/27.0',
		    CURLOPT_VERBOSE => 1
		));
		$resp = curl_exec($curl);
		$resp = json_decode($resp);
		foreach($resp as $k=>$s)
		{
			$s->stop_desc = str_replace('כתובת:', '', $s->stop_desc);
		}
		$resp = json_encode($resp);
		// Open the file using the HTTP headers set above
		return $resp;
	}
	$json = json_decode(file_get_contents('stations.json'));
	echo getResponse($json);
?>