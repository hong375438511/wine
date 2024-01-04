<?php

namespace app\admin\model\unishop;

use think\Model;

/**
 * 商品销量view
 */
class Income extends Model
{

    // 表名
    protected $name = 'unishop_income';

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

}