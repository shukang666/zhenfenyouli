<template>
	<view>
		<view class="u-rela">
			<u-image width="100%" height="340" src="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/xcxkf052/admin/20260617/08e04d3c0f112405a9b9c2d40c82862a.png"></u-image>
			<view class="u-p-20 white u-radius-10 u-abso u-abso-b-30 u-abso-l-30 u-abso-r-30" style="background: rgba(255,255,255,0.38);">
				{{setting.civilization_notice}}
			</view>
		</view>
		<view class="u-p-30">
			<!-- 督导员 -->
			<view class="">
				<view class="u-flex u-row-center">
					<u-divider color="#f9a04c" bg-color="rgba(0,0,0,0)" font-size="30">督导员</u-divider>
				</view>
				<view class="u-m-t-30 u-flex">
					<view class="u-w-212 u-type-white-bg u-radius-10 u-p-tb-30 u-text-center" @click="navigator('/pages/dtsh/volunteer-home')">
						<view class="u-flex u-row-center">
							<u-image width="80" height="80" src="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/xcxkf052/admin/20250512/e48645c17b800739e2f492b8f95c4ba6.png"></u-image>
						</view>
						<view class="u-m-t-30 u-font-bold">
							督导员
						</view>
					</view>
					<view class="u-w-212 u-type-white-bg u-radius-10 u-p-tb-30 u-text-center u-m-l-30" @click="navigator('/pages/zzhd/ranking')">
						<view class="u-flex u-row-center">
							<u-image width="80" height="80" src="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/xcxkf052/admin/20250512/6f8d9cc52ce9afca23824a19564aed64.png"></u-image>
						</view>
						<view class="u-m-t-30 u-font-bold">
							督导员排行
						</view>
					</view>
				</view>
			</view>
			<!-- 点位管理 -->
			<view class="u-m-t-30" v-if="!setting.app_test">
				<view class="u-flex u-row-center">
					<!-- <u-image width="447" height="28" src="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/xcxkf052/admin/20250512/0cc0d28145c0004f49ac016f9e2dc8de.png"></u-image> -->
					<u-divider color="#f9a04c" bg-color="rgba(0,0,0,0)" font-size="30">点位管理</u-divider>
				</view>
				<view class="u-m-t-30 u-flex">
					<view class="u-w-212 u-type-white-bg u-radius-10 u-p-tb-30 u-text-center" @click="navigator('/pages/zzhd/dwxj/dwxj?type=3')">
						<view class="u-flex u-row-center">
							<u-image width="80" height="80" src="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/xcxkf052/admin/20250512/51956890c56bc0241bad6c6eacf709dc.png"></u-image>
						</view>
						<view class="u-m-t-30 u-font-bold">
							督导巡查
						</view>
					</view>
					<view class="u-w-212 u-type-white-bg u-radius-10 u-p-tb-30 u-text-center u-m-l-30" @click="navigator('/pages/zzhd/yxsj/yxsj?type=6')" v-if="setting.is_deed">
						<view class="u-flex u-row-center">
							<u-image width="80" height="80" src="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/xcxkf052/admin/20250512/18e7281ba65d9a3afb6bc9902b0e9139.png"></u-image>
						</view>
						<view class="u-m-t-30 u-font-bold">
							优秀事迹
						</view>
					</view>
					<view class="u-w-212 u-type-white-bg u-radius-10 u-p-tb-30 u-text-center u-m-l-30" @click="navigator('/pages/zzhd/dwxj/dwxj?type=4')">
						<view class="u-flex u-row-center">
							<u-image width="80" height="80" src="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/xcxkf052/admin/20250512/ae1cb9fd359b5fe3d106e4293a174f57.png"></u-image>
						</view>
						<view class="u-m-t-30 u-font-bold">
							其他督导
						</view>
					</view>
				</view>
			</view>
			<!-- 商家资质 -->
			<view class="u-m-t-30" v-if="!setting.app_test">
				<view class="u-flex u-row-center">
					<!-- <u-image width="447" height="28" src="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/xcxkf052/admin/20260611/679d3a0d12e5f67118d6f54039d2793a.png"></u-image> -->
					<u-divider color="#f9a04c" bg-color="rgba(0,0,0,0)" font-size="30">商家自治</u-divider>

				</view>
				<view class="u-m-t-30 u-flex">
					<view class="u-w-212 u-type-white-bg u-radius-10 u-p-tb-30 u-text-center" @click="goApplyShop">
						<view class="u-flex u-row-center">
							<u-image width="80" height="80" src="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/xcxkf052/admin/20260611/8c95821e23155697d1e0a2fd8e8439c8.png"></u-image>
						</view>
						<view class="u-m-t-30 u-font-bold">
							商家申请
						</view>
					</view>
					<view class="u-w-212 u-type-white-bg u-radius-10 u-p-tb-30 u-text-center u-m-l-30" @click="goShopSign">
						<view class="u-flex u-row-center">
							<u-image width="80" height="80" src="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/xcxkf052/admin/20260611/1b473a1a2a5fde71f6d0aa98459bab92.png"></u-image>
						</view>
						<view class="u-m-t-30 u-font-bold">
							商家真分
						</view>
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
				shopInfo: {},
			};
		},
		onShow() {
			if (this.isLogin) {
				this.getUserInfo()
				this.get_shop_new()
			}
		},
		methods: {
			goShopSign() {
				if (this.shopInfo.status != 2) return this.$u.toast("请先申请商家")
				uni.navigateTo({
					url: '/pages/zzhd/shop-sign'
				})
			},
			async get_shop_new() {
				try {
					let params = {}
					let res = await this.$u.api.get_shop_new(params)
					this.shopInfo = res.data
				} catch (e) {
					console.log(e);
					// this.$u.toast(e.msg)
				}
			},
			goApplyShop() {
				if (!this.$u.func.checkLogin()) return
				if (this.shopInfo.status == 1 || this.shopInfo.status == 3) {
					uni.navigateTo({
						url: '/pages/zzhd/apply-status'
					})
					return
				}
				uni.navigateTo({
					url: '/pages/zzhd/apply-shop'
				})
			},
			// 获取用户信息
			getUserInfo() {
				this.$u.api.getUserInfo().then(res => {
					this.$u.vuex('userInfo', res.data)
				}).catch(err => {
					this.$u.vuex('userInfo', {})
					this.$u.vuex('isLogin', false)
				})
			},
		// 跳转页面（督导员统一权限：任一角色通过即可访问全部功能）
		navigator(url, type) {
			if (!this.isLogin) return this.$u.route('/pages/public/login')
			// type=1 时跳过权限检查（用于督导员入口直接跳转）
			if (this.userInfo.check_status != 2 && !type) return this.$u.toast('无权限，请先申请督导员')
			this.$u.route(url)
		},
			// 跳转其他小程序
			navigateToMiniProgram(appId) {
				uni.navigateToMiniProgram({
					appId,
				})
			}
		}
	}
</script>

<style lang="scss">

</style>