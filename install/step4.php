<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

$appurl  = $_POST['appurl'];
$db_host = $_POST['dbhost'];
$db_port = trim($_POST['dbport'] ?? '');
$db_user = $_POST['dbuser'];
$db_pass = $_POST['dbpass'];
$db_name = $_POST['dbname'];
$db_ssl  = isset($_POST['dbssl']) && $_POST['dbssl'] === 'yes';
$db_ssl_ca = trim($_POST['dbsslca'] ?? '');
$cn = '0';

$dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
if ($db_port !== '') {
    $dsn .= ";port={$db_port}";
}

// PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT was moved to Pdo\Mysql in PHP 8.5
$_ssl_verify_attr = class_exists('Pdo\Mysql') ? Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT : PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT;
$pdo_opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
if (!empty($db_ssl_ca)) {
    $pdo_opts[PDO::MYSQL_ATTR_SSL_CA] = $db_ssl_ca;
    $pdo_opts[$_ssl_verify_attr] = true;
} elseif ($db_ssl) {
    $pdo_opts[$_ssl_verify_attr] = false;
}

try {
    $dbh = new PDO($dsn, $db_user, $db_pass, $pdo_opts);
    $cn = '1';
} catch (PDOException $ex) {
    $cn = '0';
}

if ($cn == '1') {
    $ssl_line      = $db_ssl    ? 'true'  : 'false';
    $ssl_ca_line   = addslashes($db_ssl_ca);
    $port_line     = addslashes($db_port);

    $radius_block = '';
    if (isset($_POST['radius']) && $_POST['radius'] == 'yes') {
        $radius_block = '
// Database Radius
$radius_host    = getenv(\'RADIUS_HOST\') ?: \'' . addslashes($db_host) . '\';
$radius_user    = getenv(\'RADIUS_USER\') ?: \'' . addslashes($db_user) . '\';
$radius_pass    = getenv(\'RADIUS_PASS\') ?: \'' . addslashes($db_pass) . '\';
$radius_name    = getenv(\'RADIUS_NAME\') ?: \'' . addslashes($db_name) . '\';
';
    }

    $input = '<?php

$protocol = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off" || (isset($_SERVER["SERVER_PORT"]) && $_SERVER["SERVER_PORT"] == 443)) ? "https://" : "http://";
$host = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : (isset($_SERVER["SERVER_NAME"]) ? $_SERVER["SERVER_NAME"] : "localhost");
$baseDir = rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/\\\\");
define("APP_URL", $protocol . $host . $baseDir);

$_app_stage = "Live";

// Database connection — env vars override installer values (Docker / cloud)
$db_host   = getenv("DB_HOST") ?: "' . addslashes($db_host) . '";
$db_port   = getenv("DB_PORT") ?: "' . $port_line . '";
$db_user   = getenv("DB_USER") ?: "' . addslashes($db_user) . '";
$db_pass   = getenv("DB_PASS") ?: "' . addslashes($db_pass) . '";
$db_name   = getenv("DB_NAME") ?: "' . addslashes($db_name) . '";
$db_ssl    = filter_var(getenv("DB_SSL") ?: ' . $ssl_line . ', FILTER_VALIDATE_BOOLEAN);
$db_ssl_ca = getenv("DB_SSL_CA") ?: "' . $ssl_ca_line . '";
' . $radius_block . '
error_reporting(E_ERROR);
if ($_app_stage !== "Live") {
    ini_set("display_errors", 1);
    ini_set("display_startup_errors", 1);
} else {
    ini_set("display_errors", 0);
    ini_set("display_startup_errors", 0);
}';

    $wConfig = "../config.php";
    $fh = fopen($wConfig, 'w') or die("Can't create config file, your server does not support 'fopen' function,
    please create a file named - config.php with following contents- <br/>" . htmlspecialchars($input));
    fwrite($fh, $input);
    fclose($fh);

    // Cloud DBs (Aiven, RDS) enforce sql_require_primary_key=ON globally.
    // Disable it for this session so the dump (which adds PKs via ALTER TABLE) can import cleanly.
    try { $dbh->exec('SET SESSION sql_require_primary_key=0'); } catch (PDOException $e) { /* no permission — ignore, may still work */ }

    $sql = file_get_contents('phpnuxbill.sql');
    $dbh->exec($sql);
    if (isset($_POST['radius']) && $_POST['radius'] == 'yes') {
        $sql = file_get_contents('radius.sql');
        $dbh->exec($sql);
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