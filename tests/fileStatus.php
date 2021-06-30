<?php

/**
 * @author lys
 */
$file_load_path = '../../../autoload.php';
if (file_exists($file_load_path)) {
    include $file_load_path;
} else {
    include '../vendor/autoload.php';
}


use PhpShardUpload\ShardUploadStatus;
$size = $_POST['size'];   //文件总大小
$shardSize = $_POST['shardSize'];  //文件分块大小
$total = $_POST['total'];
$md5Hash = $_POST['md5Hash'];
$sha1Hash = $_POST['sha1Hash'];
$fileBaseDir = './fileDir/';
$shard = new ShardUploadStatus($total, $shardSize, $size, $md5Hash, $sha1Hash, $fileBaseDir);
$response = $shard->getUploadStatus();
if($response['status'] == 1){
   $manage = new \PhpShardUpload\FileManage($md5Hash, $sha1Hash, $fileBaseDir);
//   var_dump($manage->getUploadSuccessFilePath()); //已成功上传的文件路径
}
header('Content-Type:application/json;charset=utf-8');
echo json_encode($response,JSON_UNESCAPED_UNICODE);
