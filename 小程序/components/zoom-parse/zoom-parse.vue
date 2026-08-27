<template>
	<!-- #ifdef MP-WEIXIN -->
	<view class="zoom-viewport" :data-vleft="viewportLeft" :data-vtop="viewportTop" :prop="zoomMeta" :change:prop="zoom.propObserver" @touchstart="zoom.touchstart" @touchmove="zoom.touchmove"
		@touchend="zoom.touchend" @touchcancel="zoom.touchend">
		<view class="zoom-content">
			<view class="zoom-inner-wrap">
				<u-parse :html="html" :loading="loading" :useIframe="useIframe" :imgMaxWidth="imgMaxWidth" @load="onContentLoad" @ready="onContentLoad" />
				<view class="u-h-200"></view>
			</view>
		</view>
	</view>
	<!-- #endif -->
	<!-- #ifndef MP-WEIXIN -->
	<view class="zoom-fallback">
		<u-parse :html="html" :loading="loading" :useIframe="useIframe" :imgMaxWidth="imgMaxWidth" />
		<view class="u-h-200"></view>
	</view>
	<!-- #endif -->
</template>

<script>
	export default {
		name: 'zoom-parse',
		props: {
			html: {
				type: String,
				default: ''
			},
			loading: {
				type: [Boolean, String],
				default: false
			},
			useIframe: {
				type: [Boolean, String],
				default: 'false'
			},
			imgMaxWidth: {
				type: String,
				default: '100%'
			},
			defaultScale: {
				type: Number,
				default: 0.5
			}
		},
		data() {
			return {
				contentHeight: 0,
				contentWidth: 0,
				viewportWidth: 375,
				viewportHeight: 600,
				viewportLeft: 0,
				viewportTop: 0,
				resetKey: 0,
				userZoomed: false
			};
		},
		computed: {
			zoomMeta() {
				return {
					contentHeight: this.contentHeight,
					contentWidth: this.contentWidth,
					viewportWidth: this.viewportWidth,
					viewportHeight: this.viewportHeight,
					viewportLeft: this.viewportLeft,
					viewportTop: this.viewportTop,
					defaultScale: this.defaultScale,
					reset: this.resetKey
				};
			}
		},
		watch: {
			html() {
				this.userZoomed = false;
				this.resetKey++;
				this.$nextTick(() => {
					setTimeout(() => this.measureContent(true), 300);
				});
			}
		},
		mounted() {
			this.measureViewport();
		},
		methods: {
			measureViewport() {
				const query = uni.createSelectorQuery().in(this);
				query.select('.zoom-viewport').boundingClientRect((rect) => {
					if (rect && rect.width && rect.height) {
						this.viewportWidth = rect.width;
						this.viewportHeight = rect.height;
						this.viewportLeft = rect.left;
						this.viewportTop = rect.top;
					}
				}).exec();
			},
			measureContent(force = false) {
				if (this.userZoomed && !force) return;
				const query = uni.createSelectorQuery().in(this);
				query.select('.zoom-inner-wrap').boundingClientRect((rect) => {
					if (!rect || !rect.height) return;
					// 还原为未缩放时的布局尺寸（测量时处于 defaultScale）
					const scale = this.defaultScale || 1;
					const layoutH = rect.height / scale;
					const layoutW = Math.max(rect.width / scale, this.viewportWidth);
					// 放大后重复测量会得到偏大的值，需过滤
					if (this.contentHeight && layoutH > this.contentHeight * 1.2 && this.userZoomed) return;
					this.contentHeight = layoutH;
					this.contentWidth = layoutW;
				}).exec();
				this.measureViewport();
			},
			onContentLoad() {
				this.$nextTick(() => {
					setTimeout(() => this.measureContent(!this.contentHeight), 100);
				});
			},
			markUserZoomed() {
				this.userZoomed = true;
			}
		}
	};
</script>

<script src="./zoom-parse.wxs" module="zoom" lang="wxs"></script>

<style lang="scss" scoped>
	.zoom-viewport {
		width: 100%;
		height: 100%;
		overflow: hidden;
		position: relative;
		touch-action: none;
	}

	.zoom-content {
		will-change: transform;
	}

	.zoom-inner-wrap {
		display: inline-block;
		min-width: 100%;
		vertical-align: top;
	}

	.zoom-fallback {
		width: 100%;
	}
</style>