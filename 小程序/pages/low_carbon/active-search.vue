<template>
	<view class="search-page">
		<!-- 顶部自定义导航：返回箭头 + 搜索框 -->
		<u-navbar :is-back="true" title="" title-color="rgba(0,0,0,0)"
				:background="{ background: '#ffffff' }" :border-bottom="false">
				<view class="search-navbar">
					<u-search v-model="keyword" placeholder="搜索活动名称 / 地点" bg-color="#F0F2F5" height="66"
						:show-action="false" @search="doSearch" @clear="handleClear"
						@change="onKeywordChange"></u-search>
					<!-- 原生搜索按钮：u-search 内置 show-action 按钮点击在部分环境(如 H5)不触发，改用自定义原生 view 点击 -->
					<view class="navbar-search-btn" hover-class="navbar-search-btn-hover" @click="doSearch">搜索</view>
				</view>
			</u-navbar>

		<!-- 默认态（未发起搜索）：历史搜索 + 热门活动推荐 -->
		<view v-if="!searched" class="search-home">
			<!-- 历史搜索 -->
			<view class="section" v-if="histories.length">
				<view class="section-head">
					<text class="section-title">历史搜索</text>
					<view class="clear-btn" @click="clearHistories">
						<u-icon name="trash" size="26" color="#65676B"></u-icon>
						<text class="clear-txt">清空</text>
					</view>
				</view>
				<view class="history-list">
					<view class="history-item" v-for="(h, i) in histories" :key="i" @click="searchByHistory(h)">
						<text class="history-text">{{ h }}</text>
						<view class="history-del" @click.stop="removeHistory(i)">
							<u-icon name="close" size="20" color="#c0c4cc"></u-icon>
						</view>
					</view>
				</view>
			</view>

			<!-- 热门活动推荐（空态兜底引导） -->
			<view class="section" v-if="hotActive.length">
				<view class="section-head">
					<text class="section-title">热门活动</text>
					<text class="section-sub">大家都在参加</text>
				</view>
				<scroll-view scroll-x class="hot-scroll" :show-scrollbar="false">
					<view class="hot-row">
						<view class="hot-card" v-for="item in hotActive" :key="item.id" @click="gotoDetail(item)">
							<view class="hot-img">
								<u-image width="100%" height="100%" :src="item.thumb" mode="aspectFill"></u-image>
								<view class="hot-badge" v-if="item.active_count > 0">{{ item.active_count }}人参与</view>
							</view>
							<view class="hot-body">
								<view class="hot-name u-line-1">{{ item.name }}</view>
								<view class="hot-meta">
									<text class="hot-addr u-line-1" v-if="item.address && item.address.trim()">{{ item.address }}</text>
									<text class="hot-addr" v-else>精彩活动等你来</text>
								</view>
							</view>
						</view>
					</view>
				</scroll-view>
			</view>

			<!-- 无历史也无推荐时的引导 -->
			<view class="first-tip" v-if="!hotActive.length && !histories.length">
				<u-icon name="search" size="90" color="#e3e3e3"></u-icon>
				<view class="tip-title">搜索心仪的活动</view>
				<view class="tip-sub">输入活动名称或地点，快速找到想参加的活动</view>
			</view>
		</view>

		<!-- 搜索结果态 -->
		<view v-else class="result-wrap">
			<view class="result-count" v-if="resultList.length">共找到 {{ total }} 个与 “{{ keyword }}” 相关的活动</view>

			<view class="act-card" v-for="item in resultList" :key="item.id" @click="gotoDetail(item)">
				<u-image width="100%" height="280" :src="item.thumb" mode="aspectFill"></u-image>
				<view class="card-participate" v-if="item.active_count > 0">
					<view class="avatar-list" v-if="item.avatar_array && item.avatar_array.length">
						<image :src="img" class="avatar-img" mode="aspectFill"
							v-for="(img, i) in item.avatar_array.slice(0, 3)" :key="i"></image>
					</view>
					<text class="count-text">{{ item.active_count }}人参与</text>
				</view>
				<view class="act-card-info">
					<view class="act-title u-line-1">{{ item.name }}</view>
					<view class="act-meta">
						<u-icon name="clock" size="22"></u-icon>
						<text class="meta-text">{{ item.date | date('yy-mm-dd') }}</text>
						<text class="meta-text" style="margin: 0 12rpx;">·</text>
						<u-icon name="map" size="22" v-if="item.address && item.address.trim()"></u-icon>
						<text class="meta-text" v-if="item.address && item.address.trim()">{{ item.address }}</text>
					</view>
				</view>
			</view>

			<!-- 首次搜索请求中 -->
			<view v-if="loading && !resultList.length" class="loading-first">
				<u-loading mode="circle" color="#1877F2"></u-loading>
				<view class="loading-txt">正在搜索中...</view>
			</view>

			<!-- 空结果 -->
			<view v-if="!loading && resultList.length === 0" class="empty-box">
				<u-icon name="search" size="90" color="#e3e3e3"></u-icon>
				<view class="empty-title">未找到与 “{{ keyword }}” 相关的活动</view>
				<view class="empty-sub">换个关键词试试，或看看热门活动</view>
				<view class="empty-btn" @click="backToHome">返回查看热门活动</view>
			</view>

			<view v-if="loading && resultList.length" class="foot-tip">加载中...</view>
			<view v-if="!loading && resultList.length && resultList.length >= total" class="foot-tip">没有更多了</view>
		</view>
	</view>
