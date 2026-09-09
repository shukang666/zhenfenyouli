<template>
	<view>
		<u-image width="100%" height="204" src="https://oss.ausite.cn/xcxkf052/admin/20250509/9a136b34b098cd4fe1b4d002372a6f04.png?watermark"></u-image>
		<view class="u-p-30">
			<view class="u-m-b-30" v-for="(item,index) in 3" :key="index">
				<view class="u-font-bold">
					儿童检测
				</view>
				<view class="u-m-t-30 u-type-primary-light-bg u-flex u-row-between u-p-30 u-radius-10">
					<view class="">
						<view class="u-type-primary">
							身高体重指数(BMI)计算
						</view>
						<view class="u-font-22 u-m-t-20">
							20题
						</view>
						<view class="u-m-t-20">
							<text class="u-font-32 u-font-bold">145</text>
							<text class="u-font-22">人测过</text>
						</view>
					</view>
					<view class="" v-if="setting.app_test != 1">
						<u-button type="primary" size="mini" shape="circle" @click="$u.route('/pages/sxjcgj/details?id='+item.id)">立即测评</u-button>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {

			};
		}
	}
</script>

<style lang="scss">
	page {
		background: #fff;
	}
</style>