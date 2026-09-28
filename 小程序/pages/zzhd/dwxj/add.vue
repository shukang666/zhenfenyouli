<template>
	<view class="add-page" v-if="!setting.app_test">
		<view class="add-hero">
			<text class="add-hero-title">{{ pageTitle }}</text>
			<text class="add-hero-sub">选点位 · 写说明 · 拍齐三张照片</text>
			<view class="add-steps u-flex u-row-between">
				<view class="add-step" v-for="(s, i) in stepLabels" :key="i">
					<view class="add-step-dot">{{ i + 1 }}</view>
					<text class="add-step-lab">{{ s }}</text>
				</view>
			</view>
		</view>

		<view class="add-body">
			<u-form :model="form" ref="uForm" :error-type="['toast']">
				<view class="add-card">
					<view class="add-card-head">
						<text class="add-card-title">基本信息</text>
					</view>
					<u-form-item label="上报点位" prop="cate_name" label-width="160" :border-bottom="true">
						<u-input v-model="form.cate_name" placeholder="点击选择点位" type="select" @click="cate_show = true" />
					</u-form-item>
					<u-form-item label="上报内容" prop="content" label-width="160" label-position="top" :border-bottom="false">
						<u-input
							v-model="form.content"
							type="textarea"
							height="200"
							maxlength="-1"
							placeholder="请描述本次巡检情况，如桶况、引导情况等"
						/>
					</u-form-item>
				</view>
			</u-form>

			<view class="add-card add-photo-card">
				<view class="add-card-head">
					<text class="add-card-title">现场三照</text>
					<text class="add-card-sub">点击添加，拍后可点图放大</text>
				</view>
				<view class="photo-row u-flex u-row-between">
					<view class="photo-col" v-for="sec in photoSections" :key="sec.key">
						<view class="photo-slot" hover-class="photo-slot--press" @click="onPhotoSlotTap(sec.key)">
							<image
								v-if="form[sec.key]"
								class="photo-slot-img"
								:src="photoPreviewUrl(sec.key)"
								mode="aspectFill"
								@click.stop="previewPhotos(sec.key)"
							></image>
							<view v-else class="photo-slot-empty">
								<u-icon name="camera-fill" size="44" color="#18AF38"></u-icon>
								<text class="photo-slot-add">添加</text>
							</view>
							<view
								v-if="form[sec.key]"
								class="photo-del"
								@click.stop="clearPhoto(sec.key)"
							>
								<u-icon name="close" size="24" color="#fff"></u-icon>
							</view>
						</view>
						<text class="photo-col-label">{{ sec.title }}</text>
					</view>
				</view>
			</view>
		</view>

		<u-select v-model="cate_show" label-name="name" value-name="id" :list="cate_list" @confirm="confirm"></u-select>

		<u-mask :show="tipShow">
			<view class="u-w-560 u-radius-10 u-type-white-bg u-abso-center u-p-30" v-if="showType == 1">
				<view class="u-text-center">
					<image src="https://admin.circular-economy.cn/static/images/dakachenggong.png" class="u-w-280 u-h-188" mode="aspectFill"></image>
					<view class="u-font-32 u-m-t-30">恭喜您获得 <text class="u-font-40 u-main-c u-font-bold">{{ point }}</text> 积分</view>
				</view>
				<view class="u-m-t-30 u-flex u-row-center">
					<u-button :custom-style="{ width: '400rpx', background: 'linear-gradient(141deg, #A4DB88, #18AF38)', color: '#fff' }" @click="goBack">我知道了</u-button>
				</view>
			</view>
			<view class="u-w-560 u-radius-10 u-type-white-bg u-abso-center u-p-30" v-if="showType == 2">
				<view class="u-text-center">
					<image src="https://admin.circular-economy.cn/static/images/wenxintishi.png" class="u-w-280 u-h-188" mode="aspectFill"></image>
					<view class="u-m-t-30">
						<view class="u-m-b-30">请在倒计时结束后打卡</view>
						<u-count-down
							:timestamp="remaining_seconds"
							:show-border="true"
							font-size="48"
							height="80"
							color="#ffffff"
							border-color="#18AF38"
							bg-color="#18AF38"
							separator-color="#18AF38"
						></u-count-down>
					</view>
				</view>
				<view class="u-m-t-30 u-flex u-row-center">
					<u-button :custom-style="{ width: '400rpx' }" @click="tipShow = false">我知道了</u-button>
				</view>
			</view>
		</u-mask>

		<view class="add-footer">
			<view class="add-submit-btn" hover-class="add-submit-btn--press" @click="submit">确认提交</view>
			<view class="add-record-link" @click="$u.route('/pages/zzhd/dwxj/record?type=' + form.type)">发布记录</view>
		</view>
	</view>
