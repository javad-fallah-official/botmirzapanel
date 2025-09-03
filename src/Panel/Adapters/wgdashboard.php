<?php

declare(strict_types=1);

require_once 'config.php';
ini_set('error_log', 'error_log');

/**
 * Get WireGuard user information
 * 
 * @param string $username Username
 * @param string $namepanel Panel name
 * @return array<string, mixed> User information
 */
function get_userwg(string $username, string $namepanel): array
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $namepanel, "select");
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $marzban_list_get['url_panel'] . '/api/getWireguardConfigurationInfo?configurationName=' . $marzban_list_get['inboundid'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
            'wg-dashboard-apikey: '.$marzban_list_get['password_panel']
        ),
    ));
    $response = json_decode(curl_exec($curl), true);
    if ($response === false) return [];
    $outputpear = array_merge($response['data']['configurationPeers'], $response['data']['configurationRestrictedPeers']);
    $output = [];
    foreach ($outputpear as $userinfo) {
        if ($userinfo['name'] == $username) {
            $output = $userinfo;
            break;
        }
    }
    curl_close($curl);
    return $output;
}
/**
 * Get last available IP address
 * 
 * @param string $namepanel Panel name
 * @return string Available IP address
 */
function ipslast(string $namepanel): string
{

    $marzban_list_get = select("marzban_panel", "*", "name_panel", $namepanel, "select");
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $marzban_list_get['url_panel'] . '/api/getAvailableIPs/' . $marzban_list_get['inboundid'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
            'wg-dashboard-apikey: '.$marzban_list_get['password_panel']
        ),
    ));
    $response = json_decode(curl_exec($curl), true)['data'];
    $key = array_keys($response)[0];
    curl_close($curl);
    return $response[$key][0];
}
/**
 * Download WireGuard configuration
 * 
 * @param string $namepanel Panel name
 * @param string $publickey Public key
 * @return string Configuration data
 */
function downloadconfig(string $namepanel, string $publickey): string
{

    $marzban_list_get = select("marzban_panel", "*", "name_panel", $namepanel, "select");
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $marzban_list_get['url_panel'] . "/api/downloadPeer/{$marzban_list_get['inboundid']}?id=" . urlencode($publickey),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
            'wg-dashboard-apikey: '.$marzban_list_get['password_panel']
        ),
    ));
    $response = json_decode(curl_exec($curl), true)['data'];
    curl_close($curl);
    return $response;
}
/**
 * Add WireGuard peer
 * 
 * @param string $namepanel Panel name
 * @param string $usernameac Username
 * @return array<string, mixed> Peer configuration or error response
 */
function addpear(string $namepanel, string $usernameac): array
{

    $marzban_list_get = select("marzban_panel", "*", "name_panel", $namepanel, "select");
    $pubandprivate = publickey();
    $config = array(
        'name' => $usernameac,
        'allowed_ips' => [ipslast($namepanel)],
        'private_key' => $pubandprivate['private_key'],
        'public_key' => $pubandprivate['public_key'],
        'preshared_key' => $pubandprivate['preshared_key'],
    );

    $configpanel = json_encode($config, true);
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $marzban_list_get['url_panel'] . '/api/addPeers/' . $marzban_list_get['inboundid'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $configpanel,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
            'Content-Type: application/json',
            'wg-dashboard-apikey: '.$marzban_list_get['password_panel']
        ),
    ));
    $response = json_decode(curl_exec($curl), true);
    $config['status'] = true;
    if ($response['status'] == false) return $response;

    curl_close($curl);
    return $config;
}
/**
 * Set scheduled job for peer
 * 
 * @param string $namepanel Panel name
 * @param string $type Job type
 * @param mixed $value Job value
 * @param string $publickey Public key
 * @return string Job response
 */
