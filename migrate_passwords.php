<?php
/**
 * Password Migration Script
 *
 * The old system stored passwords using MySQL's ENCODE() function (removed in MySQL 8.0).
 * This script resets all user passwords to a temporary password and hashes them with
 * PHP's password_hash() (bcrypt).
 *
 * Run once from the command line:
 *   php migrate_passwords.php
 *
 * After running, all users will have their password set to the TEMP_PASSWORD below.
 * Have each user log in and change their password immediately.
 *
 * DELETE THIS FILE after running.
 */

define('TEMP_PASSWORD', 'ChangeMe123!');

// Parse settings.php manually
$settings = array();
$settingsfile = fopen(__DIR__.'/settings.php', 'r');
if(!$settingsfile) die("ERROR: Cannot open settings.php\n");

while(!feof($settingsfile)){
    $line = fscanf($settingsfile, "%[^=]=%[^[]]", $key, $value);
    if($line){
        $key   = trim($key);
        $value = trim($value);
        if($key != "" && strpos($key, "]") === false && strpos($key, "mysql_") === 0){
            $startpos = strpos($value, '"');
            $endpos   = strrpos($value, '"');
            if($endpos !== false)
                $value = substr($value, $startpos + 1, $endpos - $startpos - 1);
            $settings[$key] = $value;
        }
    }
}
fclose($settingsfile);

// Connect
$host = (isset($settings['mysql_pconnect']) && $settings['mysql_pconnect'] === 'true')
    ? 'p:'.$settings['mysql_server']
    : $settings['mysql_server'];

$link = mysqli_connect($host, $settings['mysql_user'], $settings['mysql_userpass'], $settings['mysql_database']);
if(!$link) die("ERROR: Could not connect to database: ".mysqli_connect_error()."\n");

// Hash the temp password
$hash     = password_hash(TEMP_PASSWORD, PASSWORD_DEFAULT);
$safeHash = mysqli_real_escape_string($link, $hash);

// Update all users
$result = mysqli_query($link, "UPDATE users SET password='".$safeHash."'");

if($result){
    $count = mysqli_affected_rows($link);
    echo "SUCCESS: Reset passwords for $count user(s).\n";
    echo "Temporary password: ".TEMP_PASSWORD."\n";
    echo "All users must log in with this password and change it immediately.\n";
    echo "\nIMPORTANT: DELETE THIS FILE NOW: migrate_passwords.php\n";
} else {
    echo "ERROR: ".mysqli_error($link)."\n";
}

mysqli_close($link);
