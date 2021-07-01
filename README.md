# php-shard-upload
前端Javascript+Html5+后端PHP分块上传文件，PHP分块上传大文件
该项目可以正常运行，入口为index.html，需要正确配置fileDir的读写权限
### 安装
composer require lys/php-shard-upload
### 注意
该包必须通过composer2+ 安装 
您可以使用composer self-update --2迁移到它。如果遇到问题，您可以随时使用 返回composer self-update --1

### 环境
必须配置上传允许数据流大于2M  在php.ini里面或者nginx里面配置

1.实现断点续传，已上传过的块，前端直接过滤掉，无需继续传到后端，加速上传效率，减少带宽

2.实现快速上传，即之前上传过，该文件已经存在的，很快就能上传成功，其原理就是文件md5+文件sha1的判断

### 示例 （具体请查看tests目录）
##### 1. 创建一个 html5 页面
```html
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <title>HTML5大文件分片上传示例</title>
    <script src="https://apps.bdimg.com/libs/jquery/2.1.1/jquery.min.js"></script>
    <script src="./js/spark-md5.js"></script>
    <script src="./js/sha1.js"></script>
    <script src="./js/common.js"></script>
    <script>
        /**
         * 上传文件类
         * @constructor
         */
        function fileUploadClass() {
            this.shardSize = 2 * 1024 * 1024;  //分块大小size
            this.name = '';  //文件名称
            this.size = 0;  //总大小
            this.md5Hash = '';  //文件md5Hash
            this.sha1Hash = '';  //文件sha1Hash
            this.file = null;  //文件
            this.shardCount = 0;  //总片数
            this.succeed = 0;  //上传个数
            this.uploadSuccess = false,
                this.shardList = []; //已上传分块列表
            this.uploadIndex = 0;
        }

        fileUploadClass.prototype = {
            constructor: fileUploadClass,
            shardInit: function (file) {
                this.file = file;   //文件名
                this.name = file.name;   //文件名
                this.size = file.size;       //总大小
                this.shardCount = Math.ceil(this.size / this.shardSize);  //总片数
            },
            uploadTo: function (file, md5Hash, sha1Hash) {
                this.upload(file, md5Hash, sha1Hash, 10);
            },
            upload: function (file, md5Hash, sha1Hash, batchUploadCount) {
                var bitchCountRecord = batchUploadCount;
                var shardSize = this.shardSize;   //以2MB为一个分片
                var name = this.name;   //文件名
                var size = this.size;       //总大小
                var shardCount = this.shardCount;  //总片数
                for (var i = this.uploadIndex; i < this.uploadIndex + batchUploadCount; ++i) {
                    if (i >= this.shardCount) {
                        console.log('遍历完全部的块了');
                        return;
                    }
                    if (this.uploadSuccess) {
                        console.log('上传成功');
                        return;
                    }
                    if (this.shardList.length > 0 && this.shardList.indexOf(i + 1) > 0) {
                        this.succeed++;
                        $("#output").text(this.succeed + " / " + shardCount);
                        continue;
                    }
                    //计算每一片的起始与结束位置
                    var start = i * shardSize,
                        end = Math.min(size, start + shardSize);
                    //构造一个表单，FormData是HTML5新增的
                    var form = new FormData();
                    form.append("data", file.slice(start, end));  //slice方法用于切出文件的一部分
                    form.append("name", name);
                    form.append("total", shardCount);  //总片数
                    form.append("md5Hash", md5Hash);  //md5Hash
                    form.append("sha1Hash", sha1Hash);  //sha1Hash
                    form.append('size', this.size);
                    form.append('shardSize', this.shardSize);
                    form.append("index", i + 1);        //当前是第几片
                    var fileUploadObj = this;
                    $.ajax({
                        url: "./upload.php",
                        type: "POST",
                        data: form,
                        async: true,        //异步
                        processData: false,  //很重要，告诉jquery不要对form进行处理
                        contentType: false,  //很重要，指定为false才能形成正确的Content-Type
                        success: function (data) {
                            batchUploadCount--;
                            if (parseInt(data.status) === 0) {
                                console.log('该分片上传失败' + (i + 1));
                                return;
                            }
                            fileUploadObj.succeed++;
                            $("#output").text(fileUploadObj.succeed + " / " + shardCount);
                            if (batchUploadCount <= 0) {
                                fileUploadObj.uploadIndex += bitchCountRecord;
                                fileUploadObj.upload(file, md5Hash, sha1Hash, bitchCountRecord);
                            }
                        },
                        error: function (data) {
                            console.log(data);
                            console.log('该分片上传失败' + (i + 1));
                            batchUploadCount--;
                            if (batchUploadCount <= 0) {
                                fileUploadObj.uploadIndex += bitchCountRecord;
                                fileUploadObj.upload(file, md5Hash, sha1Hash, bitchCountRecord);
                            }
                        }
                    });
                }
            },
            monitor: function (callback) {
                var form = new FormData();
                form.append('md5Hash', this.md5Hash);
                form.append('sha1Hash', this.sha1Hash);
                form.append('total', this.shardCount);
                form.append('size', this.size);
                form.append('shardSize', this.shardSize);
                var fileUploadObj = this;
                $.ajax({
                    url: "./fileStatus.php",
                    type: "POST",
                    data: form,
                    async: true,        //异步
                    processData: false,  //很重要，告诉jquery不要对form进行处理
                    contentType: false,  //很重要，指定为false才能形成正确的Content-Type
                    success: function (data) {
                        console.log(data);
                        fileUploadObj.shardList = data.data.list;
                        if (parseInt(data.status) === 1) {  //上传成功
                            fileUploadObj.uploadSuccess = true;
                            downUrl = './fileDown.php?md5Hash=' + fileUploadObj.md5Hash + '&sha1Hash=' + fileUploadObj.sha1Hash + '&name=' + encodeURIComponent(fileUploadObj.name);
                            $("#output").html(fileUploadObj.shardCount + " / " + fileUploadObj.shardCount + '（上传成功）<a href="' + downUrl + '" target="_blank">下载</a>');
                            console.log('上传成功monitor');
                            $("#upload").removeAttr("disabled");
                            return;
                        }
                        if (callback !== undefined) {
                            callback();
                        }
                        window.setTimeout(function () {
                            fileUploadObj.monitor();
                        }, 1000);

                    },
                    error: function (data) {
                        console.log(data);
                        window.setTimeout(function () {
                            fileUploadObj.monitor();
                        }, 1000);
                    }
                });
            }
        };
        var page = {
            init: function () {
                $("#upload").click($.proxy(this.upload, this));
            },
            upload: function () {
                $("#upload").attr("disabled", "disabled");
                var fileUploadObj = new fileUploadClass();
                var file = $("#file")[0].files[0]; //文件对象
                fileUploadObj.file = file;
                fileUploadObj.shardInit(file);
                $("#output").text('文件识别中...');
                md5File(file, function (md5Hash) {
                    fileUploadObj.md5Hash = md5Hash;
                    sha1File(file, function (sha1Hash) {
                        fileUploadObj.sha1Hash = sha1Hash;
                        fileUploadObj.monitor(function () {
                            fileUploadObj.uploadTo(file, md5Hash, sha1Hash);
                        });
                    });
                });
            }
        };
        $(function () {
            page.init();
        });
    </script>
</head>
<body>
<input type="file" id="file"/>

<button id="upload">上传</button>

<span id="output" style="font-size:12px">等待</span>

</body>

</html>
```

##### 2.创建一个 upload.php 文件
```php
<?php
$file_load_path = '../../../autoload.php';
if (file_exists($file_load_path)) {
    include $file_load_path;
} else {
    include '../vendor/autoload.php';
}
use PhpShardUpload\ShardUpload;
$file = $_FILES['data'];
$index = $_POST['index'];
$total = $_POST['total'];
$shardSize = $_POST['shardSize'];  //分块大小
$size = $_POST['size'];  //总大小
$md5Hash = $_POST['md5Hash'];
$sha1Hash = $_POST['sha1Hash'];
$fileBaseDir = './fileDir/';
$shard = new ShardUpload($file, $index, $total, $shardSize, $size, $md5Hash, $sha1Hash, $fileBaseDir);
$response = $shard->upload();
header('Content-Type:application/json;charset=utf-8');
echo json_encode($response,JSON_UNESCAPED_UNICODE);

```
##### 3.创建一个 fileStatus.php 文件
```php
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

```

