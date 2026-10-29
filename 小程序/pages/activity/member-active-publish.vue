<template>
	<view class="map-page">
		<view class="map-hero">
			<text class="map-hero-title">{{ headline }}</text>
			<text class="map-hero-sub">{{ subline }}</text>
		</view>

		<view class="map-card">
			<view class="field">
				<text class="field-label">活动名称 <text class="required">*</text></text>
				<text class="field-hint">一句话说清「做什么」，30 字以内</text>
				<input class="input" v-model="form.name" placeholder="例如：周末亲子徒步捡垃圾" maxlength="30" />
			</view>

			<view class="field">
				<text class="field-label">活动类型 <text class="required">*</text></text>
				<text class="field-hint">方便邻居快速了解活动气质</text>
				<view class="chips">
					<view
						class="chip"
						:class="{ on: form.theme_id === t.id }"
						v-for="t in themes"
						:key="t.id"
						@tap="form.theme_id = t.id"
					>{{ t.name }}</view>
				</view>
			</view>

			<view class="field">
				<text class="field-label">封面图 <text class="required">*</text></text>
				<text class="field-hint">横图效果更好，建议活动现场或主题相关照片</text>
				<view class="cover-row">
					<view class="cover-box" v-if="form.coverPreview" @tap="chooseCover">
						<image :src="form.coverPreview" mode="aspectFill" class="cover-img"></image>
						<text class="cover-change">更换</text>
					</view>
					<view class="cover-add" v-else @tap="chooseCover">
						<u-icon name="camera" size="44" color="#16864b"></u-icon>
						<text>添加封面</text>
					</view>
				</view>
			</view>

			<view class="field">
				<text class="field-label">活动日期 <text class="required">*</text></text>
				<picker mode="date" :value="form.activity_date" :start="dateStart" @change="onDateChange">
					<view class="picker-line">
						<text :class="{ placeholder: !form.activity_date }">{{ form.activity_date || '选择日期' }}</text>
						<u-icon name="calendar" size="32" color="#18AF38"></u-icon>
					</view>
				</picker>
			</view>

			<view class="field field-row">
				<view class="field-half">
					<text class="field-label">开始 <text class="required">*</text></text>
					<picker mode="time" :value="form.start_time" @change="onStartChange">
						<view class="picker-line">
							<text>{{ form.start_time || '09:00' }}</text>
						</view>
					</picker>
				</view>
				<view class="field-half">
					<text class="field-label">结束 <text class="required">*</text></text>
					<picker mode="time" :value="form.end_time" @change="onEndChange">
						<view class="picker-line">
							<text>{{ form.end_time || '11:00' }}</text>
						</view>
					</picker>
				</view>
			</view>
			<text class="field-hint field-hint--block">该时段内支持现场定位签到（500 米内）</text>

			<view class="field">
				<text class="field-label">活动地点 <text class="required">*</text></text>
				<text class="field-hint">点选地图位置，自动填入地址与坐标</text>
				<view class="loc-card" @tap="chooseLocation">
					<view class="loc-main">
						<text class="loc-name u-line-1">{{ form.place_name || '点击在地图上选点' }}</text>
						<text class="loc-addr u-line-2">{{ form.address || '支持微信地图选点，选好后可微调地址' }}</text>
					</view>
					<u-icon name="map" size="36" color="#18AF38"></u-icon>
				</view>
			</view>

			<view class="field">
				<text class="field-label">联系方式 <text class="required">*</text></text>
				<text class="field-hint">手机号或微信号，仅报名用户可见</text>
				<input class="input" v-model="form.contact" placeholder="方便参与者联系您" maxlength="40" />
			</view>
		</view>

		<!-- 详情页预览 -->
		<view class="preview-section">
			<view class="preview-head">
				<text class="preview-title">详情页预览</text>
				<view class="preview-tabs">
					<text class="preview-tab" :class="{ on: previewMode === 'mine' }" @tap="previewMode = 'mine'">我的内容</text>
					<text class="preview-tab" :class="{ on: previewMode === 'example' }" @tap="previewMode = 'example'">样式示例</text>
				</view>
			</view>
			<text class="preview-hint">报名后邻居在活动详情里看到的效果（时间地点等固定信息会自动展示在上方）</text>
			<member-active-blocks-display
				:blocks="previewBlocks"
				:placeholder-text="previewMode === 'example' ? '' : '填写后将显示在这里'"
			/>
		</view>

		<view class="map-card map-card--blocks">
			<view class="blocks-head">
				<text class="field-label">自定义展示字段</text>
				<text class="field-hint">可新增、排序；支持单行、多行、图片集</text>
			</view>
			<view class="block-editor" v-for="(block, idx) in customBlocks" :key="block.id">
				<view class="block-editor-toolbar">
					<input class="block-label-input" v-model="block.label" placeholder="字段标题，如：温馨提示" maxlength="20" />
					<view class="block-type-tag">{{ blockTypeName(block.type) }}</view>
					<text class="block-tool" @tap="moveBlock(idx, -1)" v-if="idx > 0">上移</text>
					<text class="block-tool" @tap="moveBlock(idx, 1)" v-if="idx < customBlocks.length - 1">下移</text>
					<text class="block-tool block-tool--danger" @tap="removeBlock(idx)">删除</text>
				</view>
				<input
					v-if="block.type === 'text'"
					class="input"
					v-model="block.value"
					placeholder="单行内容"
					maxlength="100"
				/>
				<textarea
					v-else-if="block.type === 'textarea'"
					class="textarea"
					v-model="block.value"
					placeholder="多行内容，可换行"
					maxlength="800"
				/>
				<view v-else-if="block.type === 'images'" class="block-images">
					<view class="photos">
						<view class="photo" v-for="(src, pi) in block.images" :key="'p-' + pi">
							<image :src="src" mode="aspectFill" class="photo-image"></image>
							<view class="photo-delete" @tap="removeBlockImage(idx, pi)">×</view>
						</view>
						<view class="photo photo-add" v-if="blockImageCount(block) < 6" @tap="chooseBlockImages(idx)">
							<u-icon name="camera" size="36" color="#16864b"></u-icon>
							<text>添加</text>
						</view>
					</view>
				</view>
			</view>
			<view class="add-block-row">
				<text class="add-block-label">添加字段：</text>
				<view
					class="chip chip--add"
					v-for="t in blockTypes"
					:key="t.type"
					@tap="addBlock(t.type)"
				>{{ t.name }}</view>
			</view>
		</view>

		<view class="map-tip">
			提交后进入审核，通过后在活动页「玩转活动」展示。配置式活动：邻居可报名，现场定位签到；不消耗积分，平台不代收费用。
		</view>

		<view class="map-submit" :class="{ disabled: submitting }" @tap="submit">{{ submitting ? '提交中…' : '提交发布' }}</view>
	</view>
