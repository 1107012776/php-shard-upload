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

$md5Hash = $_GET['md5Hash'];
$sha1Hash = $_GET['sha1Hash'];
$name= isset($_GET['name']) ? $_GET['name']:''; //下载文件名称
$fileBaseDir = './fileDir/';
$manage = new \PhpShardUpload\FileManage($md5Hash, $sha1Hash, $fileBaseDir);
$manage->download($name);