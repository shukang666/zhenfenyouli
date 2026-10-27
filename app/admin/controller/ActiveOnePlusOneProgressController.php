<?php



namespace app\admin\controller;



use api\wxapp\service\ActiveWebOnePlusOneService;

use cmf\controller\AdminBaseController;

use think\facade\Db;



/**

 * 1+1 Web 活动 · 用户七日任务完成进度

 */

class ActiveOnePlusOneProgressController extends AdminBaseController

{

    protected function progressTable(): string

    {

        return ActiveWebOnePlusOneService::progressTable();

    }



    public function index()

    {

        $params = $this->request->param();

        $activeId = (int)$this->request->param('active_id', 0);

        $keyword = trim((string)$this->request->param('keyword', ''));

        $dayFilter = (int)$this->request->param('day_filter', 0);

        $dayMiss = (int)$this->request->param('day_miss', 0);

        $registerAfter = trim((string)$this->request->param('register_after', ''));

        $registerAfterTs = $this->parseRegisterAfterTs($registerAfter);



        $actives = $this->listOnePlusOneActives();

        if ($activeId <= 0 && !empty($actives)) {

            $activeId = (int)$actives[0]['id'];

        }



        $dayTitles = $this->dayTitles();

        $cohortMode = $registerAfterTs > 0;

        $totalDays = ActiveWebOnePlusOneService::TOTAL_DAYS;



        if ($cohortMode && $activeId > 0) {

            $dayStats = $this->buildCohortDayStats($activeId, $registerAfterTs);

            $query = $this->buildCohortQuery($activeId, $registerAfterTs, $keyword, $dayFilter, $dayMiss);

        } else {

            $dayStats = $this->buildDayStats($activeId);

            $query = $this->buildProgressQuery($activeId, $keyword, $dayFilter);

            $dayMiss = 0;

        }



        $paginator = $query->paginate(20);
        $rows = [];
        foreach ($paginator as $vo) {
            $row = is_array($vo) ? $vo : $vo->toArray();
            $rows[] = $this->enrichProgressRow($row, $cohortMode);
        }

        $this->assign('list', $rows);
        $paginator->appends($params);

        $this->assign('page', $paginator->render());

        $this->assign('active_id', $activeId);

        $this->assign('keyword', $keyword);

        $this->assign('day_filter', $dayFilter);

        $this->assign('day_miss', $dayMiss);

        $this->assign('register_after', $registerAfter);

        $this->assign('cohort_mode', $cohortMode ? 1 : 0);

        $this->assign('actives', $actives);

        $this->assign('day_titles', $dayTitles);

        $this->assign('day_stats', $dayStats);

        $this->assign('total_days', $totalDays);

        $this->assign('day_numbers', range(1, $totalDays));

        $dayStatCards = [];
        for ($d = 1; $d <= $totalDays; $d++) {
            $dayStatCards[] = [
                'day'       => $d,
                'done'      => (int)($dayStats[$d] ?? 0),
                'miss'      => (int)($dayStats['miss_' . $d] ?? 0),
                'title'     => $dayTitles[$d] ?? '',
                'filter_on' => ($dayFilter === $d),
                'miss_on'   => ($dayMiss === $d),
            ];
        }
        $this->assign('day_stat_cards', $dayStatCards);

        return $this->fetch();

    }



    protected function parseRegisterAfterTs(string $date): int

    {

        $date = trim($date);

        if ($date === '') {

            return 0;

        }

        $ts = strtotime($date . ' 00:00:00');

        return $ts > 0 ? (int)$ts : 0;

    }



    /** 仅有进度记录的用户（原逻辑） */

    protected function buildProgressQuery(int $activeId, string $keyword, int $dayFilter)

    {

        $where = [];

        if ($activeId > 0) {

            $where[] = ['p.active_id', '=', $activeId];

        }

        if ($keyword !== '') {

            $where[] = ['m.nickname|m.mobile|m.true_name', 'like', '%' . $keyword . '%'];

        }



        $query = Db::name($this->progressTable())->alias('p')

            ->join('member m', 'm.id = p.user_id', 'LEFT')

            ->join('active a', 'a.id = p.active_id', 'LEFT')

            ->field($this->progressSelectFields(false))

            ->where($where)

            ->order('p.update_time desc');



        if ($dayFilter >= 1 && $dayFilter <= ActiveWebOnePlusOneService::TOTAL_DAYS) {

            $query->whereRaw('FIND_IN_SET(?, REPLACE(p.completed_days, " ", ""))', [$dayFilter]);

        }



        return $query;

    }



