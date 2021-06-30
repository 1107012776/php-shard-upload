<?php

$file_load_path = '../../../autoload.php';
if (file_exists($file_load_path)) {
    include $file_load_path;
} else {
    include '../vendor/autoload.php';
}
use PhpShardUpload\ShardUpload;
$file = $_FILES['data'];
$index = $_POST['index'];
$total = $_POST['total'];
$shardSize = $_POST['shardSize'];  //分块大小
$size = $_POST['size'];  //总大小
$md5Hash = $_POST['md5Hash'];
$sha1Hash = $_POST['sha1Hash'];
$fileBaseDir = './fileDir/';
$shard = new ShardUpload($file, $index, $total, $shardSize, $size, $md5Hash, $sha1Hash, $fileBaseDir);
$response = $shard->upload();
header('Content-Type:application/json;charset=utf-8');
echo json_encode($response,JSON_UNESCAPED_UNICODE);
