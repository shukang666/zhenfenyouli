<template>
	<view class="u-p-30">
		<u-navbar title-color="#fff" title="督导监测" :is-back="true" back-icon-color="#fff" background="transparent" :borderBottom="false" :custom-back="customBack">
		</u-navbar>
		<view class="u-abso u-h-552" style="z-index: -1;top: 0;left: 0;right: 0;background: linear-gradient(to bottom,#2fb74c,#EFF9F1);">
		</view>
		<view class="u-p-40 u-type-white-bg u-radius-6 u-m-t-30">
			<view class="u-text-center u-font-32 u-font-bold u-m-b-30">督导监测</view>
			<view class="u-font-28 u-type-info u-text-center u-m-b-30">
				厨余垃圾督导检查信息登记
			</view>

			<!-- 所属社区 -->
			<view class="u-m-t-30">
				<view class="u-font-28 u-m-b-16">所属社区 <text class="form-required">*</text></view>
				<view class="location-picker" @click="showCommunityPicker = true">
					<text v-if="form.community_name" class="location-text">{{ form.community_name }}</text>
					<text v-else class="location-placeholder">请选择所属社区</text>
					<u-icon name="arrow-right" size="24" color="#999"></u-icon>
				</view>
			</view>

			<!-- 单位名称 -->
			<view class="u-m-t-30">
				<view class="u-font-28 u-m-b-16">单位名称 <text class="form-required">*</text></view>
				<input
					class="merchant-input"
					v-model="form.unit_name"
					placeholder="请输入单位名称"
					placeholder-style="color:#ccc;font-size:26rpx;"
					maxlength="50"
				/>
			</view>

			<!-- 单位类型 -->
			<view class="u-m-t-30">
				<view class="u-font-28 u-m-b-16">单位类型 <text class="form-required">*</text></view>
				<view class="u-flex u-flex-wrap">
					<view class="u-m-r-20 u-m-b-16" v-for="item in unitTypes" :key="item.value" @click="form.unit_type = item.value">
						<u-tag :text="item.label" :mode="form.unit_type == item.value ? 'dark' : 'plain'" :type="form.unit_type == item.value ? 'success' : 'info'" />
					</view>
				</view>
			</view>

			<!-- 日期 -->
			<view class="u-m-t-30">
				<view class="u-font-28 u-m-b-16">日期 <text class="form-required">*</text></view>
				<picker mode="date" :value="form.check_date" @change="onDateChange">
					<view class="location-picker">
						<text v-if="form.check_date" class="location-text">{{ form.check_date }}</text>
						<text v-else class="location-placeholder">请选择日期</text>
						<u-icon name="calendar" size="28" color="#999"></u-icon>
					</view>
				</picker>
			</view>

			<!-- 地址 -->
			<view class="u-m-t-30">
				<view class="u-font-28 u-m-b-16">地址 <text class="form-required">*</text></view>
				<input
					class="merchant-input"
					v-model="form.address"
					placeholder="请输入地址"
					placeholder-style="color:#ccc;font-size:26rpx;"
					maxlength="100"
				/>
			</view>

			<!-- 联系人 -->
			<view class="u-m-t-30">
				<view class="u-font-28 u-m-b-16">联系人 <text class="form-required">*</text></view>
				<input
					class="merchant-input"
					v-model="form.contact"
					placeholder="请输入联系人"
					placeholder-style="color:#ccc;font-size:26rpx;"
					maxlength="20"
				/>
			</view>

			<!-- 联系电话 -->
			<view class="u-m-t-30">
				<view class="u-font-28 u-m-b-16">联系电话 <text class="form-required">*</text></view>
				<input
					class="merchant-input"
					v-model="form.contact_phone"
					type="number"
					placeholder="请输入联系电话"
					placeholder-style="color:#ccc;font-size:26rpx;"
					maxlength="11"
				/>
			</view>

			<!-- 是否产生厨余垃圾 -->
			<view class="u-m-t-30">
				<view class="u-font-28 u-m-b-16">是否产生厨余垃圾 <text class="form-required">*</text></view>
				<view class="u-flex">
					<view class="u-m-r-20" @click="form.has_waste = 1">
						<u-tag text="是" :mode="form.has_waste === 1 ? 'dark' : 'plain'" :type="form.has_waste === 1 ? 'success' : 'info'" />
					</view>
					<view @click="form.has_waste = 0">
						<u-tag text="否" :mode="form.has_waste === 0 && form.has_waste !== null ? 'dark' : 'plain'" :type="form.has_waste === 0 && form.has_waste !== null ? 'error' : 'info'" />
					</view>
				</view>
			</view>

			<!-- 厨余垃圾是否正确分出 -->
			<view class="u-m-t-30">
				<view class="u-font-28 u-m-b-16">厨余垃圾是否正确分出 <text class="form-required">*</text></view>
				<view class="u-flex">
					<view class="u-m-r-20" @click="form.waste_correct = 1">
						<u-tag text="是" :mode="form.waste_correct === 1 ? 'dark' : 'plain'" :type="form.waste_correct === 1 ? 'success' : 'info'" />
					</view>
					<view @click="form.waste_correct = 0">
						<u-tag text="否" :mode="form.waste_correct === 0 && form.waste_correct !== null ? 'dark' : 'plain'" :type="form.waste_correct === 0 && form.waste_correct !== null ? 'error' : 'info'" />
					</view>
				</view>
			</view>

			<!-- 厨余垃圾流向 -->
			<view class="u-m-t-30">
				<view class="u-font-28 u-m-b-16">厨余垃圾流向</view>
				<input
					class="merchant-input"
					v-model="form.waste_flow"
					placeholder="请输入厨余垃圾流向"
					placeholder-style="color:#ccc;font-size:26rpx;"
					maxlength="100"
				/>
			</view>

			<!-- 基础照片 -->
			<view class="u-m-t-40">
				<view class="u-font-28 u-m-b-16">门头照片</view>
				<view class="upload-area" @click="chooseImage('door_photo')">
					<view v-if="!form.door_photo" class="upload-placeholder">
						<u-icon name="camera" size="50" color="#ccc"></u-icon>
						<view class="u-m-t-10 u-font-24 u-type-info">点击拍照或选择图片</view>
					</view>
					<image v-else :src="form.door_photo" mode="aspectFill" class="upload-image"></image>
				</view>
			</view>

			<view class="u-m-t-30">
				<view class="u-font-28 u-m-b-16">内部环境照片</view>
				<view class="upload-area" @click="chooseImage('inner_photo')">
					<view v-if="!form.inner_photo" class="upload-placeholder">
						<u-icon name="camera" size="50" color="#ccc"></u-icon>
						<view class="u-m-t-10 u-font-24 u-type-info">点击拍照或选择图片</view>
					</view>
					<image v-else :src="form.inner_photo" mode="aspectFill" class="upload-image"></image>
				</view>
			</view>

			<view class="u-m-t-30">
				<view class="u-font-28 u-m-b-16">宣传品照片</view>
				<view class="upload-area" @click="chooseImage('publicity_photo')">
					<view v-if="!form.publicity_photo" class="upload-placeholder">
						<u-icon name="camera" size="50" color="#ccc"></u-icon>
						<view class="u-m-t-10 u-font-24 u-type-info">点击拍照或选择图片</view>
					</view>
					<image v-else :src="form.publicity_photo" mode="aspectFill" class="upload-image"></image>
				</view>
			</view>

			<!-- 内部环境检查 -->
			<view class="u-m-t-40 u-p-24 check-group">
				<view class="u-font-30 u-font-bold u-m-b-20">内部环境检查</view>
				<view class="u-font-28 u-m-b-16">是否合格</view>
				<view class="u-flex">
					<view class="u-m-r-20" @click="form.inner_qualified = 1">
						<u-tag text="是" :mode="form.inner_qualified === 1 ? 'dark' : 'plain'" :type="form.inner_qualified === 1 ? 'success' : 'info'" />
					</view>
					<view @click="form.inner_qualified = 0">
						<u-tag text="否" :mode="form.inner_qualified === 0 && form.inner_qualified !== null ? 'dark' : 'plain'" :type="form.inner_qualified === 0 && form.inner_qualified !== null ? 'error' : 'info'" />
					</view>
				</view>
				<view class="u-font-28 u-m-b-16 u-m-t-20">是否需要整改</view>
				<view class="u-flex">
					<view class="u-m-r-20" @click="form.need_rectify = 1">
						<u-tag text="是" :mode="form.need_rectify === 1 ? 'dark' : 'plain'" :type="form.need_rectify === 1 ? 'success' : 'info'" />
					</view>
					<view @click="form.need_rectify = 0">
						<u-tag text="否" :mode="form.need_rectify === 0 && form.need_rectify !== null ? 'dark' : 'plain'" :type="form.need_rectify === 0 && form.need_rectify !== null ? 'error' : 'info'" />
					</view>
				</view>
				<view class="u-font-28 u-m-b-16 u-m-t-20">整改后照片</view>
				<view class="upload-area" @click="chooseImage('inner_rectify_photo')">
					<view v-if="!form.inner_rectify_photo" class="upload-placeholder">
						<u-icon name="camera" size="50" color="#ccc"></u-icon>
						<view class="u-m-t-10 u-font-24 u-type-info">点击拍照或选择图片</view>
					</view>
					<image v-else :src="form.inner_rectify_photo" mode="aspectFill" class="upload-image"></image>
				</view>
			</view>

			<!-- 投放点检查 -->
			<view class="u-m-t-30 u-p-24 check-group">
				<view class="u-font-30 u-font-bold u-m-b-20">投放点检查</view>
				<view class="u-font-28 u-m-b-16">投放点照片</view>
				<view class="upload-area" @click="chooseImage('point_photo')">
					<view v-if="!form.point_photo" class="upload-placeholder">
						<u-icon name="camera" size="50" color="#ccc"></u-icon>
						<view class="u-m-t-10 u-font-24 u-type-info">点击拍照或选择图片</view>
					</view>
					<image v-else :src="form.point_photo" mode="aspectFill" class="upload-image"></image>
				</view>
				<view class="u-font-28 u-m-b-16 u-m-t-20">是否合格</view>
				<view class="u-flex">
					<view class="u-m-r-20" @click="form.point_qualified = 1">
						<u-tag text="是" :mode="form.point_qualified === 1 ? 'dark' : 'plain'" :type="form.point_qualified === 1 ? 'success' : 'info'" />
					</view>
					<view @click="form.point_qualified = 0">
						<u-tag text="否" :mode="form.point_qualified === 0 && form.point_qualified !== null ? 'dark' : 'plain'" :type="form.point_qualified === 0 && form.point_qualified !== null ? 'error' : 'info'" />
					</view>
				</view>
				<view class="u-font-28 u-m-b-16 u-m-t-20">整改后照片</view>
				<view class="upload-area" @click="chooseImage('point_rectify_photo')">
					<view v-if="!form.point_rectify_photo" class="upload-placeholder">
						<u-icon name="camera" size="50" color="#ccc"></u-icon>
						<view class="u-m-t-10 u-font-24 u-type-info">点击拍照或选择图片</view>
					</view>
					<image v-else :src="form.point_rectify_photo" mode="aspectFill" class="upload-image"></image>
				</view>
			</view>

			<!-- 点位管理检查 -->
			<view class="u-m-t-30 u-p-24 check-group">
				<view class="u-font-30 u-font-bold u-m-b-20">点位管理检查</view>
				<view class="u-font-28 u-m-b-16">点位管理照片</view>
				<view class="upload-area" @click="chooseImage('manage_photo')">
					<view v-if="!form.manage_photo" class="upload-placeholder">
						<u-icon name="camera" size="50" color="#ccc"></u-icon>
						<view class="u-m-t-10 u-font-24 u-type-info">点击拍照或选择图片</view>
					</view>
					<image v-else :src="form.manage_photo" mode="aspectFill" class="upload-image"></image>
				</view>
				<view class="u-font-28 u-m-b-16 u-m-t-20">是否合格</view>
				<view class="u-flex">
					<view class="u-m-r-20" @click="form.manage_qualified = 1">
						<u-tag text="是" :mode="form.manage_qualified === 1 ? 'dark' : 'plain'" :type="form.manage_qualified === 1 ? 'success' : 'info'" />
					</view>
					<view @click="form.manage_qualified = 0">
						<u-tag text="否" :mode="form.manage_qualified === 0 && form.manage_qualified !== null ? 'dark' : 'plain'" :type="form.manage_qualified === 0 && form.manage_qualified !== null ? 'error' : 'info'" />
					</view>
				</view>
				<view class="u-font-28 u-m-b-16 u-m-t-20">整改后照片</view>
				<view class="upload-area" @click="chooseImage('manage_rectify_photo')">
					<view v-if="!form.manage_rectify_photo" class="upload-placeholder">
						<u-icon name="camera" size="50" color="#ccc"></u-icon>
						<view class="u-m-t-10 u-font-24 u-type-info">点击拍照或选择图片</view>
					</view>
					<image v-else :src="form.manage_rectify_photo" mode="aspectFill" class="upload-image"></image>
				</view>
			</view>

			<!-- 其他照片 -->
			<view class="u-m-t-30">
				<view class="u-font-28 u-m-b-16">收运链条照片</view>
				<view class="upload-area" @click="chooseImage('transport_photo')">
					<view v-if="!form.transport_photo" class="upload-placeholder">
						<u-icon name="camera" size="50" color="#ccc"></u-icon>
						<view class="u-m-t-10 u-font-24 u-type-info">点击拍照或选择图片</view>
					</view>
					<image v-else :src="form.transport_photo" mode="aspectFill" class="upload-image"></image>
				</view>
			</view>

			<view class="u-m-t-30">
				<view class="u-font-28 u-m-b-16">餐饮企业相关宣传</view>
				<view class="upload-area" @click="chooseImage('catering_photo')">
					<view v-if="!form.catering_photo" class="upload-placeholder">
						<u-icon name="camera" size="50" color="#ccc"></u-icon>
						<view class="u-m-t-10 u-font-24 u-type-info">点击拍照或选择图片</view>
					</view>
					<image v-else :src="form.catering_photo" mode="aspectFill" class="upload-image"></image>
				</view>
			</view>

			<view class="u-m-t-30">
				<view class="u-font-28 u-m-b-16">准确率照片</view>
				<view class="upload-area" @click="chooseImage('accuracy_photo')">
					<view v-if="!form.accuracy_photo" class="upload-placeholder">
						<u-icon name="camera" size="50" color="#ccc"></u-icon>
						<view class="u-m-t-10 u-font-24 u-type-info">点击拍照或选择图片</view>
					</view>
					<image v-else :src="form.accuracy_photo" mode="aspectFill" class="upload-image"></image>
				</view>
			</view>

			<!-- 总体评价 -->
			<view class="u-m-t-30">
				<view class="u-font-28 u-m-b-16">总体评价</view>
				<textarea
					class="remark-textarea"
					v-model="form.overall_evaluation"
					placeholder="请输入总体评价"
					placeholder-style="color:#ccc;font-size:24rpx;"
					maxlength="500"
				></textarea>
			</view>

			<!-- 未审核通过提示 -->
			<view class="u-m-t-30" v-if="!can_submit">
				<view class="u-font-24" style="color:#ff9900;text-align:center;">您的指导员身份尚未审核通过，暂不能提交</view>
			</view>

			<!-- 提交按钮 -->
			<view class="u-m-t-30 u-m-b-40">
				<u-button
					type="primary"
					shape="circle"
					hover-class="none"
					:disabled="!canSubmit || !can_submit"
					@click="submit"
					:customStyle="{ background: 'linear-gradient(141deg, #A4DB88 0%, #18AF38 100%)', height: '90rpx', fontSize: '32rpx' }"
				>提交</u-button>
			</view>
		</view>

		<!-- 社区选择器 -->
		<u-select
			v-model="showCommunityPicker"
			label-name="name"
			value-name="id"
			:list="communityList"
			@confirm="onCommunityConfirm"
		></u-select>
	</view>
