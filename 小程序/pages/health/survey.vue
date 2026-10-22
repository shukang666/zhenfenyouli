<template>
	<view class="hs-page">
		<view class="hs-head" v-if="survey.title">
			<text class="hs-title">{{ survey.title }}</text>
			<text class="hs-desc" v-if="survey.description">{{ survey.description }}</text>
		</view>

		<view v-if="submitted && !survey.allow_repeat" class="hs-done">
			<u-icon name="checkmark-circle-fill" size="80" color="#18AF38"></u-icon>
			<text class="hs-done-t">您已提交过本问卷</text>
			<u-button type="primary" hover-class="none" @click="goBack">返回</u-button>
		</view>

		<view v-else class="hs-form u-p-30">
			<view class="hs-field" v-for="field in fields" :key="field.field_key">
				<view class="hs-label">
					<text class="hs-label-t">{{ field.label }}</text>
					<text class="hs-req" v-if="field.required">*</text>
				</view>

				<block v-if="field.field_type === 'textarea'">
					<u-input v-model="form[field.field_key]" type="textarea" :height="160" :placeholder="field.placeholder || '请输入'" />
				</block>
				<block v-else-if="field.field_type === 'radio'">
					<u-radio-group v-model="form[field.field_key]" wrap>
						<u-radio v-for="(opt, oi) in field.options" :key="oi" :name="opt" active-color="#3974f2">{{ opt }}</u-radio>
					</u-radio-group>
				</block>
				<block v-else-if="field.field_type === 'checkbox'">
					<u-checkbox-group wrap @change="onCheckboxChange(field.field_key, $event)">
						<u-checkbox v-for="(opt, oi) in field.options" :key="oi" :name="opt" v-model="checkboxMap[field.field_key + '_' + oi]" active-color="#3974f2">{{ opt }}</u-checkbox>
					</u-checkbox-group>
				</block>
				<block v-else-if="field.field_type === 'select'">
					<u-input type="select" :select-open="selectOpenKey === field.field_key" v-model="form[field.field_key]" :placeholder="field.placeholder || '请选择'" @click="openSelect(field)" />
				</block>
				<block v-else-if="field.field_type === 'number'">
					<u-input v-model="form[field.field_key]" type="number" :placeholder="field.placeholder || '请输入数字'" />
				</block>
				<block v-else-if="field.field_type === 'date'">
					<u-input type="select" :select-open="pickerOpenKey === field.field_key" v-model="form[field.field_key]" placeholder="请选择日期" @click="openDate(field)" />
				</block>
				<block v-else-if="field.field_type === 'rate'">
					<u-rate v-model="form[field.field_key]" :count="5" active-color="#3974f2"></u-rate>
				</block>
				<block v-else>
					<u-input v-model="form[field.field_key]" :placeholder="field.placeholder || '请输入'" />
				</block>
			</view>

			<view class="hs-submit">
				<u-button type="primary" :loading="submitting" hover-class="none" @click="submit">提交</u-button>
			</view>
		</view>

		<u-select v-model="selectShow" mode="single-column" :list="selectList" @confirm="onSelectConfirm"></u-select>
		<u-picker v-model="dateShow" mode="time" :params="dateParams" @confirm="onDateConfirm"></u-picker>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				surveyId: 0,
				survey: {},
				fields: [],
				form: {},
				checkboxMap: {},
				submitted: false,
				submitting: false,
				selectShow: false,
				selectList: [],
				selectOpenKey: '',
				selectFieldKey: '',
				dateShow: false,
				pickerOpenKey: '',
				dateFieldKey: '',
				dateParams: { year: true, month: true, day: true, hour: false, minute: false, second: false }
			}
		},
		onLoad(option) {
			this.surveyId = parseInt(option.id, 10) || 0
			if (this.surveyId <= 0) {
				return this.$u.toast('参数错误')
			}
			this.loadDetail()
		},
		methods: {
			loadDetail() {
				this.$u.api.health_survey_detail({ id: this.surveyId }).then(res => {
					const d = res.data || {}
					this.survey = d
					this.fields = d.fields || []
					this.submitted = !!d.has_submitted
					const form = {}
					this.fields.forEach(f => {
						if (f.field_type === 'checkbox') {
							form[f.field_key] = []
						} else if (f.field_type === 'rate') {
							form[f.field_key] = 0
						} else {
							form[f.field_key] = ''
						}
					})
					this.form = form
				}).catch(err => {
					this.$u.toast(err.msg || '加载失败')
				})
			},
			onCheckboxChange(key, names) {
				this.form[key] = names || []
			},
			openSelect(field) {
				this.selectFieldKey = field.field_key
				this.selectOpenKey = field.field_key
				this.selectList = (field.options || []).map(t => ({ label: t, value: t }))
				this.selectShow = true
			},
			onSelectConfirm(e) {
				const item = e[0]
				if (item) {
					this.form[this.selectFieldKey] = item.value
				}
				this.selectOpenKey = ''
			},
			openDate(field) {
				this.dateFieldKey = field.field_key
				this.pickerOpenKey = field.field_key
				this.dateShow = true
			},
			onDateConfirm(e) {
				const y = e.year
				const m = String(e.month).padStart(2, '0')
				const d = String(e.day).padStart(2, '0')
				this.form[this.dateFieldKey] = `${y}-${m}-${d}`
				this.pickerOpenKey = ''
			},
			submit() {
				if (this.submitting) return
				this.submitting = true
				this.$u.api.health_survey_submit({
					survey_id: this.surveyId,
					answers: JSON.stringify(this.form)
				}).then(() => {
					this.$u.toast('提交成功')
					setTimeout(() => {
						uni.navigateBack()
					}, 800)
				}).catch(err => {
					this.$u.toast(err.msg || '提交失败')
				}).finally(() => {
					this.submitting = false
				})
			},
			goBack() {
				uni.navigateBack()
			}
		}
	}
</script>

<style lang="scss">
page {
	background: #f5f7f6;
}
.hs-page {
	min-height: 100vh;
	padding-bottom: 60rpx;
}
.hs-head {
	padding: 40rpx 30rpx 20rpx;
	background: linear-gradient(180deg, #3974f2 0%, #f5f7f6 100%);
}
.hs-title {
	display: block;
	font-size: 36rpx;
	font-weight: 700;
	color: #fff;
}
.hs-desc {
	display: block;
	margin-top: 12rpx;
	font-size: 26rpx;
	color: rgba(255, 255, 255, 0.9);
	line-height: 1.5;
}
.hs-field {
	margin-bottom: 36rpx;
	padding: 24rpx;
	background: #fff;
	border-radius: 16rpx;
}
.hs-label {
	margin-bottom: 16rpx;
}
.hs-label-t {
	font-size: 30rpx;
	font-weight: 600;
	color: #1d1d1f;
}
.hs-req {
	color: #e54d42;
	margin-left: 6rpx;
}
.hs-submit {
	margin-top: 20rpx;
}
.hs-done {
	padding: 120rpx 60rpx;
	text-align: center;
}
.hs-done-t {
	display: block;
	margin: 30rpx 0 40rpx;
	font-size: 30rpx;
	color: #636366;
}
</style>
