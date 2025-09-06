<?php
// WARNING: SQL injection vulnerability detected in this file
// Please review and use prepared statements or secure_* functions


declare(strict_types=1);

/**
 * Login to MikroTik panel
 * 
 * @param string $url Panel URL
 * @param string $username Username
 * @param string $password Password
 * @return array<string, mixed> Login response or error
 */
function login_mikrotik(string $url, string $username, string $password): array {
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => $url.'/rest/system/resource',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_USERPWD => $username . ":" . $password,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 1,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
));

$response = curl_exec($curl);
$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
if($response === false || $httpcode != 200)return array("error" => 404);
$response = json_decode($response,true);
curl_close($curl);
return $response;

}

/**
 * Add user to MikroTik panel
 * 
 * @param string $name_panel Panel name
 * @param string $username Username
 * @param string $password Password
 * @param string $group User group
 * @return array<string, mixed>|string User creation response
 */
function addUser_mikrotik(string $name_panel, string $username, string $password, string $group): array|string {
    $panel = select("marzban_panel","*","name_panel",$name_panel,"select");
    $curl = curl_init();
    $data = array(
        'name' => $username,
        'password' => $password
    );
    curl_setopt_array($curl, array(
      CURLOPT_URL => $panel['url_panel'].'/rest/user-manager/user/add',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_USERPWD => $panel['username_panel'] . ":" . $panel['password_panel'],
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json'
    ),
      CURLOPT_POSTFIELDS => json_encode($data,true)
));

$response = curl_exec($curl);
if($response === false)return json_encode(array("error" => 404));
set_profile_mikrotik($name_panel,$username,$group);
$response = json_decode($response,true);
curl_close($curl);
return $response;
}
/**
 * Set profile for MikroTik user
 * 
 * @param string $name_panel Panel name
 * @param string $username Username
 * @param string $prof_name Profile name
 * @return array<string, mixed>|string Profile setting response
 */
function set_profile_mikrotik(string $name_panel, string $username, string $prof_name): array|string {
    $panel = select("marzban_panel","*","name_panel",$name_panel,"select");
    $curl = curl_init();
    $data = array(
        'user' => $username,
        'profile' => $prof_name
    );
    curl_setopt_array($curl, array(
      CURLOPT_URL => $panel['url_panel'].'/rest/user-manager/user-profile/add',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_USERPWD => $panel['username_panel'] . ":" . $panel['password_panel'],
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json'
    ),
      CURLOPT_POSTFIELDS => json_encode($data,true)
));

$response = curl_exec($curl);
if($response === false)return json_encode(array("error" => 404));
$response = json_decode($response,true);
curl_close($curl);
return $response;
}
/**
 * Get MikroTik user information
 * 
 * @param string $name_panel Panel name
 * @param string $username Username
 * @return array<string, mixed>|string User information
 */
function GetUsermikrotik(string $name_panel, string $username): array|string {
    $panel = select("marzban_panel","*","name_panel",$name_panel,"select");
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => $panel['url_panel'].'/rest/user-manager/user?name='.$username,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_USERPWD => $panel['username_panel'] . ":" . $panel['password_panel'],
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json'
    )
));

$response = curl_exec($curl);
if($response === false)return json_encode(array("error" => 404));
$response = json_decode($response,true);
curl_close($curl);
return $response;
}
/**
 * Get MikroTik user volume information
 * 
 * @param string $name_panel Panel name
 * @param string $id User ID
 * @return array<string, mixed>|string User volume information
 */
function GetUsermikrotik_volume(string $name_panel, string $id): array|string {
    $panel = select("marzban_panel","*","name_panel",$name_panel,"select");
    $curl = curl_init();
    $data = array(
        'once' => true,
        '.id' => $id
        );
    curl_setopt_array($curl, array(
      CURLOPT_URL => $panel['url_panel'].'/rest/user-manager/user/monitor',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_USERPWD => $panel['username_panel'] . ":" . $panel['password_panel'],
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST', 
      CURLOPT_POSTFIELDS => json_encode($data,true),
      CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json'
    )
));

$response = curl_exec($curl);
if($response === false)return json_encode(array("error" => 404));
$response = json_decode($response,true)[0];
curl_close($curl);
return $response;
}
/**
 * Delete MikroTik user
 * 
 * @param string $name_panel Panel name
 * @param string $username Username
 * @return array<string, mixed>|string Deletion response
 */
function deleteUser_mikrotik(string $name_panel, string $username): array|string {
    $panel = select("marzban_panel","*","name_panel",$name_panel,"select");
    $curl = curl_init();
    $data = array(
        '.id' => $username
        );
    curl_setopt_array($curl, array(
      CURLOPT_URL => $panel['url_panel'].'/rest/user-manager/user/remove',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_USERPWD => $panel['username_panel'] . ":" . $panel['password_panel'],
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST', 
      CURLOPT_POSTFIELDS => json_encode($data,true),
      CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json'
    )
));

$response = curl_exec($curl);
if($response === false)return json_encode(array("error" => 404));
$response = json_decode($response,true)[0];
curl_close($curl);
return $response;
}
