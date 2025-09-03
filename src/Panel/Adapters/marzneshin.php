<?php

declare(strict_types=1);

#-----------------------------#
/**
 * Get authentication token for Marzneshin panel
 * 
 * @param string $url_panel Panel URL
 * @param string $username_panel Username
 * @param string $password_panel Password
 * @return array<string, mixed> Token response or error
 */
function token_panelm(string $url_panel, string $username_panel, string $password_panel): array {
    $panel = select("marzban_panel","*","url_panel",$url_panel,"select");
    if($panel['datelogin'] != null){
        $date = json_decode($panel['datelogin'],true);
        if(isset($date['time'])){
            $timecurrent = time();
            $start_date = time() - strtotime($date['time']);
            if($start_date <= 600){
                return $date;
            }
        }
    }
    $url_get_token = $url_panel.'/api/admins/token';
    $data_token = array(
        'username' => $username_panel,
        'password' => $password_panel
    );
    $options = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT_MS => 6000,
        CURLOPT_POSTFIELDS => http_build_query($data_token),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/x-www-form-urlencoded',
            'accept: application/json'
        )
    );
    $curl_token = curl_init($url_get_token);
    curl_setopt_array($curl_token, $options);
    $token = curl_exec($curl_token);
    if (curl_error($curl_token)) {
        $token = [];
        $token['errror'] = curl_error($curl_token);
        return $token;
    }
    curl_close($curl_token);

    $body = json_decode( $token, true);
    if(isset($body['access_token'])){
        $time = date('Y/m/d H:i:s');
        $data = json_encode(array(
            'time' => $time,
            'access_token' => $body['access_token']
        ));
        update("marzban_panel","datelogin",$data,'name_panel',$panel['name_panel']);
    }
    return $body;
}

#-----------------------------#

/**
 * Get user information from Marzneshin panel
 * 
 * @param string $username Username
 * @param string $location Panel location
 * @return array<string, mixed>|null User data or null
 */
function getuserm(string $username, string $location): array|null
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location,"select");
    $Check_token = token_panelm($marzban_list_get['url_panel'], $marzban_list_get['username_panel'], $marzban_list_get['password_panel']);
    $usernameac = $username;
    $url =  $marzban_list_get['url_panel'].'/api/users/' . $usernameac;
    if(!isset($Check_token['access_token']))return;
    $header_value = 'Bearer ';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Authorization: ' . $header_value .  $Check_token['access_token']
    ));

    $output = curl_exec($ch);
    curl_close($ch);
    $data_useer = json_decode($output, true);
    return $data_useer;
}
#-----------------------------#
/**
 * Reset user data usage in Marzneshin panel
 * 
 * @param string $username Username
 * @param string $location Panel location
 * @return array<string, mixed>|null Reset response or null
 */
function ResetUserDataUsagem(string $username, string $location): array|null
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location,"select");
    $Check_token = token_panelm($marzban_list_get['url_panel'], $marzban_list_get['username_panel'], $marzban_list_get['password_panel']);
    $usernameac = $username;
    $url =  $marzban_list_get['url_panel'].'/api/users/' . $usernameac.'/reset';
    $header_value = 'Bearer ';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST , true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Authorization: ' . $header_value .  $Check_token['access_token']
    ));

    $output = curl_exec($ch);
    curl_close($ch);
    $data_useer = json_decode($output, true);
    return $data_useer;
}
/**
 * Revoke user subscription in Marzneshin panel
 * 
 * @param string $username Username
 * @param string $location Panel location
 * @return array<string, mixed>|null Revoke response or null
 */
function revoke_subm(string $username, string $location): array|null
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location,"select");
    $Check_token = token_panelm($marzban_list_get['url_panel'], $marzban_list_get['username_panel'], $marzban_list_get['password_panel']);
    $usernameac = $username;
    $url =  $marzban_list_get['url_panel'].'/api/users/' . $usernameac.'/revoke_sub';
    $header_value = 'Bearer ';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST , true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Authorization: ' . $header_value .  $Check_token['access_token']
    ));

    $output = curl_exec($ch);
    curl_close($ch);
    $data_useer = json_decode($output, true);
    return $data_useer;
}
#-----------------------------#
/**
 * Add new user to Marzneshin panel
 * 
 * @param string $location Panel location
 * @param int $data_limit Data limit in bytes
 * @param string $username_ac Username
 * @param int $timestamp Expiration timestamp
 * @return string|null User creation response or null
 */
