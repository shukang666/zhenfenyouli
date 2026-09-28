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

		<!-- 首次：设置分类打卡点（仅家庭/亲子） -->
		<view class="scf-body" v-if="flowStep === 'setup'">
			<view class="scf-intro">
				<text class="scf-intro-lead">{{ introDisplayLead }}</text>
				<text class="scf-intro-summary">{{ introDisplaySummary }}</text>
				<view class="scf-intro-toggle" hover-class="scf-card--press" @tap="introExpanded = !introExpanded">
					<text class="scf-intro-toggle-t">{{ introExpanded ? '收起' : '了解更多' }}</text>
					<text class="scf-intro-chevron">{{ introExpanded ? '▲' : '▼' }}</text>
				</view>
				<view class="scf-intro-detail" v-if="introExpanded">
					<view class="scf-intro-block" v-for="block in introBlocks" :key="block.id">
						<text class="scf-intro-block-title">{{ block.title }}</text>
						<text class="scf-intro-block-body">{{ block.body }}</text>
					</view>
				</view>
			</view>
			<view class="scf-setup-list">
				<view class="scf-setup-block" v-for="(row, idx) in setupRows" :key="idx">
					<view class="scf-point-card scf-point-card--preview">
						<image v-if="row.point_image_url" class="scf-point-photo" :src="row.point_image_url" mode="aspectFill"></image>
						<view class="scf-cate-hero" :class="'scf-cate-hero--' + cateTone(setupCateName(row))">
							<text class="scf-cate-hero-kicker">垃圾分类</text>
							<text class="scf-cate-hero-name">{{ setupCateName(row) || '请选择类型' }}</text>
						</view>
						<text class="scf-cate-desc-lines">{{ cateHintLines(row) }}</text>
						<text class="scf-point-name">{{ row.point_name || '点位名称（如厨房桶）' }}</text>
					</view>
					<view class="scf-setup-form">
						<text class="scf-setup-label">点位名称</text>
						<input class="scf-setup-input scf-setup-input--block" v-model="row.point_name" placeholder="如：厨房厨余桶、客厅可回收" maxlength="16" />
						<text class="scf-setup-label">垃圾类型</text>
						<picker mode="selector" :range="catePickerLabels" :value="row.cate_index" @change="onSetupCateChange(idx, $event)">
							<view class="scf-setup-picker scf-setup-picker--block">{{ catePickerLabels[row.cate_index] || '选择四分类之一' }}</view>
						</picker>
						<text class="scf-setup-label">点位照片（必填）</text>
						<view class="scf-point-upload" @tap="chooseSetupPhoto(idx)">
							<image v-if="row.point_image_url" class="scf-point-upload-img" :src="row.point_image_url" mode="aspectFill"></image>
							<view v-else class="scf-point-upload-placeholder">
								<text class="scf-photo-add-icon">📷</text>
								<text class="scf-photo-add-t">拍摄/上传点位环境</text>
							</view>
						</view>
						<text class="scf-setup-del" v-if="setupRows.length > 1" @tap="removeSetupRow(idx)">删除本点位</text>
					</view>
				</view>
			</view>
			<view class="scf-setup-add" @tap="addSetupRow">+ 再添一个扔垃圾的地方</view>
			<view class="scf-foot scf-foot--single">
				<view class="scf-btn scf-btn--primary" hover-class="scf-card--press" @tap="saveSetup">保存，去选位置拍照</view>
			</view>
		</view>

		<!-- 打卡点列表（打卡入口） -->
		<view class="scf-body" v-if="flowStep === 'points'">
			<view class="scf-intro" :class="{ 'scf-intro--compact': isCommunityHub }">
				<text class="scf-intro-lead">{{ introDisplayLead }}</text>
				<text class="scf-intro-summary">{{ introDisplaySummary }}</text>
				<view class="scf-intro-toggle" hover-class="scf-card--press" @tap="introExpanded = !introExpanded">
					<text class="scf-intro-toggle-t">{{ introExpanded ? '收起' : '了解更多' }}</text>
					<text class="scf-intro-chevron">{{ introExpanded ? '▲' : '▼' }}</text>
				</view>
				<view class="scf-intro-detail" v-if="introExpanded">
					<view class="scf-intro-block" v-for="block in introBlocks" :key="block.id">
						<text class="scf-intro-block-title">{{ block.title }}</text>
						<text class="scf-intro-block-body">{{ block.body }}</text>
					</view>
				</view>
			</view>
			<view class="scf-points-head">
				<text class="scf-hint scf-hint--inline">{{ pointsStepHint }}</text>
				<text class="scf-manage-link" v-if="!isCommunityHub" @tap="enterManage">改分类位置</text>
			</view>
			<view class="scf-point-grid">
				<view
					v-for="(group, gIdx) in pointGroups"
					:key="group.key"
					class="scf-point-card scf-point-card--cluster"
				>
					<view class="scf-cate-hero" :class="'scf-cate-hero--' + cateTone(group.cate_name)">
						<text class="scf-cate-hero-kicker">垃圾分类</text>
						<text class="scf-cate-hero-name">{{ group.cate_name }}</text>
						<text class="scf-cate-hero-count" v-if="group.items.length > 1">{{ group.items.length }} 个点位</text>
					</view>
					<text class="scf-cate-desc-lines">{{ cateHintByName(group.cate_name) }}</text>
					<view class="scf-cluster-list">
						<view
							class="scf-cluster-item"
							v-for="(p, pIdx) in group.items"
							:key="p.id"
							hover-class="scf-card--press"
							@tap="pickClusterPoint(gIdx, pIdx)"
						>
							<image
								v-if="p.point_image"
								class="scf-cluster-photo"
								:src="p.point_image"
								mode="aspectFill"
							></image>
							<view v-else class="scf-cluster-photo scf-cluster-photo--empty">
								<text class="scf-cluster-photo-t">暂无点位照片</text>
							</view>
							<view class="scf-cluster-item-foot">
								<text class="scf-cluster-item-name">{{ p.point_name }}</text>
								<text class="scf-point-go">去拍照 ›</text>
							</view>
						</view>
					</view>
				</view>
			</view>
			<view class="scf-foot scf-foot--single" v-if="!points.length && !isCommunityHub">
				<view class="scf-btn scf-btn--primary" @tap="goSetupStep">去设置家里分类位置</view>
			</view>
		</view>

		<!-- 管理打卡点 -->
		<view class="scf-body" v-if="flowStep === 'manage'">
			<text class="scf-hint">可以改名字、种类或照片；删掉后以前交过的记录还在。</text>
			<view class="scf-point-grid">
				<view class="scf-setup-block" v-for="(p, mIdx) in points" :key="p.id">
					<view class="scf-point-card scf-point-card--preview">
						<image v-if="p._edit_image_url" class="scf-point-photo" :src="p._edit_image_url" mode="aspectFill"></image>
						<view class="scf-cate-hero" :class="'scf-cate-hero--' + cateTone(manageCateName(p))">
							<text class="scf-cate-hero-kicker">垃圾分类</text>
							<text class="scf-cate-hero-name">{{ manageCateName(p) }}</text>
						</view>
						<text class="scf-cate-desc-lines">{{ cateHintByName(manageCateName(p)) }}</text>
						<text class="scf-point-name">{{ p._edit_name || p.point_name }}</text>
					</view>
					<view class="scf-setup-form">
						<input class="scf-setup-input scf-setup-input--block" :value="p._edit_name" placeholder="点位名称" maxlength="16" @input="onManageNameInput(mIdx, $event)" />
						<picker mode="selector" :range="catePickerLabels" :value="p._edit_cate_index" @change="onManageCateChangeByIndex(mIdx, $event)">
							<view class="scf-setup-picker scf-setup-picker--block">{{ catePickerLabels[p._edit_cate_index] || p.cate_name }}</view>
						</picker>
						<view class="scf-point-upload" @tap="chooseManagePhoto(mIdx)">
							<image v-if="p._edit_image_url" class="scf-point-upload-img" :src="p._edit_image_url" mode="aspectFill"></image>
							<view v-else class="scf-point-upload-placeholder">
								<text class="scf-photo-add-t">更新点位照片</text>
							</view>
						</view>
						<view class="scf-manage-actions">
							<text class="scf-manage-save" @tap="savePointEditByIndex(mIdx)">保存</text>
							<text class="scf-manage-del" @tap="deletePointById(p.id)">删除</text>
						</view>
					</view>
				</view>
			</view>
			<view class="scf-setup-add" @tap="addPointInManage">+ 新增点位</view>
			<view class="scf-foot scf-foot--single">
				<view class="scf-btn scf-btn--ghost" @tap="backFromManage">返回选位置</view>
			</view>
		</view>

		<!-- 处理指引 -->
		<view class="scf-body" v-if="flowStep === 'guide'">
			<view class="scf-panel">
				<view class="scf-cate-hero scf-cate-hero--panel" :class="'scf-cate-hero--' + cateTone(activeCateName)">
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

		<!-- 拍照打卡（原跳转 add 页，现页内完成） -->
		<view class="scf-body" v-if="flowStep === 'learn'">
			<view class="scf-clock-card">
				<view class="scf-cate-hero scf-cate-hero--compact" :class="'scf-cate-hero--' + cateTone(activeCateName)">
					<text class="scf-cate-hero-name">{{ activeCateName }}</text>
				</view>
				<text class="scf-clock-desc">{{ clockPhotoIntro }}</text>

				<view class="scf-clock-photo-head scf-clock-photo-head--row">
					<text class="scf-clock-photo-title">分三步拍照片</text>
					<text class="scf-clock-photo-progress">{{ clockSlotFilledCount }}/3</text>
				</view>

				<view class="scf-slot-row">
					<view
						v-for="(slot, sIdx) in clockPhotoSlotMeta"
						:key="slot.key"
						class="scf-slot-wrap"
					>
						<view v-if="sIdx > 0" class="scf-slot-arrow">›</view>
						<view class="scf-slot">
							<view class="scf-slot-head">
								<text class="scf-slot-step">{{ sIdx + 1 }}</text>
								<text class="scf-slot-title">{{ slot.title }}</text>
							</view>
							<text class="scf-slot-hint">{{ slot.hint }}</text>
							<view
								class="scf-slot-box"
								:class="{ 'scf-slot-box--done': !!clockSlotUrls[sIdx] }"
								hover-class="scf-card--press"
								@tap="onClockSlotTap(sIdx)"
							>
								<image
									v-if="clockSlotUrls[sIdx]"
									class="scf-slot-img"
									:src="clockSlotUrls[sIdx]"
									mode="aspectFill"
								></image>
								<view v-else class="scf-slot-placeholder">
									<text class="scf-slot-emoji">{{ slot.emoji }}</text>
									<text class="scf-slot-add-t">点击拍摄</text>
								</view>
								<view
									v-if="clockSlotUrls[sIdx]"
									class="scf-photo-del scf-slot-del"
									@tap.stop="clearClockSlot(sIdx)"
								>×</view>
							</view>
						</view>
					</view>
				</view>
				<text class="scf-clock-photo-foot">{{ clockPhotoFootnote }}</text>

				<view class="scf-clock-note">
					<text class="scf-clock-note-label">补充说明（选填）</text>
					<textarea
						class="scf-textarea scf-clock-textarea"
						v-model="clockContent"
						:placeholder="clockNotePlaceholder"
						maxlength="500"
					/>
				</view>

				<view
					class="scf-btn scf-btn--primary scf-btn--block scf-clock-submit"
					:class="{ 'scf-clock-submit--disabled': clockSubmitting }"
					hover-class="scf-card--press"
					@tap="submitClock"
				>{{ clockSubmitting ? '提交中…' : '提交' }}</view>

				<view class="scf-learn-link" hover-class="scf-card--press" @tap="goLearn">
					<text>还想学分类知识？</text>
					<text class="scf-learn-link-go">去学习 ›</text>
				</view>
			</view>
			<view class="scf-foot scf-foot--single">
				<view class="scf-btn scf-btn--ghost" hover-class="scf-card--press" @tap="flowStep = 'survey'">上一步</view>
			</view>
		</view>

		<!-- 打卡成功 -->
		<view class="scf-success-mask" v-if="clockSuccessShow" @tap="goActivityTab">
			<view class="scf-success-dialog" @tap.stop="">
				<text class="scf-success-emoji">✓</text>
				<text class="scf-success-title">{{ clockSuccessMsg || '打卡成功' }}</text>
				<text class="scf-success-sub">请不要忘记正确投递哦~</text>
				<view class="scf-habit-box" v-if="clockHabitTip">
					<text class="scf-habit-kicker">养成好习惯</text>
					<text class="scf-habit-text">{{ clockHabitTip }}</text>
				</view>
				<view class="scf-success-actions">
					<view class="scf-btn scf-btn--primary scf-btn--block" @tap="clockSuccessContinue">再选位置拍照</view>
					<view class="scf-btn scf-btn--ghost scf-btn--block scf-success-back" @tap="goActivityTab">返回活动</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	import { uploadUrl } from '@/common/settings'
	import { parseUploadAssetResponse } from '@/common/uploadAsset.js'
	import { pickRandomHabitTip } from '@/common/clockHabitTips.js'
	import { getHub, mergeGuide } from '@/common/sceneClockHub.js'
	import { buildSceneCateSubtitle, guideCateLabelFromName, resolveCateKind, SECTION_HINTS } from '@/common/clockScene.js'
	import {
		getStepTitles,
		getIntroLead,
		getIntroSummary,
		getIntroBlocks,
		getSetupIntroLine,
		getCommunityEmptyTitle,
		getCommunityEmptyDesc,
		getCommunityIntroLead,
		getCommunityPointsDesc,
		getPointsPickSummary,
		getPointsStepHint,
	} from '@/common/sceneClockUserCopy.js'

	export default {
		data() {
			return {
				hubKey: '',
				hub: null,
				flowStep: 'points',
				pointsBootstrapped: false,
				points: [],
				pointsLoading: false,
				setupRows: [{ point_name: '', cate_index: 0, point_image: '', point_image_url: '' }],
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
				clockSlotUrls: ['', '', ''],
				clockContent: '',
				clockSubmitting: false,
				clockSuccessShow: false,
				clockSuccessMsg: '',
				clockHabitTip: '',
				uploadUrl,
				introExpanded: false,
			}
		},
		computed: {
			introBlocks() {
				return getIntroBlocks(this.hubKey)
			},
			introDisplayLead() {
				if (this.flowStep === 'points' && this.isCommunityHub) {
					return getCommunityIntroLead(this.isStreetHub)
				}
				return getIntroLead(this.hubKey)
			},
			introDisplaySummary() {
				if (this.flowStep === 'setup') {
					return getSetupIntroLine(this.hubKey)
				}
				if (this.flowStep === 'points' && this.isCommunityHub) {
					return getCommunityPointsDesc()
				}
				if (this.flowStep === 'points' && this.points.length) {
					return getPointsPickSummary()
				}
				return getIntroSummary(this.hubKey)
			},
			pointsStepHint() {
				return getPointsStepHint(this.isCommunityHub)
			},
			clockPhotoIntro() {
				const sk = this.hub && this.hub.sceneKey ? this.hub.sceneKey : this.hubKey
				const cateLine = buildSceneCateSubtitle(sk, this.activeCateName)
				const tail = '下面三个格各拍一张：扔之前、对准桶、扔完了。'
				return (cateLine ? cateLine + '。' : '') + tail
			},
			clockPhotoFootnote() {
				return '三张都拍好再点提交；拍糊了社区可能看不清楚。'
			},
			clockPhotoSlotMeta() {
				if (this.isCommunityHub) {
					return [
						{ key: 'before', title: '扔之前', hint: '看清是啥垃圾', emoji: '📦' },
						{ key: 'aim', title: '对准桶', hint: '桶口拍清楚', emoji: '🎯' },
						{ key: 'after', title: '扔完了', hint: '桶里拍一下', emoji: '✅' },
					]
				}
				return [
					{ key: 'before', title: '扔之前', hint: '垃圾分好的样', emoji: '🗑️' },
					{ key: 'sorted', title: '分好了', hint: '袋子或桶口', emoji: '♻️' },
					{ key: 'after', title: '进桶了', hint: '已经在桶里', emoji: '✅' },
				]
			},
			clockSlotFilledCount() {
				return (this.clockSlotUrls || []).filter(u => !!String(u || '').trim()).length
			},
			clockImages() {
				return (this.clockSlotUrls || []).filter(u => !!String(u || '').trim())
			},
			clockNotePlaceholder() {
				if (this.pointName) return '可补充「' + this.pointName + '」投放说明'
				return '可补充投放位置或心得（选填）'
			},
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
				return getCommunityEmptyTitle(this.isStreetHub, this.isOfficeHub)
			},
			communityEmptyDesc() {
				return getCommunityEmptyDesc()
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
				const map = getStepTitles()
				return map[this.flowStep] || this.hub.title
			},
			visibleStepLabels() {
				if (['setup', 'points', 'manage', 'hub_empty'].indexOf(this.flowStep) >= 0) return []
				return ['指引', '小技巧', '拍照']
			},
			stepIndex() {
				const order = ['guide', 'survey', 'learn']
				const i = order.indexOf(this.flowStep)
				return i >= 0 ? i : 0
			},
			/** 同类型垃圾多个打卡点合并展示 */
			pointGroups() {
				const list = this.points || []
				const map = {}
				const keys = []
				list.forEach(p => {
					const key = p.cate_id ? 'id-' + p.cate_id : 'n-' + (p.cate_name || '')
					if (!map[key]) {
						map[key] = {
							key,
							cate_id: p.cate_id,
							cate_name: p.cate_name || '',
							items: [],
						}
						keys.push(key)
					}
					map[key].items.push(p)
				})
				return keys.map(k => map[k])
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
				this.ensureExamGate().then(ok => {
					if (ok) this.initUserPoints()
				})
			})
		},
		onShow() {
			if (!this.hub) return
			const editing = ['setup', 'manage', 'guide', 'survey', 'learn'].indexOf(this.flowStep) >= 0
			if (editing) return
			this.ensureExamGate().then(ok => {
				if (ok) this.initUserPoints()
			})
		},
		methods: {
			ensureExamGate() {
				if (!this.isLogin) return Promise.resolve(true)
				return this.$u.api.getUserInfo().then(res => {
					this.$u.vuex('userInfo', res.data || {})
					if (Number(res.data.is_answer) !== 1) {
						this.$u.toast('请先完成分类介绍与测验')
						setTimeout(() => this.$u.route('/pages/flhd/ljfldk'), 500)
						return false
					}
					return true
				}).catch(() => true)
			},
			setupCateName(row) {
				const cate = this.cateList[row.cate_index]
				return (cate && cate.name) || ''
			},
			manageCateName(p) {
				const cate = this.cateList[p._edit_cate_index]
				return (cate && cate.name) || p.cate_name || ''
			},
			cateHintByName(name) {
				const kind = resolveCateKind(name)
				return SECTION_HINTS[kind] || '按社区要求正确投放'
			},
			cateHintLines(row) {
				return this.cateHintByName(this.setupCateName(row))
			},
			goSetupStep() {
				if (!this.setupRows.length) {
					this.setupRows = [{ point_name: '', cate_index: 0, point_image: '', point_image_url: '' }]
				}
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
					this.pointsBootstrapped = true
					if (keepFlow === 'manage') {
						this.points = this.points.map(p => this.decoratePointForEdit(p))
						this.flowStep = 'manage'
						return
					}
					if (keepFlow === 'points') {
						this.flowStep = this.points.length ? 'points' : 'setup'
						return
					}
					if (keepFlow === 'setup') {
						return
					}
					if (this.isCommunityHub) {
						this.flowStep = this.points.length ? 'points' : 'hub_empty'
						return
					}
					if (this.flowStep === 'setup' || this.flowStep === 'manage') {
						return
					}
					this.flowStep = this.points.length ? 'points' : 'setup'
					if (this.flowStep === 'setup' && !this.setupRows.length) {
						this.setupRows = [{ point_name: '', cate_index: 0, point_image: '', point_image_url: '' }]
					}
				}).catch(() => {
					if (keepFlow === 'manage' || keepFlow === 'setup') return
					this.flowStep = this.isCommunityHub ? 'hub_empty' : 'setup'
				}).finally(() => {
					this.pointsLoading = false
				})
			},
			cateTone(name) {
				return resolveCateKind(name) || 'other'
			},
			addSetupRow() {
				this.setupRows.push({ point_name: '', cate_index: 0, point_image: '', point_image_url: '' })
			},
			chooseSetupPhoto(idx) {
				this.pickAndUploadPointPhoto((savePath, showUrl) => {
					this.$set(this.setupRows[idx], 'point_image', savePath)
					this.$set(this.setupRows[idx], 'point_image_url', showUrl)
				})
			},
			chooseManagePhoto(mIdx) {
				this.pickAndUploadPointPhoto((savePath, showUrl) => {
					const p = this.points[mIdx]
					if (!p) return
					this.$set(p, '_edit_image', savePath)
					this.$set(p, '_edit_image_url', showUrl)
				})
			},
			pickAndUploadPointPhoto(onDone) {
				uni.chooseImage({
					count: 1,
					sourceType: ['camera', 'album'],
					success: (res) => {
						const filePath = (res.tempFilePaths || [])[0]
						if (!filePath) return
						uni.showLoading({ title: '上传中…' })
						uni.uploadFile({
							url: this.uploadUrl,
							filePath,
							name: 'file',
							formData: { filetype: 'image' },
							success: (up) => {
								try {
									const data = JSON.parse(up.data)
									if (data.code != 1) {
										return this.$u.toast(data.msg || '上传失败')
									}
									const parsed = parseUploadAssetResponse(data)
									if (!parsed.savePath) {
										return this.$u.toast('上传失败')
									}
									onDone(parsed.savePath, parsed.showUrl)
								} catch (e) {
									this.$u.toast('上传失败')
								}
							},
							fail: () => this.$u.toast('上传失败'),
							complete: () => uni.hideLoading(),
						})
					},
				})
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
				if (!rows.length) return this.$u.toast('请至少添一个扔垃圾的地方')
				try {
					for (const row of rows) {
						const cate = this.cateList[row.cate_index]
						if (!cate) return this.$u.toast('请选择垃圾种类')
						const img = String(row.point_image || '').trim()
						if (!img) return this.$u.toast('请为每个点位上传照片')
						const res = await this.$u.api.save_clock_scene_point({
							hub_key: this.hubKey,
							point_name: String(row.point_name).trim(),
							cate_id: cate.id,
							point_image: img,
						})
						const saved = (res && res.data) || {}
						if (!saved.point_image_raw && !saved.point_image) {
							console.warn('save_clock_scene_point: point_image empty in response', saved)
						}
					}
					this.setupRows = [{ point_name: '', cate_index: 0, point_image: '', point_image_url: '' }]
					this.$u.toast('已保存')
					await this.initUserPoints('points')
				} catch (e) {
					this.$u.toast((e && e.msg) || '保存失败，请重试')
				}
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
				const raw = p.point_image_raw || ''
				const display = p.point_image || ''
				return {
					...p,
					_edit_name: p.point_name,
					_edit_cate_index: idx,
					_edit_image: raw,
					_edit_image_url: display,
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
				const img = String(p._edit_image || p.point_image || '').trim()
				if (!img) return this.$u.toast('请上传点位照片')
				await this.$u.api.save_clock_scene_point({
					id: p.id,
					hub_key: this.hubKey,
					point_name: name,
					cate_id: cate.id,
					point_image: img,
				})
				this.$u.toast('已保存')
				this.initUserPoints('manage')
			},
			deletePointById(id) {
				const pointId = Number(id)
				if (!pointId) return
				uni.showModal({
					title: '提示',
					content: '确定删掉这个位置？',
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
				this.setupRows = [{ point_name: '', cate_index: 0, point_image: '', point_image_url: '' }]
				this.flowStep = 'setup'
			},
			backFromManage() {
				this.flowStep = this.points.length ? 'points' : 'setup'
			},
			pickClusterPoint(gIdx, pIdx) {
				const groups = this.pointGroups || []
				const group = groups[gIdx]
				const p = group && group.items && group.items[pIdx]
				if (!p || p.id == null) return
				this.pickPointById(p.id)
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
				this.resetClockSlots()
				this.clockContent = ''
				this.flowStep = 'learn'
			},
			goLearn() {
				const path = this.guide.learn_path || '/pages/dtsh/knowledge'
				this.$u.route(path)
			},
			resolveClockScene() {
				const k = this.hub && this.hub.sceneKey ? String(this.hub.sceneKey) : ''
				if (k === 'home') return 'home'
				if (k === 'zhenfenjiao' || this.hubKey === 'office') return 'zhenfenjiao'
				if (k === 'qinzi' || k === 'street') return k
				return 'home'
			},
			resetClockSlots() {
				this.clockSlotUrls = ['', '', '']
			},
			onClockSlotTap(slotIdx) {
				const url = this.clockSlotUrls[slotIdx]
				if (url) {
					this.previewClockImages(slotIdx)
					return
				}
				this.chooseClockSlot(slotIdx)
			},
			chooseClockSlot(slotIdx) {
				uni.chooseImage({
					count: 1,
					sourceType: ['camera', 'album'],
					success: (res) => {
						const path = (res.tempFilePaths || [])[0]
						if (path) this.uploadClockSlotImage(slotIdx, path)
					},
				})
			},
			uploadClockSlotImage(slotIdx, filePath) {
				uni.showLoading({ title: '上传中…' })
				uni.uploadFile({
					url: this.uploadUrl,
					filePath,
					name: 'file',
					formData: { filetype: 'image' },
					success: (res) => {
						try {
							const data = JSON.parse(res.data)
							if (data.code == 1 && data.data && data.data.url) {
								this.$set(this.clockSlotUrls, slotIdx, data.data.url)
							} else {
								this.$u.toast(data.msg || '上传失败')
							}
						} catch (e) {
							this.$u.toast('上传失败')
						}
					},
					fail: () => this.$u.toast('上传失败'),
					complete: () => uni.hideLoading(),
				})
			},
			clearClockSlot(slotIdx) {
				this.$set(this.clockSlotUrls, slotIdx, '')
			},
			previewClockImages(index) {
				const urls = this.clockImages
				if (!urls.length) return
				const current = this.clockSlotUrls[index] || urls[0]
				uni.previewImage({ urls, current })
			},
			missingClockSlotLabel() {
				const meta = this.clockPhotoSlotMeta
				for (let i = 0; i < meta.length; i++) {
					if (!String(this.clockSlotUrls[i] || '').trim()) {
						return meta[i].title
					}
				}
				return ''
			},
			submitClock() {
				if (this.clockSubmitting) return
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				if (!this.userInfo.community_id) return this.$u.route('/pages/member/info')
				if (!this.cateId) return this.$u.toast('暂未匹配到垃圾种类')
				const missing = this.missingClockSlotLabel()
				if (missing) return this.$u.toast('请拍摄「' + missing + '」照片')
				const that = this
				uni.showModal({
					title: '提示',
					content: '请按真实情况拍照。分错了或照片不对，积分可能拿不到。',
					success(res) {
						if (!res.confirm) return
						that.doSubmitClock()
					},
				})
			},
			doSubmitClock() {
				this.clockSubmitting = true
				this.$u.api.add_forum({
					type: 2,
					cate_id: this.cateId,
					content: this.clockContent,
					image: this.clockImages,
					clock_scene: this.resolveClockScene(),
					clock_sub_scene: '',
					clock_point_id: this.pointId || 0,
				}).then(res => {
					this.clockSuccessMsg = res.msg || '打卡成功'
					this.clockHabitTip = pickRandomHabitTip()
					this.clockSuccessShow = true
					this.$u.api.getUserInfo().then(r => {
						this.$u.vuex('userInfo', r.data || {})
					}).catch(() => {})
				}).catch(e => {
					this.$u.toast((e && e.msg) || '提交失败')
				}).finally(() => {
					this.clockSubmitting = false
				})
			},
			clockSuccessContinue() {
				this.clockSuccessShow = false
				this.resetClockSlots()
				this.clockContent = ''
				this.flowStep = 'points'
			},
			goActivityTab() {
				this.clockSuccessShow = false
				uni.switchTab({ url: '/pages/low_carbon/low_carbon' })
			},
			finishOnly() {
				this.goActivityTab()
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
.scf-point-card--cluster {
	padding-bottom: 12rpx;
}
.scf-cate-hero-count {
	display: block;
	margin-top: 8rpx;
	font-size: 22rpx;
	opacity: 0.9;
}
.scf-cluster-list {
	margin-top: 16rpx;
	display: flex;
	flex-direction: column;
	gap: 12rpx;
}
.scf-cluster-item {
	display: flex;
	flex-direction: column;
	border-radius: 14rpx;
	overflow: hidden;
	background: #f5f8f6;
}
.scf-cluster-photo {
	width: 100%;
	height: 320rpx;
	display: block;
	background: #e8eee9;
}
.scf-cluster-photo--empty {
	display: flex;
	align-items: center;
	justify-content: center;
}
.scf-cluster-photo-t {
	font-size: 26rpx;
	color: #8a958c;
}
.scf-cluster-item-foot {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 18rpx 20rpx;
	gap: 16rpx;
}
.scf-cluster-item-name {
	flex: 1;
	min-width: 0;
	font-size: 30rpx;
	font-weight: 600;
	color: #243028;
}
.scf-cluster-item-foot .scf-point-go {
	flex-shrink: 0;
	margin-top: 0;
}
.scf-point-card--preview {
	margin-bottom: 12rpx;
}
.scf-point-photo {
	width: 100%;
	height: 320rpx;
	border-radius: 14rpx;
	margin-bottom: 14rpx;
}
.scf-cate-desc-lines {
	display: block;
	margin-top: 12rpx;
	font-size: 24rpx;
	line-height: 1.5;
	color: #6a756c;
	min-height: 72rpx;
}
.scf-intro {
	background: #fff;
	border-radius: 20rpx;
	padding: 28rpx 28rpx 24rpx;
	margin-bottom: 20rpx;
	box-shadow: 0 6rpx 28rpx rgba(20, 40, 28, 0.06);
	border-left: 6rpx solid #18af38;
}
.scf-intro--compact {
	border-left-color: #2b8bb8;
}
.scf-intro-lead {
	display: block;
	font-size: 38rpx;
	font-weight: 800;
	color: #142018;
	line-height: 1.45;
}
.scf-intro-summary {
	display: block;
	margin-top: 16rpx;
	font-size: 30rpx;
	color: #3d4840;
	line-height: 1.55;
}
.scf-intro-toggle {
	margin-top: 22rpx;
	padding-top: 20rpx;
	border-top: 1rpx solid #eef0ee;
	display: flex;
	align-items: center;
	justify-content: space-between;
}
.scf-intro-toggle-t {
	font-size: 28rpx;
	color: #18af38;
	font-weight: 600;
}
.scf-intro--compact .scf-intro-toggle-t {
	color: #156a90;
}
.scf-intro-chevron {
	font-size: 24rpx;
	color: #9aa59d;
	margin-left: 12rpx;
}
.scf-intro-detail {
	margin-top: 16rpx;
}
.scf-intro-block {
	padding: 20rpx 0;
	border-bottom: 1rpx solid #f0f2f0;
	&:last-child {
		border-bottom: none;
		padding-bottom: 0;
	}
}
.scf-intro-block-title {
	display: block;
	font-size: 30rpx;
	font-weight: 700;
	color: #243028;
	margin-bottom: 10rpx;
}
.scf-intro-block-body {
	display: block;
	font-size: 28rpx;
	color: #5a665e;
	line-height: 1.55;
}
.scf-setup-block {
	margin-bottom: 20rpx;
}
.scf-setup-form {
	background: #fff;
	border-radius: 16rpx;
	padding: 16rpx;
	box-shadow: 0 4rpx 16rpx rgba(20, 40, 28, 0.05);
}
.scf-setup-label {
	display: block;
	margin-top: 12rpx;
	margin-bottom: 8rpx;
	font-size: 24rpx;
	font-weight: 600;
	color: #4a564e;
}
.scf-setup-input--block,
.scf-setup-picker--block {
	width: 100%;
	box-sizing: border-box;
}
.scf-point-upload {
	margin-top: 8rpx;
	height: 320rpx;
	border-radius: 14rpx;
	overflow: hidden;
	border: 2rpx dashed #c5d0c8;
	background: #f7faf8;
}
.scf-point-upload-img {
	width: 100%;
	height: 100%;
}
.scf-point-upload-placeholder {
	height: 100%;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
}
.scf-cate-hero {
	border-radius: 16rpx;
	padding: 20rpx 22rpx;
	color: #fff;
}
.scf-cate-hero--panel {
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
.scf-cate-hero--compact {
	padding: 16rpx 20rpx;
	margin-bottom: 16rpx;
}
.scf-cate-hero--compact .scf-cate-hero-name {
	font-size: 36rpx;
	margin-top: 0;
}
.scf-clock-card {
	background: #fff;
	border-radius: 20rpx;
	padding: 24rpx;
	box-shadow: 0 8rpx 24rpx rgba(20, 40, 28, 0.08);
}
.scf-clock-desc {
	display: block;
	font-size: 26rpx;
	color: #6a756c;
	line-height: 1.55;
	margin-bottom: 20rpx;
}
.scf-clock-photo-head {
	margin-bottom: 16rpx;
}
.scf-clock-photo-title {
	font-size: 28rpx;
	font-weight: 700;
	color: #243028;
}
.scf-clock-photo-progress {
	font-size: 24rpx;
	color: #18af38;
	font-weight: 600;
}
.scf-clock-photo-head--row {
	display: flex;
	flex-direction: row;
	align-items: center;
	justify-content: space-between;
}
.scf-slot-row {
	display: flex;
	align-items: flex-start;
}
.scf-slot-wrap {
	flex: 1;
	min-width: 0;
	display: flex;
	align-items: flex-start;
}
.scf-slot-arrow {
	flex-shrink: 0;
	width: 20rpx;
	margin-top: 108rpx;
	font-size: 26rpx;
	color: #b8c4bc;
	line-height: 1;
	text-align: center;
}
.scf-slot {
	flex: 1;
	min-width: 0;
}
.scf-slot-head {
	display: flex;
	align-items: center;
	gap: 8rpx;
	margin-bottom: 6rpx;
}
.scf-slot-step {
	width: 32rpx;
	height: 32rpx;
	line-height: 32rpx;
	text-align: center;
	border-radius: 50%;
	background: #e8f5eb;
	color: #18af38;
	font-size: 20rpx;
	font-weight: 700;
	flex-shrink: 0;
}
.scf-slot-title {
	font-size: 24rpx;
	font-weight: 700;
	color: #243028;
}
.scf-slot-hint {
	display: block;
	font-size: 20rpx;
	color: #8a958c;
	line-height: 1.35;
	min-height: 54rpx;
	margin-bottom: 10rpx;
}
.scf-slot-box {
	position: relative;
	width: 100%;
	height: 200rpx;
	border-radius: 16rpx;
	border: 2rpx dashed #c5d0c8;
	background: #f7faf8;
	overflow: hidden;
	box-sizing: border-box;
}
.scf-slot-box--done {
	border-style: solid;
	border-color: #a8ddb8;
}
.scf-slot-img {
	width: 100%;
	height: 100%;
}
.scf-slot-placeholder {
	height: 100%;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 8rpx;
}
.scf-slot-emoji {
	font-size: 40rpx;
	line-height: 1.1;
}
.scf-slot-add-t {
	margin-top: 10rpx;
	font-size: 20rpx;
	color: #18af38;
}
.scf-photo-del {
	position: absolute;
	top: 0;
	right: 0;
	width: 44rpx;
	height: 44rpx;
	line-height: 40rpx;
	text-align: center;
	background: rgba(0, 0, 0, 0.55);
	color: #fff;
	font-size: 32rpx;
	z-index: 2;
}
.scf-slot-del {
	border-radius: 0 0 0 12rpx;
}
.scf-clock-photo-foot {
	display: block;
	margin-top: 16rpx;
	font-size: 22rpx;
	color: #8a958c;
	line-height: 1.5;
}
.scf-clock-note {
	margin-top: 24rpx;
}
.scf-clock-note-label {
	display: block;
	font-size: 26rpx;
	font-weight: 600;
	color: #243028;
	margin-bottom: 10rpx;
}
.scf-clock-textarea {
	min-height: 160rpx;
	background: #f5f7f6;
	border-radius: 12rpx;
	padding: 16rpx;
	box-sizing: border-box;
}
.scf-clock-submit {
	margin-top: 28rpx;
}
.scf-clock-submit--disabled {
	opacity: 0.65;
}
.scf-learn-link {
	margin-top: 24rpx;
	padding-top: 20rpx;
	border-top: 1rpx solid #eef0ee;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 8rpx;
	font-size: 26rpx;
	color: #7a857c;
}
.scf-learn-link-go {
	color: #18af38;
	font-weight: 600;
}
.scf-success-mask {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	z-index: 999;
	background: rgba(0, 0, 0, 0.52);
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 40rpx;
}
.scf-success-dialog {
	width: 100%;
	max-width: 600rpx;
	background: #fff;
	border-radius: 24rpx;
	padding: 40rpx 32rpx 32rpx;
	text-align: center;
}
.scf-success-emoji {
	display: block;
	width: 96rpx;
	height: 96rpx;
	line-height: 96rpx;
	margin: 0 auto 16rpx;
	border-radius: 50%;
	background: rgba(24, 175, 56, 0.12);
	color: #18af38;
	font-size: 48rpx;
	font-weight: 800;
}
.scf-success-title {
	font-size: 36rpx;
	font-weight: 800;
	color: #142018;
}
.scf-success-sub {
	display: block;
	margin-top: 10rpx;
	font-size: 26rpx;
	color: #6a756c;
}
.scf-habit-box {
	margin-top: 20rpx;
	padding: 16rpx;
	background: #f5faf6;
	border-radius: 12rpx;
	text-align: left;
}
.scf-habit-kicker {
	display: block;
	font-size: 22rpx;
	color: #18af38;
	font-weight: 600;
}
.scf-habit-text {
	display: block;
	margin-top: 6rpx;
	font-size: 26rpx;
	color: #4a564e;
	line-height: 1.5;
}
.scf-success-actions {
	margin-top: 28rpx;
	display: flex;
	flex-direction: column;
	gap: 16rpx;
}
.scf-success-back {
	margin-top: 0;
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
