<?php

namespace addons\unishop\controller;

use app\common\model\ScoreLog as ScoreLogMD;

/**
 * 积分
 */
class Score extends Base{


    /**
     * @ApiTitle    (积分列表)
     * @ApiSummary  (积分列表)
     * @ApiMethod   (GET)
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiParams   (name="page", type="integer", required=true, description="页面")
     * @ApiParams   (name="pagesize", type="integer", required=true, description="每页数量")
     * @ApiParams   (name="by", type="string", required=true, description="排序字段")
     * @ApiParams   (name="desc", type="string", required=true, description="排序desc,asc")
     * @ApiReturn   ({"code":1,"msg":"","data":[]})
     * @ApiReturnParams  (name="score", type="string", description="变动积分")
     * @ApiReturnParams  (name="before", type="integer", description="变动前")
     * @ApiReturnParams  (name="after", type="integer", description="变动后")
     * @ApiReturnParams  (name="memo", type="string", description="备注")
     * @ApiReturnParams  (name="createtime", type="string", description="时间")
     */
    public function lists()
    {
        $page = $this->request->post('page', 1);
        $pagesize = $this->request->post('pagesize', 20);
        $by = $this->request->post('by', 'createtime');
        $desc = $this->request->post('desc', 'desc');

        $productModel = new ScoreLogMD();

        echo $this->auth->id;

        $result = $productModel
            ->where(['user_id' => $this->auth->id])
            ->page($page, $pagesize)
            ->order($by, $desc)
            ->field('*')
            ->select();

        if ($result) {
            $result = collection($result)->toArray();
            foreach ($result as &$val){
                $val['createtime'] = $val['createtime'] ? date('Y-m-d H:i:s',$val['createtime']) : '';
            }
        } else {
            $this->success('没有更多数据', []);
        }
        $this->success('', $result);
    }
}
