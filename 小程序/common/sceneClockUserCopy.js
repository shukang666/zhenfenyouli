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
		guide: '怎么扔才对',
		survey: '说说您的习惯',
		learn: '拍照提交',
	}
}

export function getIntroLead(hubKey) {
	if (hubKey === 'qinzi') return '带着孩子，垃圾先分清再扔'
	return '垃圾先分清，再扔进对的桶'
}

/** 设置页：一句话说清要做什么 */
export function getSetupIntroLine(hubKey) {
	if (hubKey === 'qinzi') {
		return '请先添一个平时扔垃圾的地方（如装菜叶的桶）。起个名、选种类、给桶拍张照。'
	}
	return '请先添一个平时扔垃圾的地方（如厨房桶、装瓶罐的袋子）。起个名、选种类、给桶拍张照。'
}

/** 选择页：还没有位置时 */
export function getIntroSummary(hubKey) {
	if (hubKey === 'qinzi') return '在家固定好扔哪儿，扔的时候按提示拍照片就行。'
	return '在家固定好扔哪儿，扔的时候按提示拍照片就行。'
}

/** 展开「了解更多」 */
export function getIntroBlocks(hubKey) {
	const local =
		hubKey === 'qinzi'
			? '七贤岭这边：手机上学会怎么分，扔对了拍个照，还能攒积分；带孩子一起更好记。'
			: '七贤岭这边：手机上学会怎么分，扔对了拍个照，还能攒积分。'

	return [
		{
			id: 'what',
			title: '为啥要分一分',
			body: '菜叶剩饭、瓶罐纸板、电池药品等要分开，别混在一个袋子里，投进对应桶，小区干净，也不容易被说扔错了。',
		},
		{
			id: 'policy',
			title: '国家跟社区的要求',
			body: '现在城镇都要做垃圾分类。' + local,
		},
		{
			id: 'vision',
			title: '这个程序帮什么忙',
			body: '教怎么分、提醒别乱扔，用得勤有积分，邻里一起把环境搞好。',
		},
	]
}

export function getCommunityEmptyTitle(isStreetHub, isOfficeHub) {
	if (isStreetHub) return '店里还没安排投放点'
	if (isOfficeHub) return '楼里还没安排投放点'
	return '还没安排投放点'
}

export function getCommunityEmptyDesc() {
	return '社区会根据商铺或单位情况安排扔垃圾的地方，弄好后会出现在这里。不清楚就问社区或物业。'
}

export function getCommunityIntroLead(isStreetHub) {
	if (isStreetHub) return '到社区安排的扔垃圾点'
	return '到楼里统一的扔垃圾点'
}

export function getCommunityPointsDesc() {
	return '社区已经安排好了扔垃圾的地方，点一个，按下面步骤拍照提交。'
}

export function getPointsPickSummary() {
	return '点您这次要用的桶或位置，跟着提示拍照就行。'
}

export function getPointsStepHint(isCommunityHub) {
	if (isCommunityHub) return '选扔垃圾的地方'
	return '选这次用的位置'
}

/** 活动 Banner → 真分打卡主题引导页（原型 Screen 2） */
export function getThemeIntroHeadline() {
	return '欢迎参与垃圾分类真分打卡'
}

export function getThemeIntroSubline() {
	return '从识别、分类到投放与记录，四步养成准确分类习惯'
}

export function getThemeIntroSteps() {
	return [
		{
			num: '1',
			title: '生活场景引导',
			desc: '在厨房、客厅、办公或商铺等场景，先看清今天产生了哪些垃圾。',
			icon: '🏠',
		},
		{
			num: '2',
			title: '分类判断',
			desc: '按可回收、厨余、有害、其他等要求分好类，不确定可回看分类指导。',
			icon: '♻️',
		},
		{
			num: '3',
			title: '实际投放',
			desc: '到对应桶或社区投放点，按指引完成真实投放，不混投、不错投。',
			icon: '🗑️',
		},
		{
			num: '4',
			title: '记录与反馈',
			desc: '拍照记录本次投放，提交后获得积分与习惯反馈，坚持更易养成真分习惯。',
			icon: '📷',
		},
	]
}

export function getThemeIntroCta() {
	return '我知道了，开始真分'
}

/** 场景选择页（原型 Screen 4） */
export function getScenePickNavTitle() {
	return '真分打卡'
}

export function getScenePickHint() {
	return '选择今天的打卡场景'
}

export function getSceneHubCards() {
	return [
		{ hub: 'home', emoji: '🏠', title: '家庭真分', desc: '从居家开始，分对了更简单', theme: 'home' },
		{ hub: 'qinzi', emoji: '👨‍👩‍👧', title: '亲子真分', desc: '陪孩子一起，记得更牢', theme: 'qinzi' },
		{ hub: 'office', emoji: '🏢', title: '楼宇真分', desc: '办公区域，分类更有序', theme: 'office' },
		{ hub: 'street', emoji: '🏪', title: '商铺真分', desc: '沿街门店，干净又规范', theme: 'street' },
	]
}

/** 任务说明（原型 Screen 5） */
export function getThemeBriefTagline() {
	return '把垃圾分对，就是最好的生活方式'
}

export function getThemeBriefTaskTitle() {
	return '今日真分任务'
}

export function getThemeBriefTasks() {
	return [
		{ icon: '📋', label: '分类判断', sub: '先分清再投放' },
		{ icon: '🗑️', label: '实际投放', sub: '投到对应桶' },
		{ icon: '📷', label: '拍照记录', sub: '记录本次真分' },
	]
}

export function getThemeBriefCta() {
	return '开始真分'
}

export function getThemeIntroHeroSlogan() {
	return { line1: '垃圾分类', line2: '从我做起 让绿色成为习惯' }
}

export function getThemePromoHeadline() {
	return '从一次真分开始\n让绿色融入生活'
}

export function getThemePromoPillars() {
	return [
		{ icon: '🗂️', label: '分类投放' },
		{ icon: '♻️', label: '资源循环' },
		{ icon: '🌿', label: '绿色生活' },
		{ icon: '🤝', label: '共建共享' },
	]
}
