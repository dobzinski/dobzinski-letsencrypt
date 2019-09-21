<?php

/*

Dobzinski's project for Let's Encrypt server to auto deploy of certs.

https://github.com/dobzinski/dobz-letsencrypt/

MIT License

*/

$_info = '/opt/cert/config/acl.json';
$_certificates = '/opt/cert/certificate/';
$_clients = '/opt/cert/client/';
$_limits = '/opt/cert/limit/';
$_certs = '/opt/cert/pem/';
$_days = array(
    'critical'=>5,
    'warning'=>20,
);
$_formats = array(
    'date'=>'Y-m-d', // Y-m-d | y-m-d | d/m/Y | d/m/y
    'hour'=>'H:i:s' // H:i:s | H:i | g:i A | G:ia
);
$_theme = "clean"; // clean | dark
$_language = "en"; // en | pt-br

?>
