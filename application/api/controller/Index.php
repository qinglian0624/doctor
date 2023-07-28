<?php

namespace app\api\controller;

use app\admin\model\Card;
use app\admin\model\Courseware;
use app\admin\model\Patient;
use app\admin\model\Project;
use app\common\controller\Api;
use app\common\model\Category;
use app\common\model\UserGroup;

/**
 * 首页接口
 */
class Index extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 首页
     *
     */
    public function index()
    {
        $project = new Project();


        $result["project"] = $project->limit(5)->select()->toArray();
//        if ($type == 1) {
//            $order = "id desc";
//        }
//        if ($type == 1) {
//            $order = "id asc";
//        }
//        $result["patient"] = $patient->limit(($page - 1) * $length, $length)->where('uid', $this->auth->id)->order($order)->select()->toArray();
//        $result["total"] = $patient->where('uid', $this->auth->id)->count();
        $this->success('请求成功', $result);
    }

    public function patient(){
        $patient = new Patient();
        $page = $this->request->post("page");
        $length = $this->request->post("length");
        $type = $this->request->post("type");
        if ($type == 1) {
            $order = "id desc";
        }
        if ($type == 2) {
            $order = "id asc";
        }
        $result["patient"] = $patient->limit(($page - 1) * $length, $length)->where('uid', $this->auth->id)->order($order)->select()->toArray();
        $result["total"] = $patient->where('uid', $this->auth->id)->count();
        $this->success('请求成功', $result);
    }

    public function project_detail()
    {
        $card = new Card();
        $couse = new Courseware();
        $project_id = $this->request->post("project_id");
        $data = $card->where('uid', $this->auth->id)->where('project_id', $project_id)->find();
        $res = $couse->where('uid', $this->auth->id)->where('project_id', $project_id)->find();
        if (!empty($data)) {
            $data = $data->toArray();
        }
        if (!empty($res)){
            $res = $res->toArray();
        }

        $result["card"] = $data;
        $result["couse"] = $res;
        $this->success('', $result);
    }

    public function get_category()
    {
        $category = new Category();
        $type = $this->request->post("type");
        $result = $category->where('type', $type)->field('id,name')->select()->toArray();
        $this->success('', $result);
    }

    public function get_group()
    {
        $group = new UserGroup();
        $result = $group->select()->toArray();
        $this->success('', $result);
    }

}
