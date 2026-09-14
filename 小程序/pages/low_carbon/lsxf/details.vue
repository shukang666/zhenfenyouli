<template>
	<view>
		<view class="u-p-30">
			<u-parse :html="active.content"></u-parse>
		</view>

		<u-gap height="120"></u-gap>
		<view class="submit-btn u-p-30 u-flex" v-if="!setting.app_test">
			<view class="u-shrink-no">
				<button class="u-reset-button" open-type="share">
					<u-icon name="zhuanfa" label="分享" label-pos="bottom" color="#1877F2" label-color="#1877F2" label-size="24"></u-icon>
				</button>
			</view>
			<view class="u-flex-1 u-m-lr-30">
				<u-button type="primary" hover-class="none" :customStyle="{background: 'linear-gradient( 141deg, #F0DF73 0%, #FFA320 100%)'}"
					@click="$u.route('/pages/low_carbon/lsxf/list?id='+active.id)">查看作品</u-button>
			</view>
			<view class="u-flex-1" v-if="!setting.app_test">
					<u-button type="primary" hover-class="none" :customStyle="{background: 'linear-gradient( 141deg, #4A9BFF 0%, #1877F2 100%)'}" @click="btn">立即参与</u-button>
				</view>
		</view>
		<!--  -->
		<u-popup v-model="popup_show" mode="bottom" closeable border-radius="20">
			<view class="u-p-30">
				<view class="u-font-32 u-font-bold u-text-center">
					上传作品
				</view>
				<view class="u-m-t-30 u-type-info-light-bg u-p-30 u-radius-10">
					<u-input v-model="content" type="textarea" height="200" maxlength="-1" placeholder="请输入作品介绍~" :clearable="false" />
					<view class="">
						<cl-upload v-model="image" :action="uploadUrl" @onSuccess="onSuccess" fileType="image">
							<template v-slot:addImg>
								<u-image width="138" height="138" src="https://oss.ausite.cn/xcxkf052/admin/20250512/9ff646dc41e88127a05c7f0ebde61896.png?watermark"></u-image>
							</template>
						</cl-upload>
					</view>
				</view>
				<view class="u-p-30">
					<u-button type="primary" hover-class="none" :customStyle="{background: 'linear-gradient( 141deg, #4A9BFF 0%, #1877F2 100%)'}" @click="add_active_user">确认上传</u-button>
				</view>
			</view>
		</u-popup>
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
				popup_show: false,
				image: [],
				content: '',
				active: {}
			};
		},
		onLoad(option) {
			this.get_active_list(option.id)
		},
		methods: {
			// 
			btn() {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				this.popup_show = true
			},
			// 提交
			add_active_user() {
				if (!this.content) return this.$u.toast('请先添加作品介绍')
				if (!this.image.length) return this.$u.toast('请先添加作品照片')
				this.$u.api.add_active_user({
					active_id: this.active.id,
					image: this.image,
					content: this.content
				}).then(res => {
					this.popup_show = false
					this.$u.func.showToast({
						title: res.msg,
						icon: 'success',
					})
				}).catch(err => {
					this.$u.toast(err.msg)
				})
			},
			// 环保活动
			get_active_list(id) {
				this.$u.api.get_active_list({
					id
				}).then(res => {
					this.active = res.data
				})
			},
			// 上传图片
			onSuccess(reslut) {
				this.image.push(reslut.data.url)
			},
		},
		onShareAppMessage(res) {
			return {
				title: this.active.name,
				path: '/pages/low_carbon/lsxf/details?id=' + this.active.id,
				imageUrl: this.active.thumb
			}
		},
		onShareTimeline() {
			return {
				title: this.active.name,
				path: '/pages/low_carbon/lsxf/details?id=' + this.active.id,
				imageUrl: this.active.thumb
			}
		}
	}
</script>

<style lang="scss">

</style>
