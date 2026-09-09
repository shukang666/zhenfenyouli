<?php

namespace api\wxapp\controller;

use api\wxapp\model\AccountLogModel;
use api\wxapp\model\CommonModel;
use app\admin\model\health\HealthHomeModel;
use think\facade\Db;
use think\facade\Cache;
use cmf\controller\RestBaseController;

use think\facade\Log;
use think\Validate;
use cmf\lib\Upload;

header('Access-Control-Allow-Origin:*');
// 响应类型
header('Access-Control-Allow-Methods:*');
// 响应头设置
header('Access-Control-Allow-Headers:*');
error_reporting(0);

class ExamController extends RestBaseController
{
    /**
     * @var array|\think\Model|null
     */
    private $user_info;

    protected function initialize()
    {
        parent::initialize();
        $openid = $this->request->header('openid');
        if (empty($openid)) {
            $openid = $this->request->param('openid');
        }
        $nickname = urldecode($this->request->header('nickname'));
        $avatar = $this->request->header('avatar');
        $wxfans = array(
            "openid" => $openid,
            "nickname" => $nickname,
            "avatar" => $avatar,
        );

        $this->wxfans = $wxfans;

        $this->user_info = Db::name('member')->where('openid', $openid)->find();
        $this->uid = $this->user_info['id'];
    }


    /**
     *考试详情
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @OA\Get(
     *     tags={"问卷调查模块"},
     *     path="/wxapp/exam/find_exam",
     * 	   @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     */
    public function find_exam()
    {
        $data = $this->request->param();
        if (empty($data['id'])) {
            $this->error("id不能为空!");
        }

        $item = Db::name("exam")->where("id", $data['id'])->find();
        if ($item['thumb']) {
            $item['thumb'] = cmf_get_asset_url($item['thumb']);
        }
        if ($item['bg_thumb']) {
            $item['bg_thumb'] = cmf_get_asset_url($item['bg_thumb']);
        }
        if ($item['chou_thumb']) {
            $item['chou_thumb'] = cmf_get_asset_url($item['chou_thumb']);
        }
        if ($item['result_thumb']) {
            $item['result_thumb'] = cmf_get_asset_url($item['result_thumb']);
        }
        if ($item['result_success_thumb']) {
            $item['result_success_thumb'] = cmf_get_asset_url($item['result_success_thumb']);
        }
        if ($item['result_fail_thumb']) {
            $item['result_fail_thumb'] = cmf_get_asset_url($item['result_fail_thumb']);
        }
        $item['remark'] = cmf_replace_content_file_url(htmlspecialchars_decode($item['remark']));

        //分析是否已参与问卷调查
        $join = Db::name('exam_gift')->where(['exam_id' => $data['id'], 'openid' => $this->wxfans['openid']])->find();
        // echo Db::name('exam_gift')->getLastSql();


        $item['join'] = $join;
        $this->success("请求成功!", $item);
    }


    /**
     *我的问卷
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @OA\Get(
     *     tags={"问卷调查模块"},
     *     path="/wxapp/exam/my_exam_list",
     * 	   @OA\Parameter(
     *         name="openid",
     *         in="query",
     *         description="openid",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     * 	   @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     */
    public function my_exam_list()
    {
        $id = $this->request->param('id');
        if ($id) {
            $where['e.gift_id'] = $id;

            $list = Db::name("exam_record")->alias('e')
                ->join('exam_question m', 'e.exam_id=m.exam_id and e.question_id=m.id')
                ->field('e.*,m.question,m.option,m.type,is_thumb,is_mobile,is_other')->hidden(['openid'])->where($where)
                ->select()->each(function ($item, $key) {
                    $answer = [];

                    if ($item['type'] == 3) {
                        if (!empty($item['image_answer'])) $item['image_answer'] = cmf_get_asset_url($item['image_answer']);
                        array_push($answer, $item['select_option']);
                    } else {
                        $select_option = explode(',', $item['select_option']);
                        $options = json_decode($item['option'], true);
                        $key = 0;
                        foreach ($options as &$option) {
                            if (in_array($key + 1, $select_option)) {
                                array_push($answer, $option['option']);
                                $option['checked'] = true;
                            } else {
                                $option['checked'] = false;
                            }
                            $key++;
                        }
                        $item['option'] = $options;

                    }

                    $item['answer'] = implode(',', $answer);
                    if (!empty($item['hospital'])) {
                        $item['answer'] = $item['answer'] . ',' . $item['hospital'];
                    }
//                    unset($item['option']);
//                    $item['option'] = json_decode($item['option'], true);
                    return $item;
                });
            $this->success("请求成功!", $list);

        }
        //分析是否已参与问卷调查
        $list = Db::name('exam_gift')
            ->alias('a')
            ->where(['openid' => $this->wxfans['openid']])
            ->join('exam b', 'b.id=a.exam_id')
            ->field('a.id,b.name,a.createtime create_time')
            ->paginate(15);

        $this->success("请求成功!", $list);
    }

