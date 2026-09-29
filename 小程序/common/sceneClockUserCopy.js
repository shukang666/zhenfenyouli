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
		why: '为什么要分',
		setup: '摆好分类桶',
		points: '选' + HOME_POINT_LABEL_SHORT,
		manage: '改' + HOME_POINT_LABEL_SHORT,
		cate: '选垃圾种类',
		guide: '为什么要分',
		survey: '您的投放习惯',
		learn: '上传分类成果',
	}
}

export function getIntroLead() {
	return '分开扔，是为了少污染、少浪费，也方便社区清运'
}

export function getWhyIntroLead() {
	return '为什么要分？混装一袋，后面很难再利用；分开后厨余能堆肥、可回收能再生，有害也能单独处理'
}

export function getWhyIntroSummary() {
	return '今天先弄清「为啥要分」，再摆好家里各处的桶，选种类、拍成果就能打卡。'
}

/** 建议家里各房间放什么桶（引导页展示，非强制） */
export function getBinLayoutExamples() {
	return [
		{ room: '厨房', cate: '厨余垃圾、其他垃圾', emoji: '🍳', tip: '菜叶、剩饭、果皮放这里，别和塑料袋混一起' },
		{ room: '客厅', cate: '可回收物', emoji: '🛋️', tip: '纸箱、瓶罐、旧杂志，干燥干净再投放' },
		{ room: '卫生间', cate: '其他垃圾', emoji: '🚿', tip: '用过的纸巾、一次性用品等' },
		{ room: '固定一处', cate: '有害垃圾', emoji: '⚠️', tip: '电池、灯管、过期药品，少量攒够再投' },
	]
}

export function getBinSetupLead() {
	return '请对照下面建议，把各处的桶摆到位（名字和种类可以按您家习惯改）'
}

export function getAccumDurationOptions() {
	return ['1 天以内', '2～3 天', '大约一周', '更久']
}

export function getSortDurationOptions() {
	return ['5 分钟以内', '约 15 分钟', '半小时左右', '更久']
}

/** 设置页：一句话说清要做什么 */
export function getSetupIntroLine() {
	return '厨房放厨余桶、客厅放可回收桶……添好名字和种类，给桶拍张照，方便以后打卡对照。'
}

/** 选择页：还没有位置时 */
export function getIntroSummary() {
	return '在家固定好「扔哪儿」，扔的时候按提示拍几张照片就可以。'
}

/** 展开「了解更多」 */
export function getIntroBlocks() {
	const local =
		'在七贤岭：手机上学会怎么分，扔对了拍个照还能攒积分；全家一起动手，记得更牢。'

	return [
		{
			id: 'what',
			title: '混装会带来什么',
			body: '一袋混装，后面很难分拣再利用；厨余混进其他垃圾还容易发臭、招虫，处理成本也更高。',
		},
		{
			id: 'benefit',
			title: '分开之后的好处',
			body: '厨余可以专门处理，纸箱瓶罐能回收再造，电池药品单独收更安全。小区更干净，资源也少浪费。',
		},
		{
			id: 'policy',
			title: '社区一起参与',
			body: '现在都要做垃圾分类。' + local,
		},
		{
			id: 'vision',
			title: '打卡在记录什么',
			body: '不是考你会不会分，而是记录您认真分类的成果，用得勤有积分，邻里一起把习惯留住。',
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
			desc: '在家、上班或店里——选您今天主要在哪儿扔垃圾。',
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
		{ hub: 'home', emoji: '🏠', title: '家庭场景', desc: '厨房、客厅、卫生间，全家日常投放', theme: 'home' },
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

/** 打卡成功弹层：下一步引导 */
export function getClockSuccessNextTitle() {
	return '接下来您可以'
}

export function getClockSuccessOtherCateTitle() {
	return '换一类，再练一次'
}

export function getClockSuccessOtherCateBody(activeName, otherNames) {
	const cur = (activeName || '').trim()
	const others = (otherNames || []).filter(n => n && n !== cur)
	if (!others.length) {
		return '四类垃圾都可以打卡，习惯是慢慢养成的。想再练一次，点下面按钮选种类就行。'
	}
	const sample = others.slice(0, 3).join('、')
	const tail = others.length > 3 ? ' 等' : ''
	if (cur) {
		return `这次记的是「${cur}」。还可以试试 ${sample}${tail}，有害垃圾、可回收物换着练，分类会更熟练。`
	}
	return `还可以试试 ${sample}${tail}，换种类再来一次，积分同样会记上。`
}

export function getClockSuccessPromoTitle() {
	return '顺路参加社区活动'
}

export function getClockSuccessPromoBody() {
	return '打卡之余，社区里也有不少活动可以参与，既能长知识也能攒积分。给您挑了一个热门的：'
}

export function getClockSuccessPromoEmptyBody() {
	return '也可以打开「活动」页，看看最近有什么线下体验、轻活动，有空参加一下。'
}

export function getClockSuccessPromoMoreLabel(count) {
	if (count <= 0) return ''
	return `还有 ${count} 个活动可以看看`
}
