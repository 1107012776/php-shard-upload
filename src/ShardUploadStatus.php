<?php

namespace PhpShardUpload;

use PhpShardUpload\Components\FileDirUtil;
use PhpShardUpload\Components\FileLock;
use PhpShardUpload\Components\ServiceTrait;

class ShardUploadStatus
{
    use ServiceTrait;
    protected $total; //上传的分块总数量
    protected $size; //要上传的文件总大小
    protected $shardSize;  //上传的分块文件大小
    protected $md5Hash; //要上传的文件的md5Hash
    protected $sha1Hash;  //要上传的文件的sha1Hash
    protected $fileBaseDir;  //基础文件存储根目录
    protected $childFileDir; //上传分块保存目录

    public function __construct($total, $shardSize, $size, $md5Hash, $sha1Hash, $fileBaseDir)
    {
        $this->total = $total;
        $this->shardSize = $shardSize;
        $this->size = $size;
        $this->md5Hash = $md5Hash;
        $this->sha1Hash = $sha1Hash;
        $this->fileBaseDir = $fileBaseDir;
    }

    /**
     * 获取上传的状态
     */
    public function getUploadStatus()
    {
        $this->createFileBaseDir();
        $this->createChildFileDir();
        $filePathDir = $this->childFileDir;
        $total = $this->total;
        $size = $this->size;
        $shardSize = $this->shardSize;
        $util = new FileDirUtil();
        $list = $util->dirList($filePathDir);
        $lock = new FileLock($filePathDir . '.lock');
        $filename = $filePathDir . '.data';
        foreach ($list as &$val) {
            $val = str_replace([$filePathDir, '.part', '/'], '', $val);
        }
        if (file_exists($filename)) {
            return self::response(1, '上传成功', ['list' => $list]);
        }
        if (count($list) != $total || !$lock->lock()) {
            return self::response(0, '获取状态成功', ['list' => $list]);
        }
        if (file_exists($filename)) {
            return self::response(1, '上传成功', ['list' => $list]);
        }
        $temp = $filename . '.temp';
        $fileIndex = $filename . '.index';
        if (file_exists($fileIndex)) {
            $index = file_get_contents($fileIndex);
        }else{
            $index = 0;
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
                    return self::response(0, '合并出现异常', ['list' => $list]);
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
        return self::response(0, '获取状态成功', ['list' => $list]);
    }
}