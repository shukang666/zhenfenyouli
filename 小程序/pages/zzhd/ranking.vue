<template>
	<view class="">
		<u-image width="100%" height="300" src="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/xcxkf052/admin/20250514/663647304b90b1365fa10e743f44d60a.jpg"></u-image>
		<view class="u-p-30 u-type-white-bg u-radius-10 u-rela" style="margin-top: -60rpx; z-index: 9;">
			<block v-if="accuracy_list.length">
				<view class="u-flex u-row-between u-col-bottom">
					<view class="u-type-primary-light-bg u-radius-10 u-text-center u-shrink-no u-w-208" style="padding: 0rpx 30rpx 30rpx;">
						<view class="u-flex u-row-center">
							<u-image width="108" height="35" src="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/ranking-2.png"></u-image>
						</view>
						<u-gap height="15"></u-gap>
						<u-avatar :src="accuracy_list[1].avatar" size="70"></u-avatar>
						<view class=" u-m-t-10 u-line-1">
							{{accuracy_list[1].username||accuracy_list[1].nickname||'无'}}
						</view>
						<view class="u-font-22 u-m-t-10 u-type-info">
							环保达人LV.{{accuracy_list[1].level||'0'}}
						</view>
					</view>
					<view class="u-type-primary-light-bg u-radius-10 u-text-center u-w-208 u-shrink-no" style="padding: 0rpx 30rpx 30rpx;">
						<view class="u-flex u-row-center">
							<u-image width="108" height="35" src="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/ranking-1.png"></u-image>
						</view>
						<u-gap height="15"></u-gap>
						<u-avatar :src="accuracy_list[0].avatar" size="70"></u-avatar>
						<view class=" u-m-t-20 u-line-1">
							{{accuracy_list[0].username||accuracy_list[0].nickname||'无'}}
						</view>
						<view class="u-font-22 u-m-t-10 u-type-info">
							环保达人LV.{{accuracy_list[0].level||'0'}}
						</view>
					</view>
					<view class="u-type-primary-light-bg u-radius-10 u-text-center u-shrink-no u-w-208" style="padding: 0rpx 30rpx 30rpx;">
						<view class="u-flex u-row-center">
							<u-image width="108" height="35" src="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/ranking-3.png"></u-image>
						</view>
						<u-gap height="15"></u-gap>
						<u-avatar :src="accuracy_list[2].avatar" size="70"></u-avatar>
						<view class=" u-m-t-10 u-line-1">
							{{accuracy_list[2].username||accuracy_list[2].nickname||'无'}}
						</view>
						<view class="u-font-22 u-m-t-10 u-type-info">
							环保达人LV.{{accuracy_list[2].level||'0'}}
						</view>
					</view>
				</view>

				<view class="u-p-tb-30 u-border-bottom u-flex u-row-between" v-for="(item,index) in accuracy_list" :key="index" v-if="index>2">
					<view class="u-flex">
						<view class="u-m-r-30 u-font-bold u-font-32 u-type-info">
							{{index+1}}
						</view>
						<view class="u-flex">
							<u-avatar :src="item.avatar" size="70"></u-avatar>
							<view class="u-m-l-20 ">
								<view class="">
									{{item.nickname}}
								</view>
								<!-- <view class="u-m-t-10 u-type-info">
                                    {{item.division_name}}
                                </view> -->
							</view>
						</view>
					</view>
					<view class="u-font-22">
						环保达人LV.{{item.level||'0'}}
					</view>
				</view>
			</block>
			<block v-else>
				<u-empty text="暂无排名信息" margin-top="160"></u-empty>
			</block>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				accuracy_list: [],
				page: 1,
				total: 0,
			};
		},
		onLoad(option) {
			this.get_ranking()
		},
		methods: {
			get_ranking() {
				this.$u.api.get_ranking({
					page: this.page,
				}).then(res => {
					this.accuracy_list.push(...res.data.data)
					this.total = res.data.total
				})
			}
		},
		onReachBottom() {
			if (this.accuracy_list.length >= this.total) return
			this.page++
			this.get_ranking()
		}
	}
</script>

<style lang="scss">
	page {
		background-color: #fff;
	}
</style>