#!/bin/bash
# 一次性脚本：把本地 dev/master 历史提交中的无效 noreply 邮箱
# 批量替换为 Gitee 已绑定的真实邮箱，保留全部原始时间戳（author/committer date 不变）。
set -e

OLD_EMAIL="17483075shukang990@user.noreply.gitee.com"
NEW_EMAIL="19909851865@163.com"

echo "==> 1/4 备份远程当前状态到本地 tag（回滚用）"
git tag -f backup-old-origin-master origin/master
git tag -f backup-old-origin-dev origin/dev
echo "    备份完成: backup-old-origin-master / backup-old-origin-dev"

echo "==> 2/4 重写 dev / master 历史邮箱（时间戳保持不变）"
FILTER='if [ "$GIT_AUTHOR_EMAIL" = "'"$OLD_EMAIL"'" ]; then export GIT_AUTHOR_EMAIL="'"$NEW_EMAIL"'"; fi
if [ "$GIT_COMMITTER_EMAIL" = "'"$OLD_EMAIL"'" ]; then export GIT_COMMITTER_EMAIL="'"$NEW_EMAIL"'"; fi'
git filter-branch -f --env-filter "$FILTER" -- dev master

echo "==> 3/4 强制推送重写后的 dev 与 master"
git push --force origin dev master

echo "==> 4/4 验证：2026-08-26 的提交 author 邮箱"
git --no-pager log master --format='%h|%ad|%ae|%s' --date=format:'%Y-%m-%d' | grep '^.\{7,7\}|2026-08-26|' | sort -u || echo "（若上面为空，说明 8/26 的提交不在 master 历史中）"
echo "完成。"
