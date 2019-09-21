<?php

/*

Dobzinski's project for Let's Encrypt server to auto deploy of certs.

https://github.com/dobzinski/dobz-letsencrypt/

MIT License

*/

error_reporting(0);
require_once('config.php');
$client = $_SERVER['REMOTE_ADDR'];
$token = (isset($_GET['token']) ? $_GET['token'] : null);
$host = gethostbyaddr($client);
$filename = $_clients . $client .".json";
if ($token != null) {
    if (!is_file($filename)) {
        $array = array(
            'enable'=>'',
            'token'=>$token,
            'date'=>date('Y-m-d H:i:s'),
            'update'=>'',
            'check'=>'',
            'host'=>$host,
            'ip'=>$client
        );
        $json = json_encode($array);
        file_put_contents($filename, $json);
        echo "Ok!";
    } else {
        echo "Already installed!";
    }
} else {
    echo "Forbidden!";
}
exit;

?>
