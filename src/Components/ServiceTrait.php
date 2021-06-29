<?php
namespace PhpShardUpload\Components;
trait ServiceTrait{
    protected static function response($status, $msg = '',$data = []){
        return [
            'status' => $status,
            'msg' => $msg,
            'data' => $data
        ];
    }
}