<?php

/*

Dobzinski's project for Let's Encrypt server to auto deploy of certs.

https://github.com/dobzinski/dobz-letsencrypt/

MIT License

*/

function prepareFile($value) {
    if (!empty($value)) {
       return preg_replace('/[^a-zA-Z0-9\-.]/', '', $value);
    }
    return;
}

function updateHost() {
    global $_certificates;
    return;
}

function updateCertificate($event, $cert, $array) {
    global $_certificates;
    if (!empty($event)) {
        if (!empty($cert)) {
            $dir = $_certificates . $cert;
            if (!is_dir($dir)) {
                mkdir($dir);
                sleep(1);
            }
            if (is_array($array)) {
                if (!isset($array['ip'])) {
                    return;
                }
                $filename = $dir ."/". $array['ip'] . ".json" ;
                switch($event) {
                    case 'check':
                        if (!is_file($filename)) {
                            touch($filename);
                            sleep(1);
                            $value = array(
                                'ip'=>$array['ip'],
                                'check'=>$array['check'],
                                'date'=>$array['date'],
                                'update'=>'',
                                'check'=>$array['check']
                            );
                        } else {
                            $json = file_get_contents($filename);
                            $data = json_decode($json, true);
                            if (isset($data['ip']) && isset($data['update'])) {
                                $value = array(
                                    'ip'=>$data['ip'],
                                    'date'=>$data['date'],
                                    'update'=>$data['update'],
                                    'check'=>$array['check']
                                );
                            } else {
                                return;
                            }
                        }
                    break;
                    case 'update':
                        if (is_file($filename)) {
                            $json = file_get_contents($filename);
                            $data = json_decode($json, true);
                            if (isset($data['ip']) && isset($data['update'])) {
                                $value = array(
                                    'ip'=>$data['ip'],
                                    'date'=>$data['date'],
                                    'update'=>$array['update'],
                                    'check'=>$data['check']
                                );
                            } else {
                                return;
                            }
                        } else {
                            return;
                        }
                    break;
                    default:
                        return;
                }
                $json = json_encode($value);
                file_put_contents($filename, $json);
            }
        }
    }
    return;
}

$_today = strtotime('now');

?>
