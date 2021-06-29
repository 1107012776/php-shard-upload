<?php

$avatar = $_FILES['data'];
// 这个函数得到的是文件的扩展名
$ext = pathinfo($avatar['name'], PATHINFO_EXTENSION);
$index = $_POST['index'];
$total = $_POST['total'];
$shardSize = $_POST['shardSize'];  //分块大小
$size = $_POST['size'];  //总大小
$md5Hash = $_POST['md5Hash'];
$sha1Hash = $_POST['sha1Hash'];
$fileBaseDir = './fileDir/';
if (!file_exists($fileBaseDir)) {
    mkdir($filePathDir, 0777, true);
}
$filePathDir = $fileBaseDir . $md5Hash . '_' . $sha1Hash;
if (!file_exists($filePathDir)) {
    mkdir($filePathDir, 0777, true);
}

$target = $filePathDir . '/' . $index . '.' . 'part';
header('Content-Type:application/json;charset=utf-8');
$upSize = filesize($avatar['tmp_name']);
if ($index != $total && $upSize != $shardSize) {
    echo json_encode(['msg' => '上传文件存在问题', 'status' => 0, 'index' => $index], JSON_UNESCAPED_UNICODE);
    exit();
}
if ($index == $total && $upSize != ($size - $shardSize * ($index - 1))) {  //最后一个分块文件也要判断下的
    echo json_encode(['msg' => '上传文件存在问题', 'status' => 0, 'index' => $index], JSON_UNESCAPED_UNICODE);
    exit();
}
// 移动的目标路径中文件夹一定是一个已经存在的目录
if (!move_uploaded_file($avatar['tmp_name'], $target)) {
    echo json_encode(['msg' => '上传失败', 'status' => 0, 'index' => $index], JSON_UNESCAPED_UNICODE);
    exit();
}
echo json_encode(['msg' => '上传成功', 'status' => 1, 'index' => $index], JSON_UNESCAPED_UNICODE);
