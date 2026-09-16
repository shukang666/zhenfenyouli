<template>
	<view class="container">
		<z-paging ref='paging' v-model='dataList' @query='queryList' :fixed='true'>
			<view class="card-all" v-for="item in dataList" :key="item.id">
				<view class="u-font-26 grey">{{item.create_time|date('yy年mm月dd日 hh时MM分')}}</view>
				<view class="u-m-t-10 u-font-28 u-font-bold" style="color:#333;">公司：{{item.company}}</view>
				<view class="u-flex u-flex-wrap">
					<view class="u-flex u-row-center u-m-t-30" style="width: 33%;" v-for="img in imgList" :key="img.key" @click="previewImage([item[img.key]])" v-if="item[img.key]">
						<view class="u-w-200 u-h-200 u-rela">
							<image :src="item[img.key]" class="u-w-200 u-h-200" mode="aspectFill"></image>
							<view class="u-abso u-abso-l-0 u-abso-b-0 u-font-22 white u-text-center u-p-5 u-w-200 u-line-1" style="background: rgba(0, 0, 0, 0.4);">
								{{img.name}}
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
				imgList: [{
					name: '门头照片',
					key: 'image_one'
				}, {
					name: '宣传品照片',
					key: 'image_two'
				}, {
					name: '投放点位照片',
					key: 'image_three'
				}, {
					name: '管理制度/收运协议照片',
					key: 'image_four'
				}, {
					name: '桶内照片',
					key: 'image_five'
				}]
			};
		},
		methods: {
			previewImage(urls, current = 0) {
				uni.previewImage({
					urls,
					current
				})
			},
			async queryList(page) {
				try {
					let params = {
						page
					}
					let res = await this.$u.api.getMerchantInspectList(params)
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

</style>