</template>

<script>
	// 历史搜索本地缓存 key（同一设备共用）
	const HISTORY_KEY = 'active_search_history'
	const HISTORY_MAX = 10

	export default {
		data() {
			return {
				keyword: '',
				histories: [],
				searched: false,
				resultList: [],
				page: 1,
				total: 0,
				loading: false,
				hotActive: [],
			};
		},
		onLoad(options) {
			this.histories = uni.getStorageSync(HISTORY_KEY) || []
			// 预加载热门活动（无论是否直接带关键词进入，返回空态都能展示推荐）
			this.loadHotActive()
			// 兼容外部带关键词进入（如从活动详情回跳等场景）
			if (options && options.keyword) {
				this.keyword = options.keyword
				this.doSearch()
			}
		},
		onReachBottom() {
			if (!this.searched || this.loading) return
			if (this.resultList.length >= this.total) return
			this.page++
			this.searchActivities(this.page, false)
		},
		methods: {
			// 输入框内容变化：清空关键词时回到默认态
			onKeywordChange(value) {
				if (value === '' && this.searched) {
					this.searched = false
				}
			},
			// 点击搜索框清除按钮
			handleClear() {
				this.searched = false
			},
			// 执行搜索（键盘搜索键 / 搜索按钮）
			doSearch() {
				const kw = (this.keyword || '').trim()
				if (!kw) return this.$u.toast('请输入搜索关键词')
				this.addHistory(kw)
				// 每次搜索递增请求序列号，丢弃旧请求结果，防止连搜串页
				this._searchSeq = (this._searchSeq || 0) + 1
				this.page = 1
				this.total = 0
				this.resultList = []
				this.searched = true
				this.searchActivities(1, true)
			},
			// 点击历史关键词直接搜索
			searchByHistory(h) {
				this.keyword = h
				this.doSearch()
			},
			// 拉取搜索结果（seq 为请求序列号，replace 是否覆盖第一页）
			searchActivities(page, replace) {
				if (replace === undefined) replace = false
				const seq = this._searchSeq || 0
				this.loading = true
				this.$u.api.get_new_active_list({
					page: page,
					page_size: 10,
					keyword: (this.keyword || '').trim()
				}).then(res => {
					if (seq !== this._searchSeq) return
					let data = res.data || {}
					let list = data.data || []
					if (replace) {
						this.resultList = list
					} else {
						this.resultList.push(...list)
					}
					this.total = data.total || this.resultList.length
				}).catch(err => {
					if (seq !== this._searchSeq) return
					this.$u.toast(err.msg)
				}).finally(() => {
					if (seq === this._searchSeq) this.loading = false
				})
			},
			// 默认态加载热门活动（推荐活动，最多取 6 条）
			loadHotActive() {
				this.$u.api.get_new_active_list({
					page: 1,
					page_size: 6,
					is_recommendation: 1
				}).then(res => {
					let data = res.data || {}
					this.hotActive = data.data || []
				}).catch(() => {})
			},
			// 记录历史（去重置顶 + 最多保留 HISTORY_MAX 条）
			addHistory(kw) {
				let list = this.histories.filter(item => item !== kw)
				list.unshift(kw)
				list = list.slice(0, HISTORY_MAX)
				this.histories = list
				uni.setStorageSync(HISTORY_KEY, list)
			},
			// 删除单条历史
			removeHistory(index) {
				this.histories.splice(index, 1)
				uni.setStorageSync(HISTORY_KEY, this.histories)
			},
			// 一键清空历史
			clearHistories() {
				this.histories = []
				uni.setStorageSync(HISTORY_KEY, [])
			},
			// 空结果返回默认态
			backToHome() {
				this.searched = false
			},
			// 跳转活动详情
			gotoDetail(item) {
				this.$u.route(`/pages/low_carbon/lsxf/details-new?id=${item.id}`)
			},
		}
	}
