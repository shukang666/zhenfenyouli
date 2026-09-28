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



			<view class="add-photo-block" v-for="(sec, idx) in photoSections" :key="sec.key">

				<view class="add-photo-head u-flex u-col-center">

					<view class="add-photo-badge" :class="'add-photo-badge--' + (idx + 1)">{{ idx + 1 }}</view>

					<view>

						<text class="add-photo-title">{{ sec.title }}</text>

						<text class="add-photo-hint">{{ sec.hint }}</text>

					</view>

				</view>

				<view class="add-upload-wrap">

					<u-upload

						:custom-btn="true"

						width="690"

						height="320"

						:action="uploadUrl"

						@on-success="uploadImages($event, sec.key, true)"

						:file-list="sec.fileList"

						@on-remove="removeImages($event, sec.key, true)"

						max-count="1"

					>

						<view slot="addBtn" class="add-upload-slot">

							<u-icon name="camera-fill" size="64" color="#18AF38"></u-icon>

							<text class="add-upload-t">点击拍照或选图</text>

						</view>

					</u-upload>

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

				image_one: [],

				image_two: [],

				image_three: [],

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

			}

		},

		computed: {

			pageTitle() {

				return String(this.form.type) === '4' ? '桶边督导上报' : '点位巡检上报'

			},

			photoSections() {

				return [

					{ key: 'image_one', title: '点位照片', hint: '拍摄投放点全貌', fileList: this.image_one },

					{ key: 'image_two', title: '投放指南', hint: '分类标识或指引牌', fileList: this.image_two },

					{ key: 'image_three', title: '垃圾桶照片', hint: '桶内桶外情况', fileList: this.image_three },

				]

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

			removeImages(e, key, isOne) {

				if (isOne) {

					this.form[key] = ''

				} else {

					this.form[key].splice(e, 1)

				}

			},

			uploadImages(e, key, isOne) {

				if (isOne) {

					this.form[key] = e.data.filepath

				} else {

					this.form[key].push(e.data.filepath)

				}

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

			onSuccess(reslut) {

				this.form.image.push(reslut.data.url)

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

	backdrop-filter: blur(4px);

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

	padding: 8rpx 24rpx 16rpx;

	box-shadow: 0 8rpx 28rpx rgba(20, 40, 28, 0.07);

	margin-bottom: 24rpx;

}

.add-card-head {

	padding: 20rpx 0 8rpx;

	border-bottom: 1rpx solid #f0f2f0;

	margin-bottom: 8rpx;

}

.add-card-title {

	font-size: 30rpx;

	font-weight: 700;

	color: #243028;

}

.add-photo-block {

	background: #fff;

	border-radius: 20rpx;

	padding: 24rpx;

	margin-bottom: 24rpx;

	box-shadow: 0 6rpx 24rpx rgba(20, 40, 28, 0.05);

}

.add-photo-head {

	margin-bottom: 16rpx;

}

.add-photo-badge {

	width: 48rpx;

	height: 48rpx;

	line-height: 48rpx;

	text-align: center;

	border-radius: 12rpx;

	font-size: 26rpx;

	font-weight: 800;

	color: #fff;

	margin-right: 16rpx;

	flex-shrink: 0;

}

.add-photo-badge--1 {

	background: linear-gradient(135deg, #6bcB77, #18af38);

}

.add-photo-badge--2 {

	background: linear-gradient(135deg, #64b5f6, #1976d2);

}

.add-photo-badge--3 {

	background: linear-gradient(135deg, #ffb74d, #f57c00);

}

.add-photo-title {

	display: block;

	font-size: 30rpx;

	font-weight: 700;

	color: #243028;

}

.add-photo-hint {

	display: block;

	margin-top: 4rpx;

	font-size: 24rpx;

	color: #9aa59d;

}

.add-upload-wrap {

	border-radius: 16rpx;

	overflow: hidden;

}

.add-upload-slot {

	width: 690rpx;

	height: 320rpx;

	background: #f7faf8;

	border: 2rpx dashed #c5d0c8;

	border-radius: 16rpx;

	display: flex;

	flex-direction: column;

	align-items: center;

	justify-content: center;

	box-sizing: border-box;

}

.add-upload-t {

	margin-top: 16rpx;

	font-size: 28rpx;

	color: #18af38;

	font-weight: 600;

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