    /**
     *健康管理列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @OA\Get(
     *     tags={"问卷调查模块"},
     *     path="/wxapp/exam/find_exam_list",
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     */
    public function find_exam_list()
    {
        $list = Db::name('exam')
            ->field("id,name")
            ->cache(true, 120)
            ->order('list_order asc,id desc')
            ->select();
        //A到Z 拼到name前面 第一是A 第二个B


//            ->paginate(10);
//        $list = CommonModel::getMany($list, ['thumb']);
        $this->success("请求成功！", $list);
    }


    /**
     *问卷列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @OA\Get(
     *     tags={"问卷调查模块"},
     *     path="/wxapp/exam/find_exam_topic_list",
     *      *       @OA\Parameter(
     *         name="openid",
     *         in="query",
     *         description="openid",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *      *       @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="详情ID",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *      *       @OA\Parameter(
     *         name="cate_id",
     *         in="query",
     *         description="项目ID",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *    *       @OA\Parameter(
     *         name="is_exam",
     *         in="query",
     *         description="1.问卷，2：预约",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     */
    public function find_exam_topic_list()
    {
        $id = $this->request->param('id');
        $cate_id = $this->request->param('cate_id');
        $is_exam = $this->request->param('is_exam');

        //之前分类不能重复答题
        $cate_ids = Db::name('exam')->where('cate_id', 6)->column('id');
        $is_answer = Db::name('exam_gift')
            ->where('exam_id', 'in', $cate_ids)
            ->where(['uid' => $this->user_info['id']])
            ->count() ? 1 : 0;
        $is_answer = 1;


        if (empty($id)) {
            $where = [];
            if (!empty($cate_id)) {
                $where['cate_id'] = $cate_id;
            }
            if (!empty($is_exam) && $cate_id == 6) {
                $where['is_exam'] = $is_exam;
            }

            $where['status'] = 1;
            $data = [];
            $list = Db::name('exam')
                ->field("id,name,thumb,desc,is_demand,is_exam,is_secret,cate_id,is_repeat,list_order")
                ->where($where)
                ->cache(true, 60)
                ->order('list_order asc,id desc')
                ->withAttr('thumb', function ($value) {
                    return cmf_get_asset_url($value);
                })
                ->paginate(10)->each(function ($item) {
                    $item['is_answer'] = 0;
                    //查询答题记录
                    $is_repeat = $item['is_repeat'];
                    if ($item['cate_id'] == 6) {
                        $item['is_answer'] = 1;
                    } elseif ($is_repeat == 2) {
                        //判断是否答题过了
                        $item['is_answer'] = Db::name('exam_gift')
                            ->where(['openid' => $this->wxfans['openid']])
                            ->where('exam_id', $item['id'])->count();

                    }
                    return $item;
                });


            $this->success("请求成功！", $list);

        } else {

            $is_answer = 0;
            //查询答题记录
            $exam = Db::name('exam')->where('id', $id)->find();
            $is_repeat = $exam['is_repeat'];
            if ($is_repeat == 2) {
                //判断是否答题过了
                $is_answer = Db::name('exam_gift')
                    ->where(['openid' => $this->wxfans['openid']])
                    ->where('exam_id', $exam['id'])->count();
//            if ($count12 > 0) $this->error("您已答题，不能重复答题!");

            }

            $item = Db::name('exam')
                ->where('id', $id)
                ->field("*")
                ->cache(true, 60)
                ->order('list_order asc')
                ->find();
            $item['is_answer'] = $is_answer;
            if ($item['cate_id'] == 6) {
                $item['is_answer'] = $is_answer;
            }
            $item = CommonModel::getOne($item, ['thumb'], ['image'], ['content', 'secret']);
            $this->success("请求成功！", $item);
        }

        //A到Z 拼到name前面 第一是A 第二个B


//            ->paginate(10);
//        $list = CommonModel::getMany($list, ['thumb']);
    }

