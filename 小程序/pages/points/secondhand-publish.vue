<template>
	<view class="publish-page">
		<view class="publish-card">
			<text class="field-label">物品照片 <text class="required">*</text></text>
			<view class="photos">
				<view class="photo" v-for="(src, idx) in form.images" :key="idx">
					<image :src="src" mode="aspectFill" class="photo-image"></image>
					<view class="photo-delete" @tap="removePhoto(idx)">×</view>
				</view>
				<view class="photo photo-add" v-if="form.images.length < 6" @tap="choosePhoto"><u-icon name="camera" size="42" color="#16864b"></u-icon><text>添加照片</text></view>
			</view>
			<view class="field"><text class="field-label">物品名称 <text class="required">*</text></text><input class="input" v-model="form.title" placeholder="例如：闲置儿童自行车" maxlength="40" /></view>
			<view class="field"><text class="field-label">物品分类 <text class="required">*</text></text><view class="chips"><view class="chip" :class="{on: form.category === category.key}" v-for="category in categoryOptions" :key="category.key" @tap="chooseCategory(category.key)">{{ category.label }}</view></view></view>
			<view class="field"><text class="field-label">文字介绍</text><textarea class="textarea" v-model="form.description" placeholder="介绍新旧程度、使用情况等" maxlength="500" /></view>
			<view class="field"><text class="field-label">售价（人民币） <text class="required">*</text></text><view class="price-input"><text>¥</text><input v-model="form.price" type="digit" placeholder="输入售价" /></view></view>
			<view class="field"><text class="field-label">展示范围 <text class="required">*</text></text><view class="chips"><view class="chip" :class="{on: form.scope_type === 'community'}" @tap="chooseScope('community')">同小区</view><view class="chip" :class="{on: form.scope_type === 'parent'}" @tap="chooseScope('parent')">同社区</view><view class="chip" :class="{on: form.scope_type === 'all'}" @tap="chooseScope('all')">整个七贤岭</view></view><text class="scope-name" v-if="scopeName">展示给：{{ scopeName }}</text></view>
			<view class="field"><text class="field-label">联系方式 <text class="required">*</text></text><input class="input" v-model="form.contact" placeholder="手机号或微信号，方便买家联系" maxlength="40" /></view>
		</view>
		<view class="tip">发布经审核后展示。平台不代收款，买卖双方自行联系、协商并完成交易。</view>
		<view class="submit" :class="{disabled: submitting}" @tap="submit">{{ submitting ? '提交中…' : '发布闲置' }}</view>
	</view>
</template>

<script>
import { uploadUrl, headerTokenName, userTokenName } from '@/common/settings.js'
import { parseUploadAssetResponse, resolveAssetShowUrl } from '@/common/uploadAsset.js'
import { getCategories } from '@/common/secondhandStore.js'

