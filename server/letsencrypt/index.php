<?php

/*

Dobzinski's project for Let's Encrypt server to auto deploy of certs.

https://github.com/dobzinski/dobz-letsencrypt/

MIT License

*/

error_reporting(0);
require_once('config.php');
require_once('lang/'. (is_file('lang/'. $_language .'.php') ? $_language : 'en') .'.php');
require_once('functions.php');

$clients = array();
$certs = array();
if (is_file($_info)) {
    $info = json_decode(file_get_contents($_info), true);
}
if (is_dir($_clients)) {
    $clients = array_diff(scandir($_clients), array('.', '..'));
}
if (is_dir($_certificates)) {
    $certificates = array_diff(scandir($_certificates), array('.', '..'));
    if (count($certificates)) {
        foreach ($certificates as $c) {
            $file = array_diff(scandir($_certificates ."/". $c), array('.', '..'));
            foreach ($file as $f) {
                $filename = $_certificates ."/". $c ."/". $f;
                if (is_file($filename)) {
                    $data = json_decode(file_get_contents($filename), true);
                    $certs[$c][] = $data;
                }
            }
        }
    }
}
if (is_dir($_limits)) {
    $limits = array_diff(scandir($_limits), array('.', '..'));
    $control = array();
    if (count($limits)) {
        $subtitle = "";
        $subtitle .= "<div style=\"padding: 2px;\"><span class=\"row-alert-cert2 subtitle\">&nbsp;</span>&nbsp;". $_lang['warning'] ." (". $_days['warning'] ." ". $_lang['days'] .")</div>";
        $subtitle .= "<div style=\"padding: 2px;\"><span class=\"row-alert-cert3 subtitle\">&nbsp;</span>&nbsp;". $_lang['critical'] ." (". $_days['critical'] ." ". $_lang['days'] .")</div>";
        $subtitle .= "<div style=\"padding: 2px;\"><span class=\"row-alert-cert1 subtitle\">&nbsp;</span>&nbsp;". $_lang['expire'] ."</div>";
        foreach($limits as $limit) {
            $change = date('Ymd', filemtime($_limits . $limit));
            $strtotime = strtotime(file_get_contents($_limits . $limit));
            $left = '';
            if (!empty($strtotime)) {
                $left = round(((($strtotime - $_today)/24)/60)/60);
                if ($left < 0) {
                    $alert = 1;
                } else if ($left <= $_days['critical']) {
                    $alert = 3;
                } else if ($left <= $_days['warning']) {
                    $alert = 2;
                } else {
                    $alert = 0;
                }
                $control[] = array($limit, $strtotime, $alert, $left);
            }
        }
    }
}

include_once('html/head.php');
include_once('html/body.php');

echo "\t\t<div class=\"jumbotron jumbotron-fluid jumbotron-align\">
      <div class=\"container\">
        <h1 class=\"display-4\">Dobz :: Let's Encrypt</h1>
        <p class=\"lead\">". $_lang['subtitle'] ."</p>
      </div>
    </div>\n";

echo "<div class=\"row\">\n";

if (count($control)) {
    echo "\t\t<div class=\"col-lg-9\">\n";
    echo "\t\t<table class=\"table table-bordered table-hover\">\n";
    echo "\t\t<caption><i class=\"fa fa-arrow-up\"></i>&nbsp;". $_lang['list_of_certs'] ."</caption>\n";
    echo "\t\t\t<thead class=\"thead-dark\"><tr>";
    echo "<th scope=\"col\"><i class=\"fa fa-bell\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"". $_lang['remaining'] ."\"></i></th>";
    echo "<th scope=\"col\"><i class=\"fa fa-lock\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"". $_lang['cert'] ."\"></i></th>";
    echo "<th scope=\"col\"><i class=\"fa fa-calendar-times-o\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"". $_lang['limit'] ."\"></i></th>";
    echo "</tr></thead>\n";
    echo "\t\t\t<tbody>\n";
    foreach($control as $k) {
        echo "\t\t<tr";
        if ($k[2] > 0) {
            echo " class=\"row-alert-cert" . $k[2] ."\">";
        } else {
            echo ">";
        }
        $date = (!empty($k[1]) ? date($_formats['date'], $k[1]) : "-");
        echo "<td class=\"cell-right\"><span data-toggle=\"tooltip\" data-placement=\"top\" title=\"". htmlentities($k[3]) ."\">". htmlentities($k[3]) ."</span></td>";
        echo "<td class=\"cell-left\"><span data-toggle=\"tooltip\" data-placement=\"top\" title=\"". htmlentities($k[0]) ."\">". htmlentities($k[0]) ."</span></td>";
        echo "<td class=\"cell-right\"><span data-toggle=\"tooltip\" data-placement=\"top\" title=\"". htmlentities($date) ."\">". htmlentities($date) ."</span></td>";
        echo "</tr>\n";
    }
    echo "\t</table>\n";
    echo "\t</div>";
    echo "\t\t<div class=\"col-lg-3\" style=\"margin-bottom: 50px;\">\n";
    echo "\t\t<table class=\"table table-bordered\">\n";
    echo "\t\t\t<tbody>\n";
    echo "\t\t<tr><td>";
    echo $subtitle;
    echo "</td></tr>\n";
    echo "\t</table>\n";
    echo "\t</div>";
}

