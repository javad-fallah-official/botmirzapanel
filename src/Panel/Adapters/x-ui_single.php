<?php

declare(strict_types=1);

require_once 'config.php';

/**
 * Login to panel and get cookie
 * 
 * @param string $code_panel Panel code
 * @return array<string, mixed>|string Login response or error
 */
function panel_login_cookie(string $code_panel): array|string {
    $panel = select("marzban_panel","*","id",$code_panel,"select");
    $curl = curl_init();
    curl_setopt_array($curl, array(
  CURLOPT_URL => $panel['url_panel'].'/login',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT_MS => 4000,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => "username={$panel['username_panel']}&password={$panel['password_panel']}",
  CURLOPT_COOKIEJAR => 'cookie.txt',
));
$response = curl_exec($curl);
if (curl_error($curl)) {
        $token = [];
        $token['errror'] = curl_error($curl);
        return $token;
    }
curl_close($curl);
return $response;
}

/**
 * Login to panel with verification
 * 
 * @param string $code_panel Panel code
 * @param bool $verify Whether to verify existing login
 * @return array<string, mixed>|null Login response or null if already logged in
 */
function login(string $code_panel, bool $verify = true): array|null {
    $panel = select("marzban_panel","*","id",$code_panel,"select");
    if($panel['datelogin'] != null && $verify){
        $date = json_decode($panel['datelogin'],true);
        if(isset($date['time'])){
        $timecurrent = time();
        $start_date = time() - strtotime($date['time']);
        if($start_date <= 3000){
            file_put_contents('cookie.txt',$date['access_token']);
            return null;
        }
        }
    }
    $response = panel_login_cookie($panel['id']);
    $time = date('Y/m/d H:i:s');
    $data = json_encode(array(
        'time' => $time,
        'access_token' => file_get_contents('cookie.txt')
            ));
    update("marzban_panel","datelogin",$data,'id',$panel['id']);
     if(!is_string($response))return array('success' => false);
    return json_decode($response,true);
}


/**
 * Get client traffic information
 * 
 * @param string $username Username
 * @param string $namepanel Panel name
 * @return array<string, mixed> Client traffic data
 */
function get_Client(string $username, string $namepanel): array {
    global $connect;
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $namepanel,"select");
    login($marzban_list_get['id']);
    $curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => $marzban_list_get['url_panel'].'/panel/api/inbounds/getClientTraffics/'.$username,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
  CURLOPT_HTTPHEADER => array(
    'Accept: application/json'
  ),
  CURLOPT_COOKIEFILE => 'cookie.txt',
));
$output = [];
$response = json_decode(curl_exec($curl),true)['obj'];
curl_close($curl);
return $response;
unlink('cookie.txt');
}
/**
 * Get client configuration from inbounds
 * 
 * @param string $username Username
 * @param string $namepanel Panel name
 * @return array<string, mixed> Client configuration data
 */
function get_clinets(string $username, string $namepanel): array {
    global $connect;
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $namepanel,"select");
    $login =login($marzban_list_get['id']);
    $curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => $marzban_list_get['url_panel'].'/panel/api/inbounds/list',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
  CURLOPT_HTTPHEADER => array(
    'Accept: application/json'
  ),
  CURLOPT_COOKIEFILE => 'cookie.txt',
));
$output = [];
$response = json_decode(curl_exec($curl),true)['obj'];
if($response === false)return [];
foreach ($response as $client){
    $client= json_decode($client['settings'],true)['clients'];
    foreach($client as $clinets){
    if($clinets['email'] == $username){
        $output = $clinets;
        break;
    }
    }
    
}
curl_close($curl);
unlink('cookie.txt');
return $output;
}
/**
 * Add new client to X-UI panel
 * 
 * @param string $namepanel Panel name
 * @param string $usernameac Username
 * @param int $Expire Expiration timestamp
 * @param int $Total Total volume
 * @param string $Uuid Client UUID
 * @param string $Flow Flow configuration
 * @param string $subid Subscription ID
 * @return array<string, mixed>|null Client creation response
 */
