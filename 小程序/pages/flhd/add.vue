<template>
	<view :class="['clock-wiz', pageTheme]" v-if="!setting.app_test">
		<view class="clock-wiz-hero">
			<text class="clock-wiz-kicker">{{ sceneLabel ? '本次场景 · ' + sceneLabel : '践行真分' }}</text>
			<text class="clock-wiz-title">{{ stepTitle }}</text>
			<view class="clock-wiz-steps">
				<view
					v-for="n in 3"
					:key="n"
					class="clock-wiz-step"
					:class="{ 'clock-wiz-step--on': wizardStep >= n, 'clock-wiz-step--cur': wizardStep === n }"
				>
					<text class="clock-wiz-step-num">{{ n }}</text>
					<text class="clock-wiz-step-lab">{{ stepLabels[n - 1] }}</text>
				</view>
			</view>
		</view>

		<!-- Step 1：四种垃圾类型 -->
		<view class="clock-wiz-body" v-show="wizardStep === 1">
			<text class="clock-wiz-hint">请选择本次投放的垃圾类型</text>
			<view class="clock-cate-grid">
				<view
					v-for="(item, index) in cate_list"
					:key="'c-' + index"
					class="clock-cate-card"
					:class="'clock-cate-card--' + cateTone(item.name)"
					hover-class="clock-cate-card--press"
					@tap="pickCateAndNext(index)"
				>
					<text class="clock-cate-name">{{ item.name }}</text>
					<text class="clock-cate-sub">{{ cateHint(item.name) }}</text>
					<text class="clock-cate-go">选择 ›</text>
				</view>
			</view>
			<view class="clock-wiz-link" v-if="currentCateName" @tap="previewCurrentCate">
				<text>查看「{{ currentCateName }}」示例图</text>
			</view>
		</view>

		<!-- Step 2：上传照片 -->
		<view class="clock-wiz-body" v-show="wizardStep === 2">
			<view class="clock-wiz-panel">
				<text class="clock-wiz-panel-tag">{{ currentCateName }}</text>
				<text class="clock-wiz-panel-desc">请拍摄清晰照片，展示垃圾内容与分类是否正确</text>
				<view class="clock-upload-wrap">
					<cl-upload v-model="form.image" :action="uploadUrl" @onSuccess="onUploadSuccess" fileType="image">
						<template v-slot:addImg>
							<view class="clock-upload-add">
								<u-icon name="camera-fill" size="48" color="#18AF38"></u-icon>
								<text class="clock-upload-add-t">添加照片</text>
							</view>
						</template>
					</cl-upload>
				</view>
			</view>
			<view class="clock-wiz-foot">
				<u-button type="default" hover-class="none" :custom-style="btnGhost" @click="wizardStep = 1">上一步</u-button>
				<u-button type="success" hover-class="none" :custom-style="btnPrimary" @click="goStep3">下一步</u-button>
			</view>
		</view>

		<!-- Step 3：描述 + 提交 -->
		<view class="clock-wiz-body" v-show="wizardStep === 3">
			<view class="clock-wiz-panel">
				<text class="clock-wiz-panel-tag">{{ currentCateName }}</text>
				<text class="clock-wiz-panel-desc">{{ contentPlaceholder }}</text>
				<view class="clock-textarea-wrap">
					<u-input
						v-model="form.content"
						type="textarea"
						height="240"
						maxlength="-1"
						:placeholder="contentPlaceholder"
					/>
				</view>
				<view class="clock-thumb-row" v-if="form.image.length">
					<text class="clock-thumb-lab">已选 {{ form.image.length }} 张照片</text>
				</view>
			</view>
			<view class="clock-wiz-foot">
				<u-button type="default" hover-class="none" :custom-style="btnGhost" @click="wizardStep = 2">上一步</u-button>
				<u-button type="success" hover-class="none" :custom-style="btnPrimary" @click="submit">确认提交</u-button>
			</view>
		</view>

		<u-mask :show="mask_show">
			<view class="clock-success-wrap">
				<view class="clock-success-card">
					<u-icon name="checkmark-circle-fill" size="88" color="#18AF38"></u-icon>
					<text class="clock-success-title">{{ msg || '打卡成功' }}</text>
					<text class="clock-success-sub">请不要忘记正确投递哦~</text>
					<view class="clock-habit-box" v-if="habitTip">
						<text class="clock-habit-kicker">本次打卡结束，养成好习惯</text>
						<text class="clock-habit-text">{{ habitTip }}</text>
					</view>
					<view class="clock-success-btns">
						<u-button type="success" hover-class="none" shape="circle" :custom-style="btnPrimary" @click="closeMask">
							继续打卡
						</u-button>
						<u-button type="default" hover-class="none" shape="circle" @click="navigateBack">返回</u-button>
					</view>
				</view>
			</view>
		</u-mask>
	</view>
