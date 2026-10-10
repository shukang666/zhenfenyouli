<template>
	<view class="u-p-30">
		<view class="u-type-white-bg u-radius-10" @click="navigator('/pages/activity/scene-clock-pick?mode=theme',1)">
			<u-image width="100%" height="326" src="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/xcxkf052/admin/20250512/25a5d7f724a31ba6eccbe9d660e605ef.png"></u-image>
			<view class="u-p-20 u-flex u-row-between">
				<view class="">
					<view class="u-font-bold u-font-32">
						垃圾分类真分
					</view>
					<view class="u-m-t-10 u-font-24 u-type-info">
						参与垃圾分类学习通过考核后即可解锁
					</view>
				</view>
				<view class="">
					<u-button type="primary" size="mini" hover-class="none" :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
						@click.stop="navigator('/pages/activity/scene-clock-pick?mode=theme',1)">立即参与</u-button>
				</view>
			</view>
		</view>
		<view class="u-m-t-50">
			<view class="u-flex u-row-center">
				<u-image width="447" height="28" src="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/xcxkf052/admin/20250512/8912eace6b085bd4b6225c5c8ae185ff.png"></u-image>
			</view>
			<view class="u-m-t-30 u-flex">
				<view class="u-w-218 u-type-white-bg u-radius-10 u-p-tb-30 u-text-center" @click="navigator('/pages/flhd/list',2)">
					<view class="u-flex u-row-center">
						<u-image width="80" height="80" src="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/xcxkf052/admin/20250512/40bfaab5aeaffdd0457cf369eca0e567.png"></u-image>
					</view>
					<view class="u-m-t-30 u-font-bold">
						垃圾分类指导
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
				path: `/pages/flhd/flhd?scene=${this.userInfo.id}`,
				imageUrl: this.$u.mpShare.imageUrl
			}
		},
		onShareTimeline() {
			return {
				path: `/pages/flhd/flhd?scene=${this.userInfo.id}`,
				imageUrl: this.$u.mpShare.imageUrl
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
				if (type == 1 && (!this.userInfo.is_learn || !this.userInfo.is_answer)) {
					var that = this
					const needLearn = !this.userInfo.is_learn
					uni.showModal({
						title: '提示',
						content: needLearn
							? '请先完成真分前学习，通过答题考试后可参与垃圾分类真分'
							: '请通过垃圾分类答题考试后即可真分',
						showCancel: false,
						confirmText: needLearn ? '去学习' : '去考试',
						confirmColor: '#39BF3E',
						success: function(res) {
							if (!res.confirm) return
							if (needLearn) {
								uni.navigateTo({ url: '/pages/flhd/pre_learn' })
							} else {
								uni.navigateTo({ url: '/pages/answer/examine?id=1' })
							}
						}
					});
				} else if (type == 3) {
					if (this.userInfo.is_answer == 1) {
						// this.$u.toast('已完成答题考试')
						this.$u.route('/pages/activity/scene-clock-pick?mode=theme')
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