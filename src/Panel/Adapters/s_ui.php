<?php
// WARNING: SQL injection vulnerability detected in this file
// Please review and use prepared statements or secure_* functions


declare(strict_types=1);

require_once 'config.php';
ini_set('error_log', 'error_log');

/**
 * Get client information from S-UI panel
 * 
 * @param string $username Username to search for
 * @param string $namepanel Panel name
 * @return array<string, mixed> Client data or empty array
 */
function get_Clients_ui(string $username, string $namepanel): array {
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $namepanel,"select");
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $marzban_list_get['url_panel'].'/apiv2/clients',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT_MS => 4000,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Token: '.$marzban_list_get['password_panel']
        ),
        CURLOPT_COOKIEFILE => 'cookie.txt',
    ));
    $output = [];
    $response = curl_exec($curl);
    if($response === false)return [];
    $response = json_decode($response,true);
    if(!$response['success'])return [];
    if(!isset($response['obj']['clients']))return array();
    foreach ($response['obj']['clients'] as $data){
        if($data['name'] == $username)return $data;
    }
    return [];
    curl_close($curl);
}
/**
 * Get detailed client information from S-UI panel
 * 
 * @param string $username Username
 * @param string $namepanel Panel name
 * @return array<string, mixed> Client details or empty array
 */
function GetClientsS_UI(string $username, string $namepanel): array {
    $userdata = get_Clients_ui($username,$namepanel);
    if(count($userdata) == 0)return [];
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $namepanel,"select");
    $curl = curl_init();curl_setopt_array($curl, array(
        CURLOPT_URL => $marzban_list_get['url_panel'].'/apiv2/clients?id='.$userdata['id'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT_MS => 4000,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Token: '.$marzban_list_get['password_panel']
        ),
    ));
    $response = curl_exec($curl);
    if($response === false)return [];
    $response = json_decode($response,true);
    if(!$response['success'])return [];
    return $response['obj']['clients'][0];
    curl_close($curl);
}
/**
 * Add new client to S-UI panel
 * 
 * @param string $namepanel Panel name
 * @param string|null $usernameac Username
 * @param int $Expire Expiration timestamp
 * @param int $Total Total volume
 * @param array<int> $inboundid Inbound IDs
 * @return array<string, mixed>|string Client creation response
 */
function addClientS_ui(string $namepanel, ?string $usernameac, int $Expire, int $Total, array $inboundid): array|string {
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $namepanel,"select");
    if($Expire == 0){
        $timeservice = 0;
    }else{
        $timelast = $Expire - time();
        $timeservice = -intval(($timelast/86400)*86400000);
    }
    if($usernameac == null)return json_encode(array(
        'status' => false,
        'msg' => "error"
    ));
    $password = bin2hex(random_bytes(16));
    $configpanel = array(
        "object" => 'clients',
        'action' => "new",
        "data" => json_encode(array(
            "enable" => true,
            "name" => $usernameac,
            "config" => array (
                "mixed" => array(
                    "username" => $usernameac
                ,"password" =>generateAuthStr()
                ),"socks" =>array(
                    "username" =>$usernameac,
                    "password"=>generateAuthStr()
                ),"http"=> array(
                    "username"=>$usernameac,
                    "password"=>generateAuthStr()
                ),"shadowsocks"=>array(
                    "name"=> $usernameac,
                    "password"=>$password
                ),"shadowsocks16"=>array(
                    "name"=>$usernameac,
                    "password"=>$password
                ),"shadowtls"=>array(
                    "name"=>$usernameac,
                    "password"=>$password
                ),"vmess"=>array(
                    "name"=>$usernameac,
                    "uuid"=>generateUUID(),
                    "alterId"=>0
                ),"vless"=>array(
                    "name"=>$usernameac,
                    "uuid"=>generateUUID(),
                    "flow"=>""
                ),"trojan"=>array(
                    "name"=>$usernameac,
                    "password"=>generateAuthStr()
                ),"naive"=>array(
                    "username"=>$usernameac,
                    "password"=>generateAuthStr()
                ),"hysteria"=>array(
                    "name"=>$usernameac,
                    "auth_str"=>generateAuthStr()
                ),"tuic"=>array(
                    "name"=>$usernameac,
                    "uuid"=>generateUUID(),
                    "password"=>generateAuthStr()
                ),"hysteria2"=>array(
                    "name"=>$usernameac,
                    "password"=>generateAuthStr()
                )),
            "inbounds" => $inboundid,
            "links" => [],
            "volume" => $Total,
            "expiry" => $Expire,
            "desc" => ""
        )),
    );
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $marzban_list_get['url_panel'].'/apiv2/save',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT_MS => 4000,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $configpanel,
        CURLOPT_HTTPHEADER => array(
            'Token: '.$marzban_list_get['password_panel']
        ),
    ));
    $response = curl_exec($curl);

    curl_close($curl);
    return json_decode($response, true);
}
/**
 * Update client configuration in S-UI panel
 * 
 * @param string $namepanel Panel name
 * @param array<string, mixed> $config Configuration data
 * @return array<string, mixed>|null Update response
 */
