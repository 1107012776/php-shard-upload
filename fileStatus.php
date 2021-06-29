<?php
/**
 * @author lys
 */
include_once 'FileDirUtil.php';
include_once 'FileLock.php';
$size = $_POST['size'];   //文件总大小
$shardSize = $_POST['shardSize'];  //文件分块大小
$total = $_POST['total'];
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
$util = new FileDirUtil();
$list = $util->dirList($filePathDir);
$lock = new FileLock($filePathDir . '.lock');
$filename = $filePathDir . '.data';
foreach ($list as &$val) {
    $val = str_replace([$filePathDir, '.part', '/'], '', $val);
}
if (file_exists($filename)) {
    header('Content-Type:application/json;charset=utf-8');
    echo json_encode(['msg' => '上传成功', 'status' => 1, 'list' => $list], JSON_UNESCAPED_UNICODE);
    exit();
}
if (count($list) == $total && $lock->lock()) {
    if (file_exists($filename)) {
        header('Content-Type:application/json;charset=utf-8');
        echo json_encode(['msg' => '上传成功', 'status' => 1, 'list' => $list], JSON_UNESCAPED_UNICODE);
        $lock->unLock();
        exit();
    }
    $temp = $filename . '.temp';
    $fileIndex = $filename . '.index';
    if (file_exists($fileIndex)) {
        $index = file_get_contents($fileIndex);
    }
    $util->createFile($temp);
    $fp = fopen($temp, "w+");
    for ($i = 1; $i <= $total; $i++) {
        if ($i < $index) {   //之前合并一半被中断，绕过已合并部分
            continue;
        }
        if ($i == $index) {  //之前合并一半被中断，这边继续合并
            $mergeSize = filesize($temp);
            if ($shardSize * $i == $mergeSize) {
                continue;
            }
            if ($shardSize * $i < $mergeSize
                && $shardSize * ($i + 1) != $mergeSize
                && $mergeSize != $size  //已合并文件大小不等于总文件大小
            ) {  //满足该条件说明合并出现了异常
                fclose($fp);
                $util->unlinkFile($temp);  //合并的文件异常，需要删除重置
                $util->unlinkFile($fileIndex);  //合并的文件异常，需要删除重置
                $lock->unLock();  //解锁
                header('Content-Type:application/json;charset=utf-8');
                echo json_encode(['msg' => '合并出现异常', 'status' => 0, 'list' => $list], JSON_UNESCAPED_UNICODE);
                exit();
            }
        }
        $isWriteRes = file_put_contents($fileIndex, $i);  //游标写入
        if (!empty($isWriteRes)) {
            fwrite($fp, file_get_contents($filePathDir . '/' . $i . '.part'));
        }
    }
    fclose($fp);
    if (filesize($temp) == $size) {  //对比最后总大小
        $util->moveFile($temp, $filename);
    }
    $lock->unLock();
    if (file_exists($filename)) {
        $util->unlinkDir($filePathDir);
        $util->unlinkFile($filePathDir);
    }
}
header('Content-Type:application/json;charset=utf-8');
echo json_encode(['msg' => '获取状态成功', 'status' => 0, 'list' => $list], JSON_UNESCAPED_UNICODE);