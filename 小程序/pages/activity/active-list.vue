<template>
	<view class="container">
		<z-paging ref='paging' v-model='dataList' @query='queryList' :fixed='true'>
			<template slot="top">
				<u-navbar :is-back="true" title="" title-color="rgba(0,0,0,0)" :background="{background:'#FFFFFF'}" :border-bottom="false">
					<view class="fb-search-wrap">
						<view class="fb-search-box">
							<view class="fb-search-ico">
								<u-icon name="search" size="28" color="#18AF38"></u-icon>
							</view>
							<input class="fb-search-input" v-model="keyword" placeholder="搜索活动名称 / 地点" placeholder-class="fb-search-ph"
								confirm-type="search" @confirm="onSearchConfirm" />
							<view class="fb-search-clear" v-if="keyword" @click="clearKeyword">
								<u-icon name="close-circle-fill" size="28" color="#BCC0C4"></u-icon>
							</view>
						</view>
					</view>
				</u-navbar>
				<!-- 快捷筛选栏 -->
				<view class="fb-filter-bar" v-if="showFilter">
					<scroll-view scroll-x class="fb-filter-scroll" :show-scrollbar="false">
						<view class="fb-filter-list">
							<view class="fb-filter-item" :class="{ 'fb-filter-active': filterType === 'all' }" @click="switchFilter('all')">全部</view>
							<view class="fb-filter-item" :class="{ 'fb-filter-active': filterType === 'recommend' }" @click="switchFilter('recommend')">推荐</view>
							<view class="fb-filter-item" :class="{ 'fb-filter-active': filterType === 'light' }" @click="switchFilter('light')">轻活动</view>
							<view class="fb-filter-item" :class="{ 'fb-filter-active': filterType === 'offline' }" @click="switchFilter('offline')">线下活动</view>
							<view class="fb-filter-item" :class="{ 'fb-filter-active': filterType === 'weekly' }" @click="switchFilter('weekly')">本周活动</view>
							<view class="fb-filter-item" v-for="tag in tagOptions" :key="tag" :class="{ 'fb-filter-active': filterType === 'tag' && filterTag === tag }" @click="switchFilter('tag', tag)">{{tag}}</view>
						</view>
					</scroll-view>
				</view>
			</template>
			<view class="fb-list">
				<view class="fb-card" v-for="item in dataList" :key="item.id"
					hover-class="fb-card--on" :hover-stay-time="80"
					@click="$u.route(`/pages/activity/lsxf/details-new?id=${item.id}`)">
					<view class="fb-card-cover">
						<u-image width="100%" height="100%" :src="item.thumb" mode="aspectFill"></u-image>
						<view class="fb-card-badge" v-if="item.active_count > 0">
							<text class="fb-badge-text">{{item.active_count}}人参与</text>
						</view>
					</view>
					<view class="fb-card-body">
						<view class="fb-card-title u-line-2">{{item.name}}</view>
						<view class="fb-card-meta">
							<view class="fb-meta-item">
								<u-icon name="clock" size="22" color="#18AF38"></u-icon>
								<text class="fb-meta-text">{{item.date | date('yy-mm-dd')}}</text>
							</view>
							<view class="fb-meta-item" v-if="item.address && item.address.trim()">
								<u-icon name="map" size="22" color="#18AF38"></u-icon>
								<text class="fb-meta-text u-line-1">{{item.address}}</text>
							</view>
						</view>
						<view class="fb-card-footer">
							<view class="fb-card-point" v-if="item.send_point > 0">
								<u-icon name="integral" size="22" color="#18AF38"></u-icon>
								<text class="fb-point-text">+{{item.send_point}}积分</text>
							</view>
							<view class="fb-join-btn" @tap.stop="$u.route(`/pages/activity/lsxf/details-new?id=${item.id}`)">
								立即参与
							</view>
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
				keyword: '',
				// 筛选相关
				showFilter: false,
				filterType: 'all',
				filterTag: '',
				tagOptions: [],
			};
		},
		onLoad(op) {
			this.cate_id = op.cate_id
			// 根据入口参数决定初始筛选
			if (op.filter) {
				this.showFilter = true
				if (['recommend', 'light', 'offline', 'weekly'].includes(op.filter)) {
					this.filterType = op.filter
				} else if (op.filter.startsWith('tag:')) {
					this.filterType = 'tag'
					this.filterTag = op.filter.slice(4)
				}
			} else if (op.cate_id) {
				// 分类入口 → 显示筛选栏但只留"全部" + "推荐"
				this.showFilter = true
				this.filterType = 'all'
			} else {
				this.showFilter = true
				this.filterType = 'all'
			}
			this.loadFilterTags()
		},
		methods: {
			// 加载可选标签（用于动态筛选项，可隐藏不需要的动态标签）
			async loadFilterTags() {
				try {
					let res = await this.$u.api.get_active_tags_list({})
					let tags = res.data || []
					// 只保留轻活动、线下活动已单独做按钮；这里放剩余的标签
					this.tagOptions = tags.filter(t => !['轻活动', '线下活动'].includes(t))
				} catch (e) {
					this.tagOptions = []
				}
			},
			// 切换筛选条件
			switchFilter(type, tag) {
				this.filterType = type
				this.filterTag = tag || ''
				this.$refs.paging.reload()
			},
			// 搜索
			onSearchConfirm() {
				this.$refs.paging.reload()
			},
			async queryList(page) {
				try {
					let params = {
						page,
						keyword: this.keyword,
						community_cate_id: this.cate_id
					}
					if (this.filterType === 'recommend') {
						params.is_recommendation = 1
					} else if (this.filterType === 'light') {
						params.tag = '轻活动'
					} else if (this.filterType === 'offline') {
						params.tag = '线下活动'
					} else if (this.filterType === 'weekly') {
						params.is_month_recommend = 1
					} else if (this.filterType === 'tag' && this.filterTag) {
						params.tag = this.filterTag
					}
					let res = await this.$u.api.get_new_active_list(params)
					this.$refs.paging.complete(res.data.data);
				} catch (e) {
					console.log(e);
					this.$refs.paging.complete([]);
				}
			},
			clearKeyword() {
				this.keyword = ''
				this.$refs.paging.reload()
			}
		}
	}
