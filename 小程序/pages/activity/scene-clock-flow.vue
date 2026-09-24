<template>
	<view :class="['scf-page', hubTheme]" v-if="hub">
		<view class="scf-hero">
			<text class="scf-hero-kicker">{{ hub.title }}</text>
			<text class="scf-hero-title">{{ stepTitle }}</text>
			<view class="scf-steps">
				<view
					v-for="(lab, idx) in visibleStepLabels"
					:key="idx"
					class="scf-step"
					:class="{ 'scf-step--on': stepIndex >= idx, 'scf-step--cur': stepIndex === idx }"
				>
					<text class="scf-step-num">{{ idx + 1 }}</text>
					<text class="scf-step-lab">{{ lab }}</text>
				</view>
			</view>
		</view>

		<!-- 1 子场景 -->
		<view class="scf-body" v-if="flowStep === 'sub'">
			<text class="scf-hint">请选择具体情境</text>
			<view class="scf-sub-grid">
				<view
					v-for="sub in subList"
					:key="sub.key"
					class="scf-sub-card"
					hover-class="scf-card--press"
					@tap="pickSub(sub)"
				>
					<text class="scf-sub-emoji">{{ sub.emoji }}</text>
					<text class="scf-sub-name">{{ sub.name }}</text>
					<text class="scf-sub-desc">{{ sub.desc }}</text>
				</view>
			</view>
		</view>

		<!-- 2 垃圾种类 -->
		<view class="scf-body" v-if="flowStep === 'cate'">
			<text class="scf-hint" v-if="subName">「{{ subName }}」请选择垃圾种类</text>
			<text class="scf-hint" v-else>请选择垃圾种类</text>
			<view class="scf-cate-grid">
				<view
					v-for="(label, idx) in cateLabels"
					:key="idx"
					class="scf-cate-card"
					:class="'scf-cate-card--' + cateTone(label)"
					hover-class="scf-card--press"
					@tap="pickCate(label)"
				>
					<text class="scf-cate-name">{{ label }}</text>
					<text class="scf-cate-go">选择 ›</text>
				</view>
			</view>
			<view class="scf-foot" v-if="hasSubStep">
				<u-button type="default" hover-class="none" :custom-style="btnGhost" @click="flowStep = 'sub'">上一步</u-button>
			</view>
		</view>

		<!-- 3 处理指引 -->
		<view class="scf-body" v-if="flowStep === 'guide'">
			<view class="scf-panel">
				<text class="scf-panel-tag">{{ cateLabel }}</text>
				<view class="scf-info-block">
					<text class="scf-info-title">如何处理</text>
					<text class="scf-info-text">{{ guide.handle_guide }}</text>
				</view>
				<view class="scf-info-block scf-info-block--time">
					<text class="scf-info-title">建议投放时间</text>
					<text class="scf-info-text">{{ guide.dispose_time }}</text>
				</view>
			</view>
			<view class="scf-foot">
				<u-button type="default" hover-class="none" :custom-style="btnGhost" @click="flowStep = 'cate'">上一步</u-button>
				<u-button type="success" hover-class="none" :custom-style="btnPrimary" @click="flowStep = 'survey'">下一步</u-button>
			</view>
		</view>

		<!-- 4 问卷 / 技巧 -->
		<view class="scf-body" v-if="flowStep === 'survey'">
			<text class="scf-hint">{{ guide.survey_question }}</text>
			<view class="scf-tip-list">
				<view
					v-for="(tip, idx) in guide.preset_tips"
					:key="idx"
					class="scf-tip-chip"
					:class="{ 'scf-tip-chip--on': userChoice === tip }"
					@tap="userChoice = tip"
				>
					<text>{{ tip }}</text>
				</view>
			</view>
			<view class="scf-custom">
				<text class="scf-custom-label">或写下您的分类小技巧</text>
				<u-input v-model="customTip" type="textarea" height="120" placeholder="选填，分享您的经验" />
			</view>
			<view class="scf-foot">
				<u-button type="default" hover-class="none" :custom-style="btnGhost" @click="flowStep = 'guide'">上一步</u-button>
				<u-button type="success" hover-class="none" :custom-style="btnPrimary" @click="goLearnStep">下一步</u-button>
			</view>
		</view>

		<!-- 5 学习更多 / 打卡 -->
		<view class="scf-body" v-if="flowStep === 'learn'">
			<view class="scf-learn-card">
				<text class="scf-learn-title">是否想学习更多分类技巧？</text>
				<text class="scf-learn-desc">系统学习分类知识，打卡更有把握</text>
				<view class="scf-learn-actions">
					<u-button type="success" hover-class="none" :custom-style="btnPrimary" @click="goLearn">去学习</u-button>
					<u-button type="primary" hover-class="none" :custom-style="btnBlue" @click="goClock">去拍照打卡</u-button>
				</view>
				<view class="scf-learn-skip" @tap="finishOnly">稍后再说</view>
			</view>
			<view class="scf-foot">
				<u-button type="default" hover-class="none" :custom-style="btnGhost" @click="flowStep = 'survey'">上一步</u-button>
			</view>
		</view>
	</view>
