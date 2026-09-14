<template>
	<view class="container">
		<z-paging ref="paging" v-model="dataList" @query="queryList" :fixed="true">
			<template slot="top">
				<u-navbar :is-back="true" :title="theme_name || '主题活动'" title-color="#050505"
					:background="{background:'#ffffff'}" :border-bottom="false"></u-navbar>
			</template>
			<!-- 主题封面 banner -->
			<view class="theme-banner" v-if="theme_banner">
				<u-image width="100%" height="300rpx" :src="theme_banner" mode="aspectFill"></u-image>
			</view>
			<!-- 活动卡片列表 -->
			<view class="act-card" v-for="item in dataList" :key="item.id"
				@click="$u.route(`/pages/low_carbon/lsxf/details-new?id=${item.id}`)">
				<view class="act-card-thumb">
					<u-image width="100%" height="100%" :src="item.thumb" mode="aspectFill"></u-image>
					<view class="card-participate" v-if="item.active_count > 0">
						<view class="avatar-list" v-if="item.avatar_array && item.avatar_array.length">
							<image :src="img" class="avatar-img" mode="aspectFill"
								v-for="(img,i) in item.avatar_array.slice(0,3)" :key="i"></image>
						</view>
						<text class="count-text">{{item.active_count}}人参与</text>
					</view>
				</view>
				<view class="act-card-info">
					<view class="act-card-title u-line-1">{{item.name}}</view>
					<view class="act-card-desc u-line-2" v-if="item.dec">{{item.dec}}</view>
					<view class="act-card-meta-row">
						<view class="meta-item">
							<u-icon name="clock" size="22"></u-icon>
							<text class="meta-text">{{item.date | date('yy-mm-dd')}}</text>
						</view>
						<view class="meta-item meta-item-loc" v-if="item.address && item.address.trim()">
							<u-icon name="map" size="22"></u-icon>
							<text class="meta-text">{{item.address}}</text>
						</view>
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
				theme_name: '',
				theme_banner: '',
			};
		},
		onLoad(op) {
			this.cate_id = op.cate_id || ''
			this.theme_name = op.name ? decodeURIComponent(op.name) : ''
		},
		methods: {
			async queryList(page) {
				try {
					let res = await this.$u.api.get_new_active_list({
						page,
						community_cate_id: this.cate_id
					})
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
		background: #F0F2F5;
	}
	.theme-banner {
		margin: 16rpx 16rpx 0;
		border-radius: 16rpx;
		overflow: hidden;
		box-shadow: 0 1rpx 4rpx rgba(0, 0, 0, 0.08);
		border: 1rpx solid #E4E6EB;
	}
	.act-card {
		display: flex;
		align-items: center;
		background: #fff;
		border-radius: 16rpx;
		margin: 16rpx 16rpx 0;
		padding: 16rpx;
		box-shadow: 0 1rpx 4rpx rgba(0, 0, 0, 0.08);
		border: 1rpx solid #E4E6EB;
	}
	.act-card:active { opacity: 0.85; }
	.act-card-thumb {
		position: relative;
		width: 220rpx;
		height: 165rpx;
		flex-shrink: 0;
		margin-right: 16rpx;
		border-radius: 12rpx;
		overflow: hidden;
		background: #F0F2F5;
	}
	.act-card-thumb ::v-deep .u-image {
		width: 100% !important;
		height: 100% !important;
	}
	.card-participate {
		position: absolute;
		left: 10rpx; bottom: 10rpx;
		display: inline-flex;
		align-items: center;
		height: 34rpx;
		padding: 0 10rpx;
		background: rgba(0, 0, 0, 0.5);
		border-radius: 17rpx;
	}
	.avatar-list { display: flex; }
	.avatar-img {
		width: 22rpx; height: 22rpx;
		border-radius: 50%;
		margin-left: -6rpx;
		border: 2rpx solid #fff;
	}
	.avatar-img:first-child { margin-left: 0; }
	.count-text {
		color: #fff; font-size: 18rpx; margin-left: 6rpx;
	}
	.act-card-info {
		flex: 1; min-width: 0;
	}
	.act-card-title {
		font-size: 30rpx;
		font-weight: 600;
		color: #050505;
	}
	.act-card-desc {
		font-size: 24rpx;
		color: #65676B;
		line-height: 1.5;
		margin-top: 6rpx;
	}
	.act-card-meta-row {
		display: flex;
		align-items: center;
		margin-top: 12rpx;
		font-size: 22rpx;
		color: #65676B;
	}
	.meta-item {
		display: flex;
		align-items: center;
	}
	.meta-item-loc { margin-left: 16rpx; }
	.meta-item .u-icon { margin-right: 4rpx; }
	.meta-text {
		max-width: 200rpx;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}
</style>
