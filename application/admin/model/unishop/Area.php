<?php
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2019-07-14
 * Time: 22:39
 */

namespace app\admin\model\unishop;

use think\Cache;
use think\Model;

/**
 * 地区模型
 * Class Region
 * @package app\common\model
 */
class Area extends Model
{
    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'unishop_area';

    /**
     * 根据id获取地区名称
     * @param $id
     * @return string
     */
    public static function getNameById($id)
    {
        $region = self::getCacheAll();
        return $region[$id]['name'];
    }

    /**
     * 根据名称获取地区id
     * @param $name
     * @param int $level
     * @param int $pid
     * @return unied
     */
    public static function getIdByName($name, $level = 0, $pid = 0)
    {
        return static::useGlobalScope(false)->where(compact('name', 'level', 'pid'))
            ->value('id') ?: static::add($name, $level, $pid);
    }

    /**
     * @param $name
     * @param int $level
     * @param int $pid
     * @return unied
     */
    private static function add($name, $level = 0, $pid = 0)
    {
        $model = new static;
        $model->save(compact('name', 'level', 'pid'));
        Cache::rm('area');
        return $model->getLastInsID();
    }

    /**
     * 获取所有地区(树状结构)
     * @return unied
     */
    public static function getCacheTree()
    {
        return self::regionCache()['tree'];
    }

    /**
     * 获取所有地区
     * @return unied
     */
    public static function getCacheAll()
    {
        return self::regionCache()['all'];
    }

    /**
     * 获取地区缓存
     * @return unied
     */
    private static function regionCache()
    {
        if (!Cache::get('area')) {
            // 所有地区
            $all = $allData = self::useGlobalScope(false)->column('id, pid, name, level', 'id');
            // 格式化
            $tree = [];
            foreach ($allData as $pKey => $province) {
                if ($province['level'] === 1) {    // 省份
                    $tree[$province['id']] = $province;
                    unset($allData[$pKey]);
                    foreach ($allData as $cKey => $city) {
                        if ($city['level'] === 2 && $city['pid'] === $province['id']) {    // 城市
                            $tree[$province['id']]['city'][$city['id']] = $city;
                            unset($allData[$cKey]);
                            foreach ($allData as $rKey => $region) {
                                if ($region['level'] === 3 && $region['pid'] === $city['id']) {    // 地区
                                    $tree[$province['id']]['city'][$city['id']]['region'][$region['id']] = $region;
                                    unset($allData[$rKey]);
                                }
                            }
                        }
                    }
                }
            }
            Cache::set('area', compact('all', 'tree'));
        }
        return Cache::get('area');
    }

    public function getSelect($pid = 0){
        $this->where('pid', $pid);
        $rows = $this->order(['id' => 'asc'])->column('id,name');
        return $rows;
    }

}
