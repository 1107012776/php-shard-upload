# php-shard-upload
前端Javascript+Html5+后端PHP分块上传文件，PHP分块上传大文件
该项目可以正常运行，入口为index.html，需要正确配置fileDir的读写权限
### 环境
必须配置上传允许数据流大于2M  在php.ini里面或者nginx里面配置

1.实现断点续传，已上传过的块，前端直接过滤掉，无需继续传到后端，加速上传效率，减少带宽

2.实现快速上传，即之前上传过，该文件已经存在的，很快就能上传成功，其原理就是文件md5+文件sha1的判断
