<?php

namespace api\wxapp\controller;

use api\wxapp\model\AnswerItemModel;
use api\wxapp\model\AnswerLogModel;
use api\wxapp\model\AnswerModel;
use api\wxapp\model\CommonModel;
use api\wxapp\model\ProblemModel;
use api\wxapp\model\QuestionModel;
use api\wxapp\model\QuestionOptionModel;
use app\admin\model\member\CheckModel;
use app\admin\model\member\MemberModel;
use think\facade\Log;

class AnswerController extends AuthController
{


    /**
     * 问题列表
     * @OA\Post(
     *tags={"答题"},
     *path="/wxapp/answer/get_problem_list",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="id",description="详情",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="type",description="1：垃圾分类，2课程培训，3社区知识竞赛",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_problem_list(ProblemModel $problemModel, AnswerModel $AnswerModel)
    {
        $this->checkUser();
        $type = $this->request->param('type');
        $id = $this->request->param('id');
        if ($id) {
            $item = $problemModel->where('id', $id)->find();
            $item = CommonModel::getOne($item, ['thumb'], [], ['content']);
            $item['status'] = $AnswerModel
                ->where(['exam_id' => $item['id'], 'user_id' => $this->user_id, 'status' => 3])
                ->count();
            $item['status_name'] = $item['status'] ? '已完成' : '待答题';
            $this->success('请求成功', $item);
        }
        $where = [];
        if ($type) $where[] = ['type', '=', $type];
        $where[] = ['status', '=', 1];
        $list = $problemModel->where($where)->order('id desc')->paginate($this->page_size);
        if ($list->isEmpty()) $this->error('暂无数据');
        foreach ($list as &$item) {
            $item['status'] = $AnswerModel
                ->where(['exam_id' => $item['id'], 'user_id' => $this->user_id, 'status' => 3])
                ->count();
            $item['status_name'] = $item['status'] ? '已完成' : '待答题';
        }
        $list = CommonModel::getMany($list, ['thumb']);
        $this->success('请求成功', $list);
    }


    /**
     * 获取单个答题题目
     * @OA\Post(
     *tags={"答题"},
     *path="/wxapp/answer/get_question_one",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),

     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_question_one()
    {
        $this->checkUser();
        $id = $this->request->param('id');
        if (empty($id)) $this->error('id错误');
        $ProblemModel = new ProblemModel();
        $QuestionModel = new QuestionModel();
        $AnswerModel = new AnswerModel();
        $AnswerItemModel = new AnswerItemModel();
        $Problem = $ProblemModel->find($id);
        if (empty($Problem)) $this->error('暂无数据');
//        $is_Answer = $AnswerModel
//            ->where(['pid' => $id])
//            ->where('user_id', $this->user_id)
//            ->where('status', 3)->find();
//        if ($is_Answer) {
//            $this->error('已完成答题请勿重复答题');
//        }

        $Answer = $AnswerModel
            ->where(['pid' => $id])
            ->where('user_id', $this->user_id)
            ->where('status', 1)->find();
        if ($Answer) {
            $item = $AnswerItemModel->where(['answer_id' => $Answer['id'], 'pid' => $id, 'status' => 1])->find();
            $map[] = ['a.id', '=', $item['q_id']];
            $list = $QuestionModel->getInfo($map, -1, $item['q_id'], 'a.*', 'a.id', $item['answer_id']);
            $list['id'] = $item['id'];
            $this->success('请求成功', $list);
        }
        $map[] = ['status', '=', 1];
        $map[] = ['pid', '=', $id];
        $QuestionModel->startTrans();

        if ($Problem) {
            $random_num = $Problem['random_num'];
        }
        if (isset($random_num) && $random_num > 0) {
            // 直接让数据库返回随机题目（更高效）
            $list = $QuestionModel->where($map)
                ->orderRaw('RAND()') // MySQL随机排序
                ->limit($random_num) // 限制返回数量
                ->select();
        } else {
            $list = $QuestionModel->where($map)->select();
        }
        if ($list->isEmpty()) {
            $this->error('暂无数据');
        }
        $total_question_num = count($list);
        $answer_id = $AnswerModel->insertGetId([
            'user_id' => $this->user_id, 'exam_id' => $id,
            'type' => $Problem['type'],
            'pid' => $id, 'create_time' => time(),
            'total_num' => $total_question_num]);
        $answer_item_arr = [];
        $upper = 0;
        foreach ($list as $key => &$item) {
            $answer_item_arr[] = [
                'q_id' => $item['id'],
                'answer_id' => $answer_id,
                'upper' => $upper,
                'pid' => $id,
            ];
            $upper = $item['id'];
        }
        $AnswerItemModel->saveAll($answer_item_arr);
        $QuestionModel->commit();
        $item = $AnswerItemModel->where(['answer_id' => $answer_id])->find();
        $info = $QuestionModel->getInfo($map, -1, $item['q_id'], 'a.*', 'a.id', $item->answer_id);
        
        //2025-11-06 中玉修改，增加了q_id 查询条件，缺失该条件返回的数据是错误的
        $id = $AnswerItemModel->where(['answer_id' => $answer_id, 'q_id'=>$item['q_id'],'pid' => $id, 'status' => 1])
            ->field('id')->value('id');
            
        $info['id'] = $id;
        
        $this->success('请求成功', $info);//'answer_id' => $answer_id,
    }


    /**
     * 单个答题提交
     * @OA\Post(
     *tags={"答题"},
     *path="/wxapp/answer/add_answer_one",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="answer_id",description="题目id",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="o_id",description="答题id",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function add_answer_one()
    {
//        $answer_id = $this->request->param('answer_id');
        $q_id = $this->request->param('answer_id');
        $o_id = $this->request->param('o_id');
        $AnswerModel = new AnswerModel();
        $QuestionOptionModel = new QuestionOptionModel();
        $AnswerLogModel = new AnswerLogModel();

        $AnswerItemModel = new AnswerItemModel();
        $AnswerItem = $AnswerItemModel->where(['id' => $q_id])->find();
        if ($AnswerItem['status'] == 2) {
            $this->error('已提交');
        }
        $Answer = $AnswerModel->where(['id' => $AnswerItem['answer_id']])->find();
        if (empty($Answer)) {
            $this->error('错误');
        }

        if ($Answer['status'] == 2) {
            $this->success('答题完成', ['score' => $Answer['score']]);
        }
        $answer_id = $AnswerItem->answer_id;

        $q_id = $AnswerItem['q_id'];
        $ProblemModel = new ProblemModel();
        $Problem = $ProblemModel->where(['id' => $Answer['pid']])->find();
        $score = $Problem['score'];//得分
        if (empty($score)) $score = 1;
        $exam_id = $Answer['pid'];
        $q_info = QuestionModel::find($q_id);
        if (!empty($q_info['desc'])) $q_info['desc'] = htmlspecialchars_decode($q_info['desc']);
        $user_answer = '';
        if (!empty($q_info)) {
            $QuestionOption = $QuestionOptionModel->where(['id' => $o_id])->find();
//            Log::write($QuestionOption['letter'] . '===' . $q_info['true_answer'],'垃圾打开答案');
            if ($QuestionOption['letter'] != $q_info['true_answer']) {
                $answer_status = 2;
            } else {
                if ($Problem['type'] == 1) {
                    $add_score = $QuestionOption['score'] + $Answer['score'];
                    $Answer->save(['score' => $add_score]);
                } else {
                    $add_score = $score + +$Answer['score'];
                    $Answer->save(['score' => $add_score]);
                }
                $answer_status = 1;
            }
        } else {
            $answer_status = 3;
        }
        $map = [];
        $map[] = ['answer_id', '=', $answer_id];
        $count = $AnswerLogModel->where($map)->count();
        $AnswerLogModel->startTrans();
        $AnswerLogModel->insert(
            [
                'answer_id' => $answer_id,
                'option_id' => !empty($option_id_arr) ? implode(',', $option_id_arr) : '',
                'user_answer' => $user_answer,
                'answer_status' => $answer_status,
                'user_id' => $this->user_id,
                'exam_id' => $exam_id,
                'q_id' => $q_id,
                'true_answer' => $q_info['true_answer'],
                'create_time' => time(),
                'list_num' => $count + 1
            ]
        );
        $next = $AnswerItemModel->where(['upper' => $AnswerItem['q_id'], 'answer_id' => $answer_id])->value('q_id');
        $QuestionOption = $QuestionOptionModel->find($next);
        $AnswerItem->save(['status' => 2]);
        $next = 0;
        $pass = 0;
        $AnswerLogModel->commit();
        if (empty($QuestionOption)) {

            $next = 1;

            if ($Problem['type'] == 1) {//type1考试及格才能完成

                if ($Problem['pass_score'] < $Answer['score']) {//&& $Problem['type'] == 1
                    $pass = 1;
                    $Answer->save(['status' => 3]);//及格
                } else {
                    $Answer->save(['status' => 2]);//答题完成
                }
            } else {//其他只能打一次
                $Answer->save(['status' => 3]);//答题完成
            }


        }
        $newOption = $QuestionOptionModel->find($o_id);
        $option = $newOption['letter'] . '.' . $newOption['option'];
        $result = [
            'answer_status' => $answer_status,
            'option' => $option,
            'next' => $next,
            'pass' => $pass,
            'desc' => $q_info['desc'],
            'score' => $Answer['score'],
        ];
//        Log::write($result,'垃圾答题内容');
        $this->success('提交成功', $result);
    }


    /**
     * 单个答题提交-测试
     * @OA\Post(
     *tags={"答题"},
     *path="/wxapp/answer/add_answer_one_test",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="answer_id",description="题目id",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="o_id",description="答题id",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function add_answer_one_test()
    {
//        $answer_id = $this->request->param('answer_id');
        $q_id = $this->request->param('answer_id');
        $o_id = $this->request->param('o_id');
        $AnswerModel = new AnswerModel();
        $QuestionOptionModel = new QuestionOptionModel();
        $AnswerLogModel = new AnswerLogModel();

        $AnswerItemModel = new AnswerItemModel();
        $AnswerItem = $AnswerItemModel->where(['id' => $q_id])->find();
//        if ($AnswerItem['status'] == 2) {
//            $this->error('已提交');
//        }
        $Answer = $AnswerModel->where(['id' => $AnswerItem['answer_id']])->find();
        if (empty($Answer)) {
            $this->error('错误');
        }

        if ($Answer['status'] == 2) {
            $this->success('答题完成', ['score' => $Answer['score']]);
        }
        $answer_id = $AnswerItem->answer_id;

//        $AnswerItem = $AnswerItemModel->where(['answer_id' => $Answer->id, 'q_id' => $q_id])->find();

        $q_id = $AnswerItem['q_id'];
        $ProblemModel = new ProblemModel();
        $Problem = $ProblemModel->where(['id' => $Answer['pid']])->find();
        $score = $Problem['score'];//得分
        if (empty($score)) $score = 1;
        $exam_id = $Answer['pid'];
        $q_info = QuestionModel::find($q_id);
        if (!empty($q_info['desc'])) $q_info['desc'] = htmlspecialchars_decode($q_info['desc']);
        $option_id_arr = $o_id ?? [];
        $user_answer = '';
        if (!empty($option_id_arr)) {
            if (!is_array($option_id_arr)) {
                $option_id_arr = [$option_id_arr];
            }
            $letter = [];
            foreach ($option_id_arr as $option_id) {
                $letter[] = $QuestionOptionModel->where(['id' => $option_id])->value('letter');
            }
            $QuestionOption = $QuestionOptionModel->where(['id' => $o_id])->find();

            sort($letter);
            $user_answer = implode(',', $letter);
//            var_dump($user_answer, $q_info['true_answer']);exit;
            if ($user_answer != $q_info['true_answer']) {
                $answer_status = 2;
            } else {
                $answer_status = 1;
            }
        } else {
            $answer_status = 3;
        }
        $map = [];
        $map[] = ['answer_id', '=', $answer_id];
        $count = $AnswerLogModel->where($map)->count();
        // $AnswerLogModel->startTrans();
        $AnswerLogModel->insert(
            [
                'answer_id' => $answer_id,
                'option_id' => !empty($option_id_arr) ? implode(',', $option_id_arr) : '',
                'user_answer' => $user_answer,
                'answer_status' => $answer_status,
                'user_id' => $this->user_id,
                'exam_id' => $exam_id,
                'q_id' => $q_id,
                'true_answer' => $q_info['true_answer'],
                'create_time' => time(),
                'list_num' => $count + 1
            ]
        );
        $next = $AnswerItemModel->where(['upper' => $AnswerItem['q_id'], 'answer_id' => $answer_id])->value('q_id');
        $QuestionOption = $QuestionOptionModel->find($next);
        $AnswerItem->save(['status' => 2]);
        $next = 0;
        $pass = 0;
        // $AnswerLogModel->commit();
        if (empty($QuestionOption)) {

            $next = 1;
            if ($Problem['type'] == 1) {//type1考试及格才能完成
                if ($Problem['pass_score'] < $Answer['score']) {//&& $Problem['type'] == 1
                    $pass = 1;
                    $Answer->save(['status' => 3]);//及格
                } else {
                    $Answer->save(['status' => 2]);//答题完成
                }
            } else {//其他只能打一次
                $Answer->save(['status' => 3]);//答题完成
            }


        }
        $newOption = $QuestionOptionModel->find($o_id);
        $option = $newOption['letter'] . '.' . $newOption['option'];

        $this->success('提交成功', [
            'answer_status' => $answer_status,
            'option' => $option,
            'next' => $next,
            'pass' => $pass,
            'desc' => $q_info['desc'],
            'score' => $Answer['score'],
        ]);
    }


    /**
     * @OA\Post(
     *     tags={"答题"},
     *     summary="获取答题题目",
     *     path="/wxapp/answer/get_question",
     *
     *          @OA\Parameter(
     *         name="exam_id",
     *         in="query",
     *         description="菜单id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *          @OA\Parameter(
     *         name="answer_id",
     *         in="query",
     *         description="传answer_id取做过的题的答案",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *          @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="传id取单个题",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public
    function get_question()
    {
        $this->checkUser();
//        $param = $this->request->param();
//        Log::write($param,'题目参数');
        $exam_id = $this->request->param('exam_id');
        $answer_id = $this->request->param('answer_id');
        $id = $this->request->param('id');
        if (empty($exam_id)) $this->error('错误');
        $QuestionModel = new QuestionModel();
        $map = [];
        $map[] = ['a.pid', '=', $exam_id];
        $map[] = ['a.status', '=', 1];
        $list = $QuestionModel->getInfo($map, -1, $id, 'a.*', 'a.id', $answer_id);
        $this->success('获取成功', $list);
    }


    /**
     * @OA\Post(
     *     tags={"答题"},
     *     summary="提交答卷",
     *     path="/wxapp/answer/add_answer",
     *          @OA\Parameter(
     *         name="exam_id",
     *         in="query",
     *         description="菜单id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *          @OA\Parameter(
     *         name="from_uid",
     *         in="query",
     *         description="分享人的ID",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *         @OA\Parameter(
     *         name="answer",
     *         in="query",
     *         description="答案 [{'q_id'=>1,'o_id'=>1},{'q_id'=>2,'o_id'=>[2,3]},{'q_id'=>2,'o_id'=>'填空答案'}]",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public
    function add_answer()
    {
        $this->checkUser();
        $exam_id = $this->request->param('exam_id');
        $answer_arr = $this->request->param('answer');

        if (empty($exam_id) || empty($answer_arr)) $this->error('错误');
        if (!is_array($answer_arr)) $this->error('错误');
        $AnswerModel = new AnswerModel();
        $QuestionOptionModel = new QuestionOptionModel();
        $QuestionModel = new QuestionModel();
        $AnswerLogModel = new AnswerLogModel();
        $ProblemModel = new ProblemModel();
        $Question = $QuestionModel->where(['id' => $exam_id])->find();
        $Problem = $ProblemModel->where(['id' => $Question['pid']])->find();
        $score = $Problem['score'] ?? 1;//得分
//        $map = [];
//        $map[] = ['exam_id','=',$exam_id];
//        $map[] = ['user_id','=',$this->user_id];
//        $is = $AnswerModel->where($map)->find();
//        if(!empty($is)) $this->error('不能重复作答');

        $map = [];
        $map[] = ['status', '=', 1];
        $map[] = ['exam_id', '=', $exam_id];
        $total_question_num = $QuestionModel->where($map)->count();

        $answer_id = $AnswerModel->insertGetId(['user_id' => $this->user_id, 'exam_id' => $exam_id,
            'create_time' => time(), 'total_num' => $total_question_num]);

        foreach ($answer_arr as $item) {
            $q_id = $item['q_id'];
            $q_info = QuestionModel::find($q_id);
            if ($q_info['type'] == 1 || $q_info['type'] == 2 || $q_info['type'] == 4) {
                $option_id_arr = $item['o_id'] ?? [];
                if (!empty($option_id_arr)) {
                    if (!is_array($option_id_arr)) {
                        $option_id_arr = [$option_id_arr];
                    }
                    $letter = [];
                    foreach ($option_id_arr as $option_id) {
                        $letter[] = $QuestionOptionModel->where(['id' => $option_id])->value('letter');
                    }
                    sort($letter);
                    $user_answer = implode(',', $letter);
                } else {
                    $user_answer = '';
                }
            } else {
                $user_answer = $item['o_id'];
            }

            if (empty($user_answer)) {
                $answer_status = 3;
            } else {
                if (in_array($q_info['type'], [1, 2, 4])) {
                    if ($user_answer != $q_info['true_answer']) {
                        $answer_status = 2;
                    } else {
                        $answer_status = 1;
                    }
                } else {
                    if (mb_strlen($user_answer) >= 10) {
                        $answer_status = 1;
                    } else {
                        $answer_status = 2;
                    }
                }

            }
            $map = [];
            $map[] = ['answer_id', '=', $answer_id];
            $count = $AnswerLogModel->where($map)->count();
            $AnswerLogModel->insert(
                [
                    'answer_id' => $answer_id,
                    'option_id' => !empty($option_id_arr) ? implode(',', $option_id_arr) : '',
                    'user_answer' => $user_answer,
                    'answer_status' => $answer_status,
                    'user_id' => $this->user_id,
                    'exam_id' => $exam_id,
                    'q_id' => $q_id,
                    'true_answer' => $q_info['true_answer'],
                    'create_time' => time(),
                    'list_num' => $count + 1
                ]
            );
        }

        $map = [];
        $map[] = ['answer_status', '=', 1];
        $map[] = ['answer_id', '=', $answer_id];
        $right_num = $AnswerLogModel->where($map)->count();
        $total_score = $score * $right_num;
        $AnswerModel->where(['id' => $answer_id])->save(['right_num' => $right_num, 'score' => $total_score]);
        $this->success('答题成功', ['answer_id' => $answer_id, 'total_score' => $total_score]);

    }


    /**
     * @OA\Post(
     *     tags={"答题"},
     *     summary="获取记录",
     *     path="/wxapp/answer/get_answer",
     *
     *          @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="传id取详情",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public
    function get_answer()
    {


//        $param = $this->request->param();
//        Log::write('答题记录',$param);
        $this->checkUser();
        $id = $this->request->param('id');
        $map = [];
        $AnswerModel = new AnswerModel();
        $map[] = ['a.user_id', '=', $this->user_id];
        $data = $AnswerModel->getInfo($map, $this->page_size, $id);
        if (!empty($id)) {
            $AnswerLogModel = new AnswerLogModel();
            $map = [];
            $map[] = ['a.answer_id', '=', $id];
            $data['child'] = $AnswerLogModel->getInfo($map, -1);
        }
        $this->success('获取成功', $data);
    }


    /**
     *
     * 答题排行榜
     * @OA\Get(
     *      tags={"答题"},
     *     path="/wxapp/answer/get_answer_ranking",
     *      *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *      *@OA\Parameter(name="id",description="问题ID",in="query",required=false, @OA\Schema(type="string",)),
     *     @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_answer_ranking()
    {
        $this->checkUser();
        $AnswerModel = new AnswerModel();
        $id = $this->request->param('id');
        if (empty($id)) $this->error('id不能为空');
//        Log::write('id'.$id,'答题排行榜');
        $where = [];//if (!empty($id))
        $where[] = ['a.pid', '=', $id];
        $where[] = ['a.status', '=', 3];
        $list = $AnswerModel
            ->alias('a')
            ->leftjoin('member b', 'a.user_id=b.id')
            ->field(' user_id,nickname,avatar, a.score')
            ->where($where)
            ->order('score desc')
            ->paginate($this->page_size);

        //排行榜
        foreach ($list as &$item) {
            $item['avatar'] = cmf_get_asset_url($item['avatar']);
        }
        $this->success('获取成功', $list);
    }

}