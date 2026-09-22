<template>
	<view class="u-p-30" @touchstart="onCareTouchStart" @touchmove="onCareTouchMove" @touchend="onCareTouchEnd">
		
		<!-- 关怀版下拉提示 -->
		<view class="care-pull-layer" :style="{ height: carePullHeight + 'px', opacity: careHintOpacity }" v-if="showCareHint">
			<view class="care-pull-content">
<text class="care-pull-text">{{ carePullHeight >= 90 ? '松开进入关怀版' : '下拉进入关怀版' }}</text>
			<u-icon :name="carePullHeight >= 90 ? 'arrow-up' : 'arrow-down'" size="28" color="#18AF38"></u-icon>
			</view>
		</view>
		<!-- 骨架屏 -->
		<skeleton-screen :show="isLoading" type="health"></skeleton-screen>
		<u-navbar title-color="#fff" is-fixed :is-back="false" background="transparent" :borderBottom="false">
			<view class="u-m-l-30">
				<u-image width="231" height="64" :src="setting.min_logo"></u-image>
			</view>
		</u-navbar>
		<care-pull-hint :visible="!showCareHint"></care-pull-hint>
		<view class="u-abso u-h-552" style="z-index: -1;top: 0;left: 0;right: 0;background: linear-gradient(180deg, #3974F2 0%, #EFF4FF 100%);">
			<!-- <u-image width="100%" height="527" src="/static/index-bg.png"></u-image> -->
		</view>

		<!-- 为了通过小程序审核，先注释掉下面 -->
		<!-- <view class="u-radius-10" id="guide-health-diag" style="background: linear-gradient(90deg, #3974F2 0%, rgba(57,116,242,0.5) 100%);">
			<view class="u-font-36 u-p-20 white">
				健康诊断
			</view>
			<view class="u-type-white-bg u-radius-10 u-p-20">
				<view class="u-flex u-row-between">
					<u-image width="314" height="123" src="https://oss.ausite.cn/xcxkf052/admin/20250509/8c9d75f326df69f47e92f609090fc0f6.png?watermark"
						@click="$u.route('/pages/sxjk/sxjk')"></u-image>
					<u-image width="314" height="123" src="https://oss.ausite.cn/xcxkf052/admin/20250509/b2067dad50db00339fec1bd39423168c.png?watermark" @click="$u.toast('功能开发中')"
						v-if="!setting.app_test"></u-image>
				</view>
				<view class="u-flex u-row-between u-m-t-20">
					<u-image width="314" height="123" src="https://oss.ausite.cn/xcxkf052/admin/20250509/cec0fddb04741996404e1d791be2ee9d.png?watermark"
						@click="$u.route('/pages/sxjcgj/sxjcgj')"></u-image>
					<u-image width="314" height="123" src="https://oss.ausite.cn/xcxkf052/admin/20250509/aba1eaaf62ee9821200689817e12d60f.png?watermark" @click="$u.toast('功能开发中')"
						v-if="!setting.app_test"></u-image>
				</view>
				<view class="u-flex u-row-between u-m-t-20">
					<u-image width="314" height="123" src="https://oss.ausite.cn/xcxkf052/admin/20250509/32c529525baa3d53fcc4b12f5a289299.png?watermark"
						@click="$u.route('/pages/healthcare/add?id=0')"></u-image>
					<u-image width="314" height="123" src="https://oss.ausite.cn/xcxkf052/admin/20250509/f4babaa3267896ff8e4737c9ffa4ce3c.png?watermark" @click="openChannelsLive(setting.video_id)"
						v-if="setting.app_test != 1"></u-image>
				</view>
			</view>
		</view> -->
		<view class="u-radius-10 u-m-t-30" id="guide-health-life" style="background: linear-gradient(90deg, #3974F2 0%, rgba(57,116,242,0.5) 100%);" v-if="!setting.app_test">
			<view class="u-font-36 u-p-20 white u-flex u-row-between">
				<view class="">
					健康生活社区
				</view>
				<view class="">
					<u-icon name="arrow-right" label="更多" label-size="22" label-color="#fff" color="#fff" label-pos="left" size="22" @click="$u.route('/pages/community/community')"></u-icon>
				</view>
			</view>
			<view class="u-type-white-bg u-radius-10 u-p-20">
				<view class="u-type-info-light-bg u-radius-10 u-p-20 u-m-b-20" v-for="(item,index) in forum_list" :key="index"
					@click="$u.route('/pages/community/details?id='+item.id+'&index='+index)">
					<view class="u-flex u-row-between">
						<view class="u-flex">
							<u-avatar :src="item.avatar" size="40"></u-avatar>
							<view class="u-m-l-10 u-font-bold">
								{{item.nickname}}
							</view>
						</view>
						<view class="u-font-24 u-type-info">
							{{item.create_time|date('yy年mm月dd日 hh:MM')}}
						</view>
					</view>
					<view class="u-m-t-20 u-font-bold u-font-26">
						{{item.title}}
					</view>
				<view class="u-m-t-20 u-font-bold u-font-26 content-text">
					{{item.content}}
				</view>
					<view class="u-m-t-20">
						<cl-upload v-model="item.image" :max="item.image?item.image.length:''" :remove="false" :isPreviewImage="false">
						</cl-upload>
					</view>
					<view class="u-m-t-20 u-flex u-row-between">
						<view class="u-font-24 u-type-info">
							已有{{item.view_num}}人围观
						</view>
						<view class="u-flex">
							<view class="">
								<u-icon :name="!item.is_like?'thumb-up':'thumb-up-fill'" size="36" :color="!item.is_like?'#909399':'#3974f2'" :label="item.like_total" label-color="#909399"></u-icon>
							</view>
							<view class="u-m-l-20">
								<u-icon name="chat" :label="item.comment_total" size="38" color="#909399" label-color="#909399"></u-icon>
							</view>
							<view class="u-m-l-20">
								<button class="u-reset-button" open-type="share" @click.stop="share_brn(item)">
									<u-icon name="zhuanfa" label="分享" size="34" color="#909399" label-color="#909399"></u-icon>
								</button>
							</view>
						</view>
					</view>
				</view>
			</view>
		</view>
		<!-- tabbar 占位：防止悬浮 tabbar 遮挡底部内容 -->
		<view style="height: 172rpx; flex-shrink: 0;"></view>
		<page-guide page="health"></page-guide>
	</view>
