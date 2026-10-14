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

				<textarea class="sh-pub__textarea" v-model="form.description" placeholder="新旧程度、物品说明等" maxlength="500" />

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

				<text class="sh-pub__label">交接说明</text>

				<input class="sh-pub__input" v-model="form.area_text" placeholder="如：参加绿色二手市集现场交接" maxlength="60" />

			</view>

		</view>

		<view class="sh-pub__tip">提交后需后台审核通过才会展示；交换与积分划转在市集现场办理。</view>

		<view class="sh-pub__submit" :class="{ 'sh-pub__submit--disabled': submitting }" hover-class="sh-pub__submit--press" @tap="submit">

			{{ submitting ? '提交中…' : '发布到社区集市' }}

		</view>

	</view>

</template>



<script>

import { getCategories, getConditions } from '@/common/secondhandStore.js'

import { uploadUrl, headerTokenName, userTokenName } from '@/common/settings.js'

import { parseUploadAssetResponse, resolveAssetShowUrl } from '@/common/uploadAsset.js'



export default {

	data() {

		return {

			categoryOptions: getCategories().filter(c => c.key !== 'all'),

			conditions: getConditions(),

			submitting: false,

			form: {

				title: '',

				description: '',

				point_price: '',

				category: 'other',

				condition: 'used',

				area_text: '绿色二手市集现场交接',

				images: [],

			},

			imagePaths: [],

		}

	},

	onLoad() {

		if (!this.$u.func.checkLogin()) return

	},

	methods: {

		choosePhoto() {

			const remain = 6 - this.form.images.length

			uni.chooseImage({

				count: remain,

				sizeType: ['compressed'],

				sourceType: ['album', 'camera'],

				success: res => {

					const paths = res.tempFilePaths || []

					this.form.images = this.form.images.concat(paths).slice(0, 6)

					while (this.imagePaths.length < this.form.images.length) {

						this.imagePaths.push('')

					}

				},

			})

		},

		removePhoto(idx) {

			this.form.images.splice(idx, 1)

			this.imagePaths.splice(idx, 1)

		},

		uploadOne(filePath) {

			const openid = (this.userInfo && this.userInfo[userTokenName]) || ''

			return new Promise((resolve, reject) => {

				uni.uploadFile({

					url: uploadUrl,

					filePath,

					name: 'file',

					formData: { filetype: 'image' },

					header: { [headerTokenName]: openid },

					success: uploadRes => {

						try {

							const body = JSON.parse(uploadRes.data)

							if (body.code == 1 && body.data) {

								const { savePath, showUrl } = parseUploadAssetResponse(body)

								if (!savePath) {

									reject(new Error('上传失败'))

									return

								}

								resolve({ savePath, showUrl: showUrl || resolveAssetShowUrl(savePath) })

							} else {

								reject(new Error(body.msg || '上传失败'))

							}

						} catch (e) {

							reject(e)

						}

					},

					fail: reject,

				})

			})

		},

		async submit() {

			if (this.submitting) return

			if (!this.form.images.length) {

				uni.showToast({ title: '请至少上传一张图片', icon: 'none' })

				return

			}

			if (!String(this.form.title || '').trim()) {

				uni.showToast({ title: '请填写物品名称', icon: 'none' })

				return

			}

			const price = parseInt(this.form.point_price, 10)

			if (!price || price < 1) {

				uni.showToast({ title: '请填写积分价格', icon: 'none' })

				return

			}



			this.submitting = true

			uni.showLoading({ title: '上传图片…', mask: true })

			try {

				const paths = []

				for (let i = 0; i < this.form.images.length; i++) {

					if (this.imagePaths[i]) {

						paths.push(this.imagePaths[i])

						continue

					}

					const up = await this.uploadOne(this.form.images[i])

					this.imagePaths[i] = up.savePath

					this.$set(this.form.images, i, up.showUrl)

					paths.push(up.savePath)

				}

				if (!paths.length) {

					throw new Error('图片上传失败')

				}

				uni.showLoading({ title: '提交中…', mask: true })

				await this.$u.api.secondhandPublish({

					title: this.form.title,

					description: this.form.description,

					point_price: price,

					category: this.form.category,

					condition: this.form.condition,

					area_text: this.form.area_text,

					images: JSON.stringify(paths),

				})

				uni.hideLoading()

				uni.showToast({ title: '已提交，等待审核', icon: 'success' })

				setTimeout(() => {

					uni.navigateBack({

						fail: () => {

							uni.redirectTo({ url: '/pages/points/secondhand-list' })

						},

					})

				}, 600)

			} catch (e) {

				uni.hideLoading()

				uni.showToast({ title: (e && e.message) || (e && e.msg) || '发布失败', icon: 'none' })

			} finally {

				this.submitting = false

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

	font-size: 24rpx;

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



.sh-pub__submit--disabled {

	opacity: 0.65;

}



.sh-pub__submit--press {

	opacity: 0.88;

}

</style>

