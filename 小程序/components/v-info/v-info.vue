<template>
	<u-popup v-model="show" mode="center" width="80%" border-radius="20" :mask-close-able="false" closeable>
		<view class="u-p-30">
			<view class="u-font-32 u-text-center">完善后可体验更多功能</view>
			<u-gap height="30"></u-gap>
			<u-form :model="form" ref="uForm">
			    <u-form-item :label-width="0" prop="avatar" :borderBottom="false">
					<view class="u-text-center">
						<button class="u-reset-button" open-type="chooseAvatar" @chooseavatar="chooseAvatar">
							<u-avatar :src="form.avatar" show-level level-icon="camera-fill" size="120"></u-avatar>
						</button>
					</view>
			    </u-form-item>
				<u-form-item :label-width="0" prop="nickname" :borderBottom="false">
					<view class="nickname-cell">
						<view class="u-shrink-no u-m-r-20">昵称</view>
						<view class="u-width-100">
							<u-input type="nickname" v-model="form.nickname" placeholder="请输入昵称" @input="nameChange" />
						</view>
					</view>
				</u-form-item>
				<view class="u-flex u-m-t-20">
					<view class="u-type-success-light-bg u-radius-5 u-p-10 u-shrink-no">
						<u-icon name="weixin-fill" color="#16D46B" size="42"></u-icon>
					</view>
					<view class="u-font-24 u-m-l-20">99%+的用户使用选择微信头像和微信昵称，便于订单发货和售后沟通</view>
				</view>
			    <view class="u-m-t-50">
					<u-button type="error" shape="circle" :custom-style="{background:'#FF2E40'}" @click="handleSubmit">完成</u-button>
			    </view>
			</u-form>
		</view>
	</u-popup>
</template>

<script>
	import { uploadUrl } from '@/common/settings'
	export default {
		name:"v-info",
		data() {
			return {
				show: false,
				uploadUrl,
				form: {
					avatar: '',
					nickname: ''
				},
				filepath: '' //图片保存所需路径
			};
		},
        methods: {
            // 更新会员信息
			handleSubmit(){
				this.$u.api.updateMember({
					id: this.userInfo.id,
				    nickname: this.form.nickname,
				    avatar: this.filepath
				}).then(res => {
					this.$u.toast(res.msg, 'success');
					setTimeout(() => {
						this.show = false;
					},1000)
				}).catch(err => {
				    this.$u.toast(err.msg);
				})
			},
            // 获取用户头像
            chooseAvatar(e) {
				var that = this;
				that.form.avatar = e.detail.avatarUrl;
				uni.uploadFile({
				    url: uploadUrl,
				    filePath: e.detail.avatarUrl,
				    name: 'file',
				    success: (uploadFileRes) => {
				        let res = JSON.parse(uploadFileRes.data)
				        that.filepath = res.data.filepath;
				    },
					fail: (err) => {
				    	console.log(err)
				    }
				});
            },
            // 获取用户昵称
            nameChange(value) {
                this.form.nickname = value;
            }
        }
	}
</script>

<style lang="scss" scoped>
	.nickname-cell {
		display: flex;
		background: #F7F7F7;
		border-radius: 5px;
		padding: 5rpx 20rpx;
		margin-top: -20rpx;
	}
</style>