function addClient(string $namepanel, string $usernameac, int $Expire, int $Total, string $Uuid, string $Flow, string $subid): array|null {
    global $connect;
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $namepanel,"select");
    $Allowedusername = get_Client($usernameac,$namepanel);
    if (isset($Allowedusername['email'])) {
        $random_number = rand(1000000, 9999999);
        $username_ac = $usernameac . $random_number;
    }
    login($marzban_list_get['id']);
    $config = array(
        "id" => intval($marzban_list_get['inboundid']),
        'settings' => json_encode(array(
            'clients' => array(
                array(
                "id" => $Uuid,
                "flow" => $Flow,
                "email" => $usernameac,
                "totalGB" => $Total,
                "expiryTime" => $Expire,
                "enable" => true,
                "tgId" => "",
                "subId" => $subid,
                "reset" => 0
            )),
             'decryption' => 'none',
            'fallbacks' => array(),
        ))
        );

    $configpanel = json_encode($config,true);

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $marzban_list_get['url_panel'].'/panel/api/inbounds/addClient',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $configpanel,
        CURLOPT_COOKIEFILE => 'cookie.txt',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
            'Content-Type: application/json',
        ),
    ));
    $response = curl_exec($curl);

    curl_close($curl);
    unlink('cookie.txt');
    return json_decode($response, true);
}
/**
 * Update client configuration
 * 
 * @param string $namepanel Panel name
 * @param string $username Username
 * @param array<string, mixed> $config Configuration data
 * @return array<string, mixed>|null Update response
 */
function updateClient(string $namepanel, string $username, array $config): array|null {
    global $connect;
    $UsernameData = get_clinets($username,$namepanel);
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $namepanel,"select");
    login($marzban_list_get['id']);
    $configpanel = json_encode($config,true);

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $marzban_list_get['url_panel'].'/panel/api/inbounds/updateClient/'.$UsernameData['id'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $configpanel,
        CURLOPT_COOKIEFILE => 'cookie.txt',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
            'Content-Type: application/json',
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    unlink('cookie.txt');
    return json_decode($response, true);
}
/**
 * Reset user data usage in X-UI panel
 * 
 * @param string $usernamepanel Username
 * @param string $namepanel Panel name
 * @return void
 */
function ResetUserDataUsagex_uisin(string $usernamepanel, string $namepanel): void {
    global $connect;
    $data_user = get_clinets($usernamepanel,$namepanel);
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $namepanel,"select");
    login($marzban_list_get['id']);
    $curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => $marzban_list_get['url_panel']."/panel/api/inbounds/{$marzban_list_get['inboundid']}/resetClientTraffic/".$data_user['email'],
  CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $configpanel,
        CURLOPT_COOKIEFILE => 'cookie.txt',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
            'Content-Type: application/json',
        ),

));

$response = curl_exec($curl);
curl_close($curl);
unlink('cookie.txt');
}
/**
 * Remove client from X-UI panel
 * 
 * @param string $location Panel location
 * @param string $username Username
 * @return array<string, mixed>|null Removal response
 */
function removeClient(string $location, string $username): array|null {
    global $connect;
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location,"select");
    $data_user = get_clinets($username,$location);
    login($marzban_list_get['id']);
    $curl = curl_init();
    curl_setopt_array($curl, array(
  CURLOPT_URL => $marzban_list_get['url_panel']."/panel/api/inbounds/{$marzban_list_get['inboundid']}/delClient/".$data_user['id'],
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_COOKIEFILE => 'cookie.txt',
  CURLOPT_HTTPHEADER => array(
    'Accept: application/json',
  ),
));

$response = json_decode(curl_exec($curl),true);
curl_close($curl);
unlink('cookie.txt');
return $response;
}
/**
 * Check if client is online
 * 
 * @param string $name_panel Panel name
 * @param string $username Username
 * @return string Online status ('online' or 'offline')
 */
function get_onlinecli(string $name_panel, string $username): string {
    global $connect;
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $name_panel,"select");
    login($marzban_list_get['id']);
    $curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => $marzban_list_get['url_panel'].'/panel/api/inbounds/onlines',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_HTTPHEADER => array(
    'Accept: application/json'
  ),
  CURLOPT_COOKIEFILE => 'cookie.txt',
));
$response = json_decode(curl_exec($curl),true)['obj'];
if($response == null)return "offline";
if(in_array($username,$response))return "online";
return "offline";
curl_close($curl);
unlink('cookie.txt');

}