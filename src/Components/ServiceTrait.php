<?php

namespace PhpShardUpload\Components;
trait ServiceTrait
{
    protected static function response($status, $msg = '', $data = [])
    {
        return [
            'status' => $status,
            'msg' => $msg,
            'data' => $data
        ];
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