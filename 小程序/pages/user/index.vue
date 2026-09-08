<template>
	<view class="u-p-30 user-page">
		<image v-if="imgUrl" :src="`${imgUrl}user-bg.png`" class="u-abso u-abso-l-0 u-abso-t-0 u-w-750 u-h-448" mode="aspectFill"></image>
		<view class="u-flex u-row-between u-rela" style="padding-top: 150rpx;">
			<view class="u-flex u-col-top">
				<u-avatar :src="userInfo.avatar" size="130"></u-avatar>
				<view class="u-m-l-30 u-h-138" style="line-height: 138rpx;" @click="$u.route('/pages/public/login')" v-if="!isLogin">
					点击登录
				</view>
				<view class="u-m-l-30" v-else>
					<view class="u-font-32 u-font-bold u-flex">
						<text class="u-m-r-15">{{userInfo.nickname}}</text>
						<u-tag v-if="userInfo.is_environmental" text="环保达人" bg-color="#18AF38" color="#ffffff" border-color="#18AF38" size="mini" />
						<image :src="item.image" class="u-w-36 u-h-36 u-p-l-15" mode="aspectFill" v-for="item in badge_list" :key="item.id"></image>
					</view>
					<view class="u-font-24 u-m-t-20">{{userInfo.mobile}}</view>
					<view class="u-m-t-20 " v-if="userInfo.type==2">
						<view class="">
							<u-tag text="员工" bg-color="#fff" color="#B181CE" border-color="#fff" size="mini" shape="circle" />
						</view>
					</view>
					<view class="u-flex u-m-t-20 u-font-24">
						<view class="u-flex">
							<image src="/static/images/zhanghujifen.png" class="u-w-28 u-h-28" mode="aspectFill"></image>
							<view class="u-m-lr-5">账户积分</view>
							<view class="u-font-32 u-main-c u-font-bold">{{userInfo.point}}</view>
						</view>
						<!-- 						<view class="u-flex u-m-l-30 u-font-24">
							<image src="/static/images/lianxudaka.png" class="u-w-28 u-h-28" mode="aspectFill"></image>
							<view class="u-m-lr-5">连续打卡</view>
							<view class="u-font-32 u-main-c u-font-bold">2580</view>
							<view>天</view>
						</view> -->
					</view>
				</view>
			</view>
			<!-- <view class="u-type-white-bg u-radius-30" style="padding: 6rpx 20rpx;" @click="$u.route('/pages/code/code')"
                v-if="userInfo.is_admin==1">
                <u-icon name="scan" label="扫一扫" color="#9CC036" label-color="#9CC036"></u-icon>
            </view> -->
		</view>

		<view class="u-rela u-w-690 u-m-t-30" v-if="!userInfo.is_environmental && isLogin">
			<image v-if="imgUrl" :src="`${imgUrl}jiankangdaren-bg.png`" class="u-w-690 u-h-200" mode="aspectFill"></image>
			<view class="u-w-690 u-h-200 u-abso u-abso-l-0 u-abso-t-0 u-flex u-p-30">
				<image src="/static/images/jiankangdaren.png" mode="aspectFill" class="u-w-130 u-h-130"></image>
				<view class="u-m-l-15 u-hui">
					<view class="u-flex u-row-between u-w-450 u-m-b-15">
						<view class="u-font-30 u-main-c u-font-bold u-w-300">环保达人</view>
						<view class="u-flex u-row-right u-font-24 u-hui" @click="descShow = true">
							<text>等级说明</text>
							<u-icon name="question-circle"></u-icon>
						</view>
					</view>
					<!-- <view class="u-font-24">成长值 <text class="u-main-c">{{userInfo.continuous_check}}</text>/{{userInfo.continuous_check}}</view> -->
					<u-line-progress :striped="true" :percent="(userInfo.clock_count / userInfo.continuous_check) * 100" height="18" active-color="#18AF38" :show-percent="false"></u-line-progress>

					<view class="u-font-24 u-m-t-15">已连续打卡 <text class="u-main-c">{{userInfo.clock_count}}</text>天，还差<text class="orange">{{userInfo.remaining_count}}</text>天</view>
				</view>
			</view>
		</view>
		<view class="u-m-t-30 u-type-white-bg u-radius-10 u-rela">
			<view class="u-font-bold u-p-lr-30 u-m-t-30">
				常用功能
			</view>
			<u-grid :col="4" :border="false">
				<u-grid-item @click="navigator('/pages/points/points')">
					<u-icon name="/static/user-1.png" :size="50"></u-icon>
					<view class="u-font-24 u-m-t-10">积分明细</view>
				</u-grid-item>
				<u-grid-item @click="navigator('/pages/low_carbon/my_activity')">
					<u-icon name="/static/user-2.png" :size="50"></u-icon>
					<view class="u-font-24 u-m-t-10">我的活动</view>
				</u-grid-item>
				<u-grid-item @click="navigator('/pages/order_list/order_list')">
					<u-icon name="/static/user-3.png" :size="50"></u-icon>
					<view class="u-font-24 u-m-t-10">兑换订单</view>
				</u-grid-item>
				<u-grid-item @click="navigator('/pages/msg_list/msg_list')">
					<u-icon name="/static/user-4.png" :size="50"></u-icon>
					<view class="u-font-24 u-m-t-10">消息通知</view>
				</u-grid-item>
			</u-grid>
		</view>
		<view class="u-m-t-30">
			<u-image width="100%" height="130" src="https://oss.ausite.cn/xcxkf052/admin/20250610/5457d5a5128588468ce6ff8019b57ae2.png?watermark" v-if="userInfo.shop_status==0"
				@click="navigator('/pages/settled/settled')"></u-image>
			<u-image width="100%" height="130" src="https://oss.ausite.cn/xcxkf052/admin/20250610/5457d5a5128588468ce6ff8019b57ae2.png?watermark"
				v-if="userInfo.shop_status==1||userInfo.shop_status==3" @click="navigator('/pages/settled/state')"></u-image>
			<u-image width="100%" height="130" src="https://oss.ausite.cn/xcxkf052/admin/20250610/3d7904797608c5d73cf85b06a2043fda.png?watermark" v-if="userInfo.shop_status==2"
				@click="navigator('/pages/store/store')"></u-image>
		</view>
		<view class="u-m-t-30 u-type-white-bg u-radius-10">
			<view class="u-font-bold u-p-lr-30 u-m-t-30">
				常用功能
			</view>
			<u-grid :col="4" :border="false">
				<u-grid-item @click="navigator('pages/my_comment/my_comment')" v-if="!setting.app_test">
					<u-icon name="/static/user-5.png" :size="50"></u-icon>
					<view class="u-font-24 u-m-t-10">我的评论</view>
				</u-grid-item>
				<u-grid-item @click="navigator('pages/my_evaluation/my_evaluation')">
					<u-icon name="/static/user-6.png" :size="50"></u-icon>
					<view class="u-font-24 u-m-t-10">我的测评记录</view>
				</u-grid-item>
				<u-grid-item @click="navigator('pages/my_questionnaire/my_questionnaire')">
					<u-icon name="/static/user-7.png" :size="50"></u-icon>
					<view class="u-font-24 u-m-t-10">我的问卷</view>
				</u-grid-item>
				<u-grid-item @click="navigator('pages/my_reservation/my_reservation')">
					<u-icon name="/static/user-8.png" :size="50"></u-icon>
					<view class="u-font-24 u-m-t-10">我的预约</view>
				</u-grid-item>
				<u-grid-item @click="navigator('pages/community/record')">
					<u-icon name="/static/user-9.png" :size="50"></u-icon>
					<view class="u-font-24 u-m-t-10">健康生活社区</view>
				</u-grid-item>
				<u-grid-item @click="navigator('/pages/flhd/record')">
				<u-icon name="/static/user-10.png" :size="50"></u-icon>
				<view class="u-font-24 u-m-t-10">垃圾分类打卡</view>
			</u-grid-item>
			<u-grid-item @click="navigator('/pages/flhd/list')">
				<u-icon name="https://oss.ausite.cn/xcxkf052/admin/20250512/40bfaab5aeaffdd0457cf369eca0e567.png?watermark" :size="50"></u-icon>
				<view class="u-font-24 u-m-t-10">垃圾分类指导</view>
			</u-grid-item>
			<u-grid-item @click="navigator('/pages/answer/examine?id=1')">
				<u-icon name="https://oss.ausite.cn/xcxkf052/admin/20250512/ee564dd59c747c37e5b5b01eb2db7c54.png?watermark" :size="50"></u-icon>
				<view class="u-font-24 u-m-t-10">答题考试</view>
			</u-grid-item>
			<u-grid-item @click="navigator('/pages/user/badge')">
				<u-icon name="/static/user-9.png" :size="50"></u-icon>
				<view class="u-font-24 u-m-t-10">我的徽章</view>
			</u-grid-item>
			</u-grid>
		</view>
		<view class="u-m-t-30 u-type-white-bg u-radius-10">
			<view class="u-font-bold u-p-lr-30 u-m-t-30">
				其他功能
			</view>
			<u-grid :col="4" :border="false">
				<u-grid-item @click="navigator('/pages/coupon/my_coupon')">
					<u-icon name="/static/user-11.png" :size="50"></u-icon>
					<view class="u-font-24 u-m-t-10">优惠券</view>
				</u-grid-item>
				<u-grid-item @click="navigator('/pages/address/index')">
					<u-icon name="/static/user-12.png" :size="50"></u-icon>
					<view class="u-font-24 u-m-t-10">地址管理</view>
				</u-grid-item>
				<u-grid-item @click="mask_show = true">
					<u-icon name="/static/user-13.png" :size="50"></u-icon>
					<view class="u-font-24 u-m-t-10">联系客服</view>
				</u-grid-item>
				<u-grid-item @click="navigator('/pages/user/info')">
					<u-icon name="/static/user-14.png" :size="50"></u-icon>
					<view class="u-font-24 u-m-t-10">个人信息</view>
				</u-grid-item>
				<!-- <u-grid-item>
                    <u-icon name="/static/user-15.png" :size="50"></u-icon>
                    <view class="u-font-24 u-m-t-10">扫码核销</view>
                </u-grid-item>
                <u-grid-item>
                    <u-icon name="/static/user-16.png" :size="50"></u-icon>
                    <view class="u-font-24 u-m-t-10">核销记录</view>
                </u-grid-item> -->
			</u-grid>
		</view>
		<!-- 弹窗 -->
		<u-mask :show="mask_show">
			<view class="warp">
				<view class="">
					<view class="u-w-560 u-radius-10 u-type-white-bg u-p-30 u-text-center">
						<view class="u-font-bold">
							联系客服
						</view>
						<view class="u-m-t-20 u-flex u-row-center">
							<u-image width="420" height="420" :src="setting.wx_code"></u-image>
						</view>
						<view class="u-m-t-10 u-font-24">
							扫码添加客服微信
						</view>
						<view class="u-m-t-30 u-p-lr-30">
							<u-button type="success" shape="circle" :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}" hover-class="none"
								open-type="contact">在线客服</u-button>
						</view>
					</view>
					<view class="u-m-t-30 u-text-center">
						<u-icon name="close-circle" color="#fff" size="60" @click="mask_show = false"></u-icon>
					</view>
				</view>
			</view>
		</u-mask>
		<!-- 预约说明 -->
		<u-mask :show="descShow">
			<view class="u-abso-center u-w-640 u-radius-10 u-type-white-bg">
				<view class="u-font-28 u-font-bold u-p-30 u-text-center">等级说明</view>
				<scroll-view scroll-y="true" class="u-h-700 u-p-30">
					<view class="u-font-26 u-white-space">{{setting.level_description}}</view>
				</scroll-view>
				<view class="u-p-30 u-m-t-30 u-p-t-30 u-border-top u-text-center u-font-32 u-main-c" @click="descShow = false">
					我知道了
				</view>
			</view>
		</u-mask>
	</view>
