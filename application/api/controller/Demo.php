<?php

namespace app\api\controller;

use app\admin\model\Card;
use app\admin\model\Courseware;
use app\admin\model\Patient;
use app\common\controller\Api;

/**
 * 示例接口
 */
class Demo extends Api
{

    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    //
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['test', 'test1'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['test2'];

    /**
     * 测试方法
     *
     * @ApiTitle    (测试名称)
     * @ApiSummary  (测试描述信息)
     * @ApiMethod   (POST)
     * @ApiRoute    (/api/demo/test/id/{id}/name/{name})
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @ApiParams   (name="id", type="integer", required=true, description="会员ID")
     * @ApiParams   (name="name", type="string", required=true, description="用户名")
     * @ApiParams   (name="data", type="object", sample="{'user_id':'int','user_name':'string','profile':{'email':'string','age':'integer'}}", description="扩展数据")
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功")
     * @ApiReturnParams   (name="data", type="object", sample="{'user_id':'int','user_name':'string','profile':{'email':'string','age':'integer'}}", description="扩展数据返回")
     * @ApiReturn   ({
         'code':'1',
         'msg':'返回成功'
        })
     */
    public function commit_card()
    {
        $uid = $this->auth->id;
        $data["uid"] = $uid;
        $data["state"] = 0;
        $data["createtime"] = time();
        $data["idcard"] = $this->request->post('idcard') ? $this->request->post('idcard') : "";
        $data["titlecard"] = $this->request->post('titlecard') ? $this->request->post('titlecard') : "";
        $data["project_id"] = $this->request->post('project_id') ? $this->request->post('project_id') : "";
        var_dump($data);die();
        $card = new Card();
        $sum = $card->insert($data);
        if ($sum == 1){
            $this->success('提交成功！');
        }
        $this->error('提交失败！');
    }

    /**
     * 无需登录的接口
     *
     */
    public function courseware()
    {
        $course = new Courseware();
        $id = $this->request->post('id') ? $this->request->post('id') : "";
        $data["cours_name"] = $this->request->post('cours_name') ? $this->request->post('cours_name') : "";
        $data["edu_type"] = $this->request->post('edu_type') ? $this->request->post('edu_type') : "";
        $data["edu_dire"] = $this->request->post('edu_dire') ? $this->request->post('edu_dire') : "";
        $data["edu_area"] = $this->request->post('edu_area') ? $this->request->post('edu_area') : "";
        $data["cours_files"] = $this->request->post('cours_files') ? $this->request->post('cours_files') : "";
        $data["cours_video"] = $this->request->post('cours_video') ? $this->request->post('cours_video') : "";
        $sum = $course->where('id',$id)->update($data);
        if ($sum == 1){
            $this->success('提交成功');
        }
        $this->success('提交失败！');
    }

    /**
     * 添加患者
     *
     */
    public function patient()
    {
        $data["name"] = $this->request->post('name') ? $this->request->post('name') : "";
        $data["age"] = $this->request->post('age') ? $this->request->post('age') : "";
        $data["mobile"] = $this->request->post('mobile') ? $this->request->post('mobile') : "";
        $data["createtime"] = time();
        $data["uid"] = $this->auth->id;
        $pa = new Patient();
        $sum = $pa->insert($data);
        if ($sum == 1){
            $this->success('提交成功！');
        }
        $this->error('提交失败！');
    }

    /**
     * 需要登录且需要验证有相应组的权限
     *
     */
    public function test3()
    {
        $this->success('返回成功', ['action' => 'test3']);
    }

}