</template>

<script>
	import {
		uploadUrl
	} from '@/common/settings'
	export default {
		data() {
			return {
				can_submit: true,       // 权限：审核通过的指导员才能提交
				unitTypes: [
					{ value: 1, label: '公共机构' },
					{ value: 2, label: '经营场所' },
					{ value: 3, label: '沿街商铺' },
					{ value: 4, label: '经营区域' },
				],
				form: {
					community_id: 0,
					community_name: '',
					unit_name: '',
					unit_type: null,
					check_date: '',
					address: '',
					contact: '',
					contact_phone: '',
					has_waste: null,
					waste_correct: null,
					waste_flow: '',
					door_photo: '',
					inner_photo: '',
					publicity_photo: '',
					inner_qualified: null,
					need_rectify: null,
					inner_rectify_photo: '',
					point_photo: '',
					point_qualified: null,
					point_rectify_photo: '',
					manage_photo: '',
					manage_qualified: null,
					manage_rectify_photo: '',
					transport_photo: '',
					catering_photo: '',
					accuracy_photo: '',
					overall_evaluation: '',
				},
				communityList: [],          // 社区列表
				showCommunityPicker: false,
				uploadUrl,
			};
		},
		computed: {
			canSubmit() {
				return this.form.community_id > 0
					&& this.form.unit_name.trim()
					&& this.form.unit_type !== null
					&& this.form.check_date
					&& this.form.address.trim()
					&& this.form.contact.trim()
					&& this.form.contact_phone.trim()
					&& this.form.has_waste !== null
					&& this.form.waste_correct !== null;
			}
		},
		onLoad(option) {
			if (option.can_submit !== undefined) {
				this.can_submit = option.can_submit == 1;
			}
			this.loadCommunityList();
		},
		methods: {
			customBack() {
				uni.navigateBack({
					fail() {
						uni.switchTab({
							url: '/pages/index/index'
						})
					}
				})
			},

			// 加载社区列表（现成接口）
			loadCommunityList() {
				this.$u.api.get_community_list().then(res => {
					this.communityList = res.data || [];
				}).catch(() => {
					this.communityList = [];
				});
			},

			// 社区选择确认
			onCommunityConfirm(e) {
				if (e && e.length) {
					this.form.community_id = e[0].value;
					this.form.community_name = e[0].label;
				}
			},

			// 日期选择确认
			onDateChange(e) {
				this.form.check_date = e.detail.value;
			},

			// 选择图片并上传（field 为表单字段名）
			chooseImage(field) {
				uni.chooseImage({
					count: 1,
					sourceType: ['camera', 'album'],
					success: (res) => {
						const tempFilePath = res.tempFilePaths[0];
						this.uploadImage(field, tempFilePath);
					}
				});
			},

			// 上传图片到服务器
			uploadImage(field, filePath) {
				uni.showLoading({ title: '上传中...' });
				uni.uploadFile({
					url: this.uploadUrl + '/wxapp/public/upload_asset',
					filePath: filePath,
					name: 'file',
					formData: {
						filetype: 'image'
					},
					success: (uploadRes) => {
						uni.hideLoading();
						try {
							const data = JSON.parse(uploadRes.data);
							if (data.code == 1) {
								this.form[field] = data.data.url;
								uni.showToast({ title: '上传成功', icon: 'success' });
							} else {
								uni.showToast({ title: data.msg || '上传失败', icon: 'none' });
							}
						} catch (e) {
							uni.showToast({ title: '上传失败', icon: 'none' });
						}
					},
					fail: () => {
						uni.hideLoading();
						uni.showToast({ title: '上传失败', icon: 'none' });
					}
				});
			},

			// 提交表单
			submit() {
				if (!this.form.community_id) {
					uni.showToast({ title: '请选择所属社区', icon: 'none' });
					return;
				}
				if (!this.form.unit_name.trim()) {
					uni.showToast({ title: '请填写单位名称', icon: 'none' });
					return;
				}
				if (this.form.unit_type === null) {
					uni.showToast({ title: '请选择单位类型', icon: 'none' });
					return;
				}
				if (!this.form.check_date) {
					uni.showToast({ title: '请选择日期', icon: 'none' });
					return;
				}
				if (!this.form.address.trim()) {
					uni.showToast({ title: '请填写地址', icon: 'none' });
					return;
				}
				if (!this.form.contact.trim()) {
					uni.showToast({ title: '请填写联系人', icon: 'none' });
					return;
				}
				if (!this.form.contact_phone.trim()) {
					uni.showToast({ title: '请填写联系电话', icon: 'none' });
					return;
				}
				if (this.form.has_waste === null) {
					uni.showToast({ title: '请选择是否产生厨余垃圾', icon: 'none' });
					return;
				}
				if (this.form.waste_correct === null) {
					uni.showToast({ title: '请选择厨余垃圾是否正确分出', icon: 'none' });
					return;
				}

				uni.showLoading({ title: '提交中...' });
				this.$u.api.postSupervisionMonitor({
					...this.form
				}).then(res => {
					uni.hideLoading();
					uni.showToast({
						title: res.msg || '已提交',
						icon: 'none',
						duration: 2000
					});
					setTimeout(() => {
						uni.navigateBack();
					}, 2000);
				}).catch(err => {
					uni.hideLoading();
					uni.showToast({ title: (err && err.msg) || '提交失败，请重试', icon: 'none' });
				});
			}
		}
	}