</template>

<script>
	import { checkCommunity } from '@/common/communityCheck.mixin.js'
	export default {
	data() {
		return {
			mask_show: false,
			descShow: false,
			badge_list: [],
		}
	},
		onLoad() {},
		onShow() {
			// 更新自定义tabbar选中态
			this.updateTabBarSelected(4)
			if (checkCommunity(this)) return
			if (this.isLogin) {
				this.getUserInfo()
				this.get_new_badge_list()
			}
		},
		methods: {
			// 更新自定义tabbar选中索引
			updateTabBarSelected(index) {
				const tabBar = this.$mp?.page?.getTabBar && this.$mp.page.getTabBar()
				if (tabBar) {
					tabBar.setSelected(index)
				}
			},
			async get_new_badge_list() {
				try {
					let params = {}
					let res = await this.$u.api.get_new_badge_list(params)
					this.badge_list = res.data
				} catch (e) {
					console.log(e);
					this.$u.toast(e.msg)
				}
			},
			// 跳转页面
			navigator(url) {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				this.$u.route(url)
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
		}
	}
</script>

<style lang="scss">
	.warp {
		display: flex;
		align-items: center;
		justify-content: center;
		height: 100%;
	}
	/* tabbar 悬浮：底部预留安全间距，防止内容被遮挡 */
	.user-page {
		padding-bottom: 120rpx;
		padding-bottom: calc(120rpx + constant(safe-area-inset-bottom));
		padding-bottom: calc(120rpx + env(safe-area-inset-bottom));
	}
</style>
