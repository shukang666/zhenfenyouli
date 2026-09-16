<template>
	<view class="container">
		<view class="qrcode__box" :style="{ paddingTop: height + 18 + 'px' }">
			<image class="qrcode__ic" :src="shareImg" mode="widthFix"></image>
			<view class="share__box" :style="{ paddingTop: height + 50 + 'px' }">
				<view class="avatar">
					<u-avatar :src="userInfo.avatar || 'https://admin.circular-economy.cn/static/user/avatar.png'" size="165"></u-avatar>
				</view>
				<view class="share__tip">快来看看我为你精心准备的神秘礼物</view>
				<view class="imgcode">
					<u-image width="300rpx" height="300rpx" :src="imgCode"></u-image>
				</view>
				<view class="share__tip">识别二维码看看好物</view>
			</view>
		</view>
		<view class="share__btn">
			<u-button type="primary" shape="circle" @click="savePic">保存到手机</u-button>
		</view>
		<canvas :style="{ width: winWidth + 'px', height: winHeight + 'px' }" canvas-id="posterId" id="posterId" class="tui-poster__canvas"></canvas>
	</view>
</template>

<script>
	import { prodUrl } from '@/common/settings'
	const poster = require('@/utils/poster.js');
	export default {
		data() {
			return {
				shareImg: prodUrl + '/web/poster.png',
				avatar: 'https://admin.circular-economy.cn/static/user/avatar.png',
				imgCode: '',
				height: 20,
				winWidth: uni.upx2px(560 * 2),
				winHeight: uni.upx2px(890 * 2),
				posterImg: ''
			};
		},
		onLoad(options) {
			setTimeout(() => {
				let sys = uni.getSystemInfoSync();
				this.winWidth = sys.windowWidth;
				//高度可根据内容固定，也可生成全屏
				this.winHeight = uni.upx2px(324 * 2 + 30 * 2 * 2 + 70 * 2 + 160 * 2); //sys.windowHeight
			}, 66);
			this.getImgcode(); //获取小程序码
		},
		methods: {
			//获取小程序码
			async getImgcode() {
				const res = await this.$u.api.getShareCode();
				this.imgCode = res.data;
				console.log(res.data)
			},
			//创建海报
			async createPoster() {
				uni.showLoading({
					mask: true,
					title: '图片生成中...'
				});
				let mainPic = await poster.getImage(this.shareImg);
				let avatar = await poster.getImage(this.userInfo.avatar);
				let qrcode = await poster.getImage(this.imgCode);
				// #ifdef MP-WEIXIN
				await poster.removeSavedFile()
				// #endif
				if (mainPic && qrcode) {
					const imgs = {
						avatar: avatar,
						mainPic: mainPic,
						qrcode: qrcode
					};

					let text = '快来看看我为你精心准备的神秘礼物';
					poster.drawPoster('posterId', this.winWidth, this.winHeight, imgs, text, '识别二维码看看好物', res => {
						uni.hideLoading();
						if (res) {
							this.posterImg = res;
							this.savePic();
						} else {
							this.$u.func.showToast('生成海报失败,请稍后再试');
						}
					});
				} else {
					uni.hideLoading();
					this.$u.func.showToast('生成海报图片下载失败,请稍后再试');
				}
			},
			//保存图片
			savePic() {
				if (this.posterImg) {
					poster.saveImage(this.posterImg);
				} else {
					this.createPoster();
				}
			}
		}
	};
</script>

<style lang="scss">
	.qrcode__box {
		width: 80%;
		height: 80%;
		margin: 0 auto;
		position: relative;
	}

	.qrcode__box image {
		width: 100%;
		height: 100%;
	}

	.share__box {
		width: 100%;
		display: flex;
		justify-content: center;
		flex-direction: column;
		text-align: center;
		position: absolute;
		left: 0;
		top: 0;

		.avatar,
		.imgcode {
			margin: 0 auto;
		}

		.share__tip {
			margin: 30rpx 0 50rpx;
			font-size: 32rpx;
			color: #FFFFFF;

			&:last-child {
				font-size: 36rpx;
				margin: 0;
			}
		}
	}

	.share__btn {
		margin: 50rpx 30rpx 0;
	}

	.tui-poster__canvas {
		background-color: #fff;
		position: absolute;
		top: 0;
		left: -9999px;
	}
</style>