</template>

<script>
	import { getHub, mergeGuide } from '@/common/sceneClockHub.js'
	import { resolveCateByLabel, resolveCateKind } from '@/common/clockScene.js'

	export default {
		data() {
			return {
				hubKey: '',
				hub: null,
				flowStep: 'sub',
				subKey: '',
				subName: '',
				cateLabel: '',
				cateId: 0,
				cateList: [],
				guide: {
					handle_guide: '',
					dispose_time: '',
					survey_question: '',
					preset_tips: [],
					learn_path: '/pages/flhd/list',
				},
				userChoice: '',
				customTip: '',
				btnPrimary: {
					background: 'linear-gradient(141deg, #A4DB88 0%, #18AF38 100%)',
					border: 'none',
				},
				btnBlue: {
					background: 'linear-gradient(141deg, #5aa8ff 0%, #1877f2 100%)',
					border: 'none',
				},
				btnGhost: {
					background: '#f0f2f1',
					color: '#555',
					border: 'none',
				},
			}
		},
		computed: {
			hubTheme() {
				return (this.hub && this.hub.theme) || 'scf--home'
			},
			subList() {
				if (this.hub && this.hub.subs && this.hub.subs.length) return this.hub.subs
				return []
			},
			hasSubStep() {
				return this.subList.length > 0
			},
			stepIndex() {
				const order = this.hasSubStep
					? ['sub', 'cate', 'guide', 'survey', 'learn']
					: ['cate', 'guide', 'survey', 'learn']
				return order.indexOf(this.flowStep)
			},
			visibleStepLabels() {
				const base = []
				if (this.hasSubStep) base.push('选情境')
				base.push('选种类', '怎么处理', '小技巧', '下一步')
				return base
			},
			cateLabels() {
				if (this.subKey && this.subList.length) {
					const sub = this.subList.find(s => s.key === this.subKey)
					if (sub && sub.cates) return sub.cates
				}
				if (this.hub && this.hub.cates) return this.hub.cates
				return []
			},
			stepTitle() {
				const map = {
					sub: '选择情境',
					cate: '选择垃圾种类',
					guide: '分类指引',
					survey: '分享分类技巧',
					learn: '继续学习',
				}
				return map[this.flowStep] || ''
			},
		},
		onLoad(option) {
			const hubKey = option.hub ? String(option.hub) : 'home'
			this.hubKey = hubKey
			this.hub = getHub(hubKey)
			if (!this.hub) {
				this.$u.toast('场景无效')
				setTimeout(() => uni.navigateBack(), 800)
				return
			}
			uni.setNavigationBarTitle({ title: this.hub.title })
			this.flowStep = this.hasSubStep ? 'sub' : 'cate'
			this.loadCateList()
		},
		methods: {
			loadCateList() {
				this.$u.api.get_select_list({ type: 2 }).then(res => {
					this.cateList = res.data || []
				}).catch(() => {})
			},
			cateTone(label) {
				const kind = resolveCateKind(label)
				return kind || 'other'
			},
			pickSub(sub) {
				this.subKey = sub.key
				this.subName = sub.name
				this.flowStep = 'cate'
			},
			pickCate(label) {
				this.cateLabel = label
				const item = resolveCateByLabel(this.cateList, label)
				this.cateId = item ? item.id : 0
				this.loadGuide()
				this.flowStep = 'guide'
			},
			loadGuide() {
				const def = mergeGuide(null, this.hubKey, this.subKey, this.cateLabel)
				this.guide = def
				this.$u.api.get_clock_scene_guide({
					hub_key: this.hubKey,
					sub_key: this.subKey,
					cate_label: this.cateLabel,
				}).then(res => {
					const row = res.data
					if (row && (row.handle_guide || (row.preset_tips && row.preset_tips.length))) {
						this.guide = mergeGuide(row, this.hubKey, this.subKey, this.cateLabel)
					}
				}).catch(() => {})
			},
			goLearnStep() {
				if (!this.userChoice && !this.customTip.trim()) {
					return this.$u.toast('请选择或填写一种方法')
				}
				this.$u.api.submit_clock_scene_survey({
					hub_key: this.hubKey,
					sub_key: this.subKey,
					cate_label: this.cateLabel,
					user_choice: this.userChoice,
					custom_tip: this.customTip,
				}).catch(() => {})
				this.flowStep = 'learn'
			},
			goLearn() {
				const path = this.guide.learn_path || '/pages/flhd/list'
				this.$u.route(path)
			},
			goClock() {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				if (!this.userInfo.community_id) return this.$u.route('/pages/member/info')
				if (!this.cateId) return this.$u.toast('暂未匹配到垃圾种类，请稍后重试')
				const sceneKey = this.hub.sceneKey
				const sceneName = this.subName || this.hub.title
				let url =
					'/pages/flhd/add?scene_key=' +
					encodeURIComponent(sceneKey) +
					'&scene=' +
					encodeURIComponent(sceneName) +
					'&cate_id=' +
					this.cateId +
					'&sub_scene=' +
					encodeURIComponent(this.subKey || '')
				this.$u.route(url)
			},
			finishOnly() {
				uni.navigateBack()
			},
		},
	}
