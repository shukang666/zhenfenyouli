<template>
	<view>
		<view class="">
			<u-image width="100%" height="340" src="https://oss.ausite.cn/xcxkf052/admin/20250512/5d288456e544b333329d345733847049.png?watermark"></u-image>
		</view>
		<view class="u-p-30">
			<!-- 督导员 -->
			<view class="">
				<view class="u-flex u-row-center">
					<u-image width="447" height="28" src="https://oss.ausite.cn/xcxkf052/admin/20250512/ef3263d29adf8ccfcce54e46827409ea.png?watermark"></u-image>
				</view>
				<view class="u-m-t-30">
					<u-row gutter="30">
						<u-col span="4">
							<view class="u-width-100 u-type-white-bg u-radius-10 u-p-tb-30 u-text-center" @click="navigator('/pages/sjhd/list?type=2')" v-if="setting.is_course">
								<view class="u-flex u-row-center">
									<u-image width="80" height="80" src="https://oss.ausite.cn/xcxkf052/admin/20250512/6dd071cac6cdfc81e7297998c0a011ee.png?watermark"></u-image>
								</view>
								<view class="u-m-t-30 u-font-bold">
									课程培训
								</view>
							</view>
						</u-col>
						<u-col span="4">
							<view class="u-width-100 u-type-white-bg u-radius-10 u-p-tb-30 u-text-center" @click="navigator('/pages/activity/lsxf/lsxf?name=社区活动&type=3')" v-if="setting.is_active">
								<view class="u-flex u-row-center">
									<u-image width="80" height="80" src="https://oss.ausite.cn/xcxkf052/admin/20250512/8f9e9a13bb18f32ed74e6e7a43f4b081.png?watermark"></u-image>
								</view>
								<view class="u-m-t-30 u-font-bold">
									社区活动
								</view>
							</view>
						</u-col>
						<u-col span="4">
							<view class="u-width-100 u-type-white-bg u-radius-10 u-p-tb-30 u-text-center" @click="navigator('/pages/sjhd/list?type=3')" v-if="setting.is_match">
								<view class="u-flex u-row-center">
									<u-image width="80" height="80" src="https://oss.ausite.cn/xcxkf052/admin/20250512/956ddc6fd692c9cf48c6b2dd2ef6f5d2.png?watermark"></u-image>
								</view>
								<view class="u-m-t-30 u-font-bold">
									社区知识竞赛
								</view>
							</view>
						</u-col>
					</u-row>
				</view>
			</view>
			<!-- 宣传教育 -->
			<view class="u-m-t-30">
				<view class="u-flex u-row-center">
					<u-image width="447" height="28" src="https://oss.ausite.cn/xcxkf052/admin/20250512/cd4026f1bc19470be6bbf9efb0a9b38a.png?watermark"></u-image>
				</view>
				<view class="u-m-t-30">
					<u-row gutter="30">
						<u-col span="4">
							<view class="u-width-100 u-type-white-bg u-radius-10 u-p-tb-30 u-text-center" @click="navigator('/pages/article/article?type=1')">
								<view class="u-flex u-row-center">
									<u-image width="80" height="80" src="https://oss.ausite.cn/xcxkf052/admin/20250512/6dd071cac6cdfc81e7297998c0a011ee.png?watermark"></u-image>
								</view>
								<view class="u-m-t-30 u-font-bold">
									宣传手册
								</view>
							</view>
						</u-col>
						<u-col span="4">
							<view class="u-width-100 u-type-white-bg u-radius-10 u-p-tb-30 u-text-center" @click="navigator('/pages/article/article?type=2')">
								<view class="u-flex u-row-center">
									<u-image width="80" height="80" src="https://oss.ausite.cn/xcxkf052/admin/20250512/8f9e9a13bb18f32ed74e6e7a43f4b081.png?watermark"></u-image>
								</view>
								<view class="u-m-t-30 u-font-bold">
									入户宣传
								</view>
							</view>
						</u-col>
						<u-col span="4">
							<view class="u-width-100 u-type-white-bg u-radius-10 u-p-tb-30 u-text-center" @click="navigator('/pages/zzhd/yxsj/yxsj?type=7')" v-if="!setting.is_environmental">
								<view class="u-flex u-row-center">
									<u-image width="80" height="80" src="https://oss.ausite.cn/xcxkf052/admin/20250512/f02ace719b53730c610058eda02a8006.png?watermark"></u-image>
								</view>
								<view class="u-m-t-30 u-font-bold">
									环保论坛
								</view>
							</view>
						</u-col>
					</u-row>

					<u-row gutter="30">
						<u-col span="4">
							<view class="u-width-100 u-type-white-bg u-radius-10 u-p-tb-30 u-text-center u-m-t-30" @click="navigator('/pages/activity/lsxf/lsxf?name=环保活动&type=2')"
								v-if="!setting.app_test">
								<view class="u-flex u-row-center">
									<u-image width="80" height="80" src="https://oss.ausite.cn/xcxkf052/admin/20250512/b2bc3031e9b1fcb65fb59b52c2be27f5.png?watermark"></u-image>
								</view>
								<view class="u-m-t-30 u-font-bold">
									环保活动
								</view>
							</view>
						</u-col>
						<u-col span="4" v-if="!setting.is_mutual">
							<view class="u-width-100 u-type-white-bg u-radius-10 u-p-tb-30 u-text-center u-m-t-30" @click="navigator('/pages/zzhd/yxsj/yxsj?type=5')">
								<view class="u-flex u-row-center">
									<u-image width="80" height="80" src="https://oss.ausite.cn/xcxkf052/admin/20250512/6e77a6e3afc5faefab1d80db1fb13e4c.png?watermark"></u-image>
								</view>
								<view class="u-m-t-30 u-font-bold">
									互助平台
								</view>
							</view>
						</u-col>
					</u-row>
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
		methods: {
			// 跳转页面
			navigator(url) {
				// if (!this.isLogin) return this.$u.route('/pages/public/login')
				this.$u.route(url)
			},
		}
	}
</script>

<style lang="scss">

</style>