</template>

<script>
import { uploadUrl, headerTokenName, userTokenName } from '@/common/settings.js'
import { parseUploadAssetResponse } from '@/common/uploadAsset.js'
import MemberActiveBlocksDisplay from '@/components/member-active-blocks-display/member-active-blocks-display.vue'
import {
	MEMBER_BLOCK_TYPES,
	createBlock,
	defaultPublishBlocks,
	examplePreviewBlocks,
	moveBlock as moveBlockList,
	validateBlocks,
	serializeBlocksForApi,
} from '@/common/memberActiveBlocks.js'

export default {
	components: { MemberActiveBlocksDisplay },
	data() {
		const today = new Date()
		const p = (n) => String(n).padStart(2, '0')
		const dateStart = `${today.getFullYear()}-${p(today.getMonth() + 1)}-${p(today.getDate())}`
		return {
			headline: '发一场邻里小活动',
			subline: '只填必要信息，其余交给小程序报名与签到',
			themes: [],
			dateStart,
			submitting: false,
			coverPath: '',
			previewMode: 'mine',
			blockTypes: MEMBER_BLOCK_TYPES,
			customBlocks: defaultPublishBlocks(),
			exampleBlocks: examplePreviewBlocks(),
			form: {
				name: '',
				theme_id: 0,
				activity_date: '',
				start_time: '09:00',
				end_time: '11:00',
				place_name: '',
				address: '',
				lat: '',
				lng: '',
				contact: '',
				coverPreview: '',
			},
		}
	},
	onLoad() {
		if (!this.$u.func.checkLogin()) return
		this.$u.api.memberActiveMeta().then((res) => {
			const data = res.data || {}
			this.themes = data.themes || []
			if (data.tips) {
				this.headline = data.tips.headline || this.headline
				this.subline = data.tips.sub || this.subline
			}
			if (this.themes.length && !this.form.theme_id) {
				this.form.theme_id = this.themes[0].id
			}
		}).catch(() => {})
		const mobile = (this.userInfo && this.userInfo.mobile) || ''
		if (mobile) this.form.contact = mobile
	},
	computed: {
		previewBlocks() {
			if (this.previewMode === 'example') {
				return this.exampleBlocks
			}
			return (this.customBlocks || []).map((b) => ({
				type: b.type,
				label: b.label,
				value: b.value,
				images: b.type === 'images' ? (b.images || []) : [],
			}))
		},
	},
	methods: {
		blockTypeName(type) {
			const t = this.blockTypes.find((x) => x.type === type)
			return (t && t.name) || type
		},
		addBlock(type) {
			this.customBlocks = this.customBlocks.concat([createBlock(type, { label: '', value: '' })])
			this.previewMode = 'mine'
		},
		removeBlock(idx) {
			if (this.customBlocks.length <= 1) {
				return uni.showToast({ title: '至少保留 1 个展示字段', icon: 'none' })
			}
			this.customBlocks = this.customBlocks.filter((_, i) => i !== idx)
		},
		moveBlock(idx, dir) {
			this.customBlocks = moveBlockList(this.customBlocks, idx, dir)
		},
		blockImageCount(block) {
			return (block.images || []).length + (block.imagePaths || []).length
		},
		chooseBlockImages(blockIdx) {
			const block = this.customBlocks[blockIdx]
			if (!block || block.type !== 'images') return
			const left = 6 - this.blockImageCount(block)
			if (left <= 0) return
			uni.chooseImage({
				count: left,
				sizeType: ['compressed'],
				sourceType: ['album', 'camera'],
				success: (res) => {
					const paths = res.tempFilePaths || []
					block.images = (block.images || []).concat(paths).slice(0, 6)
					this.$set(this.customBlocks, blockIdx, block)
				},
			})
		},
		removeBlockImage(blockIdx, pi) {
			const block = this.customBlocks[blockIdx]
			if (!block || !block.images) return
			block.images.splice(pi, 1)
			block.imagePaths && block.imagePaths.splice(pi, 1)
			this.$set(this.customBlocks, blockIdx, block)
		},
		uploadOne(filePath) {
			const openid = (this.userInfo && this.userInfo[userTokenName]) || ''
			return new Promise((resolve, reject) => {
				uni.uploadFile({
					url: uploadUrl,
					filePath,
					name: 'file',
					formData: { filetype: 'image' },
					header: { [headerTokenName]: openid },
					success: (res) => {
						try {
							const body = JSON.parse(res.data)
							if (body.code != 1 || !body.data) throw new Error(body.msg || '上传失败')
							const asset = parseUploadAssetResponse(body)
							if (!asset.savePath) throw new Error('上传失败')
							resolve(asset.savePath)
						} catch (e) {
							reject(e)
						}
					},
					fail: reject,
				})
			})
		},
		onDateChange(e) {
			this.form.activity_date = e.detail.value
		},
		onStartChange(e) {
			this.form.start_time = e.detail.value
		},
		onEndChange(e) {
			this.form.end_time = e.detail.value
		},
		chooseLocation() {
			uni.chooseLocation({
				success: (res) => {
					this.form.place_name = res.name || ''
					this.form.address = res.address || res.name || ''
					this.form.lat = res.latitude
					this.form.lng = res.longitude
				},
			})
		},
		chooseCover() {
			uni.chooseImage({
				count: 1,
				sizeType: ['compressed'],
				sourceType: ['album', 'camera'],
				success: (res) => {
					const path = (res.tempFilePaths || [])[0]
					if (path) {
						this.form.coverPreview = path
						this.coverPath = ''
					}
				},
			})
		},
		async submit() {
			if (this.submitting) return
			if (!String(this.form.name).trim()) {
				return uni.showToast({ title: '请填写活动名称', icon: 'none' })
			}
			if (!this.form.theme_id) {
				return uni.showToast({ title: '请选择活动类型', icon: 'none' })
			}
			if (!this.form.coverPreview) {
				return uni.showToast({ title: '请添加封面图', icon: 'none' })
			}
			if (!this.form.activity_date) {
				return uni.showToast({ title: '请选择活动日期', icon: 'none' })
			}
			if (!this.form.address || !this.form.lat || !this.form.lng) {
				return uni.showToast({ title: '请在地图上选活动地点', icon: 'none' })
			}
			if (!String(this.form.contact).trim()) {
				return uni.showToast({ title: '请填写联系方式', icon: 'none' })
			}
			const blockErr = validateBlocks(this.customBlocks)
			if (blockErr) {
				return uni.showToast({ title: blockErr, icon: 'none' })
			}
			this.submitting = true
			uni.showLoading({ title: '上传图片…', mask: true })
			try {
				if (!this.coverPath) {
					this.coverPath = await this.uploadOne(this.form.coverPreview)
				}
				for (let i = 0; i < this.customBlocks.length; i++) {
					const block = this.customBlocks[i]
					if (block.type !== 'images') continue
					block.imagePaths = block.imagePaths || []
					for (let j = 0; j < (block.images || []).length; j++) {
						const local = block.images[j]
						if (!local || String(local).indexOf('http') === 0) {
							if (local && !block.imagePaths[j]) block.imagePaths[j] = local
							continue
						}
						if (!block.imagePaths[j]) {
							block.imagePaths[j] = await this.uploadOne(local)
						}
					}
					block.imagePaths = block.imagePaths.filter(Boolean).slice(0, 6)
					this.$set(this.customBlocks, i, block)
				}
				const payloadBlocks = serializeBlocksForApi(this.customBlocks)
				uni.showLoading({ title: '提交中…', mask: true })
				await this.$u.api.memberActivePublish({
					name: this.form.name,
					thumb: this.coverPath,
					activity_date: this.form.activity_date,
					start_time: this.form.start_time,
					end_time: this.form.end_time,
					place_name: this.form.place_name,
					address: this.form.address,
					lat: this.form.lat,
					lng: this.form.lng,
					contact: this.form.contact,
					theme_id: this.form.theme_id,
					custom_blocks: JSON.stringify(payloadBlocks),
				})
				uni.hideLoading()
				uni.showToast({ title: '已提交，审核通过后展示', icon: 'success' })
				try {
					uni.setStorageSync('member_active_tab_refresh', 1)
				} catch (e) {}
				setTimeout(() => {
					uni.switchTab({
						url: '/pages/low_carbon/low_carbon',
						fail: () => uni.navigateBack(),
					})
				}, 700)
			} catch (e) {
				uni.hideLoading()
				uni.showToast({ title: (e && (e.message || e.msg)) || '发布失败', icon: 'none' })
			} finally {
				this.submitting = false
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.map-page {
	min-height: 100vh;
	box-sizing: border-box;
	padding: 20rpx 28rpx calc(40rpx + env(safe-area-inset-bottom));
	background: linear-gradient(180deg, #eef7f0 0%, #f4f7f5 28%);
}
.map-hero {
	padding: 16rpx 8rpx 28rpx;
}
.map-hero-title {
	display: block;
	font-size: 38rpx;
	font-weight: 700;
	color: #1a2e22;
}
.map-hero-sub {
	display: block;
	margin-top: 10rpx;
	font-size: 26rpx;
	color: #5f7166;
	line-height: 1.5;
}
.map-card {
	padding: 28rpx;
	border-radius: 24rpx;
	background: #fff;
	box-shadow: 0 10rpx 36rpx rgba(22, 53, 36, 0.06);
}
.field {
	margin-top: 32rpx;
}
.field:first-child {
	margin-top: 0;
}
.field-row {
	display: flex;
	gap: 20rpx;
}
.field-half {
	flex: 1;
}
.field-label {
	display: block;
	font-size: 28rpx;
	font-weight: 600;
	color: #26372d;
}
.field-hint {
	display: block;
	margin-top: 8rpx;
	font-size: 23rpx;
	color: #8a9a90;
	line-height: 1.45;
}
.field-hint--block {
	margin-top: 12rpx;
	padding-left: 4rpx;
}
.required {
	color: #e54d42;
}
.input,
.textarea,
.picker-line {
	box-sizing: border-box;
	width: 100%;
	margin-top: 14rpx;
	padding: 0 20rpx;
	border-radius: 14rpx;
	background: #f5f8f6;
	font-size: 28rpx;
	color: #1a2e22;
}
.input {
	height: 84rpx;
}
.textarea {
	min-height: 168rpx;
	padding-top: 18rpx;
	padding-bottom: 18rpx;
}
.picker-line {
	display: flex;
	align-items: center;
	justify-content: space-between;
	height: 84rpx;
}
.placeholder {
	color: #aab5ad;
}
.chips {
	display: flex;
	flex-wrap: wrap;
	gap: 14rpx;
	margin-top: 16rpx;
}
.chip {
	padding: 14rpx 22rpx;
	border-radius: 999rpx;
	background: #f1f4f2;
	color: #526057;
	font-size: 25rpx;
}
.chip.on {
	background: #e3f3ea;
	color: #076d3d;
	font-weight: 600;
}
.cover-row {
	margin-top: 16rpx;
}
.cover-add,
.cover-box {
	width: 100%;
	height: 280rpx;
	border-radius: 16rpx;
	overflow: hidden;
	background: #f3f6f4;
}
.cover-add {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	border: 2rpx dashed #bddbc8;
	color: #16864b;
	font-size: 24rpx;
}
.cover-img {
	width: 100%;
	height: 100%;
}
.cover-box {
	position: relative;
}
.cover-change {
	position: absolute;
	right: 16rpx;
	bottom: 16rpx;
	padding: 8rpx 20rpx;
	border-radius: 999rpx;
	background: rgba(0, 0, 0, 0.45);
	color: #fff;
	font-size: 22rpx;
}
.loc-card {
	display: flex;
	align-items: center;
	margin-top: 14rpx;
	padding: 22rpx 20rpx;
	border-radius: 16rpx;
	background: #f5f8f6;
	border: 1rpx solid rgba(24, 175, 56, 0.12);
}
.loc-main {
	flex: 1;
	min-width: 0;
	margin-right: 16rpx;
}
.loc-name {
	display: block;
	font-size: 28rpx;
	font-weight: 600;
	color: #1a2e22;
}
.loc-addr {
	display: block;
	margin-top: 8rpx;
	font-size: 24rpx;
	color: #7a8a80;
}
.map-tip {
	margin: 24rpx 8rpx 0;
	font-size: 23rpx;
	color: #859088;
	line-height: 1.65;
}
.map-submit {
	height: 96rpx;
	margin-top: 32rpx;
	border-radius: 999rpx;
	background: linear-gradient(135deg, #1f9d55, #16864b);
	color: #fff;
	text-align: center;
	line-height: 96rpx;
	font-size: 30rpx;
	font-weight: 600;
	box-shadow: 0 12rpx 28rpx rgba(22, 134, 75, 0.28);
}
.map-submit.disabled {
	opacity: 0.65;
}
.preview-section {
	margin-top: 24rpx;
	padding: 24rpx;
	border-radius: 24rpx;
	background: #fff;
	box-shadow: 0 10rpx 36rpx rgba(22, 53, 36, 0.06);
}
.preview-head {
	display: flex;
	align-items: center;
	justify-content: space-between;
	flex-wrap: wrap;
	gap: 12rpx;
}
.preview-title {
	font-size: 30rpx;
	font-weight: 700;
	color: #1a2e22;
}
.preview-tabs {
	display: flex;
	gap: 12rpx;
}
.preview-tab {
	padding: 8rpx 18rpx;
	border-radius: 999rpx;
	font-size: 22rpx;
	color: #5f7166;
	background: #f1f4f2;
}
.preview-tab.on {
	background: #e3f3ea;
	color: #076d3d;
	font-weight: 600;
}
.preview-hint {
	display: block;
	margin: 12rpx 0 20rpx;
	font-size: 22rpx;
	color: #8a9a90;
	line-height: 1.5;
}
.map-card--blocks {
	margin-top: 20rpx;
}
.blocks-head {
	margin-bottom: 8rpx;
}
.block-editor {
	margin-top: 24rpx;
	padding-top: 20rpx;
	border-top: 1rpx solid #eef2ef;
}
.block-editor-toolbar {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	gap: 10rpx;
	margin-bottom: 12rpx;
}
.block-label-input {
	flex: 1;
	min-width: 200rpx;
	height: 72rpx;
	padding: 0 16rpx;
	border-radius: 12rpx;
	background: #f5f8f6;
	font-size: 26rpx;
}
.block-type-tag {
	padding: 6rpx 14rpx;
	border-radius: 8rpx;
	background: #eef7f0;
	color: #16864b;
	font-size: 22rpx;
}
.block-tool {
	font-size: 22rpx;
	color: #18af38;
	padding: 6rpx 8rpx;
}
.block-tool--danger {
	color: #e54d42;
}
.block-images .photos {
	display: flex;
	flex-wrap: wrap;
	gap: 14rpx;
}
.block-images .photo {
	position: relative;
	width: 140rpx;
	height: 140rpx;
	border-radius: 12rpx;
	overflow: hidden;
	background: #f3f6f4;
}
.block-images .photo-image {
	width: 100%;
	height: 100%;
}
.block-images .photo-delete {
	position: absolute;
	top: 0;
	right: 0;
	width: 36rpx;
	height: 36rpx;
	text-align: center;
	line-height: 34rpx;
	color: #fff;
	background: rgba(0, 0, 0, 0.45);
}
.block-images .photo-add {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	border: 2rpx dashed #bddbc8;
	color: #16864b;
	font-size: 20rpx;
}
.add-block-row {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	gap: 12rpx;
	margin-top: 28rpx;
	padding-top: 20rpx;
	border-top: 1rpx dashed #dce8de;
}
.add-block-label {
	font-size: 24rpx;
	color: #5f7166;
}
.chip--add {
	background: #fff;
	border: 1rpx solid #c8e6d0;
	color: #16864b;
}
</style>
