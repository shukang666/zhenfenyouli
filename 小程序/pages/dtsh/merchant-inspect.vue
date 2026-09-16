<template>
	<view class="container" style="padding-top: calc(var(--status-bar-height) + env(safe-area-inset-top));">
		<!-- 商户共治导航栏 -->
		<view class="mi-navbar">
			<view class="mi-nav-back" @click="navigateBack">
				<u-icon name="arrow-left" size="44" color="#050505"></u-icon>
			</view>
			<text class="mi-nav-title">商户共治</text>
			<view class="mi-nav-placeholder"></view>
		</view>
		<view class="u-p-30">
			<view class="u-m-t-20 u-type-white-bg u-radius-10 u-p-lr-30">
				<u-form :model="form" ref="uForm" :error-type="['toast']">
					<u-form-item label="公司" prop="company" labelWidth="140">
						<u-input v-model="form.company" placeholder="请输入公司名称" />
					</u-form-item>
				</u-form>
			</view>
		</view>

		<view class="u-m-t-30">
			<u-divider color="#FF843D" bg-color="rgba(0,0,0,0)" font-size="28">门头照片</u-divider>
			<view class="u-m-lr-30 u-p-t-30">
				<u-upload :custom-btn="true" width="690" height="372" :action="uploadUrl" @on-success="uploadImages($event,'image_one',true)" :fileList="image_one"
					@on-remove="removeImages($event,'image_one',true)" max-count="1">
					<view slot="addBtn" class="u-type-white-bg u-w-690 u-h-372 u-text-center " style="padding: 80rpx 0;">
						<image src="https://admin.circular-economy.cn/static/images/upload-img.png" class="u-w-138 u-h-138" mode="aspectFill"> </image>
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
						<image src="https://admin.circular-economy.cn/static/images/upload-img.png" class="u-w-138 u-h-138" mode="aspectFill"> </image>
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
						<image src="https://admin.circular-economy.cn/static/images/upload-img.png" class="u-w-138 u-h-138" mode="aspectFill"> </image>
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
						<image src="https://admin.circular-economy.cn/static/images/upload-img.png" class="u-w-138 u-h-138" mode="aspectFill"> </image>
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
						<image src="https://admin.circular-economy.cn/static/images/upload-img.png" class="u-w-138 u-h-138" mode="aspectFill"> </image>
						<view class="u-font-28 u-font-bold u-m-t-30">点击上传照片</view>
					</view>
				</u-upload>
			</view>
		</view>
		<view class="submit-btn u-p-b ">
			<u-button type="success" hover-class="none" @click="submit" shape="square">确认提交</u-button>
			<view class="u-text-center u-type-success u-p-30" @click="$u.route('/pages/dtsh/merchant-inspect-history')">
				提交记录
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
					company: '',
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
		navigateBack() {
			uni.navigateBack();
		},
		// 商户共治提交
			async submit() {
				if (!this.form.company) {
					this.$u.toast('请输入公司名称');
					return;
				}
				try {
					let params = {
						...this.form
					}
					let res = await this.$u.api.postMerchantInspect(params)
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

	.mi-navbar {
		display: flex;
		align-items: center;
		justify-content: space-between;
		position: sticky;
		top: 0;
		z-index: 30;
		background: #fff;
		border-bottom: 1rpx solid #E4E6EB;
		padding: 0 16rpx;
		height: 88rpx;
	}
	.mi-nav-back {
		width: 80rpx;
		display: flex;
		align-items: center;
		justify-content: center;
	}
	.mi-nav-title {
		font-size: 32rpx;
		font-weight: 600;
		color: #050505;
		flex: 1;
		text-align: center;
	}
	.mi-nav-placeholder {
		width: 80rpx;
	}
</style>

<style lang="scss" scoped>
	.container {
		padding-bottom: 250rpx;

	}
</style>
