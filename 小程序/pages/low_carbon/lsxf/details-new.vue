<template>
	<view></view>
</template>

<script>
	import { savePendingActiveDetail } from '@/common/scanSignEntry.js'
	// 后台历史活动码 page 仍是 pages/low_carbon/lsxf/details-new。
	// 详情页已迁到分包 pages/activity/lsxf/details-new，微信扫码会直开本路径；
	// 若不注册，会白屏并报 Page has not been registered / wx://not-found。
	export default {
		onLoad(option) {
			let id = option.id || option.scene || ''
			if (id) {
				try {
					id = decodeURIComponent(id)
				} catch (e) {}
			}
			if (!id) {
				uni.switchTab({
					url: '/pages/low_carbon/low_carbon'
				})
				return
			}
			savePendingActiveDetail(id)
			const url = '/pages/activity/lsxf/details-new?id=' + id
			uni.redirectTo({
				url,
				fail() {
					uni.reLaunch({
						url
					})
				}
			})
		}
	}
</script>
