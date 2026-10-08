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
		setup: '设置分类桶',
		points: '选桶打卡',
		manage: '改' + HOME_POINT_LABEL_SHORT,
		cate: '选桶打卡',
		guide: '为什么要分',
		survey: '您的投放习惯',
		learn: '上传分类成果',
	}
}

export function getIntroLead() {
	return '分开扔，少污染、少浪费，也方便社区清运'
}

export function getWhyIntroLead() {
	return '为什么要分？分开扔才好回收；混装一袋，后面很难再利用。'
}

export function getWhyIntroSummary() {
	return '看下面要点，选好生活场景，按提示打卡即可'
}

/** 建议家里各房间放什么桶（户型示意用 planId 定位格子） */
export function getBinLayoutExamples() {
	return [
		{
			planId: 'kitchen',
			room: '厨房',
			tags: [
				{ label: '厨余', tone: 'kitchen' },
				{ label: '其他', tone: 'other' },
			],
			tip: '菜叶剩饭、果皮，别和塑料袋混装',
		},
		{
			planId: 'bath',
			room: '卫生间',
			tags: [{ label: '其他', tone: 'other' }],
			tip: '纸巾、一次性用品等',
		},
		{
			planId: 'living',
			room: '客厅',
			tags: [{ label: '可回收', tone: 'recycle' }],
			tip: '纸箱、瓶罐、旧杂志，干净干燥再投',
		},
		{
			planId: 'fixed',
			room: '固定一处',
			tags: [{ label: '有害', tone: 'hazard' }],
			tip: '电池、灯管、过期药品，攒够再投',
		},
	]
}

export function getHomePlanTitle() {
	return '常见家里布局（参考）'
}

export function getHomePlanFoot() {
	return '您家户型不同也没关系，按实际情况在下方添加桶即可'
}

export function getBinSetupLead() {
	return '首次使用：请设置您家里的分类桶'
}

export function getBinSetupLeadNote() {
	return '首次设置即可；保存后下次直接选桶打卡'
}

export function getAccumDurationOptions() {
	return ['1 天以内', '2～3 天', '大约一周', '更久']
}

export function getSortDurationOptions() {
	return ['5 分钟以内', '约 15 分钟', '半小时左右', '更久']
}

/** 设置页：一句话说清要做什么 */
export function getSetupIntroLine() {
	return '按房间添桶、选种类，拍张照，以后打卡好对照。'
}

/** 选择页：还没有位置时 */
export function getIntroSummary() {
	return '在家定好扔哪儿，按提示拍照提交即可。'
}

/** 展开「了解更多」 */
export function getIntroBlocks() {
	const local = '在七贤岭：手机学分类，扔对拍照可攒积分。'

	return [
		{
			id: 'what',
			title: '混装会带来什么',
			body: '混装难再利用，厨余混进其他垃圾还容易发臭、招虫。',
		},
		{
			id: 'benefit',
			title: '分开之后的好处',
			body: '厨余、可回收、有害分开收，小区更干净，资源少浪费。',
		},
		{
			id: 'policy',
			title: '社区一起参与',
			body: '垃圾分类人人有责。' + local,
		},
		{
			id: 'vision',
			title: '打卡在记录什么',
			body: '记录您认真分类的成果，不是考试；常用有积分。',
		},
	]
}

export function getCommunityEmptyTitle(isStreetHub, isOfficeHub) {
	if (isStreetHub) return '店里还没安排投放点'
	if (isOfficeHub) return '办公真分角尚未配置'
	return '还没安排投放点'
}

