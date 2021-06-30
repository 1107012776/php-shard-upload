<?php
/**
 * PhpShardUpload  file.
 * @author linyushan  <1107012776@qq.com>
 * @link https://www.developzhe.com/
 * @package https://github.com/1107012776/php-shard-upload
 * @copyright Copyright &copy; 2019-2021
 * @license https://github.com/1107012776/php-shard-upload/blob/master/LICENSE
 */

namespace PhpShardUpload;

use PhpShardUpload\Components\FileDownload;
use PhpShardUpload\Components\ServiceTrait;

class FileManage
{
    use ServiceTrait;
    protected $md5Hash; //要上传的文件的md5Hash
    protected $sha1Hash;  //要上传的文件的sha1Hash
    protected $fileBaseDir;  //基础文件存储根目录

    public function __construct($md5Hash, $sha1Hash, $fileBaseDir)
    {
        $this->md5Hash = $md5Hash;
        $this->sha1Hash = $sha1Hash;
        $this->fileBaseDir = $fileBaseDir;
    }

    /**
     * 文件路径
     * @return string
     */
    public function getUploadSuccessFilePath()
    {
        return $this->fileBaseDir . $this->md5Hash . '_' . $this->sha1Hash . '.data';
    }


    /**
     * 文件下载
     */
    public function download($filename)
    {
        $obj = new FileDownload();
        $obj->download($this->getUploadSuccessFilePath(), $filename, true);
    }
}