    /**

     * 注册时间在某日及之后的用户（含从未进入 1+1 的用户）

     */

    protected function buildCohortQuery(

        int $activeId,

        int $registerAfterTs,

        string $keyword,

        int $dayFilter,

        int $dayMiss

    ) {

        $where = [

            ['m.create_time', '>=', $registerAfterTs],

        ];

        if ($keyword !== '') {

            $where[] = ['m.nickname|m.mobile|m.true_name', 'like', '%' . $keyword . '%'];

        }



        $query = Db::name('member')->alias('m')

            ->join(

                $this->progressTable() . ' p',

                'p.user_id = m.id AND p.active_id = ' . max(0, $activeId),

                'LEFT'

            )

            ->join('active a', 'a.id = ' . max(0, $activeId), 'LEFT')

            ->field($this->progressSelectFields(true))

            ->where($where)

            ->order('m.create_time desc');



        if ($dayFilter >= 1 && $dayFilter <= ActiveWebOnePlusOneService::TOTAL_DAYS) {

            $query->whereRaw('FIND_IN_SET(?, REPLACE(IFNULL(p.completed_days, ""), " ", ""))', [$dayFilter]);

        } elseif ($dayMiss >= 1 && $dayMiss <= ActiveWebOnePlusOneService::TOTAL_DAYS) {
            $query->whereRaw(
                '(p.id IS NULL OR NOT FIND_IN_SET(?, REPLACE(IFNULL(p.completed_days, ""), " ", "")))',
                [$dayMiss]
            );
        }



        return $query;

    }



    protected function progressSelectFields(bool $cohort): string

    {

        if ($cohort) {

            return 'm.id as user_id,m.nickname,m.mobile,m.true_name,m.create_time as member_create_time,'

                . 'p.id as progress_id,p.active_id,p.cycle_no,p.completed_days,p.day_done_at,p.deadline_at,'

                . 'p.reward_300_at,p.reward_800_at,p.update_time,p.share_ack_at,p.day1_reward_at,'

                . 'a.name as active_name';

        }



        return 'p.*,m.nickname,m.mobile,m.true_name,m.create_time as member_create_time,a.name as active_name';

    }



    /** @return array<string, mixed> */

    protected function enrichProgressRow(array $vo, bool $cohortMode): array

    {

        if ($cohortMode && empty($vo['progress_id'])) {

            $vo['completed_days'] = '';

            $vo['day_done_at'] = '{}';

            $vo['cycle_no'] = 0;

            $vo['deadline_at'] = 0;

            $vo['reward_300_at'] = 0;

            $vo['reward_800_at'] = 0;

            $vo['update_time'] = 0;

            if (empty($vo['active_name']) && !empty($vo['active_id'])) {

                // keep

            }

        }

        $vo['day_cells'] = $this->buildDayCells($vo);

        $vo['completed_count'] = count($this->parseCompletedDays((string)($vo['completed_days'] ?? '')));

        $vo['reward_300_text'] = (int)($vo['reward_300_at'] ?? 0) > 0

            ? date('Y-m-d H:i', (int)$vo['reward_300_at']) : '';

        $vo['reward_800_text'] = (int)($vo['reward_800_at'] ?? 0) > 0

            ? date('Y-m-d H:i', (int)$vo['reward_800_at']) : '';

        $vo['deadline_text'] = (int)($vo['deadline_at'] ?? 0) > 0

            ? date('Y-m-d H:i', (int)$vo['deadline_at']) : '-';

        $vo['update_text'] = (int)($vo['update_time'] ?? 0) > 0

            ? date('Y-m-d H:i', (int)$vo['update_time']) : '-';

        $mc = (int)($vo['member_create_time'] ?? 0);

        $vo['register_text'] = $mc > 0 ? date('Y-m-d H:i', $mc) : '-';

        $vo['has_progress'] = !empty($vo['progress_id']) || (!$cohortMode && !empty($vo['id']));

        return $vo;

    }



