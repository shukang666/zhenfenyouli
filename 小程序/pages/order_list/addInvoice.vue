<template>
	<view class="container">
		<view class="u-p-l-30 u-m-t-30 u-flex">
			<view class="u-w-200">订单总额</view>
			<view class="u-font-28 u-font-bold">￥{{amount}}</view>
		</view>

		<u-form :model="form" ref="uForm" label-width="200">
			<view class="u-p-lr-30 u-type-white-bg u-m-30 u-radius-10">
				<u-form-item label="发票类型">
					<u-radio-group v-model="form.invoiceKind">
						<u-radio name="82" active-color="#FF7218" icon-size="22">
							普通发票
						</u-radio>
						<u-radio name="81" active-color="#FF7218" icon-size="22">
							专用发票
						</u-radio>
					</u-radio-group>
				</u-form-item>
				<u-form-item label="抬头类型">
					<u-radio-group v-model="form.type" @change="typeChange">
						<u-radio name="2" active-color="#FF7218" icon-size="22">
							个人
						</u-radio>
						<u-radio name="1" active-color="#FF7218" icon-size="22">
							企业单位
						</u-radio>
					</u-radio-group>
				</u-form-item>
				<u-form-item label="抬头名称" v-show="form.type == 2">
					<u-input v-model="form.title" placeholder="必填" />
				</u-form-item>
				<u-form-item label="公司抬头" v-show="form.type == 1">
					<u-input v-model="form.title" placeholder="必填" />
				</u-form-item>
				<u-form-item label="公司税号" v-show="form.type == 1">
					<u-input v-model="form.tax_sn" placeholder="必填" />
				</u-form-item>
				<template v-if="isShow">
					<u-form-item label="公司地址">
						<u-input v-model="form.address" placeholder="选填" />
					</u-form-item>
					<u-form-item label="公司电话">
						<u-input v-model="form.phone" placeholder="选填" />
					</u-form-item>
					<u-form-item label="开户银行">
						<u-input v-model="form.bank_name" placeholder="选填" />
					</u-form-item>
					<u-form-item label="开户账号">
						<u-input v-model="form.bank_sn" placeholder="选填" />
					</u-form-item>
				</template>

				<view class="u-text-center u-p-tb-30 u-font-24 grey u-border-bottom" @click="isShow = !isShow"
					v-show="form.type == 1">
					<text class="u-m-r-10">{{isShow ? '收起' : '展开'}}选填信息</text>
					<u-icon name="arrow-down" v-if="!isShow"></u-icon>
					<u-icon name="arrow-up" v-else></u-icon>
				</view>
				<u-form-item label="电子邮箱">
					<u-input v-model="form.email" placeholder="电子邮箱、手机号码至少填一项" />
				</u-form-item>
				<!-- <u-form-item label="手机号码">
					<u-input v-model="form.phone" placeholder="电子邮箱、手机号码至少填一项" />
				</u-form-item> -->
			</view>


		</u-form>


		<view class="submit-btn safe-area-inset-bottom">
			<view class="u-p-30">
				<u-button @click="addInvoice">提交申请</u-button>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				form: {
					order_num: '',
					invoiceKind: 82,
					type: 2,
					title: '',
					tax_sn: '',
					address: '',
					phone: '',
					bank_name: '',
					bank_sn: '',
					email: '',
				},
				isShow: false,
				amount: 0,
			};
		},
		onLoad(op) {
			this.amount = op.amount
			this.form.order_num = op.order_num
		},
		methods: {
			addInvoice() {
				if (!this.form.title) return this.$u.toast("请填写发票抬头")
				if (this.form.type == 1 && !this.form.tax_sn) return this.$u.toast("请填写公司税号")
				if (!this.form.email && !this.form.phone) return this.$u.toast("电子邮箱、手机号码至少填一项")
				
				this.$u.api.addInvoice(this.form).then(res => {
					this.$u.toast(res.msg)
					setTimeout(() => {
						uni.navigateBack()
					}, 1500)
				}).catch(err => {
					this.$u.toast(err.msg)
				})
				
			},
			typeChange(e) {
				console.log(e);
				this.isShow = false
			}
		}
	}
</script>

<style lang="scss" scoped>
	.container {
		padding-bottom: 160rpx;
	}
</style>