<template>
	<view class="container">
		<z-paging ref='paging' v-model='dataList' @query='queryList' :fixed='true'>
			<template slot="top">
				<u-navbar :is-back="true" title="" title-color="rgba(0,0,0,0)" :background="{background:'rgba(255,255,255,1)'}" :border-bottom="false">
					<view class="u-m-l-15 u-w-448">
						<u-search :show-action="false" v-model="keyword" @search="$refs.paging.reload()" @clear="$refs.paging.reload()"></u-search>
					</view>
				</u-navbar>
			</template>
			<view class="u-w-690 u-m-30 u-radius-10 u-type-white-bg u-rela u-shadow" v-for="item in dataList" :key="item.id" @click="$u.route(`/pages/low_carbon/lsxf/details-new?id=${item.id}`)">
				<u-image width="690" height="285" :src="item.thumb"></u-image>
				<view class="u-abso u-abso-l-30 u-abso-t-230 u-flex u-radius-10" style="background: rgba(0,0,0,0.44);padding: 5rpx 20rpx;" v-if="item.active_count">
					<view class="u-flex">
						<image :src="img" class="u-w-30 u-h-30 u-radius-o" mode="aspectFill" style="margin-left: -10rpx;" v-for="img,i in item.avatar_array" :key="i"></image>
					</view>
					<view class="u-font-24 white u-m-l-10">{{item.active_count}}人参与</view>
				</view>
				<view class="u-p-30">
					<view class="u-font-32 u-font-bold u-line-1">{{item.name}}</view>
					<view class="u-flex u-row-between u-m-t-15">
						<view class="u-flex  u-font-24 u-hui u-w-400">
							<view class="u-flex">
								<u-icon name="clock"></u-icon>
								<text>{{item.date | date('yy-mm-dd')}}</text>
							</view>
							<view class="u-flex u-m-l-30">
								<u-icon name="map"></u-icon>
								<text>{{item.address}}</text>
							</view>
						</view>
						<u-button :custom-style="{width:'165rpx',height:'60rpx'}" size="mini" @click="$u.route(`/pages/low_carbon/lsxf/details-new?id=${item.id}`)">立即报名</u-button>
					</view>
				</view>
			</view>
		</z-paging>

	</view>
</template>

<script>
	export default {
		data() {
			return {
				dataList: [],
				cate_id: '',
				keyword: '',
			};
		},
		onLoad(op) {
			this.cate_id = op.cate_id
		},
		methods: {
			async queryList(page) {
				try {
					let params = {
						page,
						keyword: this.keyword,
						community_cate_id: this.cate_id
					}
					let res = await this.$u.api.get_new_active_list(params)
					this.$refs.paging.complete(res.data.data);
				} catch (e) {
					console.log(e);
					this.$refs.paging.complete([]);
				}
			},
		}
	}
</script>

<style lang="scss" scoped>
	.container {
		min-height: 100vh;
		background: #F5F5F5;
	}
</style>