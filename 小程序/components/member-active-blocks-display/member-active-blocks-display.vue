<template>
	<view class="mab-wrap">
		<view class="mab-card" v-for="(block, idx) in blocks" :key="block.id || idx">
			<view class="mab-head">
				<view class="mab-bar"></view>
				<text class="mab-title">{{ block.label || '未命名' }}</text>
			</view>
			<view class="mab-body" v-if="block.type === 'text'">
				<text class="mab-text-line">{{ displayText(block) }}</text>
			</view>
			<view class="mab-body" v-else-if="block.type === 'textarea'">
				<text class="mab-text-multi">{{ displayText(block) }}</text>
			</view>
			<view class="mab-body" v-else-if="block.type === 'images'">
				<scroll-view scroll-x class="mab-gallery" :show-scrollbar="false" v-if="displayImages(block).length">
					<image
						v-for="(src, i) in displayImages(block)"
						:key="i"
						class="mab-gallery-img"
						:src="src"
						mode="aspectFill"
						@tap="preview(displayImages(block), i)"
					/>
				</scroll-view>
				<scroll-view scroll-x class="mab-gallery" :show-scrollbar="false" v-else-if="block.demoGallery">
					<view class="mab-gallery-img mab-gallery-img--demo" v-for="n in 3" :key="'demo-' + n">
						<text class="mab-demo-label">示例图 {{ n }}</text>
					</view>
				</scroll-view>
				<text class="mab-placeholder" v-else>{{ placeholderImages }}</text>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	name: 'MemberActiveBlocksDisplay',
	props: {
		blocks: { type: Array, default: () => [] },
		placeholderText: { type: String, default: '填写后将显示在这里' },
		placeholderImages: { type: String, default: '上传后将在此横向滑动展示' },
	},
	methods: {
		displayText(block) {
			const v = String(block.value || '').trim()
			return v || this.placeholderText
		},
		displayImages(block) {
			const fromPreview = block.images || []
			const fromApi = block.image_urls || block.imageUrls || block.image_paths || []
			if (fromPreview.length) return fromPreview
			return fromApi.slice()
		},
		preview(urls, index) {
			const list = urls.filter(Boolean)
			if (!list.length) return
			uni.previewImage({ urls: list, current: list[index] || list[0] })
		},
	},
}
</script>

<style lang="scss" scoped>
.mab-wrap {
	display: flex;
	flex-direction: column;
	gap: 20rpx;
}
.mab-card {
	padding: 24rpx 22rpx;
	border-radius: 20rpx;
	background: rgba(255, 255, 255, 0.96);
	box-shadow: 0 8rpx 28rpx rgba(22, 53, 36, 0.06);
}
.mab-head {
	display: flex;
	align-items: center;
	margin-bottom: 16rpx;
}
.mab-bar {
	width: 6rpx;
	height: 28rpx;
	border-radius: 4rpx;
	background: #18af38;
	margin-right: 12rpx;
}
.mab-title {
	font-size: 30rpx;
	font-weight: 600;
	color: #1a2e22;
}
.mab-text-line {
	font-size: 28rpx;
	color: #3d4f44;
	line-height: 1.5;
}
.mab-text-multi {
	font-size: 28rpx;
	color: #3d4f44;
	line-height: 1.65;
	white-space: pre-wrap;
}
.mab-placeholder {
	font-size: 26rpx;
	color: #aab5ad;
}
.mab-gallery {
	white-space: nowrap;
	margin-top: 4rpx;
}
.mab-gallery-img {
	display: inline-block;
	width: 280rpx;
	height: 200rpx;
	border-radius: 14rpx;
	margin-right: 16rpx;
	background: #e8efe9;
}
.mab-gallery-img--demo {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	background: linear-gradient(135deg, #dceee0 0%, #c5dcc9 100%);
	border: 2rpx dashed rgba(24, 175, 56, 0.35);
}
.mab-demo-label {
	font-size: 24rpx;
	color: #5a7a62;
}
</style>