</template>

<script>
	import { checkCommunity } from '@/common/communityCheck.mixin.js'
	import SkeletonScreen from '@/components/skeleton-screen/index.vue'
	import careGesture from '@/common/care-gesture.mixin.js'
	export default {
		components: {
			SkeletonScreen
		},
		mixins: [careGesture],
		data() {
			return {
				isLoading: true,
				forum_list: [],
				page: 1,
				total: 0,
				health: {}
			};
		},
		onLoad() {
			this.loadAllData()
		},
		onShow() {
			// 更新自定义tabbar选中态
			this.updateTabBarSelected(2)
			if (checkCommunity(this)) return
		},
		methods: {
		// 加载数据，完成后隐藏骨架屏（最少显示 400ms 防止闪烁）
		async loadAllData() {
			const startTime = Date.now()
			try {
				await this.get_forum_list()
			} catch (e) {
				console.log('数据加载失败:', e)
			} finally {
				// 确保骨架屏最少显示 400ms，防止加载过快导致闪烁
				const elapsed = Date.now() - startTime
				const minDelay = 400
				if (elapsed < minDelay) {
					setTimeout(() => {
						this.isLoading = false
					}, minDelay - elapsed)
				} else {
					this.isLoading = false
				}
			}
		},
			// 分享
			share_brn(item) {
				this.health = item
			},

			// 跳转公众号直播
			openChannelsLive(finderUserName) {
				wx.openChannelsLive({
					finderUserName,
					success(res) {
						console.log(res)
					},
					fail(err) {
						console.log(err)
					}
				})
			},
		// 列表
		get_forum_list() {
			return this.$u.api.get_forum_list({
				keyword: this.keyword,
				type: 1,
				page: this.page
			}).then(res => {
				this.forum_list = res.data?.data || []
				this.total = res.data?.total || 0
			}).catch(err => {
				this.$u.toast(err.msg)
			})
		},
			// 更新自定义tabbar选中索引
			updateTabBarSelected(index) {
				const tabBar = this.$mp?.page?.getTabBar && this.$mp.page.getTabBar()
				if (tabBar) {
					tabBar.setSelected(index)
				}
			},
		},
		onReachBottom() {
			if (this.forum_list.length >= this.total) return
			this.page++
			this.get_forum_list()
		},
		onShareAppMessage(res) {
			return {
				title: this.health.title,
				imageUrl: this.health.image[0],
				path: '/pages/community/details?id=' + this.health.id
			}
		},
		onShareTimeline() {
			return {
				title: this.health.title,
				imageUrl: this.health.image[0],
				path: '/pages/community/details?id=' + this.health.id
			}
		}
	}
</script>

<style lang="scss">
.content-text {
    white-space: pre-line;
    word-break: break-all;
}

/* 关怀版下拉提示层 */
.care-pull-layer {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	background: linear-gradient(180deg, #E8F5EE 0%, rgba(232, 245, 238, 0.9) 100%);
	display: flex;
	align-items: flex-end;
	justify-content: center;
	padding-bottom: 16rpx;
	z-index: 9998;
	pointer-events: none;
	
	.care-pull-content {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 8rpx;
		
		.care-pull-text {
			font-size: 28rpx;
			color: #18AF38;
			font-weight: 500;
		}
	}
}
</style>