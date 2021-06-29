<?php

namespace PhpShardUpload;

use PhpShardUpload\Components\ServiceTrait;

class ShardUpload
{
    use ServiceTrait;
    protected $file;
    protected $index;
    protected $total;
    protected $size;
    protected $shardSize;
    protected $md5Hash;
    protected $sha1Hash;
    protected $ext;
    protected $fileBaseDir;
    protected $childFileDir;

    public function __construct($file, $index, $total, $shardSize, $size, $md5Hash, $sha1Hash, $fileBaseDir)
    {
        $this->file = $file;
        $this->ext = pathinfo($file['name'], PATHINFO_EXTENSION);  //文件后缀
        $this->index = $index;
        $this->total = $total;
        $this->shardSize = $shardSize;
        $this->size = $size;
        $this->md5Hash = $md5Hash;
        $this->sha1Hash = $sha1Hash;
        $this->fileBaseDir = $fileBaseDir;
    }

    /**
     * 文件上传
     * @return array
     */
    public function upload()
    {
        $this->createFileBaseDir();
        $this->createChildFileDir();
        $target = $this->childFileDir . '/' . $this->index . '.' . 'part';
        header('Content-Type:application/json;charset=utf-8');
        $upSize = filesize($this->file['tmp_name']);
        if ($this->index != $this->total && $upSize != $this->shardSize) {
            return self::response(0, '上传文件存在问题', ['index' => $this->index]);
        }
        if ($this->index == $this->total && $upSize != ($this->size - $this->shardSize * ($this->index - 1))) {  //最后一个分块文件也要判断下的
            return self::response(0, '上传文件存在问题', ['index' => $this->index]);
        }
        // 移动的目标路径中文件夹一定是一个已经存在的目录
        if (!move_uploaded_file($this->file['tmp_name'], $target)) {
            return self::response(0, '上传失败', ['index' => $this->index]);
        }
        return self::response(1, '上传成功', ['index' => $this->index]);
    }

    /**
     * 创建基本文件存放目录
     */
    public function createFileBaseDir()
    {
        if (!file_exists($this->fileBaseDir)) {
            mkdir($this->fileBaseDir, 0777, true);
        }
    }


    /**
     * 创建子文件存放目录（即上传的文件）
     */
    public function createChildFileDir()
    {
        $this->childFileDir = $this->fileBaseDir . $this->md5Hash . '_' . $this->sha1Hash;
        if (!file_exists($this->childFileDir)) {
            mkdir($this->childFileDir, 0777, true);
        }
    }
}