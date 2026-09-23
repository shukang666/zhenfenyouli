/** 打卡成功后随机展示的生活好习惯提示 */
export const CLOCK_HABIT_TIPS = [
	'饭后散步 15 分钟，助消化也低碳。',
	'购物自带环保袋，减少一次性塑料。',
	'随手关灯、拔插头，节约每一度电。',
	'优先选择公交、骑行或步行短途出行。',
	'厨余垃圾沥干水分再投放，减少异味与污染。',
	'旧物改造或捐赠，让资源多流转一次。',
	'双面打印、无纸化办公，省纸也省树。',
	'选购可重复使用的杯具，少买一次性饮料杯。',
	'季节性衣物整理捐赠，减少闲置浪费。',
	'洗菜水用来浇花，一水多用更环保。',
	'垃圾分类前先清空液体，桶更干净、分拣更轻松。',
	'和孩子一起认标签、分垃圾，习惯从小养成。',
]

export function pickRandomHabitTip() {
	const list = CLOCK_HABIT_TIPS
	if (!list.length) return ''
	const i = Math.floor(Math.random() * list.length)
	return list[i]
}
