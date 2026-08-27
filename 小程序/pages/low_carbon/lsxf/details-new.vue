<template>
	<view class="container">
		<view class=" u-type-white-bg u-rela ">
			<u-image width="750" height="372" :src="info.thumb"></u-image>
			<view class="u-p-30">
				<view class="u-font-32 u-font-bold u-line-1 u-p-b-30 u-border-bottom">{{info.name}}</view>
				<view class="u-flex u-row-between u-m-t-30 ">
					<view class="u-flex  u-font-24 u-hui u-w-550">
						<u-icon name="clock"></u-icon>
						<text class="u-m-l-15">活动时间：{{info.date | date('yy-mm-dd')}}</text>
					</view>
					<image src="/static/images/dianhua.png" class="u-w-92 u-h-38" mode="aspectFill" @click="makePhoneCall(info.phone)"></image>
				</view>
				<view class="u-flex u-row-between u-m-t-30 ">
					<view class="u-flex  u-font-24 u-hui u-w-550">
						<u-icon name="map"></u-icon>
						<text class="u-m-l-15">{{info.address}}</text>
					</view>
					<image src="/static/images/ditu.png" class="u-w-92 u-h-38" mode="aspectFill" @click="openLocation(info)"></image>
				</view>
			</view>
		</view>
		<view class="u-p-30 u-type-white-bg u-m-t-30">
			<view class="u-m-b-30">
				<u-divider color="#FF843D">活动说明</u-divider>
			</view>
			<u-parse :html="info.content"></u-parse>
		</view>

		<u-gap height="150"></u-gap>

		<u-mask :show="tipShow">
			<view class="u-w-560 u-radius-10 u-type-white-bg u-abso-center u-p-30" v-if="showType == 1">
				<view class="u-text-center">
					<image src="/static/images/wenxintishi.png" class="u-w-280 u-h-188" mode="aspectFill"></image>
					<view class="u-font-32">报名需要抵扣 <text class="u-font-40 u-main-c u-font-bold">{{info.point}}</text> 积分</view>
					<view class="u-font-32">是否报名该活动</view>
				</view>
				<view class="u-m-t-30 u-flex u-row-around">
					<u-button :custom-style="{width:'220rpx'}" type="warning" @click="tipShow = false">我再想想</u-button>
					<u-button :custom-style="{width:'220rpx'}" @click="post_new_active_sign">确认报名</u-button>
				</view>
			</view>
			<view class="u-w-560 u-radius-10 u-type-white-bg u-abso-center u-p-30" v-if="showType == 2">
				<view class="u-text-center">
					<image src="/static/images/qiandaochenggong.png" class="u-w-280 u-h-188" mode="aspectFill"></image>

					<view class="u-font-32">签到赠送 <text class="u-font-40 u-main-c u-font-bold">{{info.send_point}}</text> 积分</view>
					<view class="u-font-32">恭喜您签到成功~</view>
				</view>
				<view class="u-m-t-30 u-flex u-row-center">
					<u-button :custom-style="{width:'400rpx'}" @click="tipShow = false">我知道了</u-button>
				</view>
			</view>
		</u-mask>

		<u-popup v-model="sign_clock_show" mode="bottom" border-radius="20" closeable>
			<view class="u-p-30">
				<view class="u-font-bold">上传签到图片</view>
				<view class="u-m-t-30 u-hui-bg u-radius-10 u-p-30">
					<u-upload upload-text="上传图片" width="180" height="180" :action="uploadUrl" @on-success="uploadImages($event,'image_url',true)" :fileList="image_url"
						@on-remove="removeImages($event,'image_url',true)" max-count="1">
					</u-upload>
				</view>
				<view class="u-p-30">
					<u-button @click="post_new_sign_clock">确认签到</u-button>
				</view>
			</view>
		</u-popup>

		<view class="submit-btn u-p-30 u-flex u-row-between u-type-white-bg" v-if="!setting.app_test">
			<view class="u-p-lr-30 u-text-center u-rela">
				<image src="/static/images/yaoqing.png" class="u-w-44 u-h-44" mode="aspectFill"></image>
				<view class="u-font-26">邀请邻居</view>
				<button open-type="share" plain class="kefuBtn"></button>
			</view>
			<!-- 	<view v-if="info.is_clock == 0">
				<u-button :custom-style="{width:'480rpx'}" v-if="info.is_sign == 0" @click="showType = 1,tipShow = true" :disabled="info.is_end == 1">立即参与</u-button>
			</view> -->
			<u-button :custom-style="{width:'480rpx'}" v-if="info.is_clock == 0" @click="showType = 1,tipShow = true"
				:disabled="(info.is_end == 1 || info.is_sign == 1) && isLogin">{{info.is_sign == 1 ? '已参与' :'立即参与'}}</u-button>
			<view class="u-flex u-row-between u-w-490" v-if="info.is_clock == 1">
				<u-button :custom-style="{width:'230rpx'}" type="warning" plain @click="$u.route(`/pages/low_carbon/rank?id=${info.id}`)">
					<image src="/static/images/rank.png" class="u-w-40 u-h-40 u-m-r-15" mode="aspectFill"></image>
					<text>排行榜</text>
				</u-button>
				<u-button :custom-style="{width:'230rpx'}" @click="showType = 1,tipShow = true" v-if="info.is_sign == 0 ||  !isLogin" :disabled="info.is_end == 1 && isLogin">立即参与</u-button>
				<u-button :custom-style="{width:'230rpx'}" v-if="info.is_sign == 1 && isLogin" @click="sign_clock_show = true" :disabled="info.is_end == 1 || info.is_sign_clock == 1">立即签到</u-button>
			</view>
		</view>

	</view>
