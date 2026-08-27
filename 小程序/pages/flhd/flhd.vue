<template>
	<view class="u-p-30">
		<view class="u-type-white-bg u-radius-10" @click="navigator('/pages/flhd/ljfldk',1)">
			<u-image width="100%" height="326" src="https://oss.ausite.cn/xcxkf052/admin/20250512/25a5d7f724a31ba6eccbe9d660e605ef.png?watermark"></u-image>
			<view class="u-p-20 u-flex u-row-between">
				<view class="">
					<view class="u-font-bold u-font-32">
						垃圾分类打卡
					</view>
					<view class="u-m-t-10 u-font-24 u-type-info">
						参与垃圾分类学习通过考核后即可解锁
					</view>
				</view>
				<view class="">
					<u-button type="primary" size="mini" hover-class="none" :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
						@click.stop="navigator('/pages/flhd/ljfldk',1)">立即参与</u-button>
				</view>
			</view>
		</view>
		<view class="u-m-t-50">
			<view class="u-flex u-row-center">
				<u-image width="447" height="28" src="https://oss.ausite.cn/xcxkf052/admin/20250512/8912eace6b085bd4b6225c5c8ae185ff.png?watermark"></u-image>
			</view>
			<view class="u-m-t-30 u-flex">
				<view class="u-w-218 u-type-white-bg u-radius-10 u-p-tb-30 u-text-center" @click="navigator('/pages/flhd/list',2)">
					<view class="u-flex u-row-center">
						<u-image width="80" height="80" src="https://oss.ausite.cn/xcxkf052/admin/20250512/40bfaab5aeaffdd0457cf369eca0e567.png?watermark"></u-image>
					</view>
					<view class="u-m-t-30 u-font-bold">
						垃圾分类指导
					</view>
				</view>
				<view class="u-w-218 u-type-white-bg u-radius-10 u-p-tb-30 u-text-center u-m-l-30">
					<view class="u-flex u-row-center" @click="navigator('/pages/answer/examine?id=1',3)">
						<u-image width="80" height="80" src="https://oss.ausite.cn/xcxkf052/admin/20250512/ee564dd59c747c37e5b5b01eb2db7c54.png?watermark"></u-image>
					</view>
					<view class="u-m-t-30 u-font-bold">
						答题考试
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {

			};
		},
		onLoad(option) {
			if (option.scene) {
				uni.setStorageSync('vcode', option.scene)
			}
		},
		onShow() {
			if (this.isLogin) {
				this.getUserInfo()
			}
		},
		onShareAppMessage() {
			return {
				path: `/pages/flhd/flhd?scene=${this.userInfo.id}`
			}
		},
		onShareTimeline() {
			return {
				path: `/pages/flhd/flhd?scene=${this.userInfo.id}`
			}
		},
		methods: {
			// 获取用户信息
			getUserInfo() {
				this.$u.api.getUserInfo().then(res => {
					this.$u.vuex('userInfo', res.data)
				}).catch(err => {
					this.$u.vuex('userInfo', {})
					this.$u.vuex('isLogin', false)
				})
			},
			// 跳转页面
			navigator(url, type) {
				console.log(url, type);
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				if (!this.userInfo.is_answer && type == 1) {
					var that = this
					uni.showModal({
						title: '提示',
						content: '请先学习垃圾分类指导，通过答题考试后可参与活动',
						showCancel: false,
						confirmText: '立即学习',
						confirmColor: '#39BF3E',
						success: function(res) {
							if (res.confirm) {
								that.$u.route('/pages/flhd/list')
							} else if (res.cancel) {}
						}
					});
				} else if (type == 3) {
					if (this.userInfo.is_answer == 1) {
						// this.$u.toast('已完成答题考试')
						this.$u.route('/pages/flhd/ljfldk')
					} else {
						this.$u.route(url)
					}
				} else {
					this.$u.route(url)
				}
			},
		}
	}
</script>

<style lang="scss">

</style>