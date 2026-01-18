<?php
/**copyright**/

$internal_cache = false;

if (!extension_loaded('curl')) {
    echo 'curl required';
    exit;
}

$test_mode = '&mode=test';
$api_key = $service_info['Api_key'];
$vin = $call_data['registration'];

/* specs */
$specs_url = 'http://specifications.vinaudit.com/getspecifications.php?vin=' . $vin;
$specs_url .= '&key=' . $api_key . '&format=json';
$specs_res = getData($specs_url, $vin);

if ($specs_res['success']) {
    $out = json_decode($specs_res, true);
    $out_tmp = $this->extractSubNodes($out);
    $out = array();
    foreach ($out_tmp as $k => $v) {
        $out[str_replace(' ', '_', $k)] = $v;
    }
    $out['title'] = $out['attributes_Year'] . " " . $out['attributes_Make'] . " ";
    $out['title'] .= $out['attributes_Model'] . " " . $out['attributes_Trim'];
    
    //get history report
    getHistoryReport($vin, $api_key);
} else {
    $out['status'] = "error";
    $out['message'] = $lang["cs_" . $specs_res['error']] ?: $specs_res['error'];
}

function getData($url = false, $vin = false, $token = false, $post = false, $cache = false)
{
    $GLOBALS['reefless']->loadClass('Actions');
    
    $timelimit = 100;
    // check if result is cached
    $content = $GLOBALS['rlDb']->getOne("Content", "`Uniq` = 'spec_{$vin}'", "car_specs_cache");
    
    if ($content) {
        $content = unserialize(base64_decode($content));
        
        return $content;
    } else {
        if ($post && extension_loaded('curl')) {
            $curl = curl_init();
            $options = array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $post,
                CURLOPT_HTTPHEADER => array(
                    "authorization: Bearer " . $token,
                    "cache-control: no-cache",
                ),
            );
            curl_setopt_array($curl, $options);
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
        } else {
            $response = $GLOBALS['reefless']->getPageContent($url);
        }
        
        $response_status = json_decode($response, true);
        if ($response_status['success']) {
            // car_specs_cache
            $content = base64_encode(serialize($response));
            $add_data = array(
                'Uniq' => "spec_" . $vin,
                'Module' => 'vinaudit',
                'Content' => $content,
                'Date' => "NOW()",
            );
            $GLOBALS['rlActions']->insertOne($add_data, 'car_specs_cache');
        } else {
            $error = $response_status;
        }
        if ($error) {
            return $error;
        } else {
            return $response;
        }
    }
}

function getHistoryReport($vin = false, $api_key = false, $service_info = false)
{
    $user_email = $service_info['Login'];
    $user_pass = $service_info['Pass'];
    
    // $test_mode  = '&mode=test';
    $ex_history = $GLOBALS['rlDb']->getOne("Uniq", "`Uniq` = 'rh_{$vin}'", "car_specs_cache");
    if (!$ex_history) {
        $check_url = 'http://api.vinaudit.com/query.php?vin=' . $vin . '&key=' . $api_key . '&format=json' . $test_mode;
        $check_res = $GLOBALS['reefless']->getPageContent($check_url);
        
        $res = json_decode($check_res, true);
        if (isset($res['success'])) {
            $report_url = 'https://api.vinaudit.com/pullreport.php?user=' . $user_email . '&pass=' . $user_pass
                . '&vin=' . $vin . '&key=' . $api_key . '&format=json&id=' . $res['id'] . $test_mode;
            $report_res = file_get_contents($report_url);
            $history = json_decode($report_res, true);
            if ($res['success']) {
                
                if (!is_dir(RL_FILES . "vin-reports")) {
                    mkdir(RL_FILES . "vin-reports");
                }
                $local_pdf_name = RL_FILES . "vin-reports" . RL_DS . $vin . ".pdf";
                $local_pdf_url = RL_FILES_URL . "vin-reports" . RL_DS . $vin . ".pdf";
                
                if (!is_file($local_pdf_name)) {
                    $pdf_url = 'https://api.vinaudit.com/pullreport.php?user=' . $user_email . '&pass=' . $user_pass
                        . '&vin=' . $vin . '&key=' . $api_key . '&format=xml&pdf=1&id=' . $res['id'] . $test_mode;
                    copy($pdf_url, $local_pdf_name);
                    $history['pdf'] = $local_pdf_url;
                } else {
                    $history['pdf'] = $local_pdf_url;
                }
            }
            $history = $GLOBALS['rlCarSpecs']->extractSubNodes($history);
            if ($history) {
                $content = base64_encode(serialize($history));
                $add_data = array(
                    'Uniq' => "rh_" . $vin,
                    'Module' => 'vinaudit',
                    'Content' => $content,
                    'Date' => "NOW()",
                );
                $GLOBALS['rlActions']->insertOne($add_data, 'car_specs_cache');
            }
        }
    }
}
