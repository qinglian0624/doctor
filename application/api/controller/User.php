<?php

namespace app\api\controller;

use app\admin\model\Card;
use app\admin\model\Category;
use app\admin\model\Courseware;
use app\admin\model\Files;
use app\admin\model\Patient;
use app\admin\model\Project;
use app\admin\model\user\Authen;
use app\admin\model\Video;
use app\common\controller\Api;
use app\common\library\Ems;
use app\common\library\Sms;
use fast\Random;
use think\Config;
use think\Request;
use think\Validate;

/**
 * 会员接口
 */
class User extends Api
{
    protected $noNeedLogin = ['login', 'mobilelogin', 'register', 'resetpwd', 'changeemail', 'changemobile', 'third'];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();

        if (!Config::get('fastadmin.usercenter')) {
            $this->error(__('User center already closed'));
        }

    }

    /**
     * 会员中心
     */
    public function index()
    {
        $this->success('', ['welcome' => $this->auth->nickname]);
    }

    /**
     * 会员登录
     *
     * @ApiMethod (POST)
     * @param string $account 账号
     * @param string $password 密码
     */
    public function login()
    {
        $account = $this->request->post('account');
        $password = $this->request->post('password');
        if (!$account || !$password) {
            $this->error(__('Invalid parameters'));
        }
        $ret = $this->auth->login($account, $password);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 手机验证码登录
     *
     * @ApiMethod (POST)
     * @param string $mobile 手机号
     * @param string $captcha 验证码
     */
    public function mobilelogin()
    {
        $mobile = $this->request->post('mobile');
//        $captcha = $this->request->post('captcha');
//        if (!$mobile || !$captcha) {
//            $this->error(__('Invalid parameters'));
//        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
//        if (!Sms::check($mobile, $captcha, 'mobilelogin')) {
//            $this->error(__('Captcha is incorrect'));
//        }
        $user = \app\common\model\User::getByMobile($mobile);
        if ($user) {
            if ($user->status != 'normal') {
                $this->error(__('Account is locked'));
            }
            //如果已经有账号则直接登录
            $ret = $this->auth->direct($user->id);
        } else {
            $ret = $this->auth->register($mobile, Random::alnum(), '', $mobile, []);
        }
        if ($ret) {
            $authon = new Authen();
            $datas = $authon->where('id', $this->auth->id)->find();
            if (!empty($datas)) {
                $datas = $datas->toArray();
            }
            Sms::flush($mobile, 'mobilelogin');
            $data = ['userinfo' => $this->auth->getUserinfo(), 'vertify' => $datas, 'status' => $this->auth->title];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 注册会员
     *
     * @ApiMethod (POST)
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $email 邮箱
     * @param string $mobile 手机号
     * @param string $code 验证码
     */
    public function register()
    {

        $password = "111111";
//        $email = $this->request->post('email');
        $mobile = $this->request->post('mobile');
        $username = $mobile;
        $code = $this->request->post('code');
//        if (!$username || !$password) {
//            $this->error(__('Invalid parameters'));
//        }
//        if ($email && !Validate::is($email, "email")) {
//            $this->error(__('Email is incorrect'));
//        }
        if ($mobile && !Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        $ret = Sms::check($mobile, $code, 'register');
        if (!$ret) {
            $this->error(__('Captcha is incorrect'));
        }
        $ret = $this->auth->register($username, $password, "", $mobile, []);
        $authon = new Authen();
        $datas = $authon->where('id', $this->auth->id)->find();
        if (!empty($datas)) {
            $datas = $datas->toArray();
        }
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo(), 'vertify' => $datas];
            $this->success(__('Sign up successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 退出登录
     * @ApiMethod (POST)
     */
    public function logout()
    {
        if (!$this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }
        $this->auth->logout();
        $this->success(__('Logout successful'));
    }

    /**
     * 修改会员个人信息
     *
     * @ApiMethod (POST)
     * @param string $avatar 头像地址
     * @param string $username 用户名
     * @param string $nickname 昵称
     * @param string $bio 个人简介
     */
    public function profile()
    {
        $user = $this->auth->getUser();
        $username = $this->request->post('username');
        $nickname = $this->request->post('nickname');
        $bio = $this->request->post('bio');
        $avatar = $this->request->post('avatar', '', 'trim,strip_tags,htmlspecialchars');
        if ($username) {
            $exists = \app\common\model\User::where('username', $username)->where('id', '<>', $this->auth->id)->find();
            if ($exists) {
                $this->error(__('Username already exists'));
            }
            $user->username = $username;
        }
        if ($nickname) {
            $exists = \app\common\model\User::where('nickname', $nickname)->where('id', '<>', $this->auth->id)->find();
            if ($exists) {
                $this->error(__('Nickname already exists'));
            }
            $user->nickname = $nickname;
        }
        $user->bio = $bio;
        $user->avatar = $avatar;
        $user->save();
        $this->success();
    }

    /**
     * 修改邮箱
     *
     * @ApiMethod (POST)
     * @param string $email 邮箱
     * @param string $captcha 验证码
     */
    public function changeemail()
    {
        $user = $this->auth->getUser();
        $email = $this->request->post('email');
        $captcha = $this->request->post('captcha');
        if (!$email || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::is($email, "email")) {
            $this->error(__('Email is incorrect'));
        }
        if (\app\common\model\User::where('email', $email)->where('id', '<>', $user->id)->find()) {
            $this->error(__('Email already exists'));
        }
        $result = Ems::check($email, $captcha, 'changeemail');
        if (!$result) {
            $this->error(__('Captcha is incorrect'));
        }
        $verification = $user->verification;
        $verification->email = 1;
        $user->verification = $verification;
        $user->email = $email;
        $user->save();

        Ems::flush($email, 'changeemail');
        $this->success();
    }

    /**
     * 修改手机号
     *
     * @ApiMethod (POST)
     * @param string $mobile 手机号
     * @param string $captcha 验证码
     */
    public function changemobile()
    {
        $user = $this->auth->getUser();
        $mobile = $this->request->post('mobile');
        $captcha = $this->request->post('captcha');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        if (\app\common\model\User::where('mobile', $mobile)->where('id', '<>', $user->id)->find()) {
            $this->error(__('Mobile already exists'));
        }
        $result = Sms::check($mobile, $captcha, 'changemobile');
        if (!$result) {
            $this->error(__('Captcha is incorrect'));
        }
        $verification = $user->verification;
        $verification->mobile = 1;
        $user->verification = $verification;
        $user->mobile = $mobile;
        $user->save();

        Sms::flush($mobile, 'changemobile');
        $this->success();
    }

    /**
     * 第三方登录
     *
     * @ApiMethod (POST)
     * @param string $platform 平台名称
     * @param string $code Code码
     */
    public function third()
    {
        $url = url('user/index');
        $platform = $this->request->post("platform");
        $code = $this->request->post("code");
        $config = get_addon_config('third');
        if (!$config || !isset($config[$platform])) {
            $this->error(__('Invalid parameters'));
        }
        $app = new \addons\third\library\Application($config);
        //通过code换access_token和绑定会员
        $result = $app->{$platform}->getUserInfo(['code' => $code]);
        if ($result) {
            $loginret = \addons\third\library\Service::connect($platform, $result);
            if ($loginret) {
                $data = [
                    'userinfo' => $this->auth->getUserinfo(),
                    'thirdinfo' => $result
                ];
                $this->success(__('Logged in successful'), $data);
            }
        }
        $this->error(__('Operation failed'), $url);
    }

    /**
     * 重置密码
     *
     * @ApiMethod (POST)
     * @param string $mobile 手机号
     * @param string $newpassword 新密码
     * @param string $captcha 验证码
     */
    public function resetpwd()
    {
        $type = $this->request->post("type");
        $mobile = $this->request->post("mobile");
        $email = $this->request->post("email");
        $newpassword = $this->request->post("newpassword");
        $captcha = $this->request->post("captcha");
        if (!$newpassword || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        //验证Token
        if (!Validate::make()->check(['newpassword' => $newpassword], ['newpassword' => 'require|regex:\S{6,30}'])) {
            $this->error(__('Password must be 6 to 30 characters'));
        }
        if ($type == 'mobile') {
            if (!Validate::regex($mobile, "^1\d{10}$")) {
                $this->error(__('Mobile is incorrect'));
            }
            $user = \app\common\model\User::getByMobile($mobile);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Sms::check($mobile, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Sms::flush($mobile, 'resetpwd');
        } else {
            if (!Validate::is($email, "email")) {
                $this->error(__('Email is incorrect'));
            }
            $user = \app\common\model\User::getByEmail($email);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Ems::check($email, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Ems::flush($email, 'resetpwd');
        }
        //模拟一次登录
        $this->auth->direct($user->id);
        $ret = $this->auth->changepwd($newpassword, '', true);
        if ($ret) {
            $this->success(__('Reset password successful'));
        } else {
            $this->error($this->auth->getError());
        }
    }

    public function perfect()
    {
        $user = $this->auth->getUser();
        $nickname = $this->request->post("nickname");
        $group_id = $this->request->post("group_id");
        $hospital = $this->request->post("hospital");
        $title = $this->request->post("title");
        $detail = $this->request->post("detail");
        $age = $this->request->post("age");
        $gender = $this->request->post("gender");
        $city = $this->request->post("city");
//        var_dump($this->request->param());die();
//        if ($nickname) {
//            $exists = \app\common\model\User::where('nickname', $nickname)->where('id', '<>', $this->auth->id)->find();
//            if ($exists) {
//                $this->error(__('Nickname already exists'));
//            }
//            $user->nickname = $nickname;
//        }
        $user->group_id = $group_id;
        $user->hospital = $hospital;
        $user->title = $title;
        $user->detail = $detail;
        $user->age = $age;
        $user->gender = $gender;
        $user->city = $city;
        $user->save();
        $this->success("完善成功！");
    }

    public function authen()
    {
        $data["name"] = $this->request->post("name");
        $data["card_id"] = $this->request->post("card_id");
        $data["idcard_head"] = $this->request->post("idcard_head");
        $data["idcard_back"] = $this->request->post("idcard_back");
        $data["bankid"] = $this->request->post("bankid");
        $data["createtime"] = time();
        $uid = $this->auth->id;
        $authon = new Authen();
        $datas = $authon->where('uid', $uid)->find();
        if (empty($datas)) {
            $data["uid"] = $uid;
            $sum = $authon->insert($data);
        } else {
            $sum = $authon->where('uid', $uid)->update($data);
        }
        if ($sum == 1) {
            $this->success("提交成功！");
        }
        $this->error("提交失败！");
    }

    public function get_authon()
    {
        $authon = new Authen();
        $result = $authon->where('uid', $this->auth->id)->find();
        if (!empty($result)) {
            $results = $result->toArray();
            $this->success("", $results);
        }
        $this->success("", $result);
    }

    public function commit_card()
    {
        $uid = $this->auth->id;
        $data["uid"] = $uid;
        $data["state"] = 0;
        $data["createtime"] = time();
        $data["idcard"] = $this->request->post('idcard') ? $this->request->post('idcard') : "";
        $data["titlecard"] = $this->request->post('titlecard') ? $this->request->post('titlecard') : "";
        $data["project_id"] = $this->request->post('project_id') ? $this->request->post('project_id') : "";
        if (empty($data["idcard"]) || empty($data["titlecard"]) || empty($data["project_id"])) {
            $this->error('必要参数不能为空！');
        }
        $card = new Card();
        $card_dat = $card->where('uid', $uid)->where('project_id', $data["project_id"])->find();
        if (!empty($card_dat)) {
            $this->error('您已经提交过审核，请不要重复提交！');

        }
        $sum = $card->insert($data);
        if ($sum == 1) {
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
        $id = $this->request->post('project_id') ? $this->request->post('project_id') : "";
        $cours_name = $this->request->post('cours_name') ? $this->request->post('cours_name') : "";
        $edu_type = $this->request->post('edu_type') ? $this->request->post('edu_type') : "";
        $edu_dire = $this->request->post('edu_dire') ? $this->request->post('edu_dire') : "";
        $edu_area = $this->request->post('edu_area') ? $this->request->post('edu_area') : "";
        $cours_files = $this->request->post('cours_files') ? $this->request->post('cours_files') : "";
        $cours_video = $this->request->post('cours_video') ? $this->request->post('cours_video') : "";
        $video_name = $this->request->post('video_name') ? $this->request->post('video_name') : "";
        if (!empty($cours_name)){
            $data["cours_name"] = $cours_name;
        }
        if (!empty($edu_dire)){
            $data["edu_dire"] = $edu_dire;
        }
        if (!empty($edu_type)){
            $data["edu_type"] = $edu_type;
        }
        if (!empty($edu_area)){
            $data["edu_area"] = $edu_area;
        }
        if (!empty($cours_files)){
            $data["cours_files"] = $cours_files;
        }
        if (!empty($cours_video)){
            $data["cours_video"] = $cours_video;
        }
        if (!empty($video_name)){
            $data["video_name"] = $video_name;
        }

        if (!empty($data["cours_files"])) {
            $data["ppt_status"] = 0;
        }
        if (!empty($data["cours_video"])) {
            $data["video_status"] = 0;
        }
        $sum = $course->where('project_id', $id)->where('uid', $this->auth->id)->update($data);
        if ($sum == 1) {
            $this->success('提交成功');
        }
        $this->error('提交失败！');
    }

    public function commit_sign()
    {
        $data["sign_img"] = $this->request->post('sign_img') ? $this->request->post('sign_img') : "";
        $data["project_id"] = $this->request->post('project_id') ? $this->request->post('project_id') : "";
        $data["uid"] = $this->auth->id;
        $course = new Courseware();

        $data["createtime"] = time();
        $datas = $course->where('uid', $data["uid"])->where('project_id', $data["project_id"])->find();
        if (!empty($datas)) {
            $sum = $course->where('uid', $data["uid"])->where('project_id', $data["project_id"])->update($data);
        } else {
            $sum = $course->insert($data);
        }
        if ($sum == 1) {
            $this->success('提交成功！');
        }
        $this->error('提交失败！');
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
        if ($sum == 1) {
            $this->success('提交成功！');
        }
        $this->error('提交失败！');
    }

    //获取项目详情
    public function get_project()
    {
        $project = new Project();
        $course = new Courseware();
        $data["project_id"] = $this->request->post('project_id') ? $this->request->post('project_id') : "";
        $data["uid"] = $this->auth->id;
        $id = $this->request->post('id') ? $this->request->post('id') : "";
        $datas = $course->where('uid', $data["uid"])->where('project_id', $id)->find();
        if (!empty($datas)) {
            $datas = $datas->toArray();
        }
        $data = $project->where('id', $id)->find()->toArray();
        $data["image"] = Request::instance()->domain().$data["image"];
        $files = explode(',',$data["files"]);
        foreach ($files as $k=>&$v){
            $v = Request::instance()->domain().$data["image"];
        }
        $data["files"] = $files;
        $result["project"] = $data;
        $result["course"] = $datas;
        $this->success('', $result);
    }

    public function get_file()
    {
        $couse_id = $this->request->post('couse_id') ? $this->request->post('couse_id') : "";

        $file = new Files();
        $result = $file->where('couse_id', $couse_id)->select()->toArray();
        $this->success('', $result);
    }

    public function get_video()
    {
        $couse_id = $this->request->post('couse_id') ? $this->request->post('couse_id') : "";
        $file = new Video();
        $result = $file->where('couse_id', $couse_id)->select()->toArray();
        $this->success('', $result);
    }

    public function del_file()
    {
        $id = $this->request->post('id') ? $this->request->post('id') : "";

        $file = new Files();
        $result = $file->where('id', $id)->delete();
        if ($result == 1) {
            $this->success('删除成功！');
        }
        $this->error('删除失败！');
    }

    public function del_video()
    {
        $id = $this->request->post('id') ? $this->request->post('id') : "";

        $file = new Video();
        $result = $file->where('id', $id)->delete();
        if ($result == 1) {
            $this->success('删除成功！');
        }
        $this->error('删除失败！');
    }

    public function add_video()
    {
        $data["video"] = $this->request->post('video') ? $this->request->post('video') : "";
        $data["name"] = $this->request->post('name') ? $this->request->post('name') : "";
        $data["couse_id"] = $this->request->post('couse_id') ? $this->request->post('couse_id') : "";
        $data["createtime"] = time();

        $file = new Video();
        $result = $file->insert($data);
        if ($result == 1) {
            $this->success('添加成功！');
        }
        $this->error('添加失败！');
    }

    public function add_file()
    {
        $data["file"] = $this->request->post('file') ? $this->request->post('file') : "";
        $data["name"] = $this->request->post('name') ? $this->request->post('name') : "";
        $data["couse_id"] = $this->request->post('couse_id') ? $this->request->post('couse_id') : "";
        $data["createtime"] = time();

        $file = new Files();
        $result = $file->insert($data);
        if ($result == 1) {
            $this->success('添加成功！');
        }
        $this->error('添加失败！');
    }

    public function project_list()
    {
        $project = new Card();
        $user = new \app\admin\model\User();
        $result["user"] = $user->where('fa_user.id', $this->auth->id)->with('group')->field('fa_user.nickname,fa_user.hospital,fa_user.title,fa_user.detail')->find()->toArray();
        $category = new Category();
//        $hos = $category->where('id', $result["user"]["hospital"])->find()->toArray();
        $title = $category->where('id', $result["user"]["title"])->find()->toArray();
//        $result["user"]["hospital"] = $hos["name"];
        $result["user"]["title"] = $title["name"];
        $page = input("page");
        $length = input("length");
        $result["list"] = $project->where('uid', $this->auth->id)->with("project")->limit(($page - 1) * $length, $length)->select()->toArray();
        $result["total"] = $project->where('uid', $this->auth->id)->count();
        $this->success('', $result);
    }

    //重新认证
    public function edit_card()
    {
        $card = new Card();
        $data["idcard"] = $this->request->post('idcard') ? $this->request->post('idcard') : "";
        $data["titlecard"] = $this->request->post('titlecard') ? $this->request->post('titlecard') : "";
        $data["state"] = 0;
        $project_id = $this->request->post('id') ? $this->request->post('id') : "";
        $sum = $card->where('id', $project_id)->update($data);
        if ($sum == 1) {
            $this->success("提交成功！");
        }
        $this->error("提交失败！");

    }
}