export function getCommunityEmptyDesc(isStreetHub, isOfficeHub) {
	if (isOfficeHub) {
		return '请社区工作人员在后台「办公大楼回收点」中维护大楼名称、回收点地址与示意图。配置完成后重新进入本页即可打卡。'
	}
	if (isStreetHub) {
		return '社区会根据商铺情况安排投放点，弄好后会出现在这里。不清楚可以问社区或物业。'
	}
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

/** 家庭：选桶（种类已在摆桶时绑定） */
export function getResidentialPickLead() {
	return '今天打卡哪一只桶？'
}

export function getResidentialPickSub() {
	return '点选桶位上传成果即可；不必每个桶每天都打，可以次日再来'
}

export function getResidentialSetupNextCta() {
	return '开始打卡'
}

export function getResidentialSetupFootnote() {
	return '保存桶位后，下次进来会直接到这里选桶；今天不必每个桶都打卡，可以次日再来'
}

export function getResidentialBinSettingsFoldTitle() {
	return '管理家里分类桶'
}

export function getResidentialBinSettingsFoldHint() {
	return '增删改桶位（一般不用每次打开）'
}

export function getResidentialPickMaskHint() {
	return '点击高亮的桶开始打卡；点击暗处关闭'
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
	return '四步走，不用一次做完，慢慢养成习惯'
}

export function getThemeIntroSteps() {
	return [
		{
			num: '1',
			title: '选对场景',
			desc: '在家、上班或店里，选您今天主要在哪儿扔。',
			icon: '🏠',
		},
		{
			num: '2',
			title: '分清种类',
			desc: '四类分开；拿不准可看分类说明。',
			icon: '♻️',
		},
		{
			num: '3',
			title: '动手投放',
			desc: '投进对应颜色桶，别混装。',
			icon: '🗑️',
		},
		{
			num: '4',
			title: '拍照留记录',
			desc: '拍清本次投放，提交记积分。',
			icon: '📷',
		},
	]
}

export function getThemeIntroCta() {
	return '我准备好了，开始'
}

/** 场景选择页（原型 Screen 4）——场景指生活情境（家/上班/商铺），不是扔垃圾的物理点位 */
export function getScenePickNavTitle() {
	return '垃圾分类打卡'
}

export function getScenePickKicker() {
	return '选生活场景'
}

export function getScenePickHint() {
	return '选一个和今天最贴近的场景：家里、上班，还是店铺。后面再按场景引导您分类打卡'
}

/** 选场景首屏顶栏文案 */
export function getScenePickWelcomeBanner(returning) {
	if (returning) {
		return '欢迎回来。开始今天的真分吧！'
	}
	return '欢迎参与真分。开始今天的真分吧！'
}

export function getScenePickMainTitle() {
	return '选择你今日的真分场景吧！'
}

/** 选场景前：是否愿意参加真分（仅下方卡片区，上方「为什么要分」不动） */
export function getThemeOptInEyebrow() {
	return '真分打卡'
}

export function getThemeOptInTitle() {
	return '今天愿意认真真分吗？'
}

export function getThemeOptInDesc() {
	return '真分就是分对类别、投进对的桶，并拍照留记录。点「愿意」后，先选和今天最贴近的生活场景（家里、上班或店铺）。'
}

export function getThemeOptInYes() {
	return '愿意，去选场景'
}

export function getThemeOptInLater() {
	return '今天先不打了'
}

export function getSceneHubCards() {
	return [
		{
			hub: 'home',
			illusKey: 'home',
			title: '家庭真分',
			desc: '布置居家环境中的垃圾投放点位，正确分类垃圾，完成真分。',
			theme: 'home',
		},
		{
			hub: 'office',
			illusKey: 'office',
			title: '办公场所真分',
			desc: '在办公区域设置的真分角正确投递可回收物，完成真分。',
			theme: 'office',
		},
		{
			hub: 'street',
			illusKey: 'street',
			title: '商户真分',
			desc: '按店铺类别，布置对应垃圾投放点位，正确分类垃圾，完成真分。',
			theme: 'street',
		},
	]
}

/** 沿街：登记商铺 + 自传投放点照片（同家庭摆桶逻辑） */
export function getStreetMerchantHeroTitle() {
	return '店里也能真分'
}

export function getStreetMerchantHeroSub() {
	return '分得好，店里更清爽'
}

export function getStreetClaimTitle() {
	return '填写公司信息'
}

export function getStreetClaimLead() {
	return ''
}

export function getStreetClaimShopNamePh() {
	return '请输入门店/公司名称'
}

export function getStreetClaimCompanyLabel() {
	return '公司名称'
}

export function getStreetClaimTypeHint() {
	return '公司类型'
}

export function getStreetClaimSubmit() {
	return '下一步，布置店内投放点位'
}

/** 沿街：按商铺大类固定需布置的垃圾分类点位（不可增删种类） */
export function getStreetShopSetupSlotDefs(shopCategory) {
	const cat = String(shopCategory || 'retail')
	if (cat === 'catering') {
		return [
			{
				slotKey: 'shop_food',
				cateKw: '厨余',
				point_name: '店内厨余投放点',
				title: '厨余垃圾投放点',
				desc: '后厨、操作台旁设厨余桶，员工随手分，顾客区可不放。',
				uploadCta: '拍摄厨余桶/投放区',
				uploadDone: '已布置，点击可更换',
				tone: 'food',
				tag: '餐饮',
			},
			{
				slotKey: 'shop_other_c',
				cateKw: '其他',
				point_name: '店内其他垃圾点',
				title: '其他垃圾投放点',
				desc: '餐巾纸、一次性用具等进其他桶，与厨余分开。',
				uploadCta: '拍摄其他垃圾桶位',
				uploadDone: '已布置，点击可更换',
				tone: 'other',
				tag: '餐饮',
			},
		]
	}
	if (cat === 'life') {
		return [
			{
				slotKey: 'shop_other_l',
				cateKw: '其他',
				point_name: '店内其他垃圾点',
				title: '其他垃圾投放点',
				desc: '客用纸巾、清洁耗材等，固定在员工易达的位置。',
				uploadCta: '拍摄其他垃圾投放区',
				uploadDone: '已布置，点击可更换',
				tone: 'other',
				tag: '服务',
			},
			{
				slotKey: 'shop_recycle_l',
				cateKw: '可回收',
				point_name: '店内可回收物点',
				title: '可回收物收集点',
				desc: '纸箱、瓶罐等集中存放，定期清运或售卖。',
				uploadCta: '拍摄可回收收集区',
				uploadDone: '已布置，点击可更换',
				tone: 'recycle',
				tag: '服务',
			},
		]
	}
	return [
		{
			slotKey: 'shop_recycle_r',
			cateKw: '可回收',
			point_name: '店内可回收物点',
			title: '可回收物收集点',
			desc: '纸箱、包装膜等可回收物集中堆放位置。',
			uploadCta: '拍摄可回收收集区',
			uploadDone: '已布置，点击可更换',
			tone: 'recycle',
			tag: '零售',
		},
		{
			slotKey: 'shop_other_r',
			cateKw: '其他',
			point_name: '店内其他垃圾点',
			title: '其他垃圾投放点',
			desc: '不可回收的包装、杂物等，与可回收分开。',
			uploadCta: '拍摄其他垃圾投放区',
			uploadDone: '已布置，点击可更换',
			tone: 'other',
			tag: '零售',
		},
	]
}

export function getStreetSetupPageTitle() {
	return '布置店内分类投放点'
}

export function getStreetSetupPageSub() {
	return '按您店经营类型拍清对应点位，完成后即可按种类打卡'
}

export function getStreetSetupFinishCta() {
	return '完成布置，开始今日打卡'
}

export function getStreetClockNavTitle() {
	return '沿街商铺 · 今日真分'
}

export function getStreetClockShopBarHint() {
	return '今日真分'
}

export function getStreetClockStep1Title() {
	return '今天记哪一类？'
}

export function getStreetClockStep1Sub() {
	return '选一类，上传该点位的分类成果'
}

export function getStreetClockStep2Title() {
	return '大概有多少？'
}

export function getStreetClockPhotoTitle() {
	return '上传分类成果'
}

export function getStreetClockPhotoSub() {
	return '对准刚分好的垃圾或桶内成果，拍清楚即可'
}

export function getStreetClockSubmitCta() {
	return '提交今日真分'
}

export function getStreetSetupKicker() {
	return '拍您店的投放点'
}

export function getStreetSetupSub() {
	return '和家里一样：起个名字、拍清楚桶或投放位置，保存后点选即可打卡。'
}

export function getStreetSetupNamePh() {
	return '如：店后垃圾桶、门口分类桶'
}

export function getStreetSetupFootnote() {
	return '种类已按商铺类型定好，不用另选。'
}

export function getStreetSetupNextCta() {
	return '保存，去选点打卡'
}

export function getStreetPickLead() {
	return '点选下面这个投放点，上传分类照片'
}

export function getStreetPickSub() {
	return '需要改照片或名字，点下方「展开」'
}

export function getStreetPickMaskHint() {
	return '轻触空白处返回'
}

export function getStreetBinSettingsFoldTitle() {
	return '改投放点照片或名称'
}

export function getStreetBinSettingsFoldHint() {
	return '展开后可修改，保存即生效'
}

export function getStreetEditShop() {
	return '改店名或类型'
}

/** 任务说明（原型 Screen 5） */
export function getThemeBriefTagline() {
	return '分对、扔对，就是在为绿色生活出力'
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
	return { line1: '垃圾分类', line2: '从身边做起' }
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

export function getClockSuccessOtherBinTitle() {
	return '换一只桶，再记一次'
}

export function getClockSuccessOtherBinBody(activeBinName, otherBinNames) {
	const cur = (activeBinName || '').trim()
	const others = (otherBinNames || []).filter(n => n && n !== cur)
	if (!others.length) {
		return '家里几只桶都可以轮流打卡，习惯是慢慢养成的。'
	}
	const sample = others.slice(0, 3).join('、')
	const tail = others.length > 3 ? ' 等' : ''
	if (cur) {
		return `这次记的是「${cur}」。还可以试试 ${sample}${tail}，换着练分类更熟练。`
	}
	return `还可以试试 ${sample}${tail}，点一下就能继续上传。`
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

/** 家庭场景：固定三类布置点位（不可增删改名称/种类） */
export function getHomeFixedSetupSlots() {
	return [
		{
			slotKey: 'kitchen',
			section: 'start',
			title: '建议上传厨房点位布置',
			desc: '厨房里要放两个桶：一个厨余，一个其他。做饭时顺手分，不用来回跑。',
			exampleCaption: '厨房布置点位示例图',
			exampleImgKey: 'home-setup-example-kitchen',
			uploadCta: '点击开始布置厨房点位',
			uploadDone: '厨房点位已布置，点击可更换照片',
			point_name: '厨房分类点位',
			cateKw: '厨余',
		},
		{
			slotKey: 'recycle',
			section: 'recycle_harm',
			title: '布置可回收物点位',
			desc: '家里找个角落放可回收物，纸箱、瓶子攒着卖。以后打卡可回收物，记得拍售卖照片。',
			exampleCaption: '可回收物点位布置示例图',
			exampleImgKey: 'home-setup-example-recycle',
			uploadCta: '点击开始布置可回收物点位',
			uploadDone: '可回收物点位已布置，点击可更换照片',
			point_name: '可回收物点位',
			cateKw: '可回收',
		},
		{
			slotKey: 'hazard',
			section: 'recycle_harm',
			title: '布置有害垃圾点位',
			desc: '有害垃圾别乱扔，找个盒子专门放。',
			exampleCaption: '有害垃圾点位布置示例图',
			exampleImgKey: 'home-setup-example-hazard',
			uploadCta: '点击开始布置有害垃圾点位',
			uploadDone: '有害垃圾点位已布置，点击可更换照片',
			point_name: '有害垃圾点位',
			cateKw: '有害',
		},
	]
}

export function getHomeFixedSetupHeroTitle() {
	return '布置家庭垃圾分类的点位'
}

export function getHomeFixedSetupHeroSub() {
	return '家里摆好桶，真分不费劲'
}

export function getHomeFixedSectionStartTitle() {
	return '开始布置家庭分类点位吧'
}

export function getHomeFixedSectionRecycleTitle() {
	return '开始布置可回收物和有害垃圾点位'
}

export function getHomeFixedSetupRewardHint() {
	return '完成布置可获得100积分'
}

export function getHomeFixedSetupFinishCta() {
	return '完成全部上传开始今日真分'
}

export function getHomeFixedSetupRequiredHint() {
	return '三处布置照片均需上传，缺一不可'
}

export function getHomeClockNavTitle() {
	return '开始今天的真分吧'
}

export function getHomeClockBannerSlogan() {
	return '把分类融入每一天的生活'
}

export function getHomeClockStep1Title() {
	return '今天真分哪类垃圾？'
}

export function getHomeClockStep1Sub() {
	return '先选今天真实产生、并已分好的一类。'
}

/** 日常 2 分类 vs 四类按周，避免形式化打卡 */
export function getHomeClockSortRhythmNote() {
	return '在家做饭吃饭，日常按「厨余 + 其他」两类投就好；可回收、有害不必每天打卡，一般按周集中清理时再记一次即可。'
}

export function getHomeClockLocationStepTitle() {
	return '选择打卡位置'
}

export function getHomeClockLocationStepHint() {
	return '选您实际投放的一处家庭布置点位。'
}

export function getHomeClockLocationLabel(slotKey) {
	const map = {
		kitchen: '厨房点位（厨余/其他）',
		recycle: '可回收物点位',
		hazard: '有害垃圾点位',
	}
	return map[slotKey] || '布置点位'
}

export function getHomeClockStep2Title() {
	return '今天大约有多少？'
}

export function getHomeClockAmountNote() {
	return '仅作参考，不要求称重'
}

export function getHomeClockStep4Title() {
	return '上传真分照片'
}

export function getHomeClockStep4Sub() {
	return '需要拍下「垃圾+分类容器」的完整画面'
}

export function getHomeClockPhotoExampleCap() {
	return '真分照片示例图'
}

export function getHomeClockPhotoUploadCta() {
	return '点击上传今日真分照片'
}

export function getHomeClockPhotoRules() {
	return [
		{ ok: false, text: '单独拍一个矿泉水瓶' },
		{ ok: false, text: '拍一张已经扔完的垃圾桶' },
		{ ok: false, text: '审核不通过会扣除本日积分' },
		{ ok: true, text: '真实产生的垃圾 → 正确分类 → 准备投递' },
	]
}

export function getHomeClockSubmitCta() {
	return '提交真分'
}

export function getHomeClockFootNote() {
	return '完成后提交，记录今天的真分行动'
}

export function getHomeClockFootPoints() {
	return '审核成功可获得30积分'
}

export function getHomeClockAmountOptions() {
	return ['少量', '一小袋', '一桶', '较多']
}

export function getOfficeClockNavTitle() {
	return '开始今天的真分吧'
}

export function getOfficeClockBannerTitle() {
	return '在办公室也能真分'
}

export function getOfficeClockBannerSub() {
	return '纸箱、瓶子、废纸，攒着投真分角 / 拍摄投递过程，别只拍一个瓶子'
}

export function getOfficeClockBannerBadge() {
	return '每天一次，+30分，攒着好物'
}

export function getOfficeClockRecycleAlertTitle() {
	return '本次只记录可回收物'
}

export function getOfficeClockRecycleAlertBody() {
	return '请投递纸箱、瓶子、纸张等可回收物，并上传投递过程照片。'
}

export function getOfficeClockBuildingTitle() {
	return '你在哪栋楼办公？'
}

export function getOfficeClockBuildingPlaceholder() {
	return '点击选择或搜索办公楼宇'
}

export function getOfficeClockCornerTitle() {
	return '真分角位置'
}

export function getOfficeClockCornerPendingText() {
	return '正在推进中'
}

export function getOfficeClockConfirmTitle() {
	return '真分前先确认'
}

export function getOfficeClockItemTitle() {
	return '今天投了什么？'
}

export function getOfficeClockItemTags() {
	return ['纸箱', '瓶子', '其他']
}

export function getOfficeClockVolumeTags() {
	return ['少', '多']
}

export function getOfficeClockSaveTitle() {
	return '攒了多久？'
}

export function getOfficeClockSaveOptions() {
	return ['今天', '1-3天', '三天以上']
}

export function getOfficeClockPhotoTitle() {
	return '上传真分照片'
}

export function getOfficeClockPhotoUploadCta() {
	return '点击上传可回收物真分投递照片'
}

export function getOfficeClockSubmitCta() {
	return '完成提交，记录今日真分行为'
}
