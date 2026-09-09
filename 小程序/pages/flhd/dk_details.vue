<template>
	<view v-if="!setting.app_test">
		<view class="u-p-30 u-type-white-bg">
			<view class="u-flex">
				<u-avatar :src="info.avatar" size="84"></u-avatar>
				<view class="u-m-l-20">
					<view class="u-font-bold u-font-32 ">
						{{info.nickname}}
					</view>
					<view class="u-font-24 u-type-info u-m-t-10">
						{{info.create_time|date('yy-mm-dd hh:MM')}}
					</view>
				</view>
			</view>
			<!-- <view class="u-line-1 u-font-bold u-m-t-20 u-font-26">
                {{info.cate_name}}
            </view> -->
			<view v-html="info.content" class="u-m-t-20 u-font-26">
			</view>
			<view class="u-m-t-20">
				<cl-upload v-model="info.image" :max="info.image?info.image.length:''" :remove="false">
				</cl-upload>
			</view>
			<view class="u-m-t-20 u-flex u-row-between">
				<view class="u-font-24 u-type-info">
					已有{{info.view_num}}人围观
				</view>
				<view class="u-flex">
					<view class="">
						<u-icon :name="!info.is_like?'thumb-up':'thumb-up-fill'" size="36"
							:color="!info.is_like?'#909399':'#19be6b'" label-color="#909399" :label="info.like_total"
							@click="add_like(info.id,index,1)"></u-icon>
					</view>
					<view class="u-m-l-20">
						<u-icon name="chat" :label="info.comment_total" size="38" color="#909399"
							label-color="#909399"></u-icon>
					</view>
					<view class="u-m-l-20">
						<button class="u-reset-button" open-type="share">
							<u-icon name="zhuanfa" label="分享" size="34" color="#909399" label-color="#909399"></u-icon>
						</button>
					</view>
				</view>
			</view>
		</view>
		<u-gap height="30" bg-color="#f7f7f7"></u-gap>
		<!-- 评论 -->
		<view class="u-type-white-bg u-p-30 u-m-t-20">
			<view class="u-font-bold u-font-32">
				全部评论（{{comment_list.length}}）
			</view>
			<view class="u-p-tb-30 u-border-bottom" v-for="(item,index) in comment_list" :key="index">
				<view class="u-flex u-col-top">
					<u-avatar :src="item.add_avatar" size="94"></u-avatar>
					<view class="u-width-100 u-m-l-30">
						<view class="u-flex u-row-between" @click.stop="reply(item)">
							<view class="">
								<view class="u-font-bold u-font-24 u-type-primary">
									{{item.add_nickname}}
								</view>
								<view class="u-font-22 u-type-info u-m-t-20">
									{{item.create_time|date('yy-mm-dd hh:MM')}}
								</view>
							</view>
							<view class="">
								<u-icon :name="!item.is_like?'thumb-up':'thumb-up-fill'" size="36"
									:color="!item.is_like?'':'#19be6b'" :label="item.like_total"
									@click="add_like(item.id,index,2)"></u-icon>
							</view>
						</view>
						<view class="u-m-t-20" @click.stop="reply(item)">
							<text class="u-type-info">评论：</text>
							<text> {{item.content}}</text>
						</view>
						<!-- 回复 -->
						<view class="u-m-t-30">
							<view class="u-flex u-m-t-20  u-col-top" v-for="(item1,index1) in item.child" :key="index1"
								@click.stop="reply(item1)">
								<view class="u-width-100">
									<view class="u-flex u-row-between u-font-22">
										<view class="">
											<text
												class="u-font-bold u-font-24 u-type-primary">{{item1.nickname}}</text>　{{' '}}
											<text class="u-type-info">回复</text>　{{' '}}
											<text class="u-type-primary">{{item1.add_nickname}} ：</text>
										</view>
										<view class="u-font-20 u-type-info" style="flex-shrink: 0;">
											{{item1.create_time| date('yy-mm-dd hh:MM')}}
										</view>
									</view>
									<view class="u-m-t-10">
										<text> {{item1.content}}</text>
									</view>
								</view>
							</view>
						</view>
					</view>
				</view>
			</view>
		</view>
		<u-gap height="100"></u-gap>
		<!-- <view class="u-flex u-row-between u-type-white-bg submit-btn" style="padding: 15rpx 30rpx;">
            <view class="u-flex-1 u-flex u-type-info-light-bg u-radius-20 u-p-lr-30">
                <view class="u-flex-1" v-if="type==1">
                    <u-input v-model="content" :clearable="false" placeholder="友善交流，文明发言~" />
                </view>
                <view class="u--1 " v-else>
                    <u-input v-model="content" :clearable="false" :placeholder="placeholder" />
                </view>
            </view>

            <view class="u-m-l-20">
                <u-button type="success" size="mini" hover-class="none"
                    @click="add_comment()" v-if="type==1">评 论
                </u-button>
                <u-button type="success" size="mini" hover-class="none"
                    @click="add_comment()" v-else>回 复
                </u-button>
            </view>
        </view> -->
		<view class="submit-btn" style="padding: 15rpx 30rpx;">
			<view class="u-flex u-row-between">
				<view class="u-flex-1 u-flex u-radius-20 u-p-lr-30" style="background: #eee;">
					<view class="u-flex-1" v-if="type==1">
						<u-input v-model="content" :clearable="false" placeholder="友善交流，文明发言~" />
					</view>
					<view class="u-flex-1 " v-else>
						<u-input v-model="content" :clearable="false" :placeholder="placeholder" />
					</view>
				</view>

				<view class="u-m-l-20">
					<u-button type="primary" size="mini" hover-class="none" @click="add_comment()" v-if="type==1">评 论
					</u-button>
					<u-button type="primary" size="mini" hover-class="none" @click="add_comment()" v-else>回 复
					</u-button>
				</view>
			</view>
			<view class="safe-area-inset-bottom"></view>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				show: true,
				info: {},
				comment_list: [],
				page: 1,
				total: 0,
				placeholder: '',
				item: {},
				type: 1,
				content: '',
				index: 0
			};
		},
		onLoad(option) {
			this.index = option.index
			this.get_forum_list(option.id)
		},
		methods: {
			// 预览图片
			previewImage(imglist, val) {
				uni.previewImage({
					current: val,
					urls: imglist,
				});
			},
			// 收藏
			add_like(id, index, type) {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				this.$u.api.add_like({
					rid: id,
					type: type,
				}).then(res => {
					if (type == 1) {
						if (!this.info.is_like) {
							this.info.is_like = 1
							this.info.like_total++
							this.$u.func.prePage().forum_list[this.index].is_like = 1
							this.$u.func.prePage().forum_list[this.index].like_total++
						} else {
							this.info.is_like = 0
							this.info.like_total--
							this.$u.func.prePage().forum_list[this.index].is_like = 0
							this.$u.func.prePage().forum_list[this.index].like_total--
						}
					} else {
						if (!this.comment_list[index].is_like) {
							this.comment_list[index].is_like = 1
							this.comment_list[index].like_total++
						} else {
							this.comment_list[index].is_like = 0
							this.comment_list[index].like_total--
						}
					}
					this.$u.func.showToast({
						title: res.msg,
						icon: 'success',
					})
				}).catch(err => {
					this.$u.toast(err.msg)
				})
			},
		// 详情
		get_forum_list(id) {
			this.$u.api.get_forum_list({
				id: id
			}).then(res => {
				this.info = res.data
				// 将换行符替换为 <br/> 标签，使 v-html 能正确渲染换行
				if (this.info && this.info.content) {
					this.info.content = this.info.content.replace(/\n/g, '<br/>')
				}
				this.get_comment()
			})
		},
			// 评论
			pinglun() {
				this.type = 1
				this.item = {}
			},
			// 回复
			reply(item) {
				this.placeholder = '回复' + item.add_nickname + ':'
				this.type = 2
				this.item = item
			},
			// 评论
			add_comment() {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				if (!this.content) return this.$u.toast('请先输入评论')
				this.$u.api.add_comment({
					id: this.info.id,
					c_id: this.item.id || '',
					content: this.content
				}).then(res => {
					this.$u.func.showToast({
						title: res.msg,
						icon: 'success',
					})
					this.content = ''
					this.item = {}
					this.type = 1
					this.page = 1
					this.comment_list = []
					this.get_comment()
					this.info.comment_total++
					this.$u.func.prePage().forum_list[this.index].comment_total++
				}).catch(err => {
					this.$u.toast(err.msg)
				})
			},
			// 评论列表
			get_comment() {
				this.$u.api.get_comment({
					id: this.info.id
				}).then(res => {
					this.comment_list.push(...res.data.data)
					this.total = res.data.total
				})
			},
		},
		onReachBottom() {
			if (this.comment_list.length >= this.total) return
			this.page++
			this.get_comment()
		},
		onShareAppMessage(res) {
			return {
				title: this.info.title,
				imageUrl: this.info.image[0],
				path: '/pages/flhd/dk_details?id=' + this.info.id
			}
		},
		onShareTimeline() {
			return {
				title: this.info.title,
				imageUrl: this.info.image[0],
				path: '/pages/flhd/dk_details?id=' + this.info.id
			}
		}
	}
</script>

<style lang="scss">
	.submit-btn {
		background: #FFF;
	}
</style>