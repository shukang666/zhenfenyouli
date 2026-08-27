<template>
	<view>
		<view class="content">
			<u-image :src="config.logo || '/static/logo.png'" width="300" height="300"></u-image>
			<view class="u-font-32 u-font-bold u-content-color">{{ wxappName }}</view>
		</view>

		<view class="u-font-28 u-tips-color u-m-tb-50 u-p-lr-60">如需正常使用程序的功能，请点击下方授权登录按钮，打开授权弹窗，并点击允许。</view>
		
		<view class="u-m-lr-50">
			<u-button type="success" size="default" shape="circle" @click="wxLogin">
				<u-icon name="weixin-fill" color="#FFF" size="38"></u-icon>
				<text class="u-m-l-8">授权登录</text>
			</u-button>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				openid: ''
			}
		},
		onLoad(option) {
			if (option.openid) {
				this.openid = option.openid;
				this.getUserInfo(); //获取用户信息
			}
		},
		methods: {
			//授权登录
			async wxLogin() {
				this.$u.wx.wxOauth();
			},
			//获取用户信息
			getUserInfo() {
				uni.showLoading({
					title: '登录中...'
				})
				this.$u.api.getUserInfo({
					openid: this.openid
				}).then(res => {
					this.$u.vuex('isLogin', true);
					this.$u.vuex('userInfo', res.data);
					uni.hideLoading();
					this.$u.func.showToast({
						title: '登陆成功',
						icon: 'success',
						url: '/pages/user/index',
						type: 'switchTab'
					})
				}).catch(err => {
					uni.hideLoading();
					this.$u.toast(err.msg)
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
		margin: 150rpx auto;
		text-align: center;
	}
</style>
