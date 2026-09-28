<template>
	<view :class="['scf-page', hubTheme]" v-if="hub">
		<view class="scf-hero">
			<text class="scf-hero-kicker">{{ hub.title }}</text>
			<text class="scf-hero-title">{{ stepTitle }}</text>
			<view class="scf-steps" v-if="visibleStepLabels.length">
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

		<!-- 沿街/办公：尚未配置统一回收点 -->
		<view class="scf-body" v-if="flowStep === 'hub_empty'">
			<view class="scf-panel scf-office-tip">
				<text class="scf-office-tip-title">{{ communityEmptyTitle }}</text>
				<text class="scf-info-text">{{ communityEmptyDesc }}</text>
			</view>
			<view class="scf-foot scf-foot--single">
				<view class="scf-btn scf-btn--ghost" hover-class="scf-card--press" @tap="finishOnly">返回活动</view>
			</view>
		</view>

		<!-- 首次：设置真分点（仅家庭/亲子） -->
		<view class="scf-body" v-if="flowStep === 'setup'">
			<text class="scf-hint">首次进入请设置您的真分点。每个点位对应一种垃圾类型，后续可在列表中修改。</text>
			<view class="scf-setup-list">
				<view class="scf-setup-row" v-for="(row, idx) in setupRows" :key="idx">
					<input class="scf-setup-input" v-model="row.point_name" placeholder="点位名称，如厨房桶" maxlength="16" />
					<picker mode="selector" :range="catePickerLabels" :value="row.cate_index" @change="onSetupCateChange(idx, $event)">
						<view class="scf-setup-picker">{{ catePickerLabels[row.cate_index] || '选种类' }}</view>
					</picker>
					<text class="scf-setup-del" v-if="setupRows.length > 1" @tap="removeSetupRow(idx)">删</text>
				</view>
			</view>
			<view class="scf-setup-add" @tap="addSetupRow">+ 添加点位</view>
			<view class="scf-foot scf-foot--single">
				<view class="scf-btn scf-btn--primary" hover-class="scf-card--press" @tap="saveSetup">保存并开始</view>
			</view>
		</view>

		<!-- 真分点列表（打卡入口） -->
		<view class="scf-body" v-if="flowStep === 'points'">
			<view class="scf-panel scf-office-tip scf-points-intro" v-if="isCommunityHub">
				<text class="scf-office-tip-title">{{ communityPointsTitle }}</text>
				<text class="scf-info-text">{{ communityPointsDesc }}</text>
			</view>
			<view class="scf-points-head">
				<text class="scf-hint scf-hint--inline">{{ isCommunityHub ? '选择回收点打卡' : '选择要打卡的真分点' }}</text>
				<text class="scf-manage-link" v-if="!isCommunityHub" @tap="enterManage">管理真分点</text>
			</view>
			<view class="scf-point-grid">
				<view
					v-for="p in points"
					:key="p.id"
					class="scf-point-card"
					hover-class="scf-card--press"
					@tap="pickPointById(p.id)"
				>
					<view class="scf-cate-hero" :class="'scf-cate-hero--' + cateTone(p.cate_name)">
						<text class="scf-cate-hero-kicker">垃圾分类</text>
						<text class="scf-cate-hero-name">{{ p.cate_name }}</text>
					</view>
					<text class="scf-point-name">{{ p.point_name }}</text>
					<text class="scf-point-go">去打卡 ›</text>
				</view>
			</view>
			<view class="scf-foot scf-foot--single" v-if="!points.length && !isCommunityHub">
				<view class="scf-btn scf-btn--primary" @tap="goSetupStep">去设置真分点</view>
			</view>
		</view>

		<!-- 管理真分点 -->
		<view class="scf-body" v-if="flowStep === 'manage'">
			<text class="scf-hint">修改名称或种类；删除后不影响历史打卡记录。</text>
			<view class="scf-point-grid">
				<view class="scf-manage-card" v-for="(p, mIdx) in points" :key="p.id">
					<input
						class="scf-setup-input"
						:value="p._edit_name"
						placeholder="点位名称"
						maxlength="16"
						@input="onManageNameInput(mIdx, $event)"
					/>
					<picker mode="selector" :range="catePickerLabels" :value="p._edit_cate_index" @change="onManageCateChangeByIndex(mIdx, $event)">
						<view class="scf-setup-picker">{{ catePickerLabels[p._edit_cate_index] || p.cate_name }}</view>
					</picker>
					<view class="scf-manage-actions">
						<text class="scf-manage-save" @tap="savePointEditByIndex(mIdx)">保存</text>
						<text class="scf-manage-del" @tap="deletePointById(p.id)">删除</text>
					</view>
				</view>
			</view>
			<view class="scf-setup-add" @tap="addPointInManage">+ 新增点位</view>
			<view class="scf-foot scf-foot--single">
				<view class="scf-btn scf-btn--ghost" @tap="backFromManage">返回打卡</view>
			</view>
		</view>

		<!-- 处理指引 -->
		<view class="scf-body" v-if="flowStep === 'guide'">
			<view class="scf-panel">
				<view class="scf-cate-hero scf-cate-hero--block" :class="'scf-cate-hero--' + cateTone(activeCateName)">
					<text class="scf-cate-hero-kicker">垃圾分类</text>
					<text class="scf-cate-hero-name">{{ activeCateName }}</text>
				</view>
				<text v-if="pointName" class="scf-guide-point-name">{{ pointName }}</text>
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
				<view class="scf-btn scf-btn--ghost" hover-class="scf-card--press" @tap="backFromGuide">上一步</view>
				<view class="scf-btn scf-btn--primary" hover-class="scf-card--press" @tap="flowStep = 'survey'">下一步</view>
			</view>
		</view>

		<!-- 问卷 / 技巧 -->
		<view class="scf-body" v-if="flowStep === 'survey'">
			<text class="scf-hint">{{ guide.survey_question }}</text>
			<view class="scf-tip-list">
				<view
					v-for="(tip, idx) in presetTips"
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
				<textarea class="scf-textarea" v-model="customTip" placeholder="选填" maxlength="200" />
			</view>
			<view class="scf-foot">
				<view class="scf-btn scf-btn--ghost" hover-class="scf-card--press" @tap="flowStep = 'guide'">上一步</view>
				<view class="scf-btn scf-btn--primary" hover-class="scf-card--press" @tap="goLearnStep">下一步</view>
			</view>
		</view>

		<!-- 学习 / 打卡 -->
		<view class="scf-body" v-if="flowStep === 'learn'">
			<view class="scf-learn-card">
				<text class="scf-learn-title">是否想学习更多分类技巧？</text>
				<text class="scf-learn-desc">系统学习分类知识，打卡更有把握</text>
				<view class="scf-learn-actions">
					<view class="scf-btn scf-btn--primary scf-btn--block" hover-class="scf-card--press" @tap="goLearn">去学习</view>
					<view class="scf-btn scf-btn--blue scf-btn--block" hover-class="scf-card--press" @tap="goClock">去拍照打卡</view>
				</view>
				<view class="scf-learn-skip" @tap="finishOnly">稍后再说</view>
			</view>
			<view class="scf-foot scf-foot--single">
				<view class="scf-btn scf-btn--ghost" hover-class="scf-card--press" @tap="flowStep = 'survey'">上一步</view>
			</view>
		</view>
	</view>
