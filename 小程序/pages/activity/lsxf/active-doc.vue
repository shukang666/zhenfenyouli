<template>
	<view class="page">
		<view class="card">
			<u-parse v-if="html" :html="html"></u-parse>
			<text v-else class="empty">暂无内容</text>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				activeId: 0,
				field: '',
				html: ''
			}
		},
		onLoad(query) {
			this.activeId = parseInt(query.active_id, 10) || 0
			this.field = String(query.field || '').trim()
			const title = query.title ? decodeURIComponent(query.title) : '活动说明'
			uni.setNavigationBarTitle({ title })
			if (!this.activeId || !this.field) {
				this.$u.toast('参数错误')
				return
			}
			this.loadDoc()
		},
		methods: {
			loadDoc() {
				this.$u.api.get_new_active_detail({ active_id: this.activeId }).then(res => {
					const data = res.data || {}
					const allowed = ['flow', 'gain', 'content']
					if (allowed.indexOf(this.field) < 0) {
						this.html = ''
						return
					}
					this.html = data[this.field] || ''
				}).catch(err => {
					this.$u.toast(err.msg || '加载失败')
				})
			}
		}
	}
</script>

<style lang="scss" scoped>
	.page {
		min-height: 100vh;
		background: #eef4f0;
		padding: 24rpx;
		box-sizing: border-box;
	}
	.card {
		background: #fff;
		border-radius: 20rpx;
		padding: 28rpx;
		line-height: 1.7;
		color: #1b4332;
	}
	.empty {
		color: #74c69d;
		font-size: 28rpx;
	}
</style>
