<template>
	<view>
		<view class="content">
			<u-avatar size="260" mode="square" :src="setting.app_logo || '/static/logo.png'"></u-avatar>
			<view class="u-font-32 u-font-bold u-type-warning u-m-t-30">{{ setting.app_name}}</view>
		</view>

		<u-gap height="50"></u-gap>
		<view class="u-tips-color u-p-60">如需正常使用小程序的功能，请点击下方授权登录按钮，打开授权弹窗，并点击允许。</view>

		<view class="u-m-lr-50">
			<u-button type="warning" shape="circle" open-type="getPhoneNumber" @getphonenumber="getPhoneNumber" hover-class="none"
				:customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}">
				<u-icon name="phone" custom-prefix="custom-icon" color="#FFF" size="38"></u-icon>
				<text class="u-m-l-8">手机号快捷登录</text>
			</u-button>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				code: ''
			}
		},
		onLoad() {
			this.getCode(); //获取code
		},
		methods: {
			//获取code
			async getCode() {
				const code = await this.getLoginCode();
				this.code = code;
			},
			// // 小程序登录——验证是否同意隐私协议
			// handleLogin(e){
			// 	wx.getPrivacySetting({
			// 		success: res => {
			// 			if (res.needAuthorization) {
			// 				// 需要弹出隐私协议
			// 				wx.requirePrivacyAuthorize({
			// 					success: () => {
			// 						this.getPhoneNumber(e); // 用户同意授权 继续小程序逻辑	
			// 					},
			// 					fail: () => {}, // 用户拒绝授权
			// 					complete: () => {}
			// 				})
			// 			} else {
			// 				// 用户已经同意过隐私协议，所以不需要再弹出隐私协议，也能调用隐私接口
			// 				this.getPhoneNumber(e)
			// 			}
			// 		},
			// 		fail: () => {},
			// 		complete: () => {}
			// 	})
			// },
			//授权绑定手机号
			async mobileLogin(encryptedData, iv) {
				uni.showLoading({
					title: '登录中...'
				});
				let vcode = uni.getStorageSync('vcode') //把vcode从缓存取出
				const loginInfo = {
					code: this.code,
					encrypted_data: encryptedData,
					iv: iv,
					vcode,
					user_id: vcode
				}
				this.$u.api.decryptPhone(loginInfo).then(res => {
					this.$u.vuex('userInfo', res.data);
					this.$u.vuex('isLogin', true);
					uni.removeStorageSync('vcode'); //从缓存移除vcode
					uni.hideLoading();
					this.$u.func.showToast({
						title: '登录成功',
						icon: 'success',
						back: true
					})
				}).catch(res => {
					uni.hideLoading();
					this.$u.toast(res.msg);
					this.getCode(); //获取code
				})
			},
			//授权获取手机号
			getPhoneNumber(e) {
				const encryptedData = e.detail.encryptedData
				const iv = e.detail.iv
				if (e.detail.errMsg == "getPhoneNumber:ok") {
					this.mobileLogin(encryptedData, iv)
				} else {
					this.$u.toast('请点击允许授权')
				}
			},
			//授权获取code
			getLoginCode() {
				return new Promise((reject, resolve) => {
					uni.login({
						provider: 'weixin',
						success: res => {
							reject(res.code)
						},
						fail: () => {
							uni.hideLoading()
							this.$u.toast('授权登录失败')
							resolve()
						}
					})
				})
			}
		}
	}
</script>

<style lang="scss">
	.content {
		display: flex;
		flex-direction: column;
		align-items: center;
		margin: 150rpx auto 30rpx;
		text-align: center;
	}
</style>