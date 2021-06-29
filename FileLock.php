<?php


/**
 * 文件锁类
 * Class FileLock
 */
class FileLock
{
    private $fp;
    private $filePath;
    private $is_log = true;  //是否日志记录

    /**
     * 文件路径
     * FileLock constructor.
     * @param $lockFilePath
     */
    public function __construct($lockFilePath)
    {
        if (!file_exists($lockFilePath)) {
            file_put_contents($lockFilePath, date('Y-m-d H:i:s'));  //创建文件
        }
        $this->filePath = $lockFilePath;
    }

    /**
     * 锁定
     * @return boolean
     * @throws Exception
     */
    public function lock()
    {
        if (empty($this->filePath)) {
            throw new \Exception('文件路径不能为空');
        }
        $this->is_log && file_put_contents('./log_join.log', 'jinlai' . ' ' . time() . PHP_EOL, FILE_APPEND);
        $this->fp = fopen($this->filePath, 'r+');
        if (empty($this->fp)) {
            throw new \Exception('文件锁开启失败');
        }
        if (!flock($this->fp, LOCK_EX | LOCK_NB)) {
            $this->is_log && file_put_contents('./log.log', date('Y-m-d H:i:s') . PHP_EOL, FILE_APPEND);
            return false;  //已被锁定
        }
        return true;  //锁定成功
    }

    /**
     * 释放锁
     */
    public function unLock()
    {
        if (!empty($this->fp)) {
            @flock($this->fp, LOCK_UN);
            @fclose($this->fp);
        }
        $this->fp = null;
    }

    public function __destruct()
    {
        $this->unLock();
    }
}