<template>
	<view class="container">
		<view class="u-rela">
			<u-parse :html="htmlStr" :loading="false" useIframe="false" imgMaxWidth="100%" />
		</view>

		<view class="submit-btn u-p-30" style="z-index: 99999;">
			<u-button @click="$u.route(`/pages/zzhd/sign-name`)" v-if="!form.sign_image">前往签字</u-button>
			<view class="u-flex u-row-between" v-else>
				<u-button @click="$u.route(`/pages/zzhd/sign-name`)" plain :custom-style="{width:'330rpx',color:'#18AF38',background:'#ffffff'}">修改签字</u-button>
				<u-button :custom-style="{width:'330rpx'}" @click="submit">提交</u-button>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				htmlStr: '',
				form: {},
			};
		},
		onLoad(op) {
			console.log(op);
			if (op.form) this.form = JSON.parse(op.form)
			this.add_check_html()
			uni.$on('getNameImg', (res) => {
				this.form.sign_image = res
				this.add_check_html()
			})
		},
		methods: {
			submit() {
				if (!this.$u.func.checkLogin()) return
				this.$u.api.add_check(this.form).then(res => {
					this.$u.toast(res.msg)
					setTimeout(() => {
						uni.navigateBack({
							delta: 3
						})
					}, 1000)
				}).catch(err => {
					this.$u.toast(err.msg)
				})
			},
			// 获取合同内容
			async add_check_html() {
				try {
					let params = {}
					if (this.form.sign_image) params.sign_image = this.form.sign_image
					let res = await this.$u.api.add_check_html(params)
					this.htmlStr = res.data
					console.log(this.htmlStr);
				} catch (e) {
					console.log(e);
					this.$u.toast(e.msg)
				}
			},
		}
	}
</script>

<style lang="scss" scoped>
	.container {
		min-height: 100vh;
		background: white;
		padding-bottom: 150rpx;
	}
</style>