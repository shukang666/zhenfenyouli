<template>
	<view class="u-p-30">
		<u-navbar title-color="#fff" title="分类知识" :is-back="true" back-icon-color="#fff" background="transparent" :borderBottom="false" :custom-back="customBack">
		</u-navbar>
		<view class="u-abso u-h-552" style="z-index: -1;top: 0;left: 0;right: 0;background: linear-gradient(to bottom,#2fb74c,#EFF9F1);">
		</view>
		<view class="u-p-40 u-type-white-bg u-radius-6 u-m-t-30">
			<view class="u-text-center u-font-32 u-font-bold u-m-b-30">学会一个分类知识</view>
			<view class="u-font-28 u-type-info u-text-center">
				每天学习一个新的分类知识
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {};
		},
		methods: {
			customBack() {
				uni.navigateBack({
					fail() {
						uni.switchTab({
							url: '/pages/index/index'
						})
					}
				})
			}
		}
	}
</script>

<style lang="scss">

</style>
