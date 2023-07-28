<?php

namespace app\admin\model;

use think\Model;


class Files extends Model
{

    

    

    // 表名
    protected $name = 'files';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    







    public function courseware()
    {
        return $this->belongsTo('Courseware', 'couse_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