function updateClientS_ui(string $namepanel, array $config): array|null {
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $namepanel,"select");
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $marzban_list_get['url_panel'].'/apiv2/save',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT_MS => 4000,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $config,
        CURLOPT_HTTPHEADER => array(
            'Token: '.$marzban_list_get['password_panel']
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    return json_decode($response, true);
}
/**
 * Reset user data usage in S-UI panel
 * 
 * @param string $usernamepanel Username
 * @param string $namepanel Panel name
 * @return array<string, mixed>|null Reset response
 */
function ResetUserDataUsages_ui(string $usernamepanel, string $namepanel): array|null {
    $clients = GetClientsS_UI($usernamepanel,$namepanel);
    $configpanel = array(
        "object" => 'clients',
        'action' => "edit",
        "data" => json_encode(array(
            "id" => $clients['id'],
            "enable" => $clients['enable'],
            "name" => $clients['name'],
            "config" => $clients['config'],
            "inbounds" => $clients['inbounds'],
            "links" => $clients['links'],
            "volume" => $clients['volume'],
            "expiry" => $clients['expiry'],
            "desc" => $clients['desc'],
            "up" => 0,
            "down" => 0
        )),
    );
    $result = updateClientS_ui($namepanel,$configpanel);
    return $result;
}
/**
 * Remove client from S-UI panel
 * 
 * @param string $location Panel location
 * @param string $username Username
 * @return array<string, mixed>|null Removal response
 */
function removeClientS_ui(string $location, string $username): array|null {
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location,"select");
    $data_user = GetClientsS_UI($username,$location);
    $curl = curl_init();
    $configpanel = array(
        "object" => 'clients',
        'action' => "del",
        "data" => $data_user['id'],
    );
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $marzban_list_get['url_panel'].'/apiv2/save',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT_MS => 4000,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $configpanel,
        CURLOPT_HTTPHEADER => array(
            'Token: '.$marzban_list_get['password_panel']
        ),
    ));

    $response = json_decode(curl_exec($curl),true);
    curl_close($curl);
    return $response;
}
/**
 * Check if client is online in S-UI panel
 * 
 * @param string $name_panel Panel name
 * @param string $username Username
 * @return string Online status ('online' or 'offline')
 */
function get_onlineclients_ui(string $name_panel, string $username): string {
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $name_panel,"select");
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $marzban_list_get['url_panel'].'/apiv2/onlines',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT_MS => 4000,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Token: '.$marzban_list_get['password_panel']
        ),
    ));
    $response = curl_exec($curl);
    if($response == null)return "offline";
    $response = json_decode($response,true)['obj']['user'];
    if(!is_array($response))return "offline";
    if(in_array($username,$response))return "online";
    return "offline";
    curl_close($curl);

}
/**
 * Get panel settings from S-UI
 * 
 * @param string $name_panel Panel name
 * @return array<string, mixed> Panel settings or empty array
 */
function get_settig(string $name_panel): array {
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $name_panel,"select");
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $marzban_list_get['url_panel'].'/apiv2/settings',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT_MS => 4000,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Token: '.$marzban_list_get['password_panel']
        ),
    ));
    $response = curl_exec($curl);
    if($response == null)return [];
    $response = json_decode($response,true)['obj'];
    if(!is_array($response))return [];
    curl_close($curl);
    return $response;

}