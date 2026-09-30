<template>

	<view class="add-page" v-if="!setting.app_test">

		<view class="add-hero">

			<text class="add-hero-title">{{ pageTitle }}</text>

			<text class="add-hero-sub">{{ pageSubTitle }}</text>

			<view class="add-tip-bar">

				<text class="add-tip-text">照片是证明您已到场工作的留证，请先完成现场巡检/督导，再填写记录。</text>

			</view>

			<view class="add-steps u-flex u-row-between">

				<view class="add-step" v-for="(s, i) in stepLabels" :key="i">

					<view class="add-step-dot">{{ i + 1 }}</view>

					<text class="add-step-lab">{{ s }}</text>

				</view>

			</view>

		</view>



		<view class="add-body">

			<u-form :model="form" ref="uForm" :error-type="['toast']">

				<view class="add-card">

					<view class="add-card-head">

						<text class="add-card-title">工作记录</text>

					</view>

					<u-form-item label="工作小区" prop="community_name" label-width="160" :border-bottom="true">

						<u-input v-model="form.community_name" placeholder="请选择小区" type="select" @click="openCommunityPicker" />

					</u-form-item>

					<u-form-item label="巡检点位" prop="cate_name" label-width="160" :border-bottom="true">

						<u-input v-model="form.cate_name" placeholder="选择本次工作的点位" type="select" @click="openPointPicker" />

					</u-form-item>

					<view class="inspect-field">

						<text class="inspect-field-label">现场是否整洁</text>

						<view class="seg-group u-flex">

							<view

								v-for="opt in hygieneOptions"

								:key="opt.value"

								class="seg-item"

								:class="{ active: form.site_hygiene === opt.value }"

								@click="form.site_hygiene = opt.value"

							>{{ opt.label }}</view>

						</view>

					</view>

					<view class="inspect-field">

						<text class="inspect-field-label">有无整改意见</text>

						<view class="seg-group u-flex">

							<view

								class="seg-item"

								:class="{ active: form.has_rectify === 0 }"

								@click="setHasRectify(0)"

							>无</view>

							<view

								class="seg-item seg-item-warn"

								:class="{ active: form.has_rectify === 1 }"

								@click="setHasRectify(1)"

							>有</view>

						</view>

						<view class="field-hint" v-if="form.has_rectify === 1">

							提交后将同步至「随手拍问题反馈」，便于社区跟进整改

						</view>

					</view>

					<view class="inspect-field inspect-field-last" v-if="form.has_rectify === 1">

						<text class="inspect-field-label">整改意见</text>

						<u-input

							v-model="form.rectify_remark"

							type="textarea"

							height="160"

							maxlength="500"

							placeholder="请描述需要整改的问题（必填）"

						/>

					</view>

					<u-form-item label="工作情况" prop="content" label-width="160" label-position="top" :border-bottom="false">

						<u-input

							v-model="form.content"

							type="textarea"

							height="200"

							maxlength="-1"

							:placeholder="contentPlaceholder"

						/>

					</u-form-item>

				</view>

			</u-form>



			<view class="add-card add-photo-card">

				<view class="add-card-head">

					<text class="add-card-title">现场留证</text>

					<text class="add-card-sub">补充图片，证明您已完成上述工作（可点图放大）</text>

				</view>

				<view class="photo-row u-flex u-row-between">

					<view class="photo-col" v-for="sec in photoSections" :key="sec.key">

						<view class="photo-slot" hover-class="photo-slot--press" @click="onPhotoSlotTap(sec.key)">

							<image

								v-if="form[sec.key]"

								class="photo-slot-img"

								:src="photoPreviewUrl(sec.key)"

								mode="aspectFill"

								@click.stop="previewPhotos(sec.key)"

							></image>

							<view v-else class="photo-slot-empty">

								<u-icon name="plus" size="40" color="#18AF38"></u-icon>

								<text class="photo-slot-add">留证</text>

							</view>

							<view v-if="form[sec.key]" class="photo-del" @click.stop="clearPhoto(sec.key)">

								<u-icon name="close" size="24" color="#fff"></u-icon>

							</view>

						</view>

						<text class="photo-col-label">{{ sec.title }}</text>

					</view>

				</view>

			</view>

		</view>



		<u-select v-model="cate_show" label-name="name" value-name="id" :list="cate_list" @confirm="confirm"></u-select>



		<view v-if="showCommunityDropdown" class="community-dropdown-mask" @click="showCommunityDropdown = false">

			<view class="community-dropdown" @click.stop>

				<view class="u-font-28 u-font-bold u-m-b-16">选择工作小区</view>

				<view class="u-radius-5 u-p-lr-20" style="background-color: #f7f7f7;">

					<u-input v-model="communityKeyword" type="text" placeholder="输入小区名称搜索" :clearable="true" @input="onCommunitySearch" />

				</view>

				<scroll-view scroll-y class="community-dropdown-list" @scrolltolower="loadMoreCommunity" lower-threshold="50">

					<view v-if="!communityResults.length && !communityLoading" class="u-text-center u-p-30 u-font-24 u-type-info">

						{{ communityKeyword ? '未找到相关小区' : '输入名称搜索，或下滑加载更多' }}

					</view>

					<view

						v-for="item in communityResults"

						:key="item.id"

						class="u-p-lr-20 u-p-tb-15 community-dropdown-item"

						@click="selectCommunity(item)"

					>

						<view class="u-font-28">{{ item.name }}</view>

						<view class="u-font-24 u-type-info" v-if="item.parent_name">{{ item.parent_name }}</view>

					</view>

					<view v-if="communityLoading" class="u-text-center u-p-tb-20 u-font-24 u-type-info">加载中...</view>

					<view v-if="communityNoMore && communityResults.length" class="u-text-center u-p-tb-20 u-font-24 u-type-info">没有更多了</view>

				</scroll-view>

			</view>

		</view>



		<u-mask :show="tipShow">

			<view class="u-w-560 u-radius-10 u-type-white-bg u-abso-center u-p-30" v-if="showType == 1">

				<view class="u-text-center">

					<image src="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/images/dakachenggong.png" class="u-w-280 u-h-188" mode="aspectFill"></image>

					<view class="u-font-32 u-m-t-30">恭喜您获得 <text class="u-font-40 u-main-c u-font-bold">{{ point }}</text> 积分</view>

				</view>

				<view class="u-m-t-30 u-flex u-row-center">

					<u-button :custom-style="{ width: '400rpx', background: 'linear-gradient(141deg, #A4DB88, #18AF38)', color: '#fff' }" @click="goBack">我知道了</u-button>

				</view>

			</view>

			<view class="u-w-560 u-radius-10 u-type-white-bg u-abso-center u-p-30" v-if="showType == 2">

				<view class="u-text-center">

					<image src="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/images/wenxintishi.png" class="u-w-280 u-h-188" mode="aspectFill"></image>

					<view class="u-m-t-30">

						<view class="u-m-b-30">请在倒计时结束后再提交记录</view>

						<u-count-down

							:timestamp="remaining_seconds"

							:show-border="true"

							font-size="48"

							height="80"

							color="#ffffff"

							border-color="#18AF38"

							bg-color="#18AF38"

							separator-color="#18AF38"

						></u-count-down>

					</view>

				</view>

				<view class="u-m-t-30 u-flex u-row-center">

					<u-button :custom-style="{ width: '400rpx' }" @click="tipShow = false">我知道了</u-button>

				</view>

			</view>

		</u-mask>



		<view class="add-footer">

			<view class="add-submit-btn" hover-class="add-submit-btn--press" @click="submit">提交工作记录</view>

			<view class="add-record-link" @click="$u.route('/pages/zzhd/dwxj/record?type=' + form.type)">历史记录</view>

		</view>

	</view>