echo "\t</div>\n";
echo "\t<div class=\"container-fluid\">";
echo "<div class=\"row\">\n";

if (count($certs)) {
    echo "\t\t<table class=\"table table-bordered table-hover\">\n";
    echo "\t\t<caption><i class=\"fa fa-arrow-up\"></i>&nbsp;". $_lang['list_of_certs_clients'] ."</caption>\n";
    echo "\t\t\t<thead class=\"thead-dark\"><tr>";
    echo "<th scope=\"col\"><i class=\"fa fa-bookmark\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"". $_lang['status'] ."\"></i></th>";
    echo "<th scope=\"col\"><i class=\"fa fa-lock\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"". $_lang['cert'] ."\"></th>";
    echo "<th scope=\"col\"><i class=\"fa fa-sitemap\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"". $_lang['ip'] ."\"></th>";
    echo "<th scope=\"col\"><i class=\"fa fa-rocket\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"". $_lang['deploy'] ."\"></th>";
    echo "<th scope=\"col\"><i class=\"fa fa-bolt\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"". $_lang['update'] ."\"></th>";
    echo "<th scope=\"col\"><i class=\"fa fa-calendar-check-o\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"". $_lang['check'] ."\"></th>";
    echo "</tr></thead>\n";
    echo "\t\t\t<tbody>\n";
    foreach($certs as $cert=>$values) {
        foreach($values as $v) {
            $vCert = htmlentities($cert);
            $vIp = htmlentities($v['ip']);
            $vCheck = (!empty($v['check']) ? date_create($v['check']) : '');
            $vCheck = (!empty($vCheck) ? htmlentities(date_format($vCheck, 'Y-m-d') == date('Y-m-d') ? date_format($vCheck, $_formats['hour']) : date_format($vCheck, $_formats['date'])) : '-');
            $vCheckTitle = htmlentities(!empty($v['check']) ? date_format(date_create($v['check']), $_formats['date'] ." ". $_formats['hour']) : "-");
            $vUpdate = (!empty($v['update']) ? date_create($v['update']) : '');
            $vUpdate = (!empty($vUpdate) ? htmlentities(date_format($vUpdate, 'Y-m-d') == date('Y-m-d') ? date_format($vUpdate, $_formats['hour']) : date_format($vUpdate, $_formats['date'])) : '-');
            $vUpdateTitle = htmlentities(!empty($v['update']) ? date_format(date_create($v['update']), $_formats['date'] ." ". $_formats['hour']) : "-");
            $vDate = (!empty($v['date']) ? date_create($v['date']) : '');
            $vDate = (!empty($vDate) ? htmlentities(date_format($vDate, 'Y-m-d') == date('Y-m-d') ? date_format($vDate, $_formats['hour']) : date_format($vDate, $_formats['date'])) : '-');
            $vDateTitle = htmlentities(!empty($v['date']) ? date_format(date_create($v['date']), $_formats['date'] ." ". $_formats['hour']) : "-");
            if (count($control)) {
                foreach($control as $c) {
                    if ($c[0] == $vCert) {
                        switch($c[2]) {
                            case 0: $title = $_lang['valid']; $color = 'valid'; break;
                            case 1: $title = $_lang['expire']; $color = 'expire'; break;
                            case 2: $title = $_lang['warning']; $color = 'warning'; break;
                            case 3: $title = $_lang['critical']; $color = 'critical'; break;
                            default: $title = '-'; $color = 'default';
                        }
                        break;
                    }
                }
            }
            echo "\t\t<tr>";
            echo "<td class=\"cell-center\"><i class=\"icon-". $color ." fa fa-circle\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"". $title ."\"></i></td>";
            echo "<td class=\"cell-left\"><span data-toggle=\"tooltip\" data-placement=\"top\" title=\"". $vCert ."\">". $vCert ."</span></td>";
            echo "<td class=\"cell-left\"><span data-toggle=\"tooltip\" data-placement=\"top\" title=\"". $vIp ."\">". $vIp ."</span></td>";
            echo "<td class=\"cell-right\"><span data-toggle=\"tooltip\" data-placement=\"top\" title=\"". $vDateTitle ."\">". $vDate ."</span></td>";
            echo "<td class=\"cell-right\"><span data-toggle=\"tooltip\" data-placement=\"top\" title=\"". $vUpdateTitle ."\">". $vUpdate ."</span></td>";
            echo "<td class=\"cell-right\"><span data-toggle=\"tooltip\" data-placement=\"top\" title=\"". $vCheckTitle ."\">". $vCheck ."</span></td>";
            echo "</tr>\n";
        }
    }
    echo "\t\t\t</tbody>\n";
    echo "\t\t</table>\n";
}