function adduserm(string $location, int $data_limit, string $username_ac, int $timestamp): string|null
{
    global $pdo;
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location,"select");
    $Check_token = token_panelm($marzban_list_get['url_panel'], $marzban_list_get['username_panel'], $marzban_list_get['password_panel']);
    $url = $marzban_list_get['url_panel']."/api/users";
    $header_value = 'Bearer ';
    $data = array(
        'service_ids' => json_decode($marzban_list_get['proxies'],true),
        "data_limit" => $data_limit,
        "username" => $username_ac,
    );
    if($marzban_list_get['onholdstatus'] == "offonhold"){
        if ($timestamp == 0) {
            $data["expire_date"] = null;
            $data["expire_strategy"] = "never";
        } else {
            $date = date('c',$timestamp);
            $data["expire_date"] = $date;
            $data["expire_strategy"] = "fixed_date";
        }
    }else{
        if($timestamp == 0 ){
            $data["expire_date"] = null;
            $data["expire_strategy"] = "never";
        }else{
            $data["expire_date"] = null;
            $data["expire_strategy"] = "start_on_first_use";
            $data["usage_duration"] = $timestamp - time();
        }
    }
    $payload = json_encode($data);
    file_put_contents('ss',$payload);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Authorization: ' . $header_value .  $Check_token['access_token'],
        'Content-Type: application/json'
    ));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}
//----------------------------------
/**
 * Get system statistics from Marzneshin panel
 * 
 * @param string $location Panel location
 * @return array<string, mixed>|null System statistics or null
 */
function Get_System_Statsm(string $location): array|null {
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location,"select");
    $Check_token = token_panelm($marzban_list_get['url_panel'], $marzban_list_get['username_panel'], $marzban_list_get['password_panel']);
    $url =  $marzban_list_get['url_panel'].'/api/system/stats/users';
    $header_value = 'Bearer ';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Authorization: ' . $header_value .  $Check_token['access_token']
    ));

    $output = curl_exec($ch);
    curl_close($ch);
    $Get_System_Stats = json_decode($output, true);
    return $Get_System_Stats;
}
//----------------------------------
/**
 * Remove user from Marzneshin panel
 * 
 * @param string $location Panel location
 * @param string $username Username
 * @return array<string, mixed>|null Removal response or null
 */
function removeuserm(string $location, string $username): array|null
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location,"select");
    $Check_token = token_panelm($marzban_list_get['url_panel'], $marzban_list_get['username_panel'], $marzban_list_get['password_panel']);
    $url =  $marzban_list_get['url_panel'].'/api/users/'.$username;
    $header_value = 'Bearer ';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Authorization: ' . $header_value .  $Check_token['access_token']
    ));

    $output = curl_exec($ch);
    curl_close($ch);
    $data_useer = json_decode($output, true);
    return $data_useer;
}
//----------------------------------
/**
 * Modify user configuration in Marzneshin panel
 * 
 * @param string $location Panel location
 * @param string $username Username
 * @param array<string, mixed> $data User data to modify
 * @return array<string, mixed>|null Modification response or null
 */
function Modifyuserm(string $location, string $username, array $data): array|null
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location,"select");
    $Check_token = token_panelm($marzban_list_get['url_panel'], $marzban_list_get['username_panel'], $marzban_list_get['password_panel']);
    $url =  $marzban_list_get['url_panel'].'/api/users/'.$username;
    $payload = json_encode($data);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $headers = array();
    $headers[] = 'Accept: application/json';
    $headers[] = 'Authorization: Bearer '.$Check_token['access_token'];
    $headers[] = 'Content-Type: application/json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    curl_close($ch);
    $data_useer = json_decode($result, true);
    return $data_useer;
}