</script>

<style lang="scss" scoped>
.container {
	min-height: 100vh;
	background: #eef4f0;
}

.fb-search-wrap {
	width: 100%;
	padding: 0 24rpx 0 8rpx;
}
.fb-search-box {
	display: flex;
	align-items: center;
	height: 72rpx;
	background: #f3f8f4;
	border-radius: 36rpx;
	padding: 0 12rpx 0 10rpx;
}
.fb-search-ico {
	width: 48rpx;
	height: 48rpx;
	border-radius: 50%;
	background: #e8f8ec;
	display: flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
	margin-right: 8rpx;
}
.fb-search-input {
	flex: 1;
	height: 100%;
	font-size: 26rpx;
	color: #1c2b1e;
}
.fb-search-ph {
	color: #9aa39c;
	font-size: 26rpx;
}
.fb-search-clear {
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 4rpx 8rpx;
}

.fb-filter-bar {
	background: #fff;
	padding-top: 4rpx;
}
.fb-filter-scroll {
	white-space: nowrap;
	padding: 8rpx 24rpx 18rpx;
}
.fb-filter-list {
	display: inline-flex;
}
.fb-filter-item {
	display: inline-flex;
	align-items: center;
	height: 60rpx;
	padding: 0 26rpx;
	margin-right: 12rpx;
	border-radius: 30rpx;
	background: #f3f8f4;
	font-size: 24rpx;
	color: #4d5e52;
	font-weight: 500;
	white-space: nowrap;
}
.fb-filter-item:active {
	opacity: 0.85;
}
.fb-filter-active {
	background: linear-gradient(135deg, #2bc24e 0%, #18af38 100%);
	color: #fff;
	font-weight: 700;
	box-shadow: 0 6rpx 14rpx rgba(24, 175, 56, 0.22);
}
.fb-filter-active:active {
	opacity: 0.9;
}

.fb-list {
	padding: 20rpx 24rpx 40rpx;
}
.fb-card {
	display: flex;
	background: #fff;
	border-radius: 24rpx;
	margin-bottom: 20rpx;
	overflow: hidden;
	box-shadow: 0 10rpx 28rpx rgba(16, 48, 24, 0.07);
}
.fb-card--on {
	opacity: 0.92;
	transform: scale(0.99);
}

.fb-card-cover {
	width: 220rpx;
	height: 240rpx;
	flex-shrink: 0;
	position: relative;
	background: #dce8de;
	overflow: hidden;
}
.fb-card-cover ::v-deep .u-image {
	width: 100% !important;
	height: 100% !important;
}

.fb-card-badge {
	position: absolute;
	left: 10rpx;
	bottom: 10rpx;
	z-index: 2;
	display: inline-flex;
	align-items: center;
	height: 36rpx;
	padding: 0 12rpx;
	background: rgba(0, 0, 0, 0.5);
	border-radius: 18rpx;
}
.fb-badge-text {
	color: #fff;
	font-size: 18rpx;
	font-weight: 500;
}

.fb-card-body {
	flex: 1;
	padding: 18rpx 20rpx 16rpx;
	display: flex;
	flex-direction: column;
	min-width: 0;
}
.fb-card-title {
	font-size: 28rpx;
	font-weight: 700;
	color: #1c2b1e;
	line-height: 1.35;
	margin-bottom: 12rpx;
	height: 76rpx;
}
.fb-card-meta {
	display: flex;
	flex-wrap: wrap;
	flex: 1;
}
.fb-meta-item {
	display: flex;
	align-items: center;
	min-width: 0;
	max-width: 100%;
	margin: 0 12rpx 8rpx 0;
	padding: 4rpx 12rpx 4rpx 8rpx;
	background: #f3f8f4;
	border-radius: 999rpx;
}
.fb-meta-item .u-icon { margin-right: 4rpx; }
.fb-meta-text {
	font-size: 20rpx;
	color: #6a7b6e;
	flex: 1;
	overflow: hidden;
	white-space: nowrap;
	text-overflow: ellipsis;
}

.fb-card-footer {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-top: 4rpx;
}
.fb-card-point {
	display: flex;
	align-items: center;
	min-width: 0;
}
.fb-point-text {
	margin-left: 4rpx;
	font-size: 22rpx;
	color: #18AF38;
	font-weight: 700;
}
.fb-join-btn {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	height: 52rpx;
	padding: 0 26rpx;
	background: linear-gradient(135deg, #2bc24e 0%, #18af38 100%);
	color: #fff;
	font-size: 24rpx;
	font-weight: 700;
	border-radius: 26rpx;
	box-shadow: 0 6rpx 14rpx rgba(24, 175, 56, 0.22);
	flex-shrink: 0;
}
.fb-join-btn:active {
	opacity: 0.88;
}
</style>