    /**
     *查询题目列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @OA\Get(
     *     tags={"问卷调查模块"},
     *     path="/wxapp/exam/find_question_list",
     * 	   @OA\Parameter(
     *         name="exam_id",
     *         in="query",
     *         description="exam_id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     */
    public function find_question_list()
    {
        $data = $this->request->param();
        if (empty($data['exam_id'])) {
            $this->error("问卷id不能为空!");
        }
        $list = Db::name('exam_question')
            ->cache(120)
            ->field("id,question,option,type,is_other,limit_words,is_mobile,is_thumb")
            ->where("exam_id", $data['exam_id'])
            ->order('list_order asc')->select()->each(function ($item, $key) {
                $option = json_decode($item['option'], true);
                foreach ($option as &$v) {
                    $v['checked'] = false;
                }
                $item['option'] = $option;

                return $item;
            });
        if (empty($list)) {
            $this->error("暂无相关题目!");
        }
        $this->success("请求成功！", $list);
    }

    public function test_ss()
    {
        $select_option = Db::name('exam_question')->where('id', 'in', '96,97')->column('question');
        var_dump($select_option);
        die();
    }

    /**
     *提交答题记录
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @OA\Get(
     *     tags={"问卷调查模块"},
     *     path="/wxapp/exam/add_exam_record",
     * 	   @OA\parameter(
     *         name="openid",
     *         in="query",
     *         description="openid",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *    @OA\Parameter(
     *         name="exam_id",
     *         in="query",
     *         description="exam_id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *    @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="姓名",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *    @OA\Parameter(
     *         name="mobile",
     *         in="query",
     *         description="手机号",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     * 	   @OA\Parameter(
     *         name="list",
     *         in="query",
     *         description="list:[{question_id:1,select_option:111,other:补充内容}]",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     */
    public function add_exam_record()
    {
        $data = $this->request->param();
//        Log::write($data, '提交答题');
        if (empty($data['exam_id'])) {
            $this->error("考试ID不能为空!");
        }
        if (empty($this->wxfans['openid'])) {
            $this->error("请先登录!");
        }


        //查询答题记录
        $exam = Db::name('exam')->where('id', $data['exam_id'])->find();
        $data['is_gift'] = 0;
        $is_repeat = $exam['is_repeat'];
        if ($is_repeat == 2) {
            //判断id=12的是否答题过了
            $count12 = Db::name('exam_gift')
                ->where(['openid' => $this->wxfans['openid']])
                ->where('exam_id', $exam['id'])->count();
            if ($count12 > 0) $this->error("您已答题，不能重复答题!");

        }


        if ($exam['cate_id'] == 6) {//判断分类ID为6的
            $cate_ids = Db::name('exam')->where('cate_id', 6)->column('id');
            $is_answer = Db::name('exam_gift')
                ->where('exam_id', 'in', $cate_ids)
                ->where(['uid' => $this->user_info['id']])
                ->count() ? 1 : 0;
            if ($is_answer) {
                $this->error("已参与不能重复参与!");
            }
        }
        $list = $data['list'];
        $result = false;


        $gift = [
            'gift_name' => $exam['gift_name'],
            'uid' => $this->uid,
            'exam_id' => $data['exam_id'],
            'openid' => $this->wxfans['openid'],
            'gift_code' => '',
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'createtime' => time(),
            'updatetime' => time(),
        ];
        $gift_id = Db::name('exam_gift')->strict(false)->insert($gift, 1);

        if ($gift_id) {
            foreach ($list as $v) {
                if (is_array($v['select_option'])) {
                    $v['select_option'] = implode(',', $v['select_option']);
                }
                if (!empty($v['hospital'])) {
                    $v['hospital'] = implode(',', $v['hospital']);
                }
                $v['createtime'] = time();
                $v['updatetime'] = time();
                $v['exam_id'] = $data['exam_id'];
                $v['uid'] = $this->uid;
                $v['openid'] = $this->wxfans['openid'];
                $v['gift_id'] = $gift_id;
                $result = Db::name("exam_record")->strict(false)->insert($v, 1);
            }
            if (empty($exam['gift_point'])) $this->success("提交成功!");
            AccountLogModel::add_account_log($this->uid, 2, 201, $exam['gift_point'], ['from_uid' => $result]);
            $this->success("获得{$exam['gift_point']}积分！", ['record_id' => $gift_id, 'gift_point' => $exam['gift_point']]);
//
        } else {
            $this->error("提交失败，请稍后再试！");
        }
    }

