<template>
	<view>

		<view class="u-type-white-bg" style="padding: 15rpx 30rpx;">
			<u-search placeholder="搜索信息内容" v-model="keyword" :show-action="false" @clear="search" @search="search"></u-search>
			<!-- 	<view class="">
				<u-tabs :list="list" :is-scroll="false" :current="current" @change="change" active-color="#19be6b"></u-tabs>
			</view> -->
		</view>
		<view class="" v-if="!setting.app_test">
			<view class="u-type-white-bg u-p-30 u-m-b-30" v-for="(item,index) in forum_list" :key="index" @click="$u.route('/pages/zzhd/dwxj/details?id='+item.id+'&index='+index)">
				<view class="u-flex u-row-between">
					<view class="u-flex">
						<u-avatar :src="item.avatar" size="94"></u-avatar>
						<view class="">
							<view class="u-m-l-10 u-font-bold">
								{{item.nickname}}
							</view>
							<view class="u-font-24 u-type-info u-m-t-10">
								{{item.create_time|date('yy年mm月dd日 hh:MM')}}
							</view>
						</view>
					</view>
					<view class="" v-if="item.status==1">
						<u-button type="success" shape="circle" size="mini" @click="forum_id = item.id, popup_show = true">立即处理</u-button>
					</view>
					<view class="" v-if="item.status==2">
						<u-image width="100" height="100" src="https://admin.circular-economy.cn/upload/xcxkf052/admin/20250515/79bccb4b03e46c6019872696dce5ca29.png"></u-image>
					</view>
				</view>
				<view class="u-m-t-20 u-font-bold u-font-26">
					{{item.community_name}}
				</view>
				<view class="u-m-t-20 u-font-bold u-font-26">
					{{item.cate_name}}
				</view>
				<view class="u-m-t-20 u-font-bold u-font-26">
					{{item.content}}
				</view>
				<!-- <view class="u-m-t-20">
					<cl-upload v-model="item.image" :max="item.image?item.image.length:''" :remove="false" :isPreviewImage="false">
					</cl-upload>
				</view> -->
				<view class="u-flex u-flex-wrap">
					<view class="u-flex u-row-center u-m-t-30" style="width: 33%;" v-for="img in imgList" :key="img.key" @click.stop="previewImage([item[img.key]])" v-if="item[img.key]">
						<view class="u-w-200 u-h-200 u-rela">
							<image :src="item[img.key]" class="u-w-200 u-h-200" mode="aspectFill"></image>
							<view class="u-abso u-abso-l-0 u-abso-b-0 u-font-22 white u-text-center u-p-5 u-w-200 u-line-1" style="background: rgba(0, 0, 0, 0.4);">
								{{img.name}}
							</view>
						</view>
					</view>
				</view>

				<view class="u-p-15 u-type-info-light-bg u-m-t-20 u-radius-6" v-if="item.handle_info">
					<view class="u-flex u-row-between">
						<view class="u-flex">
							<u-avatar :src="item.handle_info.avatar" size="40"></u-avatar>
							<view class="u-m-l-10">
								{{item.handle_info.nickname}}
							</view>
						</view>
						<view class="u-font-24 u-type-info u-m-t-10">
							{{item.handle_info.create_time|date('yy年mm月dd日 hh:MM')}}
						</view>
					</view>
					<view class="u-m-t-10 u-font-bold u-font-26">
						{{item.handle_info.content}}
					</view>
					<view class="u-m-t-20">
						<cl-upload v-model="item.handle_info.image" :max="item.handle_info.image?item.handle_info.image.length:''" :remove="false" :isPreviewImage="false">
						</cl-upload>
					</view>
				</view>
				<view class="u-m-t-20 u-flex u-row-between">
					<view class="u-font-24 u-type-info">
						已有{{item.view_num}}人围观
					</view>
					<view class="u-flex">
						<view class="">
							<u-icon :name="!item.is_like?'thumb-up':'thumb-up-fill'" size="36" :color="!item.is_like?'#909399':'#3974f2'" :label="item.like_total" label-color="#909399"></u-icon>
						</view>
						<view class="u-m-l-20">
							<u-icon name="chat" :label="item.comment_total" size="38" color="#909399" label-color="#909399"></u-icon>
						</view>
						<view class="u-m-l-20">
							<button class="u-reset-button" open-type="share" @click.stop="share_brn(item)">
								<u-icon name="zhuanfa" label="分享" size="34" color="#909399" label-color="#909399"></u-icon>
							</button>
						</view>
					</view>
				</view>
			</view>
		</view>
		<!-- <view class="" style="position: fixed;right: 30rpx;bottom: 10%;z-index: 1;"
            @click="$u.route('/pages/zzhd/dwxj/add?type='+type)">
            <u-image width="100" height="100"
                src="https://admin.circular-economy.cn/upload/xcxkf052/admin/20250514/b080e0cca2b8055339bf87e066485ce4.png"></u-image>
        </view> -->
		<view v-if="forum_list.length == 0" class="u-p-30">
			<u-empty text="暂无数据" mode="list"></u-empty>

		</view>
		<u-gap height="120"></u-gap>
		<view class="submit-btn u-p-30">
			<u-button type="primary" hover-class="none" :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
				@click="$u.route('/pages/zzhd/dwxj/add?type='+type)">{{type==4?'添加桶边督导':'添加巡检'}}</u-button>
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
								<u-image width="138" height="138" src="https://admin.circular-economy.cn/upload/xcxkf052/admin/20250512/9ff646dc41e88127a05c7f0ebde61896.png"></u-image>
							</template>
						</cl-upload>
					</view>
				</view>
				<view class="u-p-30">
					<u-button type="primary" hover-class="none" :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}" @click="add_handle">确认上传</u-button>
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
				forum_list: [],
				page: 1,
				total: 0,
				type: 1,
				list: [{
					name: '未处理',
					status: 1
				}, {
					name: '已处理',
					status: 2
				}],
				current: 0,
				forum_id: 0,
				info: {},
				imgList: [{
					name: '点位照片',
					key: 'image_one'
				}, {
					name: '投放指南',
					key: 'image_two'
				}, {
					name: '垃圾桶照片',
					key: 'image_three'
				}]
			};
		},
		onLoad(option) {
			this.type = option.type
			uni.setNavigationBarTitle({
				title: this.type == 3 ? '督导巡检' : '桶边督导'
			})
			this.get_forum_list()
		},
		methods: {
			previewImage(urls, current = 0) {
				uni.previewImage({
					urls,
					current
				})
			},
			// 分享
			share_brn(item) {
				this.info = item
			},
			// 提交
			add_handle() {
				if (!this.content) return this.$u.toast('请先添加介绍')
				this.$u.api.add_handle({
					forum_id: this.forum_id,
					image: this.image,
					content: this.content
				}).then(res => {
					this.popup_show = false
					this.$u.func.showToast({
						title: res.msg,
						icon: 'success',
					})
					this.search()
				}).catch(err => {
					this.$u.toast(err.msg)
				})
			},
			// 上传图片
			onSuccess(reslut) {
				this.image.push(reslut.data.url)
			},
			change(index) {
				this.current = index;
				this.search()
			},
			// 切换分类
			search() {
				this.page = 1
				this.forum_list = []
				this.get_forum_list()
			},
			// 列表
			get_forum_list() {
				this.$u.api.get_forum_list({
					keyword: this.keyword,
					page: this.page,
					type: 3,
					is_my: 1
					// status: this.list[this.current].status
				}).then(res => {
					this.forum_list.push(...res.data.data)
					this.total = res.data.total
				}).catch(err => {
					this.$u.toast(err.msg)
				})
			},
		},
		onReachBottom() {
			if (this.forum_list.length >= this.total) return
			this.page++
			this.get_forum_list()
		},
		onShareAppMessage(res) {
			return {
				title: this.info.title,
				imageUrl: this.info.image.length ? this.info.image[0] : '',
				path: '/pages/zzhd/dwxj/details?id=' + this.info.id
			}
		},
		onShareTimeline() {
			return {
				title: this.info.title,
				imageUrl: this.info.image[0] || '',
				path: '/pages/zzhd/yxsj/details?id=' + this.info.id
			}
		}
	}
</script>

<style lang="scss">

</style>