echo "\t</div>";
echo "\t</div>\n";
echo "\t<div class=\"container-fluid\">";
echo "<div class=\"row\">\n";

if (count($clients)) {
    echo "\t\t<table class=\"table table-bordered table-hover\">\n";
    echo "\t\t<caption><i class=\"fa fa-arrow-up\"></i>&nbsp;". $_lang['list_of_clients'] ."</caption>\n";
    echo "\t\t\t<thead class=\"thead-dark\"><tr>";
    echo "<th scope=\"col\"><i class=\"fa fa-anchor\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"". $_lang['situation'] ."\"></i></th>";
    echo "<th scope=\"col\"><i class=\"fa fa-tags\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"". $_lang['host'] ."\"></th>";
    echo "<th scope=\"col\"><i class=\"fa fa-sitemap\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"". $_lang['ip'] ."\"></th>";
    echo "<th scope=\"col\"><i class=\"fa fa-rocket\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"". $_lang['deploy'] ."\"></th>";
    echo "<th scope=\"col\"><i class=\"fa fa-bolt\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"". $_lang['update'] ."\"></th>";
    echo "<th scope=\"col\"><i class=\"fa fa-calendar-check-o\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"". $_lang['check'] ."\"></th>";
    echo "</tr></thead>\n";
    echo "\t\t\t<tbody>\n";
    foreach($clients as $client) {
        if (is_file($_clients . $client)) {
            $json = file_get_contents($_clients . $client);
            if (!empty($json)) {
                $data = json_decode($json, true);
                if (count($data)) {
                    $vEnabled = ($data['enable'] == 'true' ? true : ($data['enable'] == 'false' ? false : ''));
                    $vHost = htmlentities($data['host']);
                    $vIp = htmlentities($data['ip']);
                    $vCheck = (!empty($data['check']) ? date_create($data['check']) : '');
                    $vCheck = (!empty($vCheck) ? htmlentities(date_format($vCheck, 'Y-m-d') == date('Y-m-d') ? date_format($vCheck, $_formats['hour']) : date_format($vCheck, $_formats['date'])) : '-');
                    $vCheckTitle = htmlentities(!empty($data['check']) ? date_format(date_create($data['check']), $_formats['date'] ." ". $_formats['hour']) : "-");
                    $vUpdate = (!empty($data['update']) ? date_create($data['update']) : '');
                    $vUpdate = (!empty($vUpdate) ? htmlentities(date_format($vUpdate, 'Y-m-d') == date('Y-m-d') ? date_format($vUpdate, $_formats['hour']) : date_format($vUpdate, $_formats['date'])) : '-');
                    $vUpdateTitle = htmlentities(!empty($data['update']) ? date_format(date_create($data['update']), $_formats['date'] ." ". $_formats['hour']) : "-");
                    $vDate = (!empty($data['date']) ? date_create($data['date']) : '');
                    $vDate = (!empty($vDate) ? htmlentities(date_format($vDate, 'Y-m-d') == date('Y-m-d') ? date_format($vDate, $_formats['hour']) : date_format($vDate, $_formats['date'])) : '-');
                    $vDateTitle = htmlentities(!empty($data['date']) ? date_format(date_create($data['date']), $_formats['date'] ." ". $_formats['hour']) : "-");
                    if ($vEnabled === '') {
                        $alert = 'wait';
                    } else {
                        $alert = '';
                        unset($alert);
                    }
                    echo "\t\t<tr>";
                    echo "<td class=\"cell-center cell-". ($vEnabled == true ? "enable" : ($vEnabled === false ? "disable" : "wait")) ."\"><i data-toggle=\"tooltip\" data-placement=\"top\" class=\"fa fa-". ($vEnabled == true ? "check\" title=\"". $_lang['enable'] : ($vEnabled === false ? "times\" title=\"". $_lang['disable'] : "clock-o\" title=\"". $_lang['wait'])) ."\"></i></td>";
                    echo "<td class=\"cell-left\"><span data-toggle=\"tooltip\" data-placement=\"top\" title=\"". $vHost ."\">". $vHost ."</span></td>";
                    echo "<td class=\"cell-left\"><span data-toggle=\"tooltip\" data-placement=\"top\" title=\"". $vIp ."\">". $vIp ."</span></td>";
                    echo "<td class=\"cell-right\"><span data-toggle=\"tooltip\" data-placement=\"top\" title=\"". $vDateTitle ."\">". $vDate ."</span></td>";
                    echo "<td class=\"cell-right\"><span data-toggle=\"tooltip\" data-placement=\"top\" title=\"". $vUpdateTitle ."\">". $vUpdate ."</span></td>";
                    echo "<td class=\"cell-right\"><span data-toggle=\"tooltip\" data-placement=\"top\" title=\"". $vCheckTitle ."\">". $vCheck ."</span></td>";
                    echo "</tr>\n";
                }
            }
        }
    }
    echo "\t\t\t</tbody>\n";
    echo "\t\t</table>\n";
}

echo "\t</div>";
echo "\t</div>\n";

include_once('html/footer.php');

?>
