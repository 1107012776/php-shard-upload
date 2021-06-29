<?php
$file_load_path = '../../../autoload.php';
if (file_exists($file_load_path)) {
    include $file_load_path;
} else {
    include '../vendor/autoload.php';
}

$avatar = $_FILES['data'];
$index = $_POST['index'];
$total = $_POST['total'];
$shardSize = $_POST['shardSize'];  //分块大小
$size = $_POST['size'];  //总大小
$md5Hash = $_POST['md5Hash'];
$sha1Hash = $_POST['sha1Hash'];
$fileBaseDir = './fileDir/';
