/**
 * 场景打卡流说明文案（短句、大字、口语，方便老年居民）
 */

/** 不说「真分点」，用家里能听懂的说法 */
export const HOME_POINT_LABEL = '家里分类位置'
export const HOME_POINT_LABEL_SHORT = '分类位置'
export const COMMUNITY_POINT_LABEL = '社区投放点'

export function getStepTitles() {
	return {
		hub_empty: '请稍等',
		setup: '设置' + HOME_POINT_LABEL,
		points: '选' + HOME_POINT_LABEL_SHORT,
		manage: '改' + HOME_POINT_LABEL_SHORT,
		cate: '选垃圾种类',
		guide: '这一步怎么扔',
		survey: '您的投放习惯',
		learn: '拍照提交',
	}
}

export function getIntroLead(hubKey) {
	if (hubKey === 'qinzi') return '带着孩子，先把垃圾分清楚再扔'
	return '先把垃圾分清楚，再扔进对应的桶'
}

/** 设置页：一句话说清要做什么 */
export function getSetupIntroLine(hubKey) {
	if (hubKey === 'qinzi') {
		return '先添一个平时扔垃圾的地方（比如装菜叶的桶）。起个容易记的名字、选种类、给桶拍张照就行。'
	}
	return '先添一个平时扔垃圾的地方（比如厨房桶、装瓶罐的袋子）。起个名字、选种类、给桶拍张照就行。'
}

/** 选择页：还没有位置时 */
export function getIntroSummary(hubKey) {
	if (hubKey === 'qinzi') return '在家固定好「扔哪儿」，扔的时候按提示拍几张照片就可以。'
	return '在家固定好「扔哪儿」，扔的时候按提示拍几张照片就可以。'
}

/** 展开「了解更多」 */
export function getIntroBlocks(hubKey) {
	const local =
		hubKey === 'qinzi'
			? '在七贤岭：手机上学会怎么分，扔对了拍个照还能攒积分；带孩子一起做，记得更牢。'
			: '在七贤岭：手机上学会怎么分，扔对了拍个照还能攒积分。'

	return [
		{
			id: 'what',
			title: '为啥要分开扔',
			body: '菜叶剩饭、瓶罐纸板、电池药品等要分开，别混在一个袋子里。投进对应颜色的桶，小区更干净，也不容易投错。',
		},
		{
			id: 'policy',
			title: '社区的要求',
			body: '现在都要做垃圾分类。' + local,
		},
		{
			id: 'vision',
			title: '这个小程序帮什么忙',
			body: '教怎么分、提醒别乱扔，用得勤有积分，大家一起把环境搞好。',
		},
	]
}

export function getCommunityEmptyTitle(isStreetHub, isOfficeHub) {
	if (isStreetHub) return '店里还没安排投放点'
	if (isOfficeHub) return '楼里还没安排投放点'
	return '还没安排投放点'
}

export function getCommunityEmptyDesc() {
	return '社区会根据商铺或单位情况安排扔垃圾的地方，弄好后会出现在这里。不清楚可以问社区或物业。'
}

export function getCommunityIntroLead(isStreetHub) {
	if (isStreetHub) return '到社区安排的扔垃圾点'
	return '到楼里统一的扔垃圾点'
}

export function getCommunityPointsDesc() {
	return '社区已经安排好了扔垃圾的地方。点一个位置，按下面步骤拍照提交就行。'
}

export function getPointsPickSummary() {
	return '点您这次要用的桶或位置，跟着提示一步步拍照即可。'
}

export function getPointsStepHint(isCommunityHub) {
	if (isCommunityHub) return '选这次要用的投放点'
	return '点一下您这次要用的桶或位置'
}

/** 活动 Banner → 打卡引导页（原型 Screen 2） */
export function getThemeIntroHeadline() {
	return '欢迎来垃圾分类打卡'
}

export function getThemeIntroSubline() {
	return '不用一次做完，跟着下面四步，慢慢就养成习惯'
}

export function getThemeIntroSteps() {
	return [
		{
			num: '1',
			title: '选对场景',
			desc: '在家、带孩子、上班或店里——选您今天主要在哪儿扔垃圾。',
			icon: '🏠',
		},
		{
			num: '2',
			title: '分清种类',
			desc: '厨余、可回收、有害、其他要分开。拿不准可以回看分类说明。',
			icon: '♻️',
		},
		{
			num: '3',
			title: '动手投放',
			desc: '按提示扔进对应颜色的桶，别混装、别投错桶。',
			icon: '🗑️',
		},
		{
			num: '4',
			title: '拍照留记录',
			desc: '拍清楚本次投放，提交后可得积分，坚持做更容易坚持。',
			icon: '📷',
		},
	]
}

export function getThemeIntroCta() {
	return '我准备好了，开始'
}

/** 场景选择页（原型 Screen 4） */
export function getScenePickNavTitle() {
	return '垃圾分类打卡'
}

export function getScenePickHint() {
	return '您今天主要在哪儿扔垃圾？点选一个就行'
}

export function getSceneHubCards() {
	return [
		{ hub: 'home', emoji: '🏠', title: '在家', desc: '厨房、客厅、卫生间日常垃圾', theme: 'home' },
		{ hub: 'qinzi', emoji: '👨‍👩‍👧', title: '带孩子一起', desc: '边分边教，孩子记得更牢', theme: 'qinzi' },
		{ hub: 'office', emoji: '🏢', title: '上班 / 楼宇', desc: '工位、茶水间、办公区', theme: 'office' },
		{ hub: 'street', emoji: '🏪', title: '沿街商铺', desc: '门店前后堂产生的垃圾', theme: 'street' },
	]
}

/** 任务说明（原型 Screen 5） */
export function getThemeBriefTagline() {
	return '分对了、扔对了，就是在为绿色生活出一份力'
}

export function getThemeBriefTaskTitle() {
	return '接下来您要做的事'
}

export function getThemeBriefTasks() {
	return [
		{ icon: '📋', label: '看清是什么垃圾', sub: '先分清属于哪一类' },
		{ icon: '🗑️', label: '投进对的桶', sub: '按颜色对应四分类' },
		{ icon: '📷', label: '拍照片提交', sub: '拍清楚就算完成本次' },
	]
}

export function getThemeBriefCta() {
	return '开始本次打卡'
}

export function getThemeIntroHeroSlogan() {
	return { line1: '垃圾分类', line2: '从我做起 让绿色成为习惯' }
}

export function getThemePromoHeadline() {
	return '从一次认真分类开始\n绿色就会慢慢融进日常'
}

export function getThemePromoPillars() {
	return [
		{ icon: '🗂️', label: '分对类' },
		{ icon: '♻️', label: '少浪费' },
		{ icon: '🌿', label: '更环保' },
		{ icon: '🤝', label: '邻里共建' },
	]
}