</template>

<script>
	import { uploadUrl } from '@/common/settings'
	export default {
		data() {
			return {
				uploadUrl,
				form: {
					type: 6,
					cate_id: 0,
					cate_name: '',
					content: '',
					image_one: '',
					image_two: '',
					image_three: '',
				},
				/** 列表展示用完整 URL，提交仍用 form 内 filepath */
				photoShowUrl: {
					image_one: '',
					image_two: '',
					image_three: '',
				},
				rules: {
					content: [{
						required: true,
						message: '请输入上报内容',
						trigger: ['change', 'blur'],
					}],
					cate_name: [{
						required: true,
						message: '请选择点位',
						trigger: ['change', 'blur'],
					}],
				},
				cate_show: false,
				cate_list: [],
				showType: 1,
				tipShow: false,
				point: 0,
				remaining_seconds: 0,
				stepLabels: ['选点位', '写说明', '拍三照'],
				photoSections: [
					{ key: 'image_one', title: '点位照片' },
					{ key: 'image_two', title: '投放指南' },
					{ key: 'image_three', title: '垃圾桶照片' },
				],
			}
		},
		computed: {
			pageTitle() {
				return String(this.form.type) === '4' ? '桶边督导上报' : '点位巡检上报'
			},
		},
		onLoad(option) {
			this.form.type = option.type
			const isType4 = String(option.type) === '4'
			uni.setNavigationBarTitle({
				title: isType4 ? '桶边督导' : '开始巡检',
			})
			this.get_community_cate_list()
		},
		methods: {
			photoPreviewUrl(key) {
				if (this.photoShowUrl[key]) return this.photoShowUrl[key]
				const path = this.form[key]
				if (!path) return ''
				if (/^https?:\/\//i.test(path)) return path
				const base = this.imgUrl || ''
				return base + path
			},
			onPhotoSlotTap(key) {
				if (this.form[key]) {
					this.previewPhotos(key)
					return
				}
				this.choosePhoto(key)
			},
			choosePhoto(key) {
				uni.chooseImage({
					count: 1,
					sourceType: ['camera', 'album'],
					success: (res) => {
						const path = (res.tempFilePaths || [])[0]
						if (path) this.uploadPhoto(path, key)
					},
				})
			},
			uploadPhoto(filePath, key) {
				uni.showLoading({ title: '上传中...' })
				uni.uploadFile({
					url: this.uploadUrl,
					filePath,
					name: 'file',
					formData: { filetype: 'image' },
					success: (uploadRes) => {
						try {
							const data = JSON.parse(uploadRes.data)
							if (data.code == 1 && data.data) {
								const filepath = data.data.filepath || data.data.url || ''
								const showUrl = data.data.url || this.photoPreviewUrlFromPath(filepath)
								if (!filepath) {
									uni.showToast({ title: '上传失败', icon: 'none' })
									return
								}
								this.form[key] = filepath
								this.$set(this.photoShowUrl, key, showUrl || filepath)
							} else {
								uni.showToast({ title: data.msg || '上传失败', icon: 'none' })
							}
						} catch (e) {
							uni.showToast({ title: '上传失败', icon: 'none' })
						}
					},
					fail: () => uni.showToast({ title: '上传失败', icon: 'none' }),
					complete: () => uni.hideLoading(),
				})
			},
			photoPreviewUrlFromPath(path) {
				if (!path) return ''
				if (/^https?:\/\//i.test(path)) return path
				return (this.imgUrl || '') + path
			},
			clearPhoto(key) {
				this.form[key] = ''
				this.$set(this.photoShowUrl, key, '')
			},
			previewPhotos(currentKey) {
				const keys = ['image_one', 'image_two', 'image_three']
				const urls = keys.filter(k => this.form[k]).map(k => this.photoPreviewUrl(k))
				if (!urls.length) return
				const current = this.photoPreviewUrl(currentKey)
				uni.previewImage({
					urls,
					current: current || urls[0],
				})
			},
			confirm(e) {
				this.form.cate_id = e[0].value
				this.form.cate_name = e[0].label
			},
			get_community_cate_list() {
				this.$u.api.get_community_cate_list().then(res => {
					this.cate_list = res.data
				})
			},
			goBack() {
				this.tipShow = false
				uni.redirectTo({
					url: `/pages/zzhd/dwxj/record?type=${this.form.type}`,
				})
			},
			submit() {
				this.$refs.uForm.validate(valid => {
					if (valid) {
						this.$u.api.add_forum(this.form).then(res => {
							this.point = res.data.point
							this.showType = 1
							this.tipShow = true
						}).catch(res => {
							this.remaining_seconds = res.data.remaining_seconds
							this.showType = 2
							this.tipShow = true
						})
					}
				})
			},
		},
		onReady() {
			this.$refs.uForm.setRules(this.rules)
		},
	}
</script>

<style lang="scss" scoped>
.add-page {
	min-height: 100vh;
	background: #eef2f0;
	padding-bottom: calc(220rpx + env(safe-area-inset-bottom));
}
.add-hero {
	padding: 32rpx 32rpx 40rpx;
	background: linear-gradient(160deg, #2fb74c 0%, #1a9a3e 50%, #d8eedc 100%);
}
.add-hero-title {
	display: block;
	font-size: 40rpx;
	font-weight: 800;
	color: #fff;
}
.add-hero-sub {
	display: block;
	margin-top: 10rpx;
	font-size: 26rpx;
	color: rgba(255, 255, 255, 0.9);
}
.add-steps {
	margin-top: 28rpx;
	padding: 20rpx 16rpx;
	background: rgba(255, 255, 255, 0.22);
	border-radius: 16rpx;
}
.add-step {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
}
.add-step-dot {
	width: 44rpx;
	height: 44rpx;
	line-height: 44rpx;
	text-align: center;
	border-radius: 50%;
	background: #fff;
	color: #18af38;
	font-size: 24rpx;
	font-weight: 800;
}
.add-step-lab {
	margin-top: 8rpx;
	font-size: 22rpx;
	color: #fff;
}
.add-body {
	margin-top: -24rpx;
	padding: 0 24rpx;
	position: relative;
	z-index: 2;
}
.add-card {
	background: #fff;
	border-radius: 20rpx;
	padding: 8rpx 24rpx 24rpx;
	box-shadow: 0 8rpx 28rpx rgba(20, 40, 28, 0.07);
	margin-bottom: 24rpx;
}
.add-card-head {
	padding: 20rpx 0 12rpx;
	border-bottom: 1rpx solid #f0f2f0;
	margin-bottom: 16rpx;
}
.add-card-title {
	font-size: 30rpx;
	font-weight: 700;
	color: #243028;
}
.add-card-sub {
	display: block;
	margin-top: 6rpx;
	font-size: 24rpx;
	color: #9aa59d;
}
.add-photo-card .add-card-head {
	margin-bottom: 20rpx;
}
.photo-row {
	align-items: flex-start;
}
.photo-col {
	flex: 1;
	min-width: 0;
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 0 6rpx;
}
.photo-slot {
	position: relative;
	width: 100%;
	height: 200rpx;
	border-radius: 16rpx;
	overflow: hidden;
	background: #f7faf8;
	border: 2rpx dashed #c5d0c8;
	box-sizing: border-box;
}
.photo-slot--press {
	opacity: 0.92;
}
.photo-slot-img {
	width: 100%;
	height: 100%;
	display: block;
}
.photo-slot-empty {
	width: 100%;
	height: 100%;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
}
.photo-slot-add {
	margin-top: 8rpx;
	font-size: 22rpx;
	color: #18af38;
}
.photo-del {
	position: absolute;
	top: 0;
	right: 0;
	width: 48rpx;
	height: 48rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	background: rgba(0, 0, 0, 0.55);
	border-radius: 0 0 0 12rpx;
	z-index: 2;
}
.photo-col-label {
	margin-top: 12rpx;
	font-size: 22rpx;
	color: #5a665e;
	text-align: center;
	line-height: 1.35;
	width: 100%;
}
.add-footer {
	position: fixed;
	left: 0;
	right: 0;
	bottom: 0;
	padding: 20rpx 32rpx calc(16rpx + env(safe-area-inset-bottom));
	background: #fff;
	box-shadow: 0 -4rpx 24rpx rgba(20, 40, 28, 0.06);
	z-index: 20;
}
.add-submit-btn {
	height: 92rpx;
	line-height: 92rpx;
	text-align: center;
	border-radius: 999rpx;
	font-size: 32rpx;
	font-weight: 700;
	color: #fff;
	background: linear-gradient(141deg, #a4db88 0%, #18af38 100%);
	box-shadow: 0 10rpx 28rpx rgba(24, 175, 56, 0.3);
}
.add-submit-btn--press {
	opacity: 0.92;
}
.add-record-link {
	margin-top: 16rpx;
	text-align: center;
	font-size: 28rpx;
	color: #18af38;
}
</style>