export default {
	data() {
		return {
			categoryOptions: getCategories().filter(category => category.key !== 'all'),
			submitting: false,
			imagePaths: [],
			communityId: 0,
			parentId: 0,
			communityName: '',
			parentName: '',
			form: { title: '', description: '', price: '', contact: '', category: 'other', scope_type: 'community', scope_id: 0, images: [] },
		}
	},
	onLoad() {
		if (!this.$u.func.checkLogin()) return
		this.$u.api.secondhandMeta().then(res => {
			const data = res.data || {}
			this.communityId = Number(data.community_id || 0)
			this.parentId = Number(data.community_parent_id || 0)
			this.communityName = data.community_name || ''
			this.parentName = data.community_parent_name || ''
			this.form.scope_id = this.communityId || this.parentId || 0
			this.form.scope_type = this.communityId ? 'community' : (this.parentId ? 'parent' : 'all')
		}).catch(() => {})
	},
	computed: {
		scopeName() {
			if (this.form.scope_type === 'community') return this.communityName || '请先完善个人资料中的小区信息'
			if (this.form.scope_type === 'parent') return this.parentName || '请先完善个人资料中的社区信息'
			return '整个七贤岭'
		},
	},
	methods: {
		chooseCategory(key) { this.form.category = key },
		chooseScope(type) {
			if (type === 'community' && !this.communityId) { uni.showToast({ title: '请先完善个人资料中的小区信息', icon: 'none' }); return }
			if (type === 'parent' && !this.parentId) { uni.showToast({ title: '请先完善个人资料中的社区信息', icon: 'none' }); return }
			this.form.scope_type = type
			this.form.scope_id = type === 'community' ? this.communityId : (type === 'parent' ? this.parentId : 0)
		},
		choosePhoto() {
			uni.chooseImage({ count: 6 - this.form.images.length, sizeType: ['compressed'], sourceType: ['album', 'camera'], success: res => {
				const paths = res.tempFilePaths || []
				this.form.images = this.form.images.concat(paths).slice(0, 6)
				while (this.imagePaths.length < this.form.images.length) this.imagePaths.push('')
			} })
		},
		removePhoto(idx) { this.form.images.splice(idx, 1); this.imagePaths.splice(idx, 1) },
		uploadOne(filePath) {
			const openid = (this.userInfo && this.userInfo[userTokenName]) || ''
			return new Promise((resolve, reject) => uni.uploadFile({
				url: uploadUrl, filePath, name: 'file', formData: { filetype: 'image' }, header: { [headerTokenName]: openid },
				success: res => {
					try {
						const body = JSON.parse(res.data)
						if (body.code != 1 || !body.data) throw new Error(body.msg || '图片上传失败')
						const asset = parseUploadAssetResponse(body)
						if (!asset.savePath) throw new Error('图片上传失败')
						resolve({ savePath: asset.savePath, showUrl: asset.showUrl || resolveAssetShowUrl(asset.savePath) })
					} catch (e) { reject(e) }
				}, fail: reject,
			}))
		},
		async submit() {
			if (this.submitting) return
			if (!this.form.images.length) { uni.showToast({ title: '请至少上传一张照片', icon: 'none' }); return }
			if (!String(this.form.title).trim()) { uni.showToast({ title: '请填写物品名称', icon: 'none' }); return }
			if (!/^\d{1,7}(\.\d{1,2})?$/.test(String(this.form.price))) { uni.showToast({ title: '请填写正确的人民币售价', icon: 'none' }); return }
			if (!String(this.form.contact).trim()) { uni.showToast({ title: '请填写联系方式', icon: 'none' }); return }
			if (!this.form.scope_type) { uni.showToast({ title: '请选择展示范围', icon: 'none' }); return }
			this.submitting = true
			uni.showLoading({ title: '上传图片…', mask: true })
			try {
				const paths = []
				for (let i = 0; i < this.form.images.length; i++) {
					if (!this.imagePaths[i]) {
						const asset = await this.uploadOne(this.form.images[i])
						this.imagePaths[i] = asset.savePath
						this.$set(this.form.images, i, asset.showUrl)
					}
					paths.push(this.imagePaths[i])
				}
				uni.showLoading({ title: '提交中…', mask: true })
				await this.$u.api.secondhandPublish({ title: this.form.title, description: this.form.description, price: this.form.price, contact: this.form.contact, scope_type: this.form.scope_type, scope_id: this.form.scope_id, category: this.form.category, condition: 'used', images: JSON.stringify(paths) })
				uni.hideLoading(); uni.showToast({ title: '已提交，等待审核', icon: 'success' })
				setTimeout(() => uni.navigateBack({ fail: () => uni.redirectTo({ url: '/pages/points/secondhand-list' }) }), 600)
			} catch (e) { uni.hideLoading(); uni.showToast({ title: (e && (e.message || e.msg)) || '发布失败', icon: 'none' }) }
			finally { this.submitting = false }
		},
	},
}
</script>

<style lang="scss" scoped>
.publish-page { min-height: 100vh; box-sizing: border-box; padding: 24rpx 30rpx calc(44rpx + env(safe-area-inset-bottom)); background: #f4f7f5; }
.publish-card { padding: 28rpx; border-radius: 24rpx; background: #fff; box-shadow: 0 8rpx 28rpx rgba(30,90,55,.06); }
.field { margin-top: 30rpx; }
.field-label { display: block; color: #26372d; font-size: 28rpx; font-weight: 600; }
.required { color: #e54d42; }
.input, .textarea, .price-input { box-sizing: border-box; width: 100%; margin-top: 14rpx; padding: 0 20rpx; border-radius: 12rpx; background: #f5f8f6; font-size: 27rpx; }
.input { height: 82rpx; }
.textarea { min-height: 160rpx; padding-top: 18rpx; }
.price-input { display: flex; align-items: center; height: 82rpx; color: #16864b; font-size: 32rpx; }
.price-input input { flex: 1; padding-left: 16rpx; color: #26372d; font-size: 28rpx; }
.photos, .chips { display: flex; flex-wrap: wrap; gap: 16rpx; margin-top: 16rpx; }
.photo { position: relative; width: 154rpx; height: 154rpx; overflow: hidden; border-radius: 14rpx; background: #f3f6f4; }
.photo-image { width: 100%; height: 100%; }
.photo-delete { position: absolute; top: 0; right: 0; width: 40rpx; height: 40rpx; text-align: center; line-height: 38rpx; color: #fff; background: rgba(0,0,0,.48); }
.photo-add { display: flex; flex-direction: column; align-items: center; justify-content: center; border: 2rpx dashed #bddbc8; color: #16864b; font-size: 22rpx; }
.chip { padding: 13rpx 22rpx; border-radius: 999rpx; background: #f1f4f2; color: #526057; font-size: 25rpx; }
.chip.on { background: #e3f3ea; color: #076d3d; font-weight: 600; }
.scope-name { display: block; margin-top: 12rpx; color: #839087; font-size: 23rpx; }
.tip { margin: 22rpx 8rpx; color: #859088; font-size: 23rpx; line-height: 1.6; }
.submit { height: 94rpx; margin-top: 30rpx; border-radius: 999rpx; background: #16864b; color: #fff; text-align: center; line-height: 94rpx; font-size: 30rpx; font-weight: 600; }
.submit.disabled { opacity: .65; }
</style>