function setjob(string $namepanel, string $type, mixed $value, string $publickey): string
{
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $namepanel, "select");
    $curl = curl_init();
    $data = json_encode(array(
        "Job" => array(
            "JobID" =>  generateUUID(),
            "Configuration" => $marzban_list_get['inboundid'],
            "Peer" => $publickey,
            "Field" => $type,
            "Operator" => "lgt",
            "Value" => strval($value),
            "CreationDate" => "",
            "ExpireDate" => null,
            "Action" => "restrict"
        )
    ));
    curl_setopt_array($curl, array(
        CURLOPT_URL => $marzban_list_get['url_panel'] . '/api/savePeerScheduleJob',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'wg-dashboard-apikey: '.$marzban_list_get['password_panel']
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}
/**
 * Update WireGuard peer settings
 * 
 * @param string $namepanel Panel name
 * @param array<string, mixed> $config Peer configuration
 * @return string Update response
 */
function updatepear(string $namepanel, array $config): string
{

    $marzban_list_get = select("marzban_panel", "*", "name_panel", $namepanel, "select");
    $configpanel = json_encode($config, true);

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $marzban_list_get['url_panel'] . '/api/updatePeerSettings/' . $marzban_list_get['inboundid'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $configpanel,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'wg-dashboard-apikey: '.$marzban_list_get['password_panel']
        ),
    ));

    $response = curl_exec($curl);
    return $response;
    curl_close($curl);
    return json_decode($response, true);
}
/**
 * Delete scheduled job
 * 
 * @param string $namepanel Panel name
 * @param array<string, mixed> $config Job configuration
 * @return array<string, mixed> Delete response
 */
function deletejob(string $namepanel, array $config): array
{

    $marzban_list_get = select("marzban_panel", "*", "name_panel", $namepanel, "select");
    $configpanel = json_encode($config);

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $marzban_list_get['url_panel'] . '/api/deletePeerScheduleJob',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $configpanel,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'wg-dashboard-apikey: '.$marzban_list_get['password_panel']
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, true);
}
/**
 * Reset WireGuard user data usage
 * 
 * @param string $publickey Public key
 * @param string $namepanel Panel name
 * @return array<string, mixed> Reset response
 */
function ResetUserDataUsagewg(string $publickey, string $namepanel): array
{

    $marzban_list_get = select("marzban_panel", "*", "name_panel", $namepanel, "select");
    $config = array(
        "id" => $publickey,
        "type" => "total"
    );
    $configpanel = json_encode($config, true);

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $marzban_list_get['url_panel'] . '/api/resetPeerData/' . $marzban_list_get['inboundid'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $configpanel,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'wg-dashboard-apikey: '.$marzban_list_get['password_panel']
        ),
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, true);
}


/**
 * Remove WireGuard user
 * 
 * @param string $location Panel location
 * @param string $username Username
 * @return array<string, mixed> Removal response
 */
function remove_userwg(string $location, string $username): array
{
    allowAccessPeers($location, $username);
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location, "select");
    $data_user = json_decode(select("invoice", "user_info", "username", $username, "select")['user_info'], true)['public_key'];
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $marzban_list_get['url_panel'] . '/api/deletePeers/' . $marzban_list_get['inboundid'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode(array(
            "peers" => array(
                $data_user
            )
        )),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'wg-dashboard-apikey: '.$marzban_list_get['password_panel']
        ),
    ));
    $response = json_decode(curl_exec($curl), true);
    curl_close($curl);
    return $response;
}
/**
 * Allow access for WireGuard peers
 * 
 * @param string $location Panel location
 * @param string $username Username
 * @return array<string, mixed> Access response
 */
function allowAccessPeers(string $location, string $username): array
{

    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location, "select");
    $data_user = json_decode(select("invoice", "user_info", "username", $username, "select")['user_info'], true)['public_key'];
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $marzban_list_get['url_panel'] . '/api/allowAccessPeers/' . $marzban_list_get['inboundid'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode(array(
            "peers" => array(
                $data_user
            )
        )),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'wg-dashboard-apikey: '.$marzban_list_get['password_panel']
        ),
    ));
    $response = json_decode(curl_exec($curl), true);
    curl_close($curl);
    return $response;
}
function restrictPeers(string $location, string $username): array
{

    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location, "select");
    $data_user = json_decode(select("invoice", "user_info", "username", $username, "select")['user_info'], true)['public_key'];
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $marzban_list_get['url_panel'] . '/api/restrictPeers/' . $marzban_list_get['inboundid'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode(array(
            "peers" => array(
                $data_user
            )
        )),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'wg-dashboard-apikey: '.$marzban_list_get['password_panel']
        ),
    ));
    $response = json_decode(curl_exec($curl), true);
    curl_close($curl);
    return $response;
}