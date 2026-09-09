<template>
	<!-- 签名横屏 -->
	<view class="signa">
		<view class="box">
			<canvas class="canvas" disable-scroll="true" :style="{'width':width+'px','height':height+'px'}" canvas-id="designature" id="designature" @touchstart="starts" @touchmove="moves"
				@touchend="end"></canvas>
			<!-- 旋转后的 -->
			<canvas class="canvasImg" :style="{'width':height+'px','height':width+'px'}" canvas-id="designature2" id="designature2"></canvas>
			<view class="bottomBox u-flex">
				<view class="tip">请你在此手动签写自己的签章，签写操作完毕点击保存按钮</view>
				<view class="btn" style="margin-top: 18vw;" @click="cancel">取消</view>
				<view class="btn" style="margin-top: 8vw;" @click="clear">清除</view>
				<view class="btn blue" @click="save">保存</view>
			</view>
		</view>

	</view>
</template>

<script>
	export default {
		name: 'f-signature',
		data() {
			return {
				resultUrl: '',
				cxt: null,
				cxt2: null,
				line: [],
				width: 0,
				height: 0,
				radius: 0,
				isSign: false, //判断是否已经签字
			}
		},
		created() {
			uni.getSystemInfo({
				success: (res) => {
					this.width = res.windowWidth - 56;
					this.height = res.windowHeight - 154;
				}
			});
			this.cxt = uni.createCanvasContext('designature', this);
			this.cxt2 = uni.createCanvasContext('designature2', this);
		},
		methods: {
			cancel() {
				uni.navigateBack()
			},
			end(e) {

			},
			distance(a, b) {
				let x = b.x - a.x;
				let y = b.y - a.y;
				return Math.sqrt(x * x + y * y);
			},
			starts(e) {
				this.line.push({
					points: [{
						time: new Date().getTime(),
						x: e.touches[0].x,
						y: e.touches[0].y,
						dis: 0
					}]
				})
				let currentPoint = {
					x: e.touches[0].x,
					y: e.touches[0].y
				}
				this.currentPoint = currentPoint
				this.drawer(this.line[this.line.length - 1])
			},
			moves(e) {
				let point = {
					x: e.touches[0].x,
					y: e.touches[0].y
				}
				this.lastPoint = this.currentPoint
				this.currentPoint = point
				this.line[this.line.length - 1].points.push({
					time: new Date().getTime(),
					x: e.touches[0].x,
					y: e.touches[0].y,
					dis: this.distance(this.currentPoint, this.lastPoint)
				})
				this.drawer(this.line[this.line.length - 1])
			},
			drawer(item) {
				this.isSign = true
				let x1, x2, y1, y2, len, radius, r, cx, cy, t = 0.5,
					x, y;
				var time = 0;
				if (item.points.length > 2) {
					let lines = item.points[item.points.length - 3];
					let line = item.points[item.points.length - 2];
					let end = item.points[item.points.length - 1];
					x = line.x;
					y = line.y;
					x1 = lines.x;
					y1 = lines.y;
					x2 = end.x;
					y2 = end.y;
					var dis = 0;
					time = (line.time - lines.time) + (end.time - line.time)
					dis = line.dis + lines.dis + end.dis;
					var cxt = this.cxt;
					var or = Math.min(time / dis * this.linePressure + this.lineMin, this.lineMax);
					cx = (x - (Math.pow(1 - t, 2) * x1) - Math.pow(t, 2) * x2) / (2 * t * (1 - t))
					cy = (y - (Math.pow(1 - t, 2) * y1) - Math.pow(t, 2) * y2) / (2 * t * (1 - t))
					cxt.setLineCap('round')
					cxt.beginPath();
					cxt.setStrokeStyle('black')
					cxt.setLineWidth(5)
					cxt.moveTo(x1, y1);
					cxt.quadraticCurveTo(cx, cy, x2, y2);
					cxt.stroke();
					cxt.draw(true)
				}
			},
			clear() {
				this.cxt.clearRect(0, 0, 1000, 1000)
				this.cxt.draw()
				this.isSign = false
			},
			save() {
				var that = this;
				if (!this.isSign) {
					uni.showToast({
						title: '请先签字再保存',
						icon: 'none'
					})
					return
				}
				uni.canvasToTempFilePath({
					canvasId: 'designature',
					fileType: 'png',
					quality: 1, //图片质量
					success: function(res) {
						// that.$emit('getImg',res.tempFilePath)
						// 旋转角度
						that.rotate(res.tempFilePath);
					},
					fail(e) {
						console.log(e)
					}
				}, this)
			},
			//旋转图片
			rotate(tempFilePath) {
				const that = this
				let width = this.height
				let height = this.width
				var ctx = this.cxt2
				ctx.translate(width / 2, height / 2)
				ctx.rotate((270 * Math.PI) / 180)
				ctx.drawImage(tempFilePath, -height / 2, -width / 2, height, width)
				ctx.draw(false, (data) => {
					// 将之前在绘图上下文中的描述（路径、变形、样式）画到 canvas 中
					uni.canvasToTempFilePath({
						width: width,
						height: height,
						canvasId: 'designature2',
						fileType: 'png',
						quality: 1, //图片质量
						success(res2) {
							that.$emit('getImg', res2.tempFilePath);
						}
					}, that)
				});
			},
		}
	}
</script>

<style scoped lang="scss">
	.signa {
		position: relative;
		overflow: hidden;
		background-color: #fff;
		height: 100vh;
		width: 100vw;
		padding: 20px 16px 30px;

		.box {
			background-color: #fff;
			border-radius: 20rpx;
			padding: 12px 12px 100px;
			box-shadow: 0 6rpx 42rpx rgba(0, 0, 0, 0.05);
		}

		.canvas {
			background-color: #E6E6E6;
			position: relative;
			z-index: 9999;
		}

		.bottomBox {
			flex-direction: column;
			justify-content: center;
			width: 100px;
			height: 80vw;
			padding: 10px;
			// background-color: #007AFF;
			position: fixed;
			left: 50%;
			bottom: -40vw;
			transform: rotate(90deg);
			margin-left: -50px;
			margin-bottom: 72px;

			.tip {
				font-size: 11px;
				color: #FF6F77;
			}

			.btn {
				width: 100%;
				height: 37px;
				background: #F5F5F5;
				border-radius: 19px;
				margin-top: 7px;
				text-align: center;
				line-height: 37px;
				color: #333333;
				font-size: 13px;

				&.blue {
					background: #50C058;
					color: #fff;
				}
			}
		}
	}

	.canvasImg {
		width: 300rpx;
		height: 300rpx;
		position: fixed;
		top: -10000rpx;
		left: 0rpx;
	}
</style>