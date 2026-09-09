<template>
	<view class="container">
		<view class="u-m-t-30">
			<u-divider color="#FF843D" bg-color="rgba(0,0,0,0)" font-size="28">门头照片</u-divider>
			<view class="u-m-lr-30 u-p-t-30">
				<u-upload :custom-btn="true" width="690" height="372" :action="uploadUrl" @on-success="uploadImages($event,'image_one',true)" :fileList="image_one"
					@on-remove="removeImages($event,'image_one',true)" max-count="1">

					<view slot="addBtn" class="u-type-white-bg u-w-690 u-h-372 u-text-center " style="padding: 80rpx 0;">
						<image src="/static/images/upload-img.png" class="u-w-138 u-h-138" mode="aspectFill"> </image>
						<view class="u-font-28 u-font-bold u-m-t-30">点击上传照片</view>
					</view>
				</u-upload>
			</view>
		</view>
		<view class="u-m-t-30">
			<u-divider color="#FF843D" bg-color="rgba(0,0,0,0)" font-size="28">宣传品照片</u-divider>
			<view class="u-m-lr-30 u-p-t-30">
				<u-upload :custom-btn="true" width="690" height="372" :action="uploadUrl" @on-success="uploadImages($event,'image_two',true)" :fileList="image_two"
					@on-remove="removeImages($event,'image_two',true)" max-count="1">

					<view slot="addBtn" class="u-type-white-bg u-w-690 u-h-372 u-text-center " style="padding: 80rpx 0;">
						<image src="/static/images/upload-img.png" class="u-w-138 u-h-138" mode="aspectFill"> </image>
						<view class="u-font-28 u-font-bold u-m-t-30">点击上传照片</view>
					</view>
				</u-upload>
			</view>
		</view>
		<view class="u-m-t-30">
			<u-divider color="#FF843D" bg-color="rgba(0,0,0,0)" font-size="28">投放点位照片</u-divider>
			<view class="u-m-lr-30 u-p-t-30">
				<u-upload :custom-btn="true" width="690" height="372" :action="uploadUrl" @on-success="uploadImages($event,'image_three',true)" :fileList="image_three"
					@on-remove="removeImages($event,'image_three',true)" max-count="1">

					<view slot="addBtn" class="u-type-white-bg u-w-690 u-h-372 u-text-center " style="padding: 80rpx 0;">
						<image src="/static/images/upload-img.png" class="u-w-138 u-h-138" mode="aspectFill"> </image>
						<view class="u-font-28 u-font-bold u-m-t-30">点击上传照片</view>
					</view>
				</u-upload>
			</view>
		</view>
		<view class="u-m-t-30">
			<u-divider color="#FF843D" bg-color="rgba(0,0,0,0)" font-size="28">管理制度/收运协议照片</u-divider>
			<view class="u-m-lr-30 u-p-t-30">
				<u-upload :custom-btn="true" width="690" height="372" :action="uploadUrl" @on-success="uploadImages($event,'image_four',true)" :fileList="image_four"
					@on-remove="removeImages($event,'image_four',true)" max-count="1">

					<view slot="addBtn" class="u-type-white-bg u-w-690 u-h-372 u-text-center " style="padding: 80rpx 0;">
						<image src="/static/images/upload-img.png" class="u-w-138 u-h-138" mode="aspectFill"> </image>
						<view class="u-font-28 u-font-bold u-m-t-30">点击上传照片</view>
					</view>
				</u-upload>
			</view>
		</view>
		<view class="u-m-t-30">
			<u-divider color="#FF843D" bg-color="rgba(0,0,0,0)" font-size="28">桶内照片</u-divider>
			<view class="u-m-lr-30 u-p-t-30">
				<u-upload :custom-btn="true" width="690" height="372" :action="uploadUrl" @on-success="uploadImages($event,'image_five',true)" :fileList="image_five"
					@on-remove="removeImages($event,'image_five',true)" max-count="1">

					<view slot="addBtn" class="u-type-white-bg u-w-690 u-h-372 u-text-center " style="padding: 80rpx 0;">
						<image src="/static/images/upload-img.png" class="u-w-138 u-h-138" mode="aspectFill"> </image>
						<view class="u-font-28 u-font-bold u-m-t-30">点击上传照片</view>
					</view>
				</u-upload>
			</view>
		</view>
		<view class="submit-btn u-p-b ">
			<u-button type="success" hover-class="none" @click="add_handle_shop" shape="square">确认提交</u-button>
			<view class="u-text-center u-type-success u-p-30" @click="$u.route('/pages/zzhd/shop-sign-history')">
				打卡记录
			</view>
		</view>
	</view>
</template>

<script>
	import {
		uploadUrl
	} from '@/common/settings';
	export default {
		data() {
			return {
				uploadUrl,
				form: {
					image_one: "",
					image_two: "",
					image_three: "",
					image_four: "",
					image_five: "",
				},
				image_one: [],
				image_two: [],
				image_three: [],
				image_four: [],
				image_five: [],
			};
		},
		methods: {
			// 商家打卡
			async add_handle_shop() {
				try {
					let params = {
						...this.form
					}
					let res = await this.$u.api.add_handle_shop(params)
					this.$u.toast(res.msg)

				} catch (e) {
					console.log(e);
					this.$u.toast(e.msg)
				}
			},
			// 删除图
			removeImages(e, key, isOne) {
				if (isOne) {
					this.form[key] = ''
				} else {
					this.form[key].splice(e, 1)
				}
			},
			// 上传图
			uploadImages(e, key, isOne) {
				if (isOne) {
					this.form[key] = e.data.filepath
				} else {
					this.form[key].push(e.data.filepath)
				}
			},
		}
	}
</script>

<style lang="scss" scoped>
	.container {
		padding-bottom: 250rpx;

	}
</style>