</template>

<script>
	import { getHub, mergeGuide } from '@/common/sceneClockHub.js'
	import { guideCateLabelFromName, resolveCateKind } from '@/common/clockScene.js'

	export default {
		data() {
			return {
				hubKey: '',
				hub: null,
				flowStep: 'points',
				points: [],
				pointsLoading: false,
				setupRows: [{ point_name: '', cate_index: 0 }],
				cateList: [],
				pointId: 0,
				pointName: '',
				subKey: '',
				cateLabel: '',
				activeCateName: '',
				cateId: 0,
				guide: {
					handle_guide: '',
					dispose_time: '',
					survey_question: '',
					preset_tips: [],
					learn_path: '/pages/dtsh/knowledge',
				},
				userChoice: '',
				customTip: '',
			}
		},
		computed: {
			isOfficeHub() {
				return this.hubKey === 'office'
			},
			isStreetHub() {
				return this.hubKey === 'street'
			},
			isCommunityHub() {
				return this.isOfficeHub || this.isStreetHub
			},
			communityEmptyTitle() {
				if (this.isStreetHub) return '暂未配置商铺回收点'
				if (this.isOfficeHub) return '暂未配置真分角回收点'
				return '暂未配置回收点'
			},
			communityEmptyDesc() {
				return '社区工作人员将根据您的商铺/单位性质人工配置统一回收点，配置完成后即可在此选择点位打卡。如有疑问请联系社区。'
			},
			communityPointsTitle() {
				if (this.isStreetHub) return '请前往统一回收点打卡'
				return '请前往大楼真分角打卡'
			},
			communityPointsDesc() {
				if (this.isStreetHub) {
					return '以下为社区根据您的商铺情况配置的集中回收点，每个点位对应一种垃圾类型，请选择后拍照打卡。'
				}
				return '以下为社区配置的办公真分角投放点，无需自行设置，请选择点位后打卡。'
			},
			presetTips() {
				const tips = this.guide && this.guide.preset_tips
				return Array.isArray(tips) ? tips : []
			},
			hubTheme() {
				return (this.hub && this.hub.theme) || 'scf--home'
			},
			catePickerLabels() {
				return (this.cateList || []).map(c => c.name)
			},
			stepTitle() {
				const map = {
					hub_empty: '等待配置',
					setup: '设置真分点',
					points: '选择真分点',
					manage: '管理真分点',
					cate: '选择垃圾种类',
					guide: '分类指引',
					survey: '分享分类技巧',
					learn: '继续学习',
				}
				return map[this.flowStep] || this.hub.title
			},
			visibleStepLabels() {
				if (['setup', 'points', 'manage', 'hub_empty'].indexOf(this.flowStep) >= 0) return []
				return ['指引', '小技巧', '打卡']
			},
			stepIndex() {
				const order = ['guide', 'survey', 'learn']
				const i = order.indexOf(this.flowStep)
				return i >= 0 ? i : 0
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
			this.subKey = ''
			this.loadCateList().then(() => {
				this.initUserPoints()
			})
		},
		methods: {
			goSetupStep() {
				this.flowStep = 'setup'
			},
			guideSubKeyForHub() {
				return this.hubKey === 'office' ? 'zhenfenjiao' : ''
			},
			loadCateList() {
				return this.$u.api.get_select_list({ type: 2 }).then(res => {
					this.cateList = res.data || []
				}).catch(() => {})
			},
			initUserPoints(keepFlow) {
				if (!this.isLogin) {
					this.flowStep = this.isCommunityHub ? 'hub_empty' : 'setup'
					return Promise.resolve()
				}
				if (!this.userInfo.community_id) {
					this.$u.route('/pages/member/info')
					return Promise.resolve()
				}
				this.pointsLoading = true
				return this.$u.api.list_clock_scene_point({ hub_key: this.hubKey }).then(res => {
					this.points = res.data || []
					if (keepFlow === 'manage') {
						this.points = this.points.map(p => this.decoratePointForEdit(p))
						this.flowStep = 'manage'
						return
					}
					if (this.isCommunityHub) {
						this.flowStep = this.points.length ? 'points' : 'hub_empty'
						return
					}
					this.flowStep = this.points.length ? 'points' : 'setup'
					if (this.flowStep === 'setup' && !this.setupRows.length) {
						this.setupRows = [{ point_name: '', cate_index: 0 }]
					}
				}).catch(() => {
					if (keepFlow === 'manage') return
					this.flowStep = this.isCommunityHub ? 'hub_empty' : 'setup'
				}).finally(() => {
					this.pointsLoading = false
				})
			},
			cateTone(name) {
				return resolveCateKind(name) || 'other'
			},
			addSetupRow() {
				this.setupRows.push({ point_name: '', cate_index: 0 })
			},
			removeSetupRow(idx) {
				this.setupRows.splice(idx, 1)
			},
			onSetupCateChange(idx, e) {
				const v = parseInt(e.detail.value, 10)
				if (!isNaN(v)) this.setupRows[idx].cate_index = v
			},
			async saveSetup() {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				const rows = this.setupRows.filter(r => r.point_name && String(r.point_name).trim())
				if (!rows.length) return this.$u.toast('请至少添加一个真分点')
				for (const row of rows) {
					const cate = this.cateList[row.cate_index]
					if (!cate) return this.$u.toast('请选择垃圾种类')
					await this.$u.api.save_clock_scene_point({
						hub_key: this.hubKey,
						point_name: String(row.point_name).trim(),
						cate_id: cate.id,
					})
				}
				this.$u.toast('已保存')
				this.initUserPoints()
			},
			enterManage() {
				this.points = (this.points || []).map(p => this.decoratePointForEdit(p))
				this.flowStep = 'manage'
			},
			decoratePointForEdit(p) {
				let idx = 0
				for (let i = 0; i < this.cateList.length; i++) {
					if (Number(this.cateList[i].id) === Number(p.cate_id)) {
						idx = i
						break
					}
				}
				return {
					...p,
					_edit_name: p.point_name,
					_edit_cate_index: idx,
				}
			},
			onManageNameInput(idx, e) {
				const val = e.detail && e.detail.value !== undefined ? e.detail.value : ''
				if (!this.points[idx]) return
				this.$set(this.points[idx], '_edit_name', val)
			},
			onManageCateChangeByIndex(idx, e) {
				const v = parseInt(e.detail.value, 10)
				if (isNaN(v) || !this.points[idx]) return
				this.$set(this.points[idx], '_edit_cate_index', v)
			},
			async savePointEditByIndex(idx) {
				const p = this.points[idx]
				if (!p) return
				const name = String(p._edit_name || '').trim()
				if (!name) return this.$u.toast('请填写点位名称')
				const cate = this.cateList[p._edit_cate_index]
				if (!cate) return this.$u.toast('请选择种类')
				await this.$u.api.save_clock_scene_point({
					id: p.id,
					hub_key: this.hubKey,
					point_name: name,
					cate_id: cate.id,
				})
				this.$u.toast('已保存')
				this.initUserPoints('manage')
			},
			deletePointById(id) {
				const pointId = Number(id)
				if (!pointId) return
				uni.showModal({
					title: '提示',
					content: '确定删除该真分点？',
					success: async (res) => {
						if (!res.confirm) return
						await this.$u.api.delete_clock_scene_point({ id: pointId })
						this.$u.toast('已删除')
						this.initUserPoints('manage')
					},
				})
			},
			addPointInManage() {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				uni.showModal({
					title: '新增真分点',
					editable: true,
					placeholderText: '点位名称',
					success: async (res) => {
						if (!res.confirm) return
						const name = String(res.content || '').trim()
						if (!name) return this.$u.toast('请填写名称')
						const cate = this.cateList[0]
						if (!cate) return this.$u.toast('种类加载中')
						await this.$u.api.save_clock_scene_point({
							hub_key: this.hubKey,
							point_name: name,
							cate_id: cate.id,
						})
						this.initUserPoints('manage')
					},
				})
			},
			backFromManage() {
				this.flowStep = this.points.length ? 'points' : 'setup'
			},
			pickPointById(id) {
				const pid = Number(id)
				const p = (this.points || []).find(item => Number(item.id) === pid)
				if (!p) return
				this.pointId = p.id
				this.pointName = p.point_name
				this.cateId = p.cate_id
				this.activeCateName = p.cate_name || ''
				this.cateLabel = guideCateLabelFromName(p.cate_name)
				this.subKey = this.guideSubKeyForHub()
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
					if (!row || typeof row !== 'object') return
					if (row.handle_guide || (row.preset_tips && row.preset_tips.length)) {
						this.guide = mergeGuide(row, this.hubKey, this.subKey, this.cateLabel)
					}
				}).catch(() => {})
			},
			backFromGuide() {
				this.flowStep = 'points'
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
				const path = this.guide.learn_path || '/pages/dtsh/knowledge'
				this.$u.route(path)
			},
			goClock() {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				if (!this.userInfo.community_id) return this.$u.route('/pages/member/info')
				if (!this.cateId) return this.$u.toast('暂未匹配到垃圾种类')
				const sceneKey = this.hub.sceneKey
				const sceneName = this.isCommunityHub
					? (this.isOfficeHub ? '真分角' : '沿街回收点')
					: (this.pointName || this.hub.title)
				let url =
					'/pages/flhd/add?scene_key=' +
					encodeURIComponent(sceneKey) +
					'&scene=' +
					encodeURIComponent(sceneName) +
					'&cate_id=' +
					this.cateId +
					'&lock_cate=1'
				if (this.pointId) {
					url += '&clock_point_id=' + this.pointId + '&point_name=' + encodeURIComponent(this.pointName)
				}
				this.$u.route(url)
			},
			finishOnly() {
				uni.switchTab({ url: '/pages/low_carbon/low_carbon' })
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
	line-height: 1.55;
}
.scf-hint--inline {
	margin-bottom: 0;
	flex: 1;
}
.scf-points-intro {
	margin-bottom: 16rpx;
}
.scf-office-tip-title {
	display: block;
	font-size: 32rpx;
	font-weight: 800;
	color: #142018;
	margin-bottom: 12rpx;
}
.scf-points-head {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 12rpx;
}
.scf-manage-link {
	font-size: 26rpx;
	color: #18af38;
}
.scf-point-grid {
	display: flex;
	flex-direction: column;
	gap: 14rpx;
}
.scf-point-card {
	background: #fff;
	border-radius: 18rpx;
	padding: 20rpx;
	box-shadow: 0 8rpx 24rpx rgba(20, 40, 28, 0.08);
}
.scf-cate-hero {
	border-radius: 16rpx;
	padding: 20rpx 22rpx;
	color: #fff;
}
.scf-cate-hero--block {
	margin-bottom: 16rpx;
}
.scf-cate-hero-kicker {
	display: block;
	font-size: 22rpx;
	opacity: 0.88;
	letter-spacing: 2rpx;
}
.scf-cate-hero-name {
	display: block;
	margin-top: 6rpx;
	font-size: 44rpx;
	font-weight: 800;
	line-height: 1.25;
	letter-spacing: 1rpx;
}
.scf-cate-hero--recycle {
	background: linear-gradient(141deg, #2b8bb8, #156a90);
}
.scf-cate-hero--food {
	background: linear-gradient(141deg, #3d9e4a, #1a7a32);
}
.scf-cate-hero--harm {
	background: linear-gradient(141deg, #c45c8a, #9a3366);
}
.scf-cate-hero--other {
	background: linear-gradient(141deg, #7a8494, #4a5568);
}
.scf-point-name {
	display: block;
	margin-top: 14rpx;
	font-size: 28rpx;
	font-weight: 600;
	color: #4a564e;
}
.scf-guide-point-name {
	display: block;
	margin-bottom: 18rpx;
	font-size: 28rpx;
	color: #6a756c;
}
.scf-point-go {
	display: block;
	margin-top: 12rpx;
	font-size: 26rpx;
	color: #18af38;
	font-weight: 600;
}
.scf-setup-list {
	display: flex;
	flex-direction: column;
	gap: 12rpx;
}
.scf-setup-row,
.scf-manage-card {
	background: #fff;
	border-radius: 16rpx;
	padding: 16rpx;
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	gap: 10rpx;
}
.scf-setup-input {
	flex: 1;
	min-width: 200rpx;
	font-size: 28rpx;
	padding: 12rpx 16rpx;
	background: #f5f7f6;
	border-radius: 10rpx;
}
.scf-setup-picker {
	font-size: 26rpx;
	padding: 12rpx 16rpx;
	background: #eef8f0;
	color: #18af38;
	border-radius: 10rpx;
}
.scf-setup-del {
	color: #e75e5e;
	font-size: 26rpx;
	padding: 8rpx;
}
.scf-setup-add {
	margin-top: 16rpx;
	text-align: center;
	font-size: 28rpx;
	color: #18af38;
	padding: 16rpx;
}
.scf-manage-actions {
	width: 100%;
	display: flex;
	gap: 24rpx;
	margin-top: 8rpx;
}
.scf-manage-save {
	color: #18af38;
	font-size: 26rpx;
}
.scf-manage-del {
	color: #e75e5e;
	font-size: 26rpx;
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
.scf-foot--single .scf-btn {
	flex: 1;
}
.scf-btn {
	flex: 1;
	text-align: center;
	padding: 22rpx 16rpx;
	border-radius: 14rpx;
	font-size: 28rpx;
	font-weight: 600;
}
.scf-btn--block {
	flex: none;
	width: 100%;
}
.scf-btn--primary {
	color: #fff;
	background: linear-gradient(141deg, #a4db88 0%, #18af38 100%);
}
.scf-btn--blue {
	color: #fff;
	background: linear-gradient(141deg, #5aa8ff 0%, #1877f2 100%);
}
.scf-btn--ghost {
	color: #555;
	background: #f0f2f1;
}
.scf-textarea {
	width: 100%;
	min-height: 160rpx;
	font-size: 26rpx;
	color: #243028;
	line-height: 1.5;
}
.scf-card--press {
	opacity: 0.92;
	transform: scale(0.99);
}
</style>