</script>

<style lang="scss" scoped>
.scf-page {
	min-height: 100vh;
	padding-bottom: calc(32rpx + env(safe-area-inset-bottom));
}
.scf--home {
	background: linear-gradient(180deg, #e8f5ea 0%, #f5f7f6 280rpx);
}
.scf--qinzi {
	background: linear-gradient(180deg, #ffe8e4 0%, #faf8f5 280rpx);
}
.scf--office {
	background: linear-gradient(180deg, #e4eef5 0%, #f5f7f9 280rpx);
}
.scf--street {
	background: linear-gradient(180deg, #dff5e8 0%, #f4f7f5 280rpx);
}
.scf-hero {
	padding: 28rpx 28rpx 16rpx;
}
.scf-hero-kicker {
	font-size: 24rpx;
	color: #6a756c;
}
.scf-hero-title {
	display: block;
	margin-top: 8rpx;
	font-size: 40rpx;
	font-weight: 800;
	color: #142018;
}
.scf-steps {
	display: flex;
	flex-wrap: wrap;
	gap: 8rpx;
	margin-top: 20rpx;
}
.scf-step {
	display: flex;
	align-items: center;
	gap: 6rpx;
	padding: 6rpx 12rpx;
	border-radius: 999rpx;
	background: rgba(255, 255, 255, 0.65);
	opacity: 0.55;
}
.scf-step--on {
	opacity: 1;
}
.scf-step--cur {
	background: #fff;
	box-shadow: 0 4rpx 12rpx rgba(20, 32, 24, 0.08);
}
.scf-step-num {
	font-size: 20rpx;
	font-weight: 700;
	color: #18af38;
}
.scf-step-lab {
	font-size: 20rpx;
	color: #3d4a42;
}
.scf-body {
	padding: 8rpx 24rpx 24rpx;
}
.scf-hint {
	display: block;
	font-size: 28rpx;
	color: #4a564e;
	margin-bottom: 16rpx;
}
.scf-sub-grid {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 16rpx;
}
.scf-sub-card {
	background: #fff;
	border-radius: 20rpx;
	padding: 24rpx 20rpx;
	box-shadow: 0 8rpx 24rpx rgba(20, 40, 28, 0.08);
}
.scf-card--press {
	opacity: 0.92;
	transform: scale(0.99);
}
.scf-sub-emoji {
	font-size: 40rpx;
}
.scf-sub-name {
	display: block;
	margin-top: 10rpx;
	font-size: 30rpx;
	font-weight: 700;
	color: #142018;
}
.scf-sub-desc {
	display: block;
	margin-top: 6rpx;
	font-size: 22rpx;
	color: #7a857c;
	line-height: 1.4;
}
.scf-cate-grid {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 14rpx;
}
.scf-cate-card {
	border-radius: 18rpx;
	padding: 22rpx 18rpx;
	color: #fff;
	min-height: 100rpx;
	display: flex;
	flex-direction: column;
	justify-content: space-between;
}
.scf-cate-card--recycle {
	background: linear-gradient(141deg, #2b8bb8, #156a90);
}
.scf-cate-card--food {
	background: linear-gradient(141deg, #3d9e4a, #1a7a32);
}
.scf-cate-card--harm {
	background: linear-gradient(141deg, #c45c8a, #9a3366);
}
.scf-cate-card--other {
	background: linear-gradient(141deg, #7a8494, #4a5568);
}
.scf-cate-name {
	font-size: 30rpx;
	font-weight: 700;
}
.scf-cate-go {
	font-size: 24rpx;
	opacity: 0.9;
	margin-top: 8rpx;
}
.scf-panel {
	background: #fff;
	border-radius: 20rpx;
	padding: 24rpx;
	box-shadow: 0 8rpx 24rpx rgba(20, 40, 28, 0.06);
}
.scf-panel-tag {
	display: inline-block;
	font-size: 22rpx;
	font-weight: 700;
	color: #18af38;
	background: rgba(24, 175, 56, 0.12);
	padding: 6rpx 16rpx;
	border-radius: 999rpx;
	margin-bottom: 16rpx;
}
.scf-info-block {
	margin-bottom: 20rpx;
}
.scf-info-block--time {
	padding-top: 16rpx;
	border-top: 1rpx solid #eef0ee;
}
.scf-info-title {
	display: block;
	font-size: 26rpx;
	font-weight: 700;
	color: #243028;
	margin-bottom: 8rpx;
}
.scf-info-text {
	font-size: 26rpx;
	color: #4a564e;
	line-height: 1.65;
}
.scf-tip-list {
	display: flex;
	flex-direction: column;
	gap: 12rpx;
}
.scf-tip-chip {
	background: #fff;
	border-radius: 14rpx;
	padding: 18rpx 20rpx;
	border: 2rpx solid transparent;
	font-size: 26rpx;
	color: #3d4a42;
}
.scf-tip-chip--on {
	border-color: #18af38;
	background: rgba(24, 175, 56, 0.08);
}
.scf-custom {
	margin-top: 20rpx;
	background: #fff;
	border-radius: 16rpx;
	padding: 16rpx;
}
.scf-custom-label {
	display: block;
	font-size: 24rpx;
	color: #7a857c;
	margin-bottom: 8rpx;
}
.scf-learn-card {
	background: #fff;
	border-radius: 20rpx;
	padding: 32rpx 24rpx;
	text-align: center;
	box-shadow: 0 8rpx 24rpx rgba(20, 40, 28, 0.08);
}
.scf-learn-title {
	font-size: 32rpx;
	font-weight: 800;
	color: #142018;
}
.scf-learn-desc {
	display: block;
	margin-top: 12rpx;
	font-size: 26rpx;
	color: #6a756c;
}
.scf-learn-actions {
	margin-top: 28rpx;
	display: flex;
	flex-direction: column;
	gap: 16rpx;
}
.scf-learn-skip {
	margin-top: 24rpx;
	font-size: 26rpx;
	color: #8a958c;
}
.scf-foot {
	margin-top: 28rpx;
	display: flex;
	gap: 16rpx;
	justify-content: space-between;
}
.scf-foot .u-btn {
	flex: 1;
}
</style>
