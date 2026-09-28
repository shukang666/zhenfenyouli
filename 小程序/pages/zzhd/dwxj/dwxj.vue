<template>

	<view class="dwxj-page">

		<view class="dwxj-hero">

			<text class="dwxj-hero-title">{{ pageTitle }}</text>

			<text class="dwxj-hero-sub">{{ pageSubTitle }}</text>

		</view>



		<view class="dwxj-search-wrap">

			<u-search

				placeholder="搜索小区、点位或内容"

				v-model="keyword"

				:show-action="false"

				bg-color="#f5f7f6"

				shape="round"

				@clear="search"

				@search="search"

			></u-search>

		</view>



		<view v-if="!setting.app_test" class="dwxj-list">

			<view

				class="dwxj-card"

				v-for="(item, index) in forum_list"

				:key="index"

				@click="$u.route('/pages/zzhd/dwxj/details?id=' + item.id + '&index=' + index)"

			>

				<view class="dwxj-card-head u-flex u-row-between u-col-center">

					<view class="u-flex u-col-center">

						<u-avatar :src="item.avatar" size="88"></u-avatar>

						<view class="u-m-l-16">

							<view class="dwxj-nickname">{{ item.nickname }}</view>

							<view class="dwxj-time">{{ item.create_time | date('yy年mm月dd日 hh:MM') }}</view>

						</view>

					</view>

					<view v-if="item.status == 1" @click.stop>

						<u-button

							type="success"

							shape="circle"

							size="mini"

							:custom-style="{ background: 'linear-gradient(141deg, #A4DB88, #18AF38)' }"

							@click="forum_id = item.id; popup_show = true"

						>立即处理</u-button>

					</view>

					<view v-if="item.status == 2">

						<u-image width="88" height="88" src="https://admin.circular-economy.cn/upload/xcxkf052/admin/20250515/79bccb4b03e46c6019872696dce5ca29.png"></u-image>

					</view>

				</view>



				<view class="dwxj-tags u-flex u-flex-wrap">

					<text class="dwxj-tag" v-if="item.community_name">{{ item.community_name }}</text>

					<text class="dwxj-tag dwxj-tag--cate" v-if="item.cate_name">{{ item.cate_name }}</text>

				</view>



				<view class="dwxj-content" v-if="item.content">{{ item.content }}</view>



				<text class="dwxj-proof-kicker" v-if="hasInspectPhotos(item)">现场留证</text>
				<view class="dwxj-photo-row u-flex u-flex-wrap" v-if="hasInspectPhotos(item)">

					<view

						class="dwxj-photo-cell"

						v-for="img in imgList"

						:key="img.key"

						v-show="item[img.key]"

						@click.stop="previewImage([item[img.key]])"

					>

						<image :src="item[img.key]" class="dwxj-photo-img" mode="aspectFill"></image>

						<text class="dwxj-photo-label">{{ img.name }}</text>

					</view>

				</view>



				<view class="dwxj-handle" v-if="item.handle_info">

					<view class="u-flex u-row-between u-col-center">

						<view class="u-flex u-col-center">

							<u-avatar :src="item.handle_info.avatar" size="40"></u-avatar>

							<text class="u-m-l-10 u-font-26">{{ item.handle_info.nickname }}</text>

						</view>

						<text class="dwxj-time">{{ item.handle_info.create_time | date('yy年mm月dd日 hh:MM') }}</text>

					</view>

					<view class="u-m-t-12 u-font-26">{{ item.handle_info.content }}</view>

					<view class="u-m-t-16">

						<cl-upload

							v-model="item.handle_info.image"

							:max="item.handle_info.image ? item.handle_info.image.length : ''"

							:remove="false"

							:isPreviewImage="false"

						></cl-upload>

					</view>

				</view>



				<view class="dwxj-foot u-flex u-row-between u-col-center">

					<text class="dwxj-view-num">{{ item.view_num }} 人围观</text>

					<view class="u-flex u-col-center">

						<u-icon

							:name="!item.is_like ? 'thumb-up' : 'thumb-up-fill'"

							size="34"

							:color="!item.is_like ? '#909399' : '#3974f2'"

							:label="item.like_total"

							label-color="#909399"

						></u-icon>

						<view class="u-m-l-24">

							<u-icon name="chat" :label="item.comment_total" size="34" color="#909399" label-color="#909399"></u-icon>

						</view>

						<view class="u-m-l-24">

							<button class="u-reset-button" open-type="share" @click.stop="share_brn(item)">

								<u-icon name="zhuanfa" label="分享" size="32" color="#909399" label-color="#909399"></u-icon>

							</button>

						</view>

					</view>

				</view>

			</view>

		</view>



		<view v-if="forum_list.length == 0" class="dwxj-empty">

			<u-empty text="完成巡检并提交记录后会显示在这里" mode="list"></u-empty>

		</view>



		<u-gap height="160"></u-gap>



		<view class="dwxj-bottom-bar">

			<view class="dwxj-start-btn" hover-class="dwxj-start-btn--press" @click="goAdd">

				<u-icon name="plus-circle-fill" color="#fff" size="40" class="u-m-r-12"></u-icon>

				<text>{{ primaryActionText }}</text>

			</view>

		</view>



		<u-popup v-model="popup_show" mode="bottom" closeable border-radius="20">

			<view class="u-p-30">

				<view class="u-font-32 u-font-bold u-text-center">上传作品</view>

				<view class="u-m-t-30 u-type-info-light-bg u-p-30 u-radius-10">

					<u-input v-model="content" type="textarea" height="200" maxlength="-1" placeholder="请输入作品介绍~" :clearable="false" />

					<view class="u-m-t-20">

						<cl-upload v-model="image" :action="uploadUrl" @onSuccess="onSuccess" fileType="image">

							<template v-slot:addImg>

								<u-image width="138" height="138" src="https://admin.circular-economy.cn/upload/xcxkf052/admin/20250512/9ff646dc41e88127a05c7f0ebde61896.png"></u-image>

							</template>

						</cl-upload>

					</view>

				</view>

				<view class="u-p-t-30">

					<u-button

						type="primary"

						hover-class="none"

						:customStyle="{ background: 'linear-gradient(141deg, #A4DB88 0%, #18AF38 100%)' }"

						@click="add_handle"

					>确认上传</u-button>

				</view>

			</view>

		</u-popup>

	</view>