</template>

<script>
	import {
		uploadUrl
	} from '@/common/settings'
	export default {
		data() {
			return {
				uploadUrl,
				popup_show: false,
				image: [],
				content: '',
				info: {},
				tipShow: false,
				showType: 1,
				pid: '',
				id: '',
				sign_clock_show: false,
				form: {
					image_url: '',
				},
				image_url: [],
			};
		},
		onLoad(option) {
			if (option.scene) {
				uni.setStorageSync('vcode', option.scene)
			}
			this.id = option.id || option.scene
			if (option.pid) this.pid = option.pid
			this.get_new_active_detail(this.id)
		},
		onHide() {
			this.tipShow = false
		},
		onShareTimeline() {
			return {
				imageUrl: this.info.thumb,
				title: this.info.name,
				path: `/pages/low_carbon/lsxf/details-new?id=${this.id}&scene=${this.userInfo.id}`
			}
		},
		onShareAppMessage() {
			return {
				imageUrl: this.info.thumb,
				title: this.info.name,
				path: `/pages/low_carbon/lsxf/details-new?id=${this.id}&scene=${this.userInfo.id}`
			}
		},
		methods: {
			// 删除图
			removeImages(e, key, isOne) {
				if (isOne) {
					this.form[key] = ''
				} else {
					this.form[key].splice(e, 1)
				}
			},
			// 上传图
			uploadImages(e, key, isOne) {
				if (isOne) {
					this.form[key] = e.data.filepath
				} else {
					this.form[key].push(e.data.filepath)
				}
			},

			// 打卡
			async post_new_sign_clock() {
				if (!this.$u.func.checkLogin()) return
				try {
					let params = {
						sign_id: this.info.sign_id,
						...this.form
					}
					let res = await this.$u.api.post_new_sign_clock(params)
					this.sign_clock_show = false
					// this.$u.toast(res.msg)
					this.showType = 2
					this.tipShow = true

					setTimeout(() => {
						this.get_new_active_detail(this.info.id)
					}, 500)
				} catch (e) {
					console.log(e);
					this.$u.toast(e.msg)
				}
			},
			makePhoneCall(phoneNumber) {
				uni.makePhoneCall({
					phoneNumber
				})
			},
			openLocation(item) {
				uni.openLocation({
					latitude: item.lat * 1,
					longitude: item.lng * 1,
					address: item.address
				})
			},
			// 活动报名
			async post_new_active_sign() {
				if (!this.$u.func.checkLogin()) return
				try {
					let params = {
						active_id: this.info.id,
						pid: this.pid

					}
					let res = await this.$u.api.post_new_active_sign(params)
					this.$u.toast(res.msg)
					this.tipShow = false
					setTimeout(() => {
						this.get_new_active_detail(this.info.id)
					}, 500)
				} catch (e) {
					console.log(e);
					this.$u.toast(e.msg)
				}
			},
			btn() {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				this.popup_show = true
			},
			// 提交
			add_active_user() {
				if (!this.$u.func.checkLogin()) return
				if (!this.content) return this.$u.toast('请先添加作品介绍')
				if (!this.image.length) return this.$u.toast('请先添加作品照片')
				this.$u.api.add_active_user({
					active_id: this.info.id,
					image: this.image,
					content: this.content
				}).then(res => {
					this.popup_show = false
					this.$u.func.showToast({
						title: res.msg,
						icon: 'success',
					})
				}).catch(err => {
					this.$u.toast(err.msg)
				})
			},
			// 活动详情
			get_new_active_detail(id) {
				this.$u.api.get_new_active_detail({
					active_id: id
				}).then(res => {
					this.info = res.data
				})
			},
			// 上传图片
			onSuccess(reslut) {
				this.image.push(reslut.data.url)
			},
		},
		// onShareAppMessage(res) {
		// 	return {
		// 		title: this.info.name,
		// 		path: '/pages/low_carbon/lsxf/details?id=' + this.info.id,
		// 		imageUrl: this.info.thumb
		// 	}
		// },
		// onShareTimeline() {
		// 	return {
		// 		title: this.info.name,
		// 		path: `/pages/low_carbon/lsxf/details?id=${this.info.id}&pid=${this.userInfo.id}`,
		// 		imageUrl: this.info.thumb
		// 	}
		// }
	}
</script>

<style lang="scss" scoped>
	.container {
		min-height: 100vh;
		background: #F5F5F5;
	}
</style>