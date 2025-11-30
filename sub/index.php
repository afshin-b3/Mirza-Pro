<?php
ini_set('error_log', 'error_log');

require_once '../config.php';
require_once '../Marzban.php';
require_once '../function.php';
require_once '../panels.php';
$ManagePanel = new ManagePanel();
$url = $_SERVER['REQUEST_URI'];
$path = parse_url($url, PHP_URL_PATH);
$link = trim(str_replace('/sub/', '', $path), '/');
header('Content-Type: text/plain; charset=utf-8');
$invoiceId = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
$tokenParam = isset($_GET['token']) ? $_GET['token'] : null;
$expiresAt = isset($_GET['exp']) ? $_GET['exp'] : null;
try {
    if (!isset($invoiceId) || $invoiceId === '' || !isset($tokenParam) || !isset($expiresAt)) {
        echo "Error!";
        return;
    }

    $nameloc = select("invoice", "*", "id_invoice", $invoiceId, "select");
    if (!$nameloc) {
        echo "Error!";
        return;
    }

    if (!validateSubToken($invoiceId, $nameloc['id_user'], $tokenParam, $expiresAt)) {
        echo "Error!";
        return;
    }

    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    $config = "";
    foreach ($DataUserOut['links'] as $Links) {
        $config .= $Links . "\r\r";
    }
    echo $config;
} catch (Exception $e) {
    echo "Error!";
}