</template>



<script>

	import { uploadUrl } from '@/common/settings'

	export default {

		data() {

			return {

				uploadUrl,

				keyword: '',

				popup_show: false,

				image: [],

				content: '',

				forum_list: [],

				page: 1,

				total: 0,

				type: 1,

				list: [{

					name: '未处理',

					status: 1,

				}, {

					name: '已处理',

					status: 2,

				}],

				current: 0,

				forum_id: 0,

				info: {},

				imgList: [{

					name: '留证·点位',

					key: 'image_one',

				}, {

					name: '留证·指引',

					key: 'image_two',

				}, {

					name: '留证·桶位',

					key: 'image_three',

				}],

			}

		},

		computed: {

			pageTitle() {

				return this.type == 4 ? '桶边督导' : '点位巡检'

			},

			pageSubTitle() {

				return this.type == 4
					? '桶边督导是现场履职；此处查看记录，照片是工作留证'
					: '巡检是到现场看桶、做引导；此处查看记录，照片是工作留证'

			},

			primaryActionText() {

				return this.type == 4 ? '开始桶边督导' : '开始巡检'

			},

		},

		onLoad(option) {

			this.type = option.type

			uni.setNavigationBarTitle({

				title: this.type == 4 ? '桶边督导' : '督导巡检',

			})

			this.get_forum_list()

		},

		methods: {

			goAdd() {

				this.$u.route('/pages/zzhd/dwxj/add?type=' + this.type)

			},

			hasInspectPhotos(item) {

				return this.imgList.some(img => item[img.key])

			},

			previewImage(urls, current = 0) {

				uni.previewImage({ urls, current })

			},

			share_brn(item) {

				this.info = item

			},

			add_handle() {

				if (!this.content) return this.$u.toast('请先添加介绍')

				this.$u.api.add_handle({

					forum_id: this.forum_id,

					image: this.image,

					content: this.content,

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

			onSuccess(reslut) {

				this.image.push(reslut.data.url)

			},

			change(index) {

				this.current = index

				this.search()

			},

			search() {

				this.page = 1

				this.forum_list = []

				this.get_forum_list()

			},

			get_forum_list() {

				this.$u.api.get_forum_list({

					keyword: this.keyword,

					page: this.page,

					type: 3,

					is_my: 1,

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

		onShareAppMessage() {

			return {

				title: this.info.title,

				imageUrl: this.info.image && this.info.image.length ? this.info.image[0] : '',

				path: '/pages/zzhd/dwxj/details?id=' + this.info.id,

			}

		},

		onShareTimeline() {

			return {

				title: this.info.title,

				imageUrl: (this.info.image && this.info.image[0]) || '',

				path: '/pages/zzhd/yxsj/details?id=' + this.info.id,

			}

		},

	}

</script>



<style lang="scss" scoped>

.dwxj-page {

	min-height: 100vh;

	background: #f0f4f2;

	padding-bottom: env(safe-area-inset-bottom);

}

.dwxj-hero {

	padding: 36rpx 32rpx 28rpx;

	background: linear-gradient(165deg, #2fb74c 0%, #1a9a3e 55%, #eff9f1 100%);

}

.dwxj-hero-title {

	display: block;

	font-size: 40rpx;

	font-weight: 800;

	color: #fff;

	letter-spacing: 1rpx;

}

.dwxj-hero-sub {

	display: block;

	margin-top: 12rpx;

	font-size: 26rpx;

	color: rgba(255, 255, 255, 0.92);

	line-height: 1.5;

	max-width: 620rpx;

}

.dwxj-search-wrap {

	margin: -20rpx 24rpx 0;

	padding: 16rpx 20rpx;

	background: #fff;

	border-radius: 20rpx;

	box-shadow: 0 8rpx 32rpx rgba(20, 40, 28, 0.08);

	position: relative;

	z-index: 2;

}

.dwxj-list {

	padding: 24rpx;

}

.dwxj-card {

	background: #fff;

	border-radius: 20rpx;

	padding: 28rpx;

	margin-bottom: 24rpx;

	box-shadow: 0 6rpx 24rpx rgba(20, 40, 28, 0.05);

}

.dwxj-nickname {

	font-size: 30rpx;

	font-weight: 700;

	color: #243028;

}

.dwxj-time {

	font-size: 22rpx;

	color: #9aa59d;

	margin-top: 6rpx;

}

.dwxj-tags {

	margin-top: 20rpx;

	gap: 12rpx;

}

.dwxj-tag {

	font-size: 22rpx;

	color: #18af38;

	background: #e8f8ec;

	padding: 6rpx 16rpx;

	border-radius: 8rpx;

}

.dwxj-tag--cate {

	color: #156a90;

	background: #e8f2f8;

}

.dwxj-content {

	margin-top: 16rpx;

	font-size: 28rpx;

	color: #3d4840;

	line-height: 1.55;

}

.dwxj-proof-kicker {

	display: block;

	margin-top: 16rpx;

	font-size: 22rpx;

	color: #9aa59d;

}

.dwxj-photo-row {

	margin-top: 12rpx;

	gap: 16rpx;

}

.dwxj-photo-cell {

	width: calc(33.33% - 12rpx);

	position: relative;

	border-radius: 12rpx;

	overflow: hidden;

}

.dwxj-photo-img {

	width: 100%;

	height: 200rpx;

	display: block;

}

.dwxj-photo-label {

	position: absolute;

	left: 0;

	right: 0;

	bottom: 0;

	font-size: 20rpx;

	color: #fff;

	text-align: center;

	padding: 8rpx 4rpx;

	background: rgba(0, 0, 0, 0.45);

}

.dwxj-handle {

	margin-top: 20rpx;

	padding: 20rpx;

	background: #f7faf8;

	border-radius: 12rpx;

}

.dwxj-foot {

	margin-top: 24rpx;

	padding-top: 20rpx;

	border-top: 1rpx solid #f0f2f0;

}

.dwxj-view-num {

	font-size: 24rpx;

	color: #9aa59d;

}

.dwxj-empty {

	padding: 80rpx 30rpx;

}

.dwxj-bottom-bar {

	position: fixed;

	left: 0;

	right: 0;

	bottom: 0;

	padding: 20rpx 32rpx calc(20rpx + env(safe-area-inset-bottom));

	background: linear-gradient(180deg, rgba(240, 244, 242, 0) 0%, #f0f4f2 30%, #f0f4f2 100%);

	z-index: 10;

}

.dwxj-start-btn {

	height: 96rpx;

	border-radius: 999rpx;

	background: linear-gradient(141deg, #a4db88 0%, #18af38 100%);

	box-shadow: 0 12rpx 32rpx rgba(24, 175, 56, 0.35);

	display: flex;

	align-items: center;

	justify-content: center;

	font-size: 32rpx;

	font-weight: 700;

	color: #fff;

}

.dwxj-start-btn--press {

	opacity: 0.92;

	transform: scale(0.98);

}

</style>


