<?php

namespace app\admin\model\unishop;

use think\Model;

/**
 * 商品销量view
 */
class Goods extends Model
{

    // 表名
    protected $name = 'unishop_goods';

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

}
