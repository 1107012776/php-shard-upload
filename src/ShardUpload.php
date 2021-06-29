<?php

namespace PhpShardUpload;

use PhpShardUpload\Components\ServiceTrait;

class ShardUpload
{
    use ServiceTrait;
    protected $file;  //上传的分块文件对象 $file = $_FILES['data'];
    protected $index;  //上传的分块索引
    protected $total; //上传的分块总数量
    protected $size; //要上传的文件总大小
    protected $shardSize;  //上传的分块文件大小
    protected $md5Hash; //要上传的文件的md5Hash
    protected $sha1Hash;  //要上传的文件的sha1Hash
    protected $ext; //要上传的文件的后缀
    protected $fileBaseDir;  //基础文件存储根目录
    protected $childFileDir; //上传分块保存目录

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


}