</script>

<style lang="scss" scoped>
	.search-page {
		min-height: 100vh;
		background: #F0F2F5;
		box-sizing: border-box;
	}

	.search-navbar {
		flex: 1;
		display: flex;
		align-items: center;
		padding: 0 24rpx 0 8rpx;
	}

	/* 导航栏右侧原生搜索按钮（修复原 u-search action 按钮点击不触发的问题） */
	.navbar-search-btn {
		flex-shrink: 0;
		display: flex;
		align-items: center;
		justify-content: center;
		height: 66rpx;
		padding: 0 30rpx;
		margin-left: 16rpx;
		background: #1877F2;
		border-radius: 33rpx;
		font-size: 26rpx;
		color: #ffffff;
		line-height: 1;
	}

	.navbar-search-btn-hover {
		opacity: 0.8;
	}

	.search-home {
		padding: 8rpx 24rpx 40rpx;
	}

	.section {
		margin-top: 24rpx;
	}

	.section-head {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 8rpx 0 20rpx;
	}

	.section-title {
		font-size: 28rpx;
		font-weight: 600;
		color: #050505;
	}

	.section-sub {
		font-size: 22rpx;
		color: #65676B;
	}

	/* 历史搜索 */
	.clear-btn {
		display: flex;
		align-items: center;
	}

	.clear-txt {
		margin-left: 4rpx;
		font-size: 24rpx;
		color: #65676B;
	}

	.history-list {
		display: flex;
		flex-wrap: wrap;
	}

	.history-item {
		display: flex;
		align-items: center;
		max-width: 100%;
		background: #ffffff;
		border-radius: 30rpx;
		padding: 10rpx 8rpx 10rpx 26rpx;
		margin: 0 16rpx 16rpx 0;
	}

	.history-text {
		max-width: 360rpx;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
		font-size: 26rpx;
		color: #050505;
	}

	.history-del {
		display: flex;
		align-items: center;
		justify-content: center;
		width: 44rpx;
		height: 44rpx;
		margin-left: 6rpx;
	}

	/* 热门活动横向滑动 */
	.hot-scroll {
		width: 100%;
		white-space: nowrap;
	}

	.hot-row {
		display: inline-flex;
	}

	.hot-card {
		width: 290rpx;
		flex-shrink: 0;
		margin-right: 18rpx;
		background: #ffffff;
		border-radius: 18rpx;
		overflow: hidden;
		box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.05);
	}

	.hot-card:last-child {
		margin-right: 0;
	}

	.hot-img {
		position: relative;
		width: 100%;
		height: 190rpx;
		background: #F0F2F5;
	}

	.hot-img ::v-deep .u-image {
		width: 100% !important;
		height: 100% !important;
	}

	.hot-badge {
		position: absolute;
		left: 12rpx;
		bottom: 12rpx;
		padding: 3rpx 12rpx;
		font-size: 20rpx;
		color: #ffffff;
		background: rgba(0, 0, 0, 0.45);
		border-radius: 16rpx;
	}

	.hot-body {
		padding: 14rpx 18rpx 18rpx;
	}

	.hot-name {
		font-size: 26rpx;
		font-weight: 600;
		color: #050505;
	}

	.hot-meta {
		margin-top: 6rpx;
	}

	.hot-addr {
		display: inline-block;
		font-size: 22rpx;
		color: #65676B;
	}

	/* 默认态引导 */
	.first-tip {
		padding-top: 140rpx;
		text-align: center;
	}

	.tip-title {
		margin-top: 24rpx;
		font-size: 30rpx;
		font-weight: 600;
		color: #050505;
	}

	.tip-sub {
		margin-top: 10rpx;
		font-size: 24rpx;
		color: #65676B;
	}

	/* ===== 结果卡片（与活动页中间标签卡片保持一致） ===== */
	.result-wrap {
		padding-bottom: 40rpx;
	}

	.result-count {
		padding: 20rpx 24rpx 4rpx;
		font-size: 24rpx;
		color: #65676B;
	}

	.act-card {
			position: relative;
			background: #fff;
			border-radius: 16rpx;
			margin: 20rpx 24rpx 0;
			overflow: hidden;
			box-shadow: 0 1rpx 4rpx rgba(0, 0, 0, 0.08);
			border: 1rpx solid #E4E6EB;
		}

	.act-card:active {
		opacity: 0.85;
	}

	.act-card > .u-image {
		display: block;
		width: 100% !important;
		height: 360rpx !important;
	}

	.card-participate {
		position: absolute;
		left: 16rpx;
		bottom: 16rpx;
		z-index: 2;
		display: inline-flex;
		align-items: center;
		height: 40rpx;
		padding: 0 14rpx;
		background: rgba(0, 0, 0, 0.5);
		border-radius: 20rpx;
	}

	.card-participate .avatar-list {
		display: flex;
	}

	.card-participate .avatar-img {
		width: 28rpx;
		height: 28rpx;
		border-radius: 50%;
		margin-left: -8rpx;
		border: 2rpx solid #fff;
		background: #F0F2F5;
	}

	.card-participate .avatar-img:first-child {
		margin-left: 0;
	}

	.card-participate .count-text {
		color: #fff;
		font-size: 20rpx;
		margin-left: 8rpx;
	}

	.act-card-info {
		padding: 20rpx 24rpx 24rpx;
	}

	.act-card-info .act-title {
		font-size: 30rpx;
		font-weight: 600;
		color: #050505;
		letter-spacing: -0.3rpx;
	}

	.act-card-info .act-meta {
		display: flex;
		align-items: center;
		margin-top: 12rpx;
		font-size: 22rpx;
		color: #65676B;
	}

	.act-card-info .act-meta .u-icon {
		margin-right: 4rpx;
	}

	.act-card-info .act-meta .meta-text {
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}

	/* 空结果 */
	.empty-box {
		padding: 120rpx 40rpx 40rpx;
		text-align: center;
	}

	.empty-title {
		margin-top: 24rpx;
		font-size: 30rpx;
		font-weight: 600;
		color: #050505;
	}

	.empty-sub {
		margin-top: 10rpx;
		font-size: 24rpx;
		color: #65676B;
	}

	.empty-btn {
		display: inline-block;
		margin-top: 40rpx;
		padding: 0 48rpx;
		height: 68rpx;
		line-height: 68rpx;
		font-size: 26rpx;
		color: #ffffff;
		background: #1877F2;
		border-radius: 34rpx;
	}

	/* 首次搜索中 */
	.loading-first {
		padding: 160rpx 0;
		text-align: center;
	}

	.loading-first .loading-txt {
		margin-top: 20rpx;
		font-size: 26rpx;
		color: #65676B;
	}

	/* 底部加载提示 */
	.foot-tip {
		padding: 30rpx 0 40rpx;
		text-align: center;
		font-size: 24rpx;
		color: #65676B;
	}
</style>