    /** @return array<int, string> */

    protected function dayTitles(): array

    {

        $defs = ActiveWebOnePlusOneService::dayDefinitions();

        $out = [];

        foreach ($defs as $d => $def) {

            $title = trim((string)($def['task_title'] ?? ''));

            $out[$d] = $title !== '' ? $title : ('第' . $d . '天');

        }

        return $out;

    }



    /** @return array<string, int> */

    protected function buildDayStats(int $activeId): array

    {

        $stats = ['users' => 0, 'cohort' => 0];

        for ($d = 1; $d <= ActiveWebOnePlusOneService::TOTAL_DAYS; $d++) {

            $stats[$d] = 0;

            $stats['miss_' . $d] = 0;

        }

        if ($activeId <= 0) {

            return $stats;

        }

        $table = $this->progressTable();

        $stats['users'] = (int)Db::name($table)->where('active_id', $activeId)->count();

        for ($d = 1; $d <= ActiveWebOnePlusOneService::TOTAL_DAYS; $d++) {

            $stats[$d] = (int)Db::name($table)

                ->where('active_id', $activeId)

                ->whereRaw('FIND_IN_SET(?, REPLACE(completed_days, " ", ""))', [$d])

                ->count();

        }

        return $stats;

    }



    /**

     * 某注册起点 cohort：每日已参与 / 未参与人数

     *

     * @return array<string, int>

     */

    protected function buildCohortDayStats(int $activeId, int $registerAfterTs): array

    {

        $stats = ['users' => 0, 'cohort' => 0];

        for ($d = 1; $d <= ActiveWebOnePlusOneService::TOTAL_DAYS; $d++) {

            $stats[$d] = 0;

            $stats['miss_' . $d] = 0;

        }

        if ($activeId <= 0 || $registerAfterTs <= 0) {

            return $stats;

        }



        $stats['cohort'] = (int)Db::name('member')->where('create_time', '>=', $registerAfterTs)->count();

        $stats['users'] = $stats['cohort'];



        for ($d = 1; $d <= ActiveWebOnePlusOneService::TOTAL_DAYS; $d++) {

            $done = (int)Db::name('member')->alias('m')

                ->join(

                    $this->progressTable() . ' p',

                    'p.user_id = m.id AND p.active_id = ' . $activeId,

                    'INNER'

                )

                ->where('m.create_time', '>=', $registerAfterTs)

                ->whereRaw('FIND_IN_SET(?, REPLACE(p.completed_days, " ", ""))', [$d])

                ->count();

            $stats[$d] = $done;

            $stats['miss_' . $d] = max(0, $stats['cohort'] - $done);

        }



        return $stats;

    }



    /** @return list<int> */

    protected function parseCompletedDays(string $csv): array

    {

        if ($csv === '') {

            return [];

        }

        $parts = array_filter(array_map('intval', explode(',', $csv)));

        sort($parts);

        return array_values(array_unique($parts));

    }



    /** @return array<int, array{done:bool,time:string}> */

    protected function buildDayCells(array $row): array

    {

        $completedFlip = array_fill_keys($this->parseCompletedDays((string)($row['completed_days'] ?? '')), true);

        $doneAtRaw = (string)($row['day_done_at'] ?? '{}');

        $doneAt = json_decode($doneAtRaw, true);

        if (!is_array($doneAt)) {

            $doneAt = [];

        }

        $cells = [];

        for ($d = 1; $d <= ActiveWebOnePlusOneService::TOTAL_DAYS; $d++) {

            $done = isset($completedFlip[$d]);

            $ts = (int)($doneAt[$d] ?? $doneAt[(string)$d] ?? 0);

            $cells[$d] = [

                'done' => $done,

                'time' => $ts > 0 ? date('m-d H:i', $ts) : '',

            ];

        }

        return $cells;

    }



    /** @return list<array<string, mixed>> */

    protected function listOnePlusOneActives(): array

    {

        return Db::name('active')

            ->where('delete_time', 0)

            ->where('display_mode', 2)

            ->where('web_url', 'like', '%one-plus-one%')

            ->field('id,name')

            ->order('id desc')

            ->select()

            ->toArray();

    }

}

