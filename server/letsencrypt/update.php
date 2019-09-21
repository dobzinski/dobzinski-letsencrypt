<?php

/*

Dobzinski's project for Let's Encrypt server to auto deploy of certs.

https://github.com/dobzinski/dobz-letsencrypt/

MIT License

*/

error_reporting(0);
require_once('config.php');
require_once('functions.php');
$remote = $_SERVER['REMOTE_ADDR'];
$token = (isset($_GET['token']) ? $_GET['token'] : null);
$cert = prepareFile(isset($_GET['file']) ? $_GET['file'] : null);
$client = $_clients . $remote .".json";
if (!is_null($token)) {
    if (is_file($client)) {
        $data = json_decode(file_get_contents($client), true);
        if ($data['enable'] == 'true' && $data['ip'] == $remote && $data['token'] == $token) {
            $data['update'] = date('Y-m-d H:i:s');
            $json = json_encode($data);
            file_put_contents($client, $json);
            updateCertificate('update', $cert, $data);
            echo "200";
        } else {
            echo "401";
        }
    } else {
        echo "402";
    }
} else {
    echo "403";
}
exit;

?>
