<?php
namespace PhpShardUpload\Components;
/** php下载类,支持断点续传
 * download: 下载文件
 * setSpeed: 设置下载速度
 * getRange: 获取header中Range
 * https://www.jb51.net/article/189099.htm
 */
set_time_limit(0);
class FileDownload{

    /** 下载
     * @param String $file 要下载的文件路径
     * @param String $name 文件名称,为空则与下载的文件名称一样
     * @param boolean $reload 是否开启断点续传
     */
    public function download($file, $name='', $reload=false){
        $fp = @fopen($file, 'rb');
        if($fp){
            if($name==''){
                $name = basename($file);
            }
            $header_array = get_headers($file, true);
            //var_dump($header_array);die;
            // 下载本地文件，获取文件大小
            if (!$header_array) {
                $file_size = filesize($file);
            } else {
                $file_size = $header_array['Content-Length'];
            }
            $ranges = $this->getRange($file_size);
            $ua = $_SERVER["HTTP_USER_AGENT"];//判断是什么类型浏览器
            header('cache-control:public');
            header('content-type:application/octet-stream');

            $encoded_filename = urlencode($name);
            $encoded_filename = str_replace("+", "%20", $encoded_filename);

            //解决下载文件名乱码
            if (preg_match("/MSIE/", $ua) || preg_match("/Trident/", $ua) ){
                header('Content-Disposition: attachment; filename="' .$encoded_filename . '"');
            } else if (preg_match("/Firefox/", $ua)) {
                header('Content-Disposition: attachment; filename*="utf8\'\'' . $name . '"');
            }else if (preg_match("/Chrome/", $ua)) {
                header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
            } else {
                header('Content-Disposition: attachment; filename="' . $name . '"');
            }
            //header('Content-Disposition: attachment; filename="' . $name . '"');

            if($reload && $ranges!=null){ // 使用续传
                header('HTTP/1.1 206 Partial Content');
                header('Accept-Ranges:bytes');

                // 剩余长度
                header(sprintf('content-length:%u',$ranges['end']-$ranges['start']));

                // range信息
                header(sprintf('content-range:bytes %s-%s/%s', $ranges['start'], $ranges['end'], $file_size));
                //file_put_contents('test.log',sprintf('content-length:%u',$ranges['end']-$ranges['start']),FILE_APPEND);
                // fp指针跳到断点位置
                fseek($fp, sprintf('%u', $ranges['start']));
            }else{
                file_put_contents('test.log','2222',FILE_APPEND);
                header('HTTP/1.1 200 OK');
                header('content-length:'.$file_size);
            }

            while(!feof($fp)){
                //echo fread($fp, round($this->_speed*1024,0));
                //echo fread($fp, $file_size);
                echo fread($fp, 4096);
                ob_flush();
            }

            ($fp!=null) && fclose($fp);
        }else{
            return '';
        }
    }

    /** 设置下载速度
     * @param int $speed
     */
    public function setSpeed($speed){
        if(is_numeric($speed) && $speed>16 && $speed<4096){
            $this->_speed = $speed;
        }
    }

    /** 获取header range信息
     * @param int $file_size 文件大小
     * @return Array
     */
    private function getRange($file_size){
        //file_put_contents('range.log', json_encode($_SERVER), FILE_APPEND);
        if(isset($_SERVER['HTTP_RANGE']) && !empty($_SERVER['HTTP_RANGE'])){
            $range = $_SERVER['HTTP_RANGE'];
            $range = preg_replace('/[\s|,].*/', '', $range);
            $range = explode('-', substr($range, 6));
            if(count($range)<2){
                $range[1] = $file_size;
            }
            $range = array_combine(array('start','end'), $range);
            if(empty($range['start'])){
                $range['start'] = 0;
            }
            if(empty($range['end'])){
                $range['end'] = $file_size;
            }
            return $range;
        }
        return null;
    }
}

//$obj = new FileDownload();
//$obj->download('https://www.charlesproxy.com/assets/release/4.6.1/charles-proxy-4.6.1-win64.msi?k=993432c69c','', true);
//$obj->download('./IntelliJIDEA_v15.0.2.rar','', true);