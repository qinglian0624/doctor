<?php

namespace app\admin\model;

use think\Model;


class Courseware extends Model
{

    

    

    // 表名
    protected $name = 'courseware';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    







    public function user()
    {
        return $this->belongsTo('User', 'uid', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function category()
    {
        return $this->belongsTo('Category', 'edu_type', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function category1()
    {
        return $this->belongsTo('Category', 'edu_dire', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    public function category2()
    {
        return $this->belongsTo('Category', 'edu_area', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function project()
    {
        return $this->belongsTo('Project', 'project_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
