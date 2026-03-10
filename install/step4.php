<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . 'Db.php';

//error_reporting (0);
$appurl = $_POST['appurl'];
$db_host = $_POST['dbhost'];
$db_port = trim($_POST['dbport']);
$db_user = $_POST['dbuser'];
$db_pass = $_POST['dbpass'];
$db_name = $_POST['dbname'];
$db_ssl_mode = Db::normalizeSslMode($_POST['dbsslmode']);
$db_ssl_ca = trim($_POST['dbsslca']);
$db_charset = 'utf8mb4';
$cn = '0';
$dbConfig = [
    'host' => $db_host,
    'port' => $db_port,
    'user' => $db_user,
    'pass' => $db_pass,
    'name' => $db_name,
    'charset' => $db_charset,
    'ssl_mode' => $db_ssl_mode,
    'ssl_ca' => $db_ssl_ca,
    'ssl_verify_server_cert' => null,
];
try {
    $dbh = Db::createPdo($dbConfig);
    $cn = '1';
} catch (PDOException $ex) {
    $cn = '0';
}

if ($cn == '1') {
    $export = function ($value) {
        return var_export($value, true);
    };
    if (isset($_POST['radius']) && $_POST['radius'] == 'yes') {
        $input = '<?php

$forwardedProto = "";
if (!empty($_SERVER["HTTP_X_FORWARDED_PROTO"])) {
    $forwardedProto = trim(explode(",", $_SERVER["HTTP_X_FORWARDED_PROTO"])[0]);
} elseif (!empty($_SERVER["HTTP_FORWARDED"]) && preg_match("/proto=([^;]+)/i", $_SERVER["HTTP_FORWARDED"], $matches)) {
    $forwardedProto = trim($matches[1], "\" ");
}
$isHttps = ((!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") || (!empty($_SERVER["REQUEST_SCHEME"]) && $_SERVER["REQUEST_SCHEME"] === "https") || (!empty($_SERVER["SERVER_PORT"]) && (string) $_SERVER["SERVER_PORT"] === "443") || (!empty($_SERVER["HTTP_X_FORWARDED_SSL"]) && $_SERVER["HTTP_X_FORWARDED_SSL"] === "on") || (!empty($_SERVER["HTTP_X_FORWARDED_PORT"]) && (string) $_SERVER["HTTP_X_FORWARDED_PORT"] === "443") || strtolower($forwardedProto) === "https");
$protocol = $isHttps ? "https://" : "http://";
$host = !empty($_SERVER["HTTP_X_FORWARDED_HOST"]) ? trim(explode(",", $_SERVER["HTTP_X_FORWARDED_HOST"])[0]) : $_SERVER["HTTP_HOST"];
$baseDir = rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/\\\\");
define("APP_URL", $protocol . $host . $baseDir);

// Live, Dev, Demo
$_app_stage = "Live";

// Database PHPNuxBill
$db_host        = ' . $export($db_host) . ';
$db_port        = ' . $export($db_port) . ';
$db_user        = ' . $export($db_user) . ';
$db_pass        = ' . $export($db_pass) . ';
$db_name        = ' . $export($db_name) . ';
$db_charset     = ' . $export($db_charset) . ';
$db_ssl_mode    = ' . $export($db_ssl_mode) . ';
$db_ssl_ca      = ' . $export($db_ssl_ca) . ';
$db_password    = $db_pass;

// Database Radius
$radius_host        = ' . $export($db_host) . ';
$radius_port        = ' . $export($db_port) . ';
$radius_user        = ' . $export($db_user) . ';
$radius_pass        = ' . $export($db_pass) . ';
$radius_name        = ' . $export($db_name) . ';
$radius_charset     = ' . $export($db_charset) . ';
$radius_ssl_mode    = ' . $export($db_ssl_mode) . ';
$radius_ssl_ca      = ' . $export($db_ssl_ca) . ';
$radius_password    = $radius_pass;

if($_app_stage!="Live"){
    error_reporting(E_ERROR);
    ini_set("display_errors", 1);
    ini_set("display_startup_errors", 1);
}else{
    error_reporting(E_ERROR);
    ini_set("display_errors", 0);
    ini_set("display_startup_errors", 0);
}';
    } else {
        $input = '<?php
$forwardedProto = "";
if (!empty($_SERVER["HTTP_X_FORWARDED_PROTO"])) {
    $forwardedProto = trim(explode(",", $_SERVER["HTTP_X_FORWARDED_PROTO"])[0]);
} elseif (!empty($_SERVER["HTTP_FORWARDED"]) && preg_match("/proto=([^;]+)/i", $_SERVER["HTTP_FORWARDED"], $matches)) {
    $forwardedProto = trim($matches[1], "\" ");
}
$isHttps = ((!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") || (!empty($_SERVER["REQUEST_SCHEME"]) && $_SERVER["REQUEST_SCHEME"] === "https") || (!empty($_SERVER["SERVER_PORT"]) && (string) $_SERVER["SERVER_PORT"] === "443") || (!empty($_SERVER["HTTP_X_FORWARDED_SSL"]) && $_SERVER["HTTP_X_FORWARDED_SSL"] === "on") || (!empty($_SERVER["HTTP_X_FORWARDED_PORT"]) && (string) $_SERVER["HTTP_X_FORWARDED_PORT"] === "443") || strtolower($forwardedProto) === "https");
$protocol = $isHttps ? "https://" : "http://";
$host = !empty($_SERVER["HTTP_X_FORWARDED_HOST"]) ? trim(explode(",", $_SERVER["HTTP_X_FORWARDED_HOST"])[0]) : $_SERVER["HTTP_HOST"];
$baseDir = rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/\\\\");
define("APP_URL", $protocol . $host . $baseDir);

// Live, Dev, Demo
$_app_stage = "Live";

// Database PHPNuxBill
$db_host        = ' . $export($db_host) . ';
$db_port        = ' . $export($db_port) . ';
$db_user        = ' . $export($db_user) . ';
$db_pass        = ' . $export($db_pass) . ';
$db_name        = ' . $export($db_name) . ';
$db_charset     = ' . $export($db_charset) . ';
$db_ssl_mode    = ' . $export($db_ssl_mode) . ';
$db_ssl_ca      = ' . $export($db_ssl_ca) . ';
$db_password    = $db_pass;

if($_app_stage!="Live"){
    error_reporting(E_ERROR);
    ini_set("display_errors", 1);
    ini_set("display_startup_errors", 1);
}else{
    error_reporting(E_ERROR);
    ini_set("display_errors", 0);
    ini_set("display_startup_errors", 0);
}';
    }
    $wConfig = "../config.php";
    $fh = fopen($wConfig, 'w') or die("Can't create config file, your server does not support 'fopen' function,
	please create a file named - config.php with following contents- <br/>$input");
    fwrite($fh, $input);
    fclose($fh);
    $sql = file_get_contents('phpnuxbill.sql');
    $qr = $dbh->exec($sql);
    if (isset($_POST['radius']) && $_POST['radius'] == 'yes') {
        $sql = file_get_contents('radius.sql');
        $qrs = $dbh->exec($sql);
    }
} else {
    header("location: step3.php?_error=1");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>PHPNuxBill Installer</title>
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!--[if lt IE 9]>
    <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->

    <link type='text/css' href='css/style.css' rel='stylesheet' />
    <link type='text/css' href="css/bootstrap.min.css" rel="stylesheet">
</head>

<body style='background-color: #FBFBFB;'>
    <div id='main-container'>
        <img src="img/logo.png" class="img-responsive" alt="Logo" />
        <hr>

        <div class="span12">
            <h4> PHPNuxBill Installer </h4>
            <?php
            if ($cn == '1') {
            ?>
                <p><strong>Config File Created and Database Imported.</strong><br></p>
                <form action="step5.php" method="post">
                    <fieldset>
                        <legend>Click Continue</legend>
                        <button type='submit' class='btn btn-primary'>Continue</button>
                    </fieldset>
                </form>
            <?php
            } elseif ($cn == '2') {
            ?>
                <p> MySQL Connection was successfull. An error occured while adding data on MySQL. Unsuccessfull
                    Installation. Please refer manual installation in the website github.com/ibnux/phpnuxbill/wiki or Contact Telegram @ibnux  for
                    helping on installation</p>
            <?php
            } else {
            ?>
                <p> MySQL Connection Failed.</p>
            <?php
            }
            ?>
        </div>
    </div>

    <div class="footer">Copyright &copy; 2021 PHPNuxBill. All Rights Reserved<br /><br /></div>
</body>

</html>