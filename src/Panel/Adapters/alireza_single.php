<?php
// WARNING: SQL injection vulnerability detected in this file
// Please review and use prepared statements or secure_* functions


declare(strict_types=1);

require_once 'config.php';
ini_set('error_log', 'error_log');

/**
 * Login to Alireza panel
 * 
 * @param string $url Panel URL
 * @param string $username Username
 * @param string $password Password
 * @return array<string, mixed> Login response or error
 */
function loginalireza(string $url, string $username, string $password): array {
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url.'/login',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT_MS => 6000,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => "username=$username&password=$password",
        CURLOPT_COOKIEJAR => 'cookie.txt',
    ));
    $response = curl_exec($curl);
    if (curl_error($curl)) {
        $token = [];
        $token['errror'] = curl_error($curl);
        return $token;
    }
    curl_close($curl);
    return json_decode($response,true);
}
/**
 * Get client traffic information from Alireza panel
 * 
 * @param string $username Username
 * @param string $namepanel Panel name
 * @return array<string, mixed>|null Client traffic data or null
 */
function get_Clientalireza(string $username, string $namepanel): array|null {
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $namepanel,"select");
    loginalireza($marzban_list_get['url_panel'],$marzban_list_get['username_panel'],$marzban_list_get['password_panel']);
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $marzban_list_get['url_panel'].'/xui/API/inbounds/getClientTraffics/'.$username,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT_MS => 4000,
        CURLOPT_SSL_VERIFYHOST =>  false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json'
        ),
        CURLOPT_COOKIEFILE => 'cookie.txt',
    ));
    $output = [];
    $response = curl_exec($curl);
    if($response === false)return null;
    $response = json_decode($response,true)['obj'];
    curl_close($curl);
    return $response;
    unlink('cookie.txt');
}
/**
 * Get client configuration from Alireza panel inbounds
 * 
 * @param string $username Username
 * @param string $namepanel Panel name
 * @return array<string, mixed>|null Client configuration data or null
 */
function get_clinetsalireza(string $username, string $namepanel): array|null {
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $namepanel,"select");
    loginalireza($marzban_list_get['url_panel'],$marzban_list_get['username_panel'],$marzban_list_get['password_panel']);
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $marzban_list_get['url_panel'].'/xui/API/inbounds',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_SSL_VERIFYHOST =>  false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT_MS => 4000,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json'
        ),
        CURLOPT_COOKIEFILE => 'cookie.txt',
    ));
    $output = [];
    $curlResponse = curl_exec($curl);
    if($curlResponse === false)return null;
    $response = json_decode($curlResponse,true)['obj'];
    if($response === null)return null;
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
 * Add new client to Alireza panel
 * 
 * @param string $namepanel Panel name
 * @param string $usernameac Username
 * @param int $Expire Expiration timestamp
 * @param int $Total Total volume
 * @param string $Uuid Client UUID
 * @param string $Flow Flow configuration
 * @param string $subid Subscription ID
 * @return array<string, mixed>|null Client creation response or null
 */
function addClientalireza_singel(string $namepanel, string $usernameac, int $Expire, int $Total, string $Uuid, string $Flow, string $subid): array|null {
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $namepanel,"select");
    $Allowedusername = get_Clientalireza($usernameac,$namepanel);
    if (isset($Allowedusername['email'])) {
        $random_number = rand(1000000, 9999999);
        $username_ac = $usernameac . $random_number;
    }
    $login  = loginalireza($marzban_list_get['url_panel'], $marzban_list_get['username_panel'], $marzban_list_get['password_panel']);
    if($login === null)return null;
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
        CURLOPT_URL => $marzban_list_get['url_panel'].'/xui/API/inbounds/addClient',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_SSL_VERIFYHOST =>  false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT_MS => 4000,
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
 * Update client configuration in Alireza panel
 * 
 * @param string $namepanel Panel name
 * @param string $username Username
 * @param array<string, mixed> $config Configuration data
 * @return array<string, mixed>|null Update response or null
 */
function updateClientalireza(string $namepanel, string $username, array $config): array|null {
    $UsernameData = get_clinetsalireza($username,$namepanel);
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $namepanel,"select");
    loginalireza($marzban_list_get['url_panel'], $marzban_list_get['username_panel'], $marzban_list_get['password_panel']);
    $configpanel = json_encode($config,true);

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $marzban_list_get['url_panel'].'/xui/API/inbounds/updateClient/'.$UsernameData['id'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_SSL_VERIFYHOST =>  false,
        CURLOPT_SSL_VERIFYPEER => false,
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
 * Reset user data usage in Alireza panel
 * 
 * @param string $usernamepanel Username
 * @param string $namepanel Panel name
 * @return void
 */
function ResetUserDataUsagealirezasin(string $usernamepanel, string $namepanel): void {
    $data_user = get_clinetsalireza($usernamepanel,$namepanel);
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $namepanel,"select");
    loginalireza($marzban_list_get['url_panel'], $marzban_list_get['username_panel'], $marzban_list_get['password_panel']);
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $marzban_list_get['url_panel']."/xui/API/inbounds/{$marzban_list_get['inboundid']}/resetClientTraffic/".$data_user['email'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_SSL_VERIFYHOST =>  false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_COOKIEFILE => 'cookie.txt',
        CURLOPT_CUSTOMREQUEST => 'POST',
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
 * Remove client from Alireza panel
 * 
 * @param string $location Panel location
 * @param string $username Username
 * @return array<string, mixed>|null Removal response or null
 */
function removeClientalireza_single(string $location, string $username): array|null {
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location,"select");
    loginalireza($marzban_list_get['url_panel'], $marzban_list_get['username_panel'], $marzban_list_get['password_panel']);
    $data_user = get_clinetsalireza($username,$location);
    loginalireza($marzban_list_get['url_panel'], $marzban_list_get['username_panel'], $marzban_list_get['password_panel']);
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $marzban_list_get['url_panel']."/xui/API/inbounds/{$marzban_list_get['inboundid']}/delClient/".$data_user['id'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_SSL_VERIFYHOST =>  false,
        CURLOPT_SSL_VERIFYPEER => false,
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
 * Check if client is online in Alireza panel
 * 
 * @param string $name_panel Panel name
 * @param string $username Username
 * @return string Online status ('online' or 'offline')
 */
function get_onlineclialireza(string $name_panel, string $username): string {
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $name_panel,"select");
    loginalireza($marzban_list_get['url_panel'],$marzban_list_get['username_panel'],$marzban_list_get['password_panel']);
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $marzban_list_get['url_panel'].'/xui/API/inbounds/onlines',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_SSL_VERIFYHOST =>  false,
        CURLOPT_SSL_VERIFYPEER => false,
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