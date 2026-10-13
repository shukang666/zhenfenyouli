<template>
	<view class="sh-pub">
		<view class="sh-pub__card">
			<view class="sh-pub__label">物品照片 <text class="sh-pub__req">*</text></view>
			<view class="sh-pub__photos">
				<view class="sh-pub__photo" v-for="(src, idx) in form.images" :key="idx">
					<image :src="src" mode="aspectFill" class="sh-pub__photo-img"></image>
					<view class="sh-pub__photo-del" @tap="removePhoto(idx)">×</view>
				</view>
				<view class="sh-pub__photo sh-pub__photo--add" v-if="form.images.length < 6" @tap="choosePhoto">
					<u-icon name="camera" size="44" color="#18AF38"></u-icon>
					<text class="sh-pub__add-t">添加</text>
				</view>
			</view>
			<view class="sh-pub__field">
				<text class="sh-pub__label">名称 <text class="sh-pub__req">*</text></text>
				<input class="sh-pub__input" v-model="form.title" placeholder="例如：闲置儿童自行车" maxlength="40" />
			</view>
			<view class="sh-pub__field">
				<text class="sh-pub__label">描述</text>
				<textarea class="sh-pub__textarea" v-model="form.description" placeholder="新旧程度、取货方式等" maxlength="500" />
			</view>
			<view class="sh-pub__field">
				<text class="sh-pub__label">积分价格 <text class="sh-pub__req">*</text></text>
				<input class="sh-pub__input" type="number" v-model="form.point_price" placeholder="仅支持整数积分" />
			</view>
			<view class="sh-pub__field">
				<text class="sh-pub__label">分类</text>
				<view class="sh-pub__chips">
					<view
						class="sh-chip"
						:class="{ 'sh-chip--on': form.category === c.key }"
						v-for="c in categoryOptions"
						:key="c.key"
						@tap="form.category = c.key"
					>{{ c.label }}</view>
				</view>
			</view>
			<view class="sh-pub__field">
				<text class="sh-pub__label">成色</text>
				<view class="sh-pub__chips">
					<view
						class="sh-chip"
						:class="{ 'sh-chip--on': form.condition === c.key }"
						v-for="c in conditions"
						:key="c.key"
						@tap="form.condition = c.key"
					>{{ c.label }}</view>
				</view>
			</view>
			<view class="sh-pub__field">
				<text class="sh-pub__label">交接范围</text>
				<input class="sh-pub__input" v-model="form.area_text" placeholder="如：XX 小区附近自提" maxlength="60" />
			</view>
		</view>
		<view class="sh-pub__tip">演示版：发布仅保存在本机；正式版需审核并绑定积分托管</view>
		<view class="sh-pub__submit" hover-class="sh-pub__submit--press" @tap="submit">发布到社区集市</view>
	</view>
</template>

<script>
import { getCategories, getConditions, addSecondhand } from '@/common/secondhandStore.js'

export default {
	data() {
		return {
			categoryOptions: getCategories().filter(c => c.key !== 'all'),
			conditions: getConditions(),
			form: {
				title: '',
				description: '',
				point_price: '',
				category: 'other',
				condition: 'used',
				area_text: '',
				images: [],
			},
		}
	},
	methods: {
		choosePhoto() {
			const remain = 6 - this.form.images.length
			uni.chooseImage({
				count: remain,
				sizeType: ['compressed'],
				sourceType: ['album', 'camera'],
				success: res => {
					this.form.images = this.form.images.concat(res.tempFilePaths || []).slice(0, 6)
				},
			})
		},
		removePhoto(idx) {
			this.form.images.splice(idx, 1)
		},
		submit() {
			try {
				const u = this.userInfo || {}
				addSecondhand({
					...this.form,
					seller_nickname: u.nickname || '社区用户',
					seller_avatar: u.avatar || '',
					seller_user_id: u.id || '',
				})
				uni.showToast({ title: '发布成功', icon: 'success' })
				setTimeout(() => {
					uni.navigateBack({
						fail: () => {
							uni.redirectTo({ url: '/pages/points/secondhand-list' })
						},
					})
				}, 600)
			} catch (e) {
				uni.showToast({ title: e.message || '发布失败', icon: 'none' })
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.sh-pub {
	min-height: 100vh;
	background: #e4e8e5;
	padding: 24rpx 30rpx calc(40rpx + env(safe-area-inset-bottom));
	box-sizing: border-box;
}

.sh-pub__card {
	background: #fff;
	border-radius: 24rpx;
	padding: 28rpx;
	box-shadow: 0 8rpx 32rpx rgba(7, 109, 61, 0.05);
}

.sh-pub__label {
	font-size: 28rpx;
	font-weight: 600;
	color: #1a1a1a;
}

.sh-pub__req {
	color: #e54d42;
}

.sh-pub__photos {
	display: flex;
	flex-wrap: wrap;
	gap: 16rpx;
	margin-top: 16rpx;
	margin-bottom: 28rpx;
}

.sh-pub__photo {
	width: 160rpx;
	height: 160rpx;
	border-radius: 16rpx;
	overflow: hidden;
	position: relative;
	background: #f2f4f3;
}

.sh-pub__photo-img {
	width: 100%;
	height: 100%;
}

.sh-pub__photo-del {
	position: absolute;
	top: 0;
	right: 0;
	width: 44rpx;
	height: 44rpx;
	line-height: 40rpx;
	text-align: center;
	background: rgba(0, 0, 0, 0.45);
	color: #fff;
	font-size: 32rpx;
}

.sh-pub__photo--add {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	border: 2rpx dashed #c5e6d2;
}

.sh-pub__add-t {
	margin-top: 8rpx;
	font-size: 22rpx;
	color: #18af38;
}

.sh-pub__field {
	margin-bottom: 28rpx;
}

.sh-pub__input {
	margin-top: 12rpx;
	height: 80rpx;
	padding: 0 20rpx;
	background: #f7faf8;
	border-radius: 12rpx;
	font-size: 28rpx;
}

.sh-pub__textarea {
	margin-top: 12rpx;
	width: 100%;
	min-height: 160rpx;
	padding: 16rpx 20rpx;
	background: #f7faf8;
	border-radius: 12rpx;
	font-size: 28rpx;
	box-sizing: border-box;
}

.sh-pub__chips {
	display: flex;
	flex-wrap: wrap;
	gap: 16rpx;
	margin-top: 12rpx;
}

.sh-chip {
	padding: 12rpx 24rpx;
	border-radius: 999rpx;
	background: #f2f4f3;
	font-size: 26rpx;
	color: #3a3a3c;
}

.sh-chip--on {
	background: #e3f3ea;
	color: #076d3d;
	font-weight: 600;
}

.sh-pub__tip {
	margin-top: 24rpx;
	font-size: 22rpx;
	color: #8e8e93;
	line-height: 1.5;
}

.sh-pub__submit {
	margin-top: 32rpx;
	height: 96rpx;
	line-height: 96rpx;
	text-align: center;
	background: #18af38;
	color: #fff;
	font-size: 32rpx;
	font-weight: 600;
	border-radius: 999rpx;
}

.sh-pub__submit--press {
	opacity: 0.88;
}
</style>
