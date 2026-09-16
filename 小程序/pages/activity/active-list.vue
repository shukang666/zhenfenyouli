<template>
	<view class="container">
		<z-paging ref='paging' v-model='dataList' @query='queryList' :fixed='true'>
			<template slot="top">
				<u-navbar :is-back="true" title="" title-color="rgba(0,0,0,0)" :background="{background:'#FFFFFF'}" :border-bottom="false">
					<view class="fb-search-wrap">
						<view class="fb-search-box">
							<u-icon name="search" size="32" color="#65676B"></u-icon>
							<input class="fb-search-input" v-model="keyword" placeholder="搜索活动" placeholder-class="fb-search-ph"
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
							<view class="fb-filter-item" v-for="tag in tagOptions" :key="tag" :class="{ 'fb-filter-active': filterType === 'tag' && filterTag === tag }" @click="switchFilter('tag', tag)">{{tag}}</view>
						</view>
					</scroll-view>
				</view>
			</template>
			<view class="fb-list">
				<view class="fb-card" v-for="item in dataList" :key="item.id"
					@click="$u.route(`/pages/activity/lsxf/details-new?id=${item.id}`)">
					<!-- 卡片封面图 -->
					<view class="fb-card-cover">
						<u-image width="100%" height="360" :src="item.thumb" mode="aspectFill"></u-image>
						<!-- 参与人数 badge -->
						<view class="fb-card-badge" v-if="item.active_count">
							<view class="fb-avatar-group">
								<image :src="img" class="fb-avatar" mode="aspectFill"
									v-for="img,i in item.avatar_array" :key="i" v-if="i<3"></image>
							</view>
							<text class="fb-badge-text">{{item.active_count}}人参与</text>
						</view>
					</view>
					<!-- 卡片正文 -->
					<view class="fb-card-body">
						<view class="fb-card-title u-line-1">{{item.name}}</view>
						<view class="fb-card-meta">
							<view class="fb-meta-item">
								<u-icon name="clock" size="26" color="#65676B"></u-icon>
								<text class="fb-meta-text">{{item.date | date('yy-mm-dd')}}</text>
							</view>
							<view class="fb-meta-item" v-if="item.address && item.address.trim()">
								<u-icon name="map" size="26" color="#65676B"></u-icon>
								<text class="fb-meta-text u-line-1">{{item.address}}</text>
							</view>
						</view>
						<view class="fb-card-footer">
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
				if (['recommend', 'light', 'offline'].includes(op.filter)) {
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
	background: #F0F2F5;
}

/* ===== Facebook 风格搜索栏 ===== */
.fb-search-wrap {
	width: 100%;
	padding: 0 24rpx 0 12rpx;
}
.fb-search-box {
	display: flex;
	align-items: center;
	height: 72rpx;
	background: #F0F2F5;
	border-radius: 36rpx;
	padding: 0 20rpx;
	gap: 12rpx;
}
.fb-search-input {
	flex: 1;
	height: 100%;
	font-size: 28rpx;
	color: #050505;
}
.fb-search-ph {
	color: #65676B;
	font-size: 28rpx;
}
.fb-search-clear {
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 4rpx;
}

/* ===== Facebook 风格卡片列表 ===== */
.fb-list {
	padding: 16rpx 16rpx 32rpx;
}
.fb-card {
	background: #fff;
	border-radius: 16rpx;
	margin-bottom: 16rpx;
	overflow: hidden;
	box-shadow: 0 1rpx 4rpx rgba(0, 0, 0, 0.08);
	border: 1rpx solid #E4E6EB;
}
.fb-card:active { opacity: 0.92; }

.fb-card-cover {
	position: relative;
	width: 100%;
	height: 360rpx;
	background: #F0F2F5;
}
.fb-card-cover ::v-deep .u-image {
	width: 100% !important;
	height: 100% !important;
}

/* 参与人数 badge */
.fb-card-badge {
	position: absolute;
	left: 20rpx; bottom: 20rpx;
	z-index: 2;
	display: inline-flex;
	align-items: center;
	height: 44rpx;
	padding: 0 16rpx 0 8rpx;
	background: rgba(0, 0, 0, 0.55);
	border-radius: 22rpx;
}
.fb-avatar-group {
	display: flex;
	align-items: center;
}
.fb-avatar {
	width: 28rpx; height: 28rpx;
	border-radius: 50%;
	margin-left: -8rpx;
	border: 2rpx solid #fff;
	background: #F0F2F5;
}
.fb-avatar:first-child { margin-left: 0; }
.fb-badge-text {
	color: #fff;
	font-size: 22rpx;
	font-weight: 500;
	margin-left: 8rpx;
}

/* 卡片正文 */
.fb-card-body {
	padding: 24rpx 24rpx 20rpx;
}
.fb-card-title {
	font-size: 32rpx;
	font-weight: 600;
	color: #050505;
	line-height: 1.4;
	margin-bottom: 14rpx;
}
.fb-card-meta {
	display: flex;
	flex-wrap: wrap;
	gap: 16rpx;
	margin-bottom: 16rpx;
}
.fb-meta-item {
	display: flex;
	align-items: center;
	min-width: 0;
	flex: 1;
}
.fb-meta-item .u-icon { margin-right: 6rpx; }
.fb-meta-text {
	font-size: 24rpx;
	color: #65676B;
	flex: 1;
	overflow: hidden;
	white-space: nowrap;
	text-overflow: ellipsis;
}

/* 底部操作区 */
.fb-card-footer {
	display: flex;
	justify-content: flex-end;
	padding-top: 8rpx;
	border-top: 1rpx solid #E4E6EB;
}
.fb-join-btn {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	height: 64rpx;
	padding: 0 36rpx;
	background: #E7F0FE;
	color: #1877F2;
	font-size: 26rpx;
	font-weight: 600;
	border-radius: 32rpx;
	margin-top: 16rpx;
	transition: all 0.2s;
}
.fb-join-btn:active {
	background: #1877F2;
	color: #fff;
}

/* ===== 快捷筛选栏 ===== */
.fb-filter-bar {
	background: #fff;
	border-bottom: 1rpx solid #E4E6EB;
}
.fb-filter-scroll {
	white-space: nowrap;
	padding: 0 24rpx 16rpx;
}
.fb-filter-list {
	display: inline-flex;
	gap: 16rpx;
}
.fb-filter-item {
	display: inline-flex;
	align-items: center;
	height: 64rpx;
	padding: 0 28rpx;
	border-radius: 32rpx;
	background: #F0F2F5;
	font-size: 26rpx;
	color: #050505;
	font-weight: 500;
	white-space: nowrap;
	transition: all 0.15s;
}
.fb-filter-item:active {
	background: #E4E6EB;
}
.fb-filter-active {
	background: #1877F2;
	color: #fff;
}
.fb-filter-active:active {
	background: #166FE5;
}
</style>
