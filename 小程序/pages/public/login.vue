<template>
	<view>
		<view class="content">
			<!-- #ifdef APP-PLUS -->
			<u-avatar size="260" mode="square" :src="setting.app_logo || 'https://admin.circular-economy.cn/static/logo.png'" @longpress="showWorkLogin = true"></u-avatar>
			<view class="u-font-12 u-tips-color u-m-t-10">长按头像可使用工作账号登录</view>
			<!-- #endif -->
			<!-- #ifndef APP-PLUS -->
			<u-avatar size="260" mode="square" :src="setting.app_logo || 'https://admin.circular-economy.cn/static/logo.png'"></u-avatar>
			<!-- #endif -->
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

		<!-- 工作账号登录弹窗（仅APP端显示） -->
		<!-- #ifdef APP-PLUS -->
		<u-popup :show="showWorkLogin" mode="center" round="10" closeable @close="showWorkLogin = false">
			<view class="work-login-popup">
				<view class="work-login-title">
					<u-icon name="account" size="36" color="#18AF38"></u-icon>
					<text class="u-m-l-10 u-font-32 u-font-bold">工作账号登录</text>
				</view>
				<view class="u-tips-color u-font-24 u-m-t-10 u-m-b-30">外勤人员请使用后台账号登录</view>

				<u--form labelPosition="left" labelWidth="60">
					<u-form-item label="账号">
						<u--input v-model="workForm.username" placeholder="请输入后台账号" border="bottom"></u--input>
					</u-form-item>
					<u-form-item label="密码">
						<u--input v-model="workForm.password" type="password" placeholder="请输入密码" border="bottom"></u--input>
					</u-form-item>
				</u--form>

				<view class="u-m-t-40">
					<u-button type="primary" shape="circle" @click="handleWorkLogin" :loading="workLoading"
						:customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}">
						<text>登 录</text>
					</u-button>
				</view>
			</view>
		</u-popup>
		<!-- #endif -->
	</view>
</template>

<script>
import { consumePendingAfterLogin } from '@/common/scanSignEntry.js'
export default {
	data() {
		return {
			code: '',
			// 工作账号登录（仅APP端）
			showWorkLogin: false,
			workLoading: false,
			workForm: {
				username: '',
				password: ''
			}
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
		goAfterLogin() {
			const pending = consumePendingAfterLogin()
			if (pending) {
				uni.$emit('pause-new-user-guide')
				this.$u.func.showToast({
					title: '登录成功',
					icon: 'success',
					url: pending,
					type: 'reLaunch'
				})
				return
			}
			this.$u.func.showToast({
				title: '登录成功',
				icon: 'success',
				url: '/pages/index/index',
				type: 'switchTab'
			})
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
			this.goAfterLogin()
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
		},
		// 工作账号登录（APP端）
		async handleWorkLogin() {
			if (!this.workForm.username) {
				this.$u.toast('请输入账号')
				return
			}
			if (!this.workForm.password) {
				this.$u.toast('请输入密码')
				return
			}

			this.workLoading = true
			uni.showLoading({ title: '登录中...' })

			try {
				const res = await this.$u.api.workLogin({
					username: this.workForm.username,
					password: this.workForm.password
				})

				this.$u.vuex('userInfo', res.data)
				this.$u.vuex('isLogin', true)
				uni.hideLoading()
				this.showWorkLogin = false
				this.workForm = { username: '', password: '' }

				this.goAfterLogin()
			} catch (e) {
				uni.hideLoading()
				this.$u.toast(e.msg || '登录失败')
			}
			this.workLoading = false
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
	.work-login-popup {
		padding: 50rpx 40rpx;
		width: 560rpx;
	}
	.work-login-title {
		display: flex;
		align-items: center;
		justify-content: center;
	}
</style>
