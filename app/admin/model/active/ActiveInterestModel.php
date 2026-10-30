<?php

namespace app\admin\model\active;

class ActiveInterestModel extends \think\Model
{
    protected $name = 'active_interest';

    public static function countByActive(int $activeId): int
    {
        if ($activeId <= 0) {
            return 0;
        }
        return (int) self::where('active_id', $activeId)->count();
    }

    public static function countByUser(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }
        return (int) self::where('user_id', $userId)->count();
    }

    public static function isInterested(int $activeId, int $userId): bool
    {
        if ($activeId <= 0 || $userId <= 0) {
            return false;
        }
        return self::where(['active_id' => $activeId, 'user_id' => $userId])->count() > 0;
    }

    /**
     * @return array{is_interested:int, interest_count:int, action:string}
     */
    public static function toggle(int $activeId, int $userId): array
    {
        $row = self::where(['active_id' => $activeId, 'user_id' => $userId])->find();
        if ($row) {
            $row->delete();
            return [
                'is_interested'   => 0,
                'interest_count'  => self::countByActive($activeId),
                'action'          => 'cancel',
            ];
        }
        self::create([
            'active_id'   => $activeId,
            'user_id'     => $userId,
            'create_time' => time(),
        ]);
        return [
            'is_interested'   => 1,
            'interest_count'  => self::countByActive($activeId),
            'action'          => 'add',
        ];
    }
}