    /**
     * 分析是否中奖
     * @param {Object} $option
     * @param {Object} $list
     */
    public function analyze_gift($option, $list)
    {
        $option = json_decode($option, true);
        if (empty($option)) {
            return 0;
        }

        $is_gift = 0;
        foreach ($list as $answer) {
            $ques_id = $answer['question_id'];
            $select_index = $answer['select_index'];
            //echo 'ques_id:'.$ques_id.'select_index:'.$select_index.'\n';
            foreach ($option as $op) {
                $match_array = explode(',', $op['next']);
                //var_dump($match_array);
                //echo 'option:'.$op['option'].'\n';
                if ($op['option'] == $ques_id && in_array($select_index, $match_array)) {
                    $is_gift = 1;
                    // echo '11111';
                    //break;
                }
            }
        }
        return $is_gift;
    }


    public function test_addrecord()
    {
        $this->wxfans['openid'] = 'abc123';
        $this->uid = 1;
        $data['exam_id'] = 1;
        //list:[{question_id:1,select_index:1,select_option:111,other:补充内容}]
        $data['list'] = array(
            ['question_id' => 7, 'select_index' => 1, 'select_option' => '男'],
            ['question_id' => 8, 'select_index' => 1, 'select_option' => 'B 25岁~29岁'],
        );
        //分析中奖年龄段
        $exam = Db::name('exam')->where('id', $data['exam_id'])->find();

        $data['is_gift'] = 0;
        $join = Db::name('exam_gift')->where(['exam_id' => $data['exam_id'], 'openid' => $this->wxfans['openid']])->find();
        if ($join) {
            //$this->error("已参与不能重复参与!");
        }
        $list = $data['list'];
        // $gift_name = $exam['age'];
        // $skin_name = $exam['skin'];

        foreach ($list as $v) {
            $v['createtime'] = time();
            $v['updatetime'] = time();
            $v['exam_id'] = $data['exam_id'];
            $v['uid'] = $this->uid;
            $v['openid'] = $this->wxfans['openid'];
            $result = Db::name("exam_record")->strict(false)->insert($v);
        }
        $data['is_gift'] = 0;
//		$data['is_gift'] = $this->analyze_gift($exam['option'],$list);
        if ($result) {
            $exam_code = Db::name('exam_code')->where('status', 0)->find();
            $gift = [
                'gift_name' => $exam['gift_name'],
                'uid' => $this->uid,
                'exam_id' => $data['exam_id'],
                'openid' => $this->wxfans['openid'],
                'gift_code' => '',
                'createtime' => time(),
                'updatetime' => time(),
            ];
            if ($exam_code) {
                $gift['gift_code'] = $exam_code['code'];
                Db::name('exam_code')->where('id', $exam_code['id'])->update(['status' => 1, 'updatetime' => time()]);
            } else {
                //$data['is_gift'] = 0;
            }

            if ($data['is_gift'] == 1) {
                $gift['is_gift'] = 1;
            } else {
                $gift['gift_name'] = '谢谢参与';
                $gift['gift_code'] = '';
            }

            Db::name('exam_gift')->strict(false)->insert($gift);

            $this->success("处理成功!", ['gift_name' => $gift['gift_name'], 'gift_code' => $gift['gift_code']]);
        } else {
            $this->error("处理失败，请稍后再试！");
        }

    }
}