</template>



<script>

	import { uploadUrl } from '@/common/settings'

	const DWXJ_COMMUNITY_STORAGE_KEY = 'dwxj_last_inspect_community'

	export default {

		data() {

			return {

				uploadUrl,

				form: {

					type: 3,

					community_id: 0,

					community_name: '',

					cate_id: 0,

					cate_name: '',

					content: '',

					site_hygiene: 'tidy',

					has_rectify: 0,

					rectify_remark: '',

					image_one: '',

					image_two: '',

					image_three: '',

				},

				photoShowUrl: {

					image_one: '',

					image_two: '',

					image_three: '',

				},

				rules: {

					content: [{

						required: true,

						message: '请填写工作情况',

						trigger: ['change', 'blur'],

					}],

					cate_name: [{

						required: true,

						message: '请选择巡检点位',

						trigger: ['change', 'blur'],

					}],

					community_name: [{

						required: true,

						message: '请选择工作小区',

						trigger: ['change', 'blur'],

					}],

				},

				hygieneOptions: [

					{ label: '整洁', value: 'tidy' },

					{ label: '一般', value: 'average' },

					{ label: '较差', value: 'poor' },

				],

				showCommunityDropdown: false,

				communityKeyword: '',

				communityResults: [],

				communityPage: 1,

				communityLastPage: 1,

				communityLoading: false,

				communitySearchTimer: null,

				cate_show: false,

				cate_list: [],

				showType: 1,

				tipShow: false,

				point: 0,

				remaining_seconds: 0,

				stepLabels: ['选点位', '写记录', '留证'],

				photoSections: [

					{ key: 'image_one', title: '点位环境' },

					{ key: 'image_two', title: '分类指引' },

					{ key: 'image_three', title: '桶位情况' },

				],

			}

		},

		computed: {

			communityNoMore() {

				return this.communityPage >= this.communityLastPage

			},

			pageTitle() {

				return String(this.form.type) === '4' ? '桶边督导记录' : '巡检工作记录'

			},

			pageSubTitle() {

				return String(this.form.type) === '4'

					? '先在桶边完成督导，再在此登记'

					: '先在现场完成巡检，再在此登记'

			},

			contentPlaceholder() {

				return String(this.form.type) === '4'

					? '请写本次督导做了什么：引导了几位居民、桶况如何、有无异常等'

					: '请写本次巡检做了什么：查看了哪些桶、有无错投、是否做了引导等'

			},

		},

		onLoad(option) {

			this.form.type = option.type

			const isType4 = String(option.type) === '4'

			uni.setNavigationBarTitle({

				title: isType4 ? '桶边督导' : '开始巡检',

			})

			this.initInspectCommunity()

		},

		methods: {

			saveLastCommunity() {

				if (!this.form.community_id) return

				try {

					uni.setStorageSync(DWXJ_COMMUNITY_STORAGE_KEY, {

						id: this.form.community_id,

						name: this.form.community_name,

					})

				} catch (e) {}

			},

			initInspectCommunity() {

				let picked = null

				try {

					picked = uni.getStorageSync(DWXJ_COMMUNITY_STORAGE_KEY)

				} catch (e) {}

				if (picked && picked.id) {

					this.form.community_id = picked.id

					this.form.community_name = picked.name || ''

				} else if (this.userInfo && this.userInfo.community_id) {

					this.form.community_id = this.userInfo.community_id

					this.form.community_name = this.userInfo.community || ''

				}

				if (this.form.community_id) {

					this.loadPointList(this.form.community_id)

				}

			},

			openCommunityPicker() {

				this.showCommunityDropdown = true

				this.communityKeyword = ''

				this.communityPage = 1

				this.communityResults = []

				this.loadCommunityList('', true)

			},

			loadCommunityList(keyword, isReset = false) {

				if (this.communityLoading) return

				this.communityLoading = true

				this.$u.api.search_community({ keyword, page: isReset ? 1 : this.communityPage, page_size: 20 }).then(res => {

					const data = res.data || {}

					const list = data.data || []

					if (isReset) {

						this.communityResults = list

						this.communityPage = 1

					} else {

						this.communityResults = this.communityResults.concat(list)

					}

					this.communityLastPage = data.last_page || 1

				}).catch(() => {}).finally(() => {

					this.communityLoading = false

				})

			},

			loadMoreCommunity() {

				if (this.communityLoading || this.communityNoMore) return

				this.communityPage++

				this.loadCommunityList(this.communityKeyword.trim(), false)

			},

			onCommunitySearch(value) {

				if (this.communitySearchTimer) clearTimeout(this.communitySearchTimer)

				const keyword = (value || '').trim()

				this.communitySearchTimer = setTimeout(() => {

					this.loadCommunityList(keyword, true)

				}, 300)

			},

			selectCommunity(item) {

				if (!item || !item.id) return

				this.form.community_id = item.id

				this.form.community_name = item.name

				this.form.cate_id = 0

				this.form.cate_name = ''

				this.showCommunityDropdown = false

				this.loadPointList(item.id)

			},

			openPointPicker() {

				if (!this.form.community_id) {

					uni.showToast({ title: '请先选择工作小区', icon: 'none' })

					this.openCommunityPicker()

					return

				}

				if (!this.cate_list.length) {

					this.loadPointList(this.form.community_id, () => {

						if (!this.cate_list.length) {

							uni.showToast({ title: '该小区暂无点位', icon: 'none' })

							return

						}

						this.cate_show = true

					})

					return

				}

				this.cate_show = true

			},

			loadPointList(communityId, done) {

				this.$u.api.get_community_cate_list({ community_id: communityId }).then(res => {

					this.cate_list = res.data || []

					if (typeof done === 'function') done()

				}).catch(() => {

					if (typeof done === 'function') done()

				})

			},

			setHasRectify(val) {

				this.form.has_rectify = val

				if (val === 0) {

					this.form.rectify_remark = ''

				}

			},

			buildSubmitContent() {

				const hygieneMap = { tidy: '整洁', average: '一般', poor: '较差' }

				const lines = [

					`现场整洁：${hygieneMap[this.form.site_hygiene] || this.form.site_hygiene}`,

					`整改意见：${this.form.has_rectify === 1 ? '有' : '无'}`,

				]

				if (this.form.has_rectify === 1 && this.form.rectify_remark) {

					lines.push(`整改说明：${this.form.rectify_remark.trim()}`)

				}

				const work = (this.form.content || '').trim()

				if (work) {

					lines.push('')

					lines.push('工作情况：')

					lines.push(work)

				}

				return lines.join('\n')

			},

			firstInspectPhotoUrl() {

				const keys = ['image_one', 'image_two', 'image_three']

				for (let i = 0; i < keys.length; i++) {

					const k = keys[i]

					if (this.form[k]) {

						return this.photoPreviewUrl(k)

					}

				}

				return ''

			},

			submitShotReportIfNeeded() {

				if (this.form.has_rectify !== 1) {

					return Promise.resolve()

				}

				const remark = (this.form.rectify_remark || '').trim()

				const image = this.firstInspectPhotoUrl()

				if (!remark || !image) {

					return Promise.resolve()

				}

				const prefix = String(this.form.type) === '4' ? '【桶边督导整改意见】' : '【点位巡检整改意见】'

				const loc = [this.form.community_name, this.form.cate_name].filter(Boolean).join('·')

				return this.$u.api.postShotReport({

					image,

					remark: `${prefix}${loc ? '（' + loc + '）' : ''} ${remark}`,

					location_type: 1,

					community_id: this.form.community_id,

					community_name: this.form.community_name,

					point_id: this.form.cate_id,

					point_name: this.form.cate_name,

				}).catch(() => {

					uni.showToast({ title: '工作记录已提交，随手拍同步失败请稍后在问题反馈补报', icon: 'none', duration: 3000 })

				})

			},

			photoPreviewUrl(key) {

				if (this.photoShowUrl[key]) return this.photoShowUrl[key]

				const path = this.form[key]

				if (!path) return ''

				if (/^https?:\/\//i.test(path)) return path

				return (this.imgUrl || '') + path

			},

			onPhotoSlotTap(key) {

				if (this.form[key]) {

					this.previewPhotos(key)

					return

				}

				this.choosePhoto(key)

			},

			choosePhoto(key) {

				uni.chooseImage({

					count: 1,

					sourceType: ['camera', 'album'],

					success: (res) => {

						const path = (res.tempFilePaths || [])[0]

						if (path) this.uploadPhoto(path, key)

					},

				})

			},

			uploadPhoto(filePath, key) {

				uni.showLoading({ title: '上传中...' })

				uni.uploadFile({

					url: this.uploadUrl,

					filePath,

					name: 'file',

					formData: { filetype: 'image' },

					success: (uploadRes) => {

						try {

							const data = JSON.parse(uploadRes.data)

							if (data.code == 1 && data.data) {

								const filepath = data.data.filepath || data.data.url || ''

								const showUrl = data.data.url || this.photoPreviewUrlFromPath(filepath)

								if (!filepath) {

									uni.showToast({ title: '上传失败', icon: 'none' })

									return

								}

								this.form[key] = filepath

								this.$set(this.photoShowUrl, key, showUrl || filepath)

							} else {

								uni.showToast({ title: data.msg || '上传失败', icon: 'none' })

							}

						} catch (e) {

							uni.showToast({ title: '上传失败', icon: 'none' })

						}

					},

					fail: () => uni.showToast({ title: '上传失败', icon: 'none' }),

					complete: () => uni.hideLoading(),

				})

			},

			photoPreviewUrlFromPath(path) {

				if (!path) return ''

				if (/^https?:\/\//i.test(path)) return path

				return (this.imgUrl || '') + path

			},

			clearPhoto(key) {

				this.form[key] = ''

				this.$set(this.photoShowUrl, key, '')

			},

			previewPhotos(currentKey) {

				const keys = ['image_one', 'image_two', 'image_three']

				const urls = keys.filter(k => this.form[k]).map(k => this.photoPreviewUrl(k))

				if (!urls.length) return

				uni.previewImage({

					urls,

					current: this.photoPreviewUrl(currentKey) || urls[0],

				})

			},

			confirm(e) {

				this.form.cate_id = e[0].value

				this.form.cate_name = e[0].label

			},

			goBack() {

				this.tipShow = false

				this.saveLastCommunity()

				uni.redirectTo({

					url: `/pages/zzhd/dwxj/record?type=${this.form.type}`,

				})

			},

			submit() {

				if (this.form.has_rectify === 1) {

					if (!(this.form.rectify_remark || '').trim()) {

						uni.showToast({ title: '请填写整改意见', icon: 'none' })

						return

					}

					if (!this.firstInspectPhotoUrl()) {

						uni.showToast({ title: '同步随手拍需至少上传一张留证照片', icon: 'none' })

						return

					}

				}

				this.$refs.uForm.validate(valid => {

					if (!valid) return

					const payload = {

						...this.form,

						content: this.buildSubmitContent(),

						inspect_community_id: this.form.community_id,

					}

					this.$u.api.add_forum(payload).then(res => {

						this.saveLastCommunity()

						return this.submitShotReportIfNeeded().then(() => {

							this.point = res.data.point

							this.showType = 1

							this.tipShow = true

						})

					}).catch(res => {

						if (res && res.data && res.data.remaining_seconds) {

							this.remaining_seconds = res.data.remaining_seconds

							this.showType = 2

							this.tipShow = true

						}

					})

				})

			},

		},

		onReady() {

			this.$refs.uForm.setRules(this.rules)

		},

	}

</script>



<style lang="scss" scoped>

.add-page {

	min-height: 100vh;

	background: #eef2f0;

	padding-bottom: calc(220rpx + env(safe-area-inset-bottom));

}

.add-hero {

	padding: 32rpx 32rpx 36rpx;

	background: linear-gradient(160deg, #2fb74c 0%, #1a9a3e 50%, #d8eedc 100%);

}

.add-hero-title {

	display: block;

	font-size: 40rpx;

	font-weight: 800;

	color: #fff;

}

.add-hero-sub {

	display: block;

	margin-top: 10rpx;

	font-size: 28rpx;

	color: rgba(255, 255, 255, 0.95);

	line-height: 1.45;

}

.add-tip-bar {

	margin-top: 20rpx;

	padding: 16rpx 20rpx;

	background: rgba(255, 255, 255, 0.25);

	border-radius: 12rpx;

}

.add-tip-text {

	font-size: 24rpx;

	color: #fff;

	line-height: 1.55;

}

.add-steps {

	margin-top: 20rpx;

	padding: 20rpx 16rpx;

	background: rgba(255, 255, 255, 0.2);

	border-radius: 16rpx;

}

.add-step {

	flex: 1;

	display: flex;

	flex-direction: column;

	align-items: center;

}

.add-step-dot {

	width: 44rpx;

	height: 44rpx;

	line-height: 44rpx;

	text-align: center;

	border-radius: 50%;

	background: #fff;

	color: #18af38;

	font-size: 24rpx;

	font-weight: 800;

}

.add-step-lab {

	margin-top: 8rpx;

	font-size: 22rpx;

	color: #fff;

}

.add-body {

	margin-top: -20rpx;

	padding: 0 24rpx;

	position: relative;

	z-index: 2;

}

.add-card {

	background: #fff;

	border-radius: 20rpx;

	padding: 8rpx 24rpx 24rpx;

	box-shadow: 0 8rpx 28rpx rgba(20, 40, 28, 0.07);

	margin-bottom: 24rpx;

}

.add-card-head {

	padding: 20rpx 0 12rpx;

	border-bottom: 1rpx solid #f0f2f0;

	margin-bottom: 16rpx;

}

.add-card-title {

	font-size: 30rpx;

	font-weight: 700;

	color: #243028;

}

.add-card-sub {

	display: block;

	margin-top: 6rpx;

	font-size: 24rpx;

	color: #9aa59d;

	line-height: 1.45;

}

.add-photo-card .add-card-head {

	margin-bottom: 20rpx;

}

.photo-row {

	align-items: flex-start;

}

.photo-col {

	flex: 1;

	min-width: 0;

	display: flex;

	flex-direction: column;

	align-items: center;

	padding: 0 6rpx;

}

.photo-slot {

	position: relative;

	width: 100%;

	height: 200rpx;

	border-radius: 16rpx;

	overflow: hidden;

	background: #f7faf8;

	border: 2rpx dashed #c5d0c8;

	box-sizing: border-box;

}

.photo-slot--press {

	opacity: 0.92;

}

.photo-slot-img {

	width: 100%;

	height: 100%;

	display: block;

}

.photo-slot-empty {

	width: 100%;

	height: 100%;

	display: flex;

	flex-direction: column;

	align-items: center;

	justify-content: center;

}

.photo-slot-add {

	margin-top: 8rpx;

	font-size: 22rpx;

	color: #18af38;

}

.photo-del {

	position: absolute;

	top: 0;

	right: 0;

	width: 48rpx;

	height: 48rpx;

	display: flex;

	align-items: center;

	justify-content: center;

	background: rgba(0, 0, 0, 0.55);

	border-radius: 0 0 0 12rpx;

	z-index: 2;

}

.photo-col-label {

	margin-top: 12rpx;

	font-size: 22rpx;

	color: #5a665e;

	text-align: center;

	line-height: 1.35;

	width: 100%;

}

.add-footer {

	position: fixed;

	left: 0;

	right: 0;

	bottom: 0;

	padding: 20rpx 32rpx calc(16rpx + env(safe-area-inset-bottom));

	background: #fff;

	box-shadow: 0 -4rpx 24rpx rgba(20, 40, 28, 0.06);

	z-index: 20;

}

.add-submit-btn {

	height: 92rpx;

	line-height: 92rpx;

	text-align: center;

	border-radius: 999rpx;

	font-size: 32rpx;

	font-weight: 700;

	color: #fff;

	background: linear-gradient(141deg, #a4db88 0%, #18af38 100%);

	box-shadow: 0 10rpx 28rpx rgba(24, 175, 56, 0.3);

}

.add-submit-btn--press {

	opacity: 0.92;

}

.add-record-link {

	margin-top: 16rpx;

	text-align: center;

	font-size: 28rpx;

	color: #18af38;

}

.inspect-field {

	padding: 20rpx 0;

	border-bottom: 1rpx solid #f0f2f0;

}

.inspect-field-last {

	border-bottom: none;

	padding-bottom: 8rpx;

}

.inspect-field-label {

	display: block;

	font-size: 28rpx;

	color: #243028;

	margin-bottom: 16rpx;

}

.seg-group {

	flex-wrap: wrap;

	gap: 16rpx;

}

.seg-item {

	padding: 12rpx 28rpx;

	font-size: 26rpx;

	color: #666;

	background: #f5f5f5;

	border-radius: 8rpx;

	border: 2rpx solid transparent;

}

.seg-item.active {

	color: #18af38;

	background: #e8f8ec;

	border-color: #18af38;

	font-weight: 600;

}

.seg-item-warn.active {

	color: #e65100;

	background: #fff3e0;

	border-color: #ff9800;

}

.field-hint {

	margin-top: 12rpx;

	font-size: 22rpx;

	color: #9aa59d;

	line-height: 1.45;

}

.community-dropdown-mask {

	position: fixed;

	left: 0;

	right: 0;

	top: 0;

	bottom: 0;

	background-color: rgba(0, 0, 0, 0.35);

	z-index: 999;

	display: flex;

	align-items: flex-start;

	justify-content: center;

}

.community-dropdown {

	margin-top: 200rpx;

	width: 690rpx;

	background-color: #fff;

	border-radius: 16rpx;

	padding: 24rpx;

	box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.12);

}

.community-dropdown-list {

	max-height: 560rpx;

	margin-top: 16rpx;

}

.community-dropdown-item {

	border-bottom: 1rpx solid #f0f0f0;

}

.community-dropdown-item:last-child {

	border-bottom: none;

}

</style>