</template>

<script>
	import { uploadUrl } from '@/common/settings'
	import { pickRandomHabitTip } from '@/common/clockHabitTips.js'
	import {
		ADD_PAGE_META,
		clockSceneLabel,
		resolveCateKind,
		SECTION_HINTS,
	} from '@/common/clockScene.js'

	export default {
		data() {
			return {
				uploadUrl,
				wizardStep: 1,
				stepLabels: ['选类型', '拍照片', '写内容'],
				sceneKey: '',
				sceneName: '',
				form: {
					type: 2,
					cate_id: 0,
					content: '',
					image: [],
					clock_scene: '',
				},
				cate_list: [],
				cate_index: 0,
				pendingCateId: 0,
				currentCateName: '',
				mask_show: false,
				msg: '',
				habitTip: '',
				btnPrimary: {
					background: 'linear-gradient(141deg, #A4DB88 0%, #18AF38 100%)',
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
			sceneLabel() {
				return clockSceneLabel(this.sceneKey, this.sceneName)
			},
			pageMeta() {
				let key = this.sceneKey
				if (key === 'office') key = 'zhenfenjiao'
				if (key === 'home' || key === 'zhenfenjiao') {
					return ADD_PAGE_META[key] || ADD_PAGE_META.zhenfenjiao
				}
				return ADD_PAGE_META[this.sceneKey] || ADD_PAGE_META.home
			},
			pageTheme() {
				const key = this.sceneKey === 'office' ? 'zhenfenjiao' : this.sceneKey
				const meta = ADD_PAGE_META[key] || ADD_PAGE_META.home
				return (meta && meta.theme) || 'clock-add--home'
			},
			contentPlaceholder() {
				return (this.pageMeta && this.pageMeta.placeholder) || '可补充投放位置、心得或注意事项（选填）'
			},
			stepTitle() {
				if (this.wizardStep === 1) return '选择垃圾类型'
				if (this.wizardStep === 2) return '上传投放照片'
				return '补充说明并提交'
			},
		},
		onLoad(option) {
			const cateId = option.cate_id ? parseInt(option.cate_id, 10) : 0
			this.sceneKey = option.scene_key ? String(option.scene_key) : 'home'
			this.sceneName = option.scene ? decodeURIComponent(option.scene) : ''
			this.pendingCateId = cateId
			this.form.clock_scene = this.resolveClockScene(this.sceneKey)
			this.get_select_list()
			uni.setNavigationBarTitle({ title: (this.sceneLabel || '垃圾分类') + '打卡' })
		},
		methods: {
			resolveClockScene(sceneKey) {
				const k = String(sceneKey || '')
				if (k === 'home') return 'home'
				if (k === 'zhenfenjiao' || k === 'office') return 'zhenfenjiao'
				if (k === 'qinzi' || k === 'street') return k
				return 'zhenfenjiao'
			},
			cateTone(name) {
				const kind = resolveCateKind(name)
				if (kind === 'recycle') return 'recycle'
				if (kind === 'food') return 'food'
				if (kind === 'harm') return 'harm'
				return 'other'
			},
			cateHint(name) {
				const kind = resolveCateKind(name)
				return SECTION_HINTS[kind] || '按社区要求正确投放'
			},
			get_select_list() {
				this.$u.api.get_select_list({ type: 2 }).then(res => {
					this.cate_list = res.data || []
					this.applyPendingCate()
				})
			},
			applyPendingCate() {
				const pending = this.pendingCateId || 0
				if (!pending || !this.cate_list.length) return
				for (let i = 0; i < this.cate_list.length; i++) {
					if (Number(this.cate_list[i].id) === Number(pending)) {
						this.pickCate(i)
						this.wizardStep = 2
						break
					}
				}
			},
			pickCate(index) {
				const item = this.cate_list[index]
				if (!item) return
				this.cate_index = index
				this.form.cate_id = item.id
				this.currentCateName = item.name || ''
			},
			pickCateAndNext(index) {
				this.pickCate(index)
				this.wizardStep = 2
			},
			goStep3() {
				if (!this.form.image.length) return this.$u.toast('请先上传至少一张照片')
				this.wizardStep = 3
			},
			previewCurrentCate() {
				const item = this.cate_list[this.cate_index]
				if (!item || !item.waste_thumb) return this.$u.toast('暂无示例图')
				uni.previewImage({ urls: [item.waste_thumb] })
			},
			onUploadSuccess(reslut) {
				this.form.image.push(reslut.data.url)
			},
			refreshPrevList() {
				const prev = this.$u.func.prePage()
				if (prev && typeof prev.get_forum_list === 'function') {
					prev.forum_list = []
					prev.page = 1
					prev.get_forum_list()
				}
			},
			resetAfterSubmit() {
				this.form.content = ''
				this.form.image = []
				this.form.cate_id = 0
				this.currentCateName = ''
				this.wizardStep = 1
				this.pendingCateId = 0
				this.refreshPrevList()
			},
			submit() {
				const that = this
				const modalContent =
					'照片需要拍清楚垃圾的内容，保持分类正确，如果错误分类可能造成环境的危害和相应的撤销积分'
				if (!that.form.cate_id) return that.$u.toast('请选择垃圾种类')
				if (!that.form.image.length) return that.$u.toast('请先上传照片')
				uni.showModal({
					title: '提示',
					content: modalContent,
					success(res) {
						if (!res.confirm) return
						that.postOne({
							type: 2,
							cate_id: that.form.cate_id,
							content: that.form.content,
							image: that.form.image,
							clock_scene: that.form.clock_scene,
						})
							.then(msg => {
								that.msg = msg
								that.habitTip = pickRandomHabitTip()
								that.mask_show = true
								that.form.content = ''
								that.form.image = []
								that.refreshPrevList()
							})
							.catch(e => {
								that.$u.toast(e.msg || '提交失败')
							})
					},
				})
			},
			postOne(payload) {
				return this.$u.api.add_forum(payload).then(res => res.msg || '发布成功')
			},
			closeMask() {
				this.mask_show = false
				this.resetAfterSubmit()
			},
			navigateBack() {
				this.mask_show = false
				uni.navigateBack()
			},
		},
	}
</script>

<style lang="scss" scoped>
.clock-wiz {
	min-height: 100vh;
	padding-bottom: calc(32rpx + env(safe-area-inset-bottom));
}
.clock-add--home {
	background: linear-gradient(180deg, #d8eedc 0%, #eef4ef 320rpx, #f5f7f6 100%);
}
.clock-add--qinzi {
	background: linear-gradient(180deg, #ffe8d4 0%, #faf3ec 320rpx, #faf8f5 100%);
}
.clock-add--street {
	background: linear-gradient(180deg, #d4e8f8 0%, #eef6fb 320rpx, #f5f8fa 100%);
}
.clock-add--office {
	background: linear-gradient(180deg, #dce2ec 0%, #eef1f5 320rpx, #f6f7f9 100%);
}
.clock-wiz-hero {
	margin: 24rpx 28rpx 0;
	padding: 28rpx 28rpx 24rpx;
	border-radius: 24rpx;
	background: #fff;
	box-shadow: 0 10rpx 32rpx rgba(36, 48, 40, 0.08);
}
.clock-wiz-kicker {
	display: block;
	font-size: 24rpx;
	color: #8a958c;
}
.clock-wiz-title {
	display: block;
	margin-top: 8rpx;
	font-size: 38rpx;
	font-weight: 800;
	color: #243028;
}
.clock-wiz-steps {
	display: flex;
	justify-content: space-between;
	margin-top: 28rpx;
	padding-top: 20rpx;
	border-top: 1rpx solid #eef0ee;
}
.clock-wiz-step {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	opacity: 0.45;
}
.clock-wiz-step--on {
	opacity: 1;
}
.clock-wiz-step--cur .clock-wiz-step-num {
	background: linear-gradient(141deg, #a4db88 0%, #18af38 100%);
	color: #fff;
}
.clock-wiz-step-num {
	width: 44rpx;
	height: 44rpx;
	line-height: 44rpx;
	text-align: center;
	border-radius: 50%;
	font-size: 22rpx;
	font-weight: 700;
	background: #e8eee9;
	color: #666;
}
.clock-wiz-step-lab {
	margin-top: 8rpx;
	font-size: 22rpx;
	color: #666;
}
.clock-wiz-body {
	padding: 24rpx 28rpx 0;
}
.clock-wiz-hint {
	display: block;
	margin-bottom: 20rpx;
	font-size: 26rpx;
	color: #666;
}
.clock-cate-grid {
	display: flex;
	flex-direction: column;
}
.clock-cate-card {
	margin-bottom: 16rpx;
	position: relative;
	overflow: hidden;
	padding: 28rpx 28rpx 24rpx;
	border-radius: 20rpx;
	color: #fff;
	box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.12);
}
.clock-cate-card::before {
	content: '';
	position: absolute;
	right: -40rpx;
	top: -40rpx;
	width: 160rpx;
	height: 160rpx;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.12);
	pointer-events: none;
}
.clock-cate-card--press {
	opacity: 0.92;
	transform: scale(0.99);
}
.clock-cate-card--recycle {
	background: linear-gradient(141deg, #2b8bb8 0%, #156a90 100%);
}
.clock-cate-card--food {
	background: linear-gradient(141deg, #c48a3a 0%, #8b5a14 100%);
}
.clock-cate-card--harm {
	background: linear-gradient(141deg, #e75e5e 0%, #b32626 100%);
}
.clock-cate-card--other {
	background: linear-gradient(141deg, #8a959c 0%, #5c656c 100%);
}
.clock-cate-name {
	display: block;
	font-size: 34rpx;
	font-weight: 800;
}
.clock-cate-sub {
	display: block;
	margin-top: 10rpx;
	font-size: 24rpx;
	line-height: 1.45;
	opacity: 0.92;
}
.clock-cate-go {
	display: block;
	margin-top: 16rpx;
	font-size: 26rpx;
	font-weight: 700;
	text-align: right;
}
.clock-wiz-link {
	margin-top: 20rpx;
	text-align: center;
	font-size: 26rpx;
	color: #18af38;
}
.clock-wiz-panel {
	padding: 28rpx;
	border-radius: 20rpx;
	background: #fff;
	box-shadow: 0 8rpx 24rpx rgba(36, 48, 40, 0.06);
}
.clock-wiz-panel-tag {
	display: inline-block;
	padding: 6rpx 16rpx;
	border-radius: 8rpx;
	font-size: 22rpx;
	font-weight: 600;
	color: #148a2e;
	background: rgba(24, 175, 56, 0.12);
}
.clock-wiz-panel-desc {
	display: block;
	margin-top: 16rpx;
	font-size: 26rpx;
	line-height: 1.55;
	color: #666;
}
.clock-upload-wrap {
	margin-top: 24rpx;
}
.clock-upload-add {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	width: 138rpx;
	height: 138rpx;
	border-radius: 16rpx;
	background: #f0f7f1;
	border: 2rpx dashed #b8dfc4;
}
.clock-upload-add-t {
	margin-top: 8rpx;
	font-size: 22rpx;
	color: #18af38;
}
.clock-textarea-wrap {
	margin-top: 20rpx;
	padding: 16rpx 20rpx;
	border-radius: 16rpx;
	background: #f5f7f6;
}
.clock-thumb-lab {
	display: block;
	margin-top: 16rpx;
	font-size: 24rpx;
	color: #8a958c;
}
.clock-wiz-foot {
	display: flex;
	gap: 20rpx;
	margin-top: 32rpx;
}
.clock-wiz-foot ::v-deep .u-btn {
	flex: 1;
}
.clock-success-wrap {
	display: flex;
	align-items: center;
	justify-content: center;
	height: 100%;
	padding: 40rpx;
	box-sizing: border-box;
}
.clock-success-card {
	width: 100%;
	max-width: 620rpx;
	padding: 48rpx 36rpx 36rpx;
	border-radius: 24rpx;
	background: #fff;
	text-align: center;
	box-shadow: 0 16rpx 48rpx rgba(0, 0, 0, 0.12);
}
.clock-success-title {
	display: block;
	margin-top: 20rpx;
	font-size: 34rpx;
	font-weight: 800;
	color: #243028;
}
.clock-success-sub {
	display: block;
	margin-top: 12rpx;
	font-size: 24rpx;
	color: #c45c2a;
}
.clock-habit-box {
	margin-top: 28rpx;
	padding: 24rpx 20rpx;
	border-radius: 16rpx;
	background: linear-gradient(135deg, #f0faf2 0%, #e8f5ea 100%);
	text-align: left;
}
.clock-habit-kicker {
	display: block;
	font-size: 24rpx;
	font-weight: 700;
	color: #148a2e;
}
.clock-habit-text {
	display: block;
	margin-top: 12rpx;
	font-size: 28rpx;
	line-height: 1.55;
	color: #444;
}
.clock-success-btns {
	margin-top: 32rpx;
	display: flex;
	flex-direction: column;
	gap: 16rpx;
}
</style>
