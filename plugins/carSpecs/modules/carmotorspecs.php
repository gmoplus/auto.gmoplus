<?php
/**copyright**/

$internal_cache = false;
if (!extension_loaded('curl')) {
    echo 'curl required';
    exit;
}

$auth_url = "https://api.motorspecs.co.uk/oauth";
$data_url ="https://api.motorspecs.co.uk/api/motorspecs/specs";

//$auth_url = "https://api.motorspecs.com/oauth";
//$data_url ="https://api.motorspecs.com/api/motorspecs/identity";

//$data_url ="https://staging.motorspecs.com/api/motorspecs/identity-specs";
//$auth_url = "https://staging.motorspecs.co.uk/oauth";
if (!$service_info['Token']) {
    $service_info['Token'] = getToken($auth_url,$service_info);
}

$post = array();
$post['registration'] = $call_data['registration'];

$data = getData($data_url, $service_info['Token'], $post, $internal_cache);
/* renew token and try to get again */
if ($data['status'] == 403) {
	$service_info['Token'] = getToken($auth_url,$service_info);    
	$data = getData($data_url, $service_info['Token'], $post, $internal_cache);
}

$out = $this -> extractSubNodes($data);

function getData($url=false, $token=false, $post = false, $cache = false)
{
    $timelimit = 100;
    $cache_request = false;

//  do not cache authorization queries
//	if ($cache && $token) {
//		$uniq = /*md5($url).*/md5(serialize($post));
//		$content = $GLOBALS['rlDb'] -> getOne("Content", "`Uniq` = '{$uniq}'", "car_specs_cache");

//		if ($content) {
//			return json_decode($content, true);
//		} else {
//			$cache_request = true;
//		}
//	}

	$curl = curl_init();

	curl_setopt_array($curl, array(
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "POST",
		CURLOPT_POSTFIELDS => json_encode($post),
		CURLOPT_HTTPHEADER => array(
			"authorization: Bearer ".$token,
			"cache-control: no-cache",
            "content-type: application/json",
            "accept: application/json"
//            "content-type: application/x-www-form-urlencoded"
		)
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);	
	curl_close($curl);

//	if ($cache_request && $content && (!$out['status'] || $out['status'] >=200 && $out['status'] < 210)) {
//		$sql = "INSERT INTO `".RL_DBPREFIX."car_specs_cache` (`Uniq`, `Content`, `Date`) VALUES('{$uniq}', '{$content}', NOW())";
//		$GLOBALS['rlDb'] -> query($sql);
//	}

	if ($err) {
		echo "cURL Error #:" . $err;
	} else {
		return json_decode($response, true);
	}
}


function getToken($auth_url, $service_info) {
	$authpost = http_build_query( array(
        'grant_type' => 'client_credentials',
        'client_id' => $service_info['Login'],
        'client_secret' => $service_info['Pass'])
    );

    $curl = curl_init();
	curl_setopt_array($curl, array(
	  CURLOPT_URL => $auth_url,
	  CURLOPT_RETURNTRANSFER => true,
      CURLOPT_SSL_VERIFYPEER => false,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "POST",
	  CURLOPT_POSTFIELDS => $authpost,
	  CURLOPT_HTTPHEADER => array(
	    "cache-control: no-cache",
	    "content-type: application/x-www-form-urlencoded"
	  ),
	));
	$content = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    $out = json_decode($content, true);

	if ($out['access_token']) {
		$token = $out['access_token'];
		$sql = "UPDATE `".RL_DBPREFIX."car_specs_services` SET `Token` = '{$token}' WHERE `Key` = '{$service_info['Key']}' ";
		$GLOBALS['rlDb']->query($sql);

		return $token;
	}
	
    return $out;
}