</script>

<style lang="scss">
	/* ===== 下拉/日期选择器 ===== */
	.location-picker {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 20rpx 24rpx;
		background: #F5F5F5;
		border-radius: 12rpx;
		border: 2rpx solid #eee;
	}

	.location-text {
		font-size: 28rpx;
		color: #333;
		flex: 1;
	}

	.location-placeholder {
		font-size: 28rpx;
		color: #ccc;
		flex: 1;
	}

	/* ===== 输入框 ===== */
	.merchant-input {
		width: 100%;
		padding: 20rpx 24rpx;
		background: #F5F5F5;
		border-radius: 12rpx;
		font-size: 28rpx;
		color: #333;
		border: 2rpx solid #eee;
		box-sizing: border-box;
	}

	/* ===== 照片上传区 ===== */
	.upload-area {
		width: 100%;
		height: 240rpx;
		background: #f5f5f5;
		border-radius: 12rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		border: 2rpx dashed #ddd;
		position: relative;
		overflow: hidden;

		.upload-placeholder {
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
		}

		.upload-image {
			width: 100%;
			height: 240rpx;
			border-radius: 12rpx;
		}
	}

	/* ===== 检查分组 ===== */
	.check-group {
		background: #FAFBFC;
		border-radius: 12rpx;
		border: 1rpx solid #E4E6EB;
	}

	/* ===== 文本域 ===== */
	.remark-textarea {
		width: 100%;
		min-height: 200rpx;
		background: #f5f5f5;
		border-radius: 12rpx;
		padding: 20rpx;
		font-size: 26rpx;
		color: #333;
		border: 2rpx solid #eee;
		box-sizing: border-box;
	}

	/* ===== 必填星号 ===== */
	.form-required {
		color: #fa3534;
		margin-left: 4rpx;
	}
</style>
