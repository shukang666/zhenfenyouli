<template>
	<view class="container" v-if="!setting.app_test">
		<view class="u-p-30">
			<u-form :model="form" ref="uForm" :error-type="['toast']">
				<view class="u-type-white-bg u-p-lr-30 u-radius-10">
					<u-form-item label="上报点位" prop="cate_name" labelWidth="160">
						<u-input v-model="form.cate_name" placeholder="请选择" type="select" @click="cate_show = true" />
					</u-form-item>
					<u-form-item label="上报内容" prop="content" labelWidth="160" labelPosition="bottom" :border-bottom="false">
						<u-input v-model="form.content" type="textarea" height="200" maxlength="-1" placeholder="请输入上报内容~" />
					</u-form-item>
				</view>
				<!-- 				<view class="u-m-t-30 u-type-white-bg u-p-lr-30 u-radius-10">
					
					<view class="u-p-tb-30">
						<cl-upload v-model="form.image" :action="uploadUrl" @onSuccess="onSuccess" fileType="image">
							<template v-slot:addImg>
								<u-image width="138" height="138" src="/static/upload.png"></u-image>
							</template>
						</cl-upload>
					</view>
				</view> -->
			</u-form>
		</view>
		<view class="u-m-t-30">
			<u-divider color="#FF843D" bg-color="rgba(0,0,0,0)" font-size="28">点位照片</u-divider>
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
			<u-divider color="#FF843D" bg-color="rgba(0,0,0,0)" font-size="28">投放指南</u-divider>
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
			<u-divider color="#FF843D" bg-color="rgba(0,0,0,0)" font-size="28">垃圾桶照片</u-divider>
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

		<!-- 选择类型 -->
		<u-select v-model="cate_show" label-name="name" value-name="id" :list="cate_list" @confirm="confirm"></u-select>
		<u-mask :show="tipShow">
			<view class="u-w-560 u-radius-10 u-type-white-bg u-abso-center u-p-30" v-if="showType == 1">
				<view class="u-text-center">
					<image src="/static/images/dakachenggong.png" class="u-w-280 u-h-188" mode="aspectFill"></image>
					<view class="u-font-32 u-m-t-30">恭喜您获得 <text class="u-font-40 u-main-c u-font-bold">{{point}}</text> 积分</view>
				</view>
				<view class="u-m-t-30 u-flex u-row-center">
					<u-button :custom-style="{width:'400rpx'}" @click="goBack">我知道了</u-button>
				</view>
			</view>
			<view class="u-w-560 u-radius-10 u-type-white-bg u-abso-center u-p-30" v-if="showType == 2">
				<view class="u-text-center">
					<image src="/static/images/wenxintishi.png" class="u-w-280 u-h-188" mode="aspectFill"></image>
					<view class="u-m-t-30">
						<view class="u-m-b-30">请在倒计时结束后打卡</view>
						<u-count-down :timestamp="remaining_seconds" :show-border="true" font-size="48" height="80" color="#ffffff" border-color="#FF843D" bg-color="#FF843D"
							separator-color="#FF843D"></u-count-down>

					</view>
				</view>
				<view class="u-m-t-30 u-flex u-row-center">
					<u-button :custom-style="{width:'400rpx'}" @click="tipShow = false">我知道了</u-button>
				</view>
			</view>
		</u-mask>

		<view class="submit-btn u-p-b u-type-white-bg">
			<u-button type="success" hover-class="none" @click="submit">确认提交</u-button>
			<view class="u-text-center u-type-success u-p-30" @click="$u.route('/pages/zzhd/dwxj/record?type='+form.type)">
				发布记录
			</view>
		</view>


	</view>
</template>

<script>
	import {
		uploadUrl
	} from '@/common/settings'
	export default {
		data() {
			return {
				uploadUrl,
				form: {
					type: 6,
					cate_id: 0,
					cate_name: '',
					content: '',
					// image: [],
					image_one: '', //点位照片
					image_two: '', //投放指南
					image_three: '', //垃圾桶照片
				},
				image_one: [], //点位照片
				image_two: [], //投放指南
				image_three: [], //垃圾桶照片
				rules: {
					content: [{
						required: true,
						message: '请输入上报内容',
						trigger: ['change', 'blur'],
					}],
					cate_name: [{
						required: true,
						message: '请选择点位',
						trigger: ['change', 'blur'],
					}],
				},
				cate_show: false,
				cate_list: [],
				showType: 1,
				tipShow: false,
				point: 0,
				remaining_seconds: 0,
			};
		},
		onLoad(option) {
			this.form.type = option.type
			this.get_community_cate_list()
		},
		methods: {
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

			// 选择点位
			confirm(e) {
				this.form.cate_id = e[0].value
				this.form.cate_name = e[0].label
			},
			// 点位信息
			get_community_cate_list() {
				this.$u.api.get_community_cate_list().then(res => {
					this.cate_list = res.data
				})
			},
			// 上传图片
			onSuccess(reslut) {
				this.form.image.push(reslut.data.url)
			},
			goBack() {
				this.tipShow = false
				uni.redirectTo({
					url: `/pages/zzhd/dwxj/record?type=${this.form.type}`
				})
			},
			// 提交
			submit() {

				// return
				this.$refs.uForm.validate(valid => {
					if (valid) {
						this.$u.api.add_forum(this.form).then(res => {
							this.point = res.data.point
							this.showType = 1
							this.tipShow = true

							// this.$u.func.showToast({
							// 	title: res.msg,
							// 	icon: 'success',
							// 	back: true
							// })


						}).catch(res => {
							// this.$u.toast(res.msg);
							this.remaining_seconds = res.data.remaining_seconds
							this.showType = 2
							this.tipShow = true

						})
					} else {
						console.log('验证失败');
					}
				});
			},
		},
		// 必须要在onReady生命周期，因为onLoad生命周期组件可能尚未创建完毕
		onReady() {
			this.$refs.uForm.setRules(this.rules);
		}
	}
</script>

<style lang="scss" scoped>
	.container {
		padding-bottom: 250rpx;

	}
</style>