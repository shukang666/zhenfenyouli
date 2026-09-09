<template>
	<view class="container">
		<view class="card-p-lr">
			<u-form :model="form">
				<u-form-item label="类型" labelWidth="160">
					<view class="u-flex u-row-right" v-if="!isDisabled">
						<u-radio-group v-model="form.type" active-color="#48BE53">
							<u-radio :name="item.id" v-for="item,i in typeList" :key="i">{{item.name}}</u-radio>
						</u-radio-group>
					</view>
					<view class="u-text-right" v-else>{{info.type_name}}</view>
				</u-form-item>
				<u-form-item label="商家名称" labelWidth="160">
					<u-input v-model="form.name" type="text" :disabled="isDisabled" input-align="right" />
				</u-form-item>
				<u-form-item label="联系人" labelWidth="160">
					<u-input v-model="form.contact" type="text" :disabled="isDisabled" input-align="right" />
				</u-form-item>
				<u-form-item label="联系电话" labelWidth="160">
					<u-input v-model="form.mobile" type="text" :disabled="isDisabled" input-align="right" />
				</u-form-item>
				<u-form-item label="所在社区" labelWidth="160">
					<u-input v-model="form.community_name" type="select" :disabled="isDisabled" input-align="right" @click="community_show = true" v-if="!isDisabled" />
					<view class="u-text-right" v-else>{{info.community}}</view>
				</u-form-item>

			</u-form>
			<view class="u-p-30" v-if="info.status != 2">
				<u-button shape="square" @click="add_shop_new">确认提交</u-button>
			</view>
		</view>


		<u-select v-model="community_show" label-name="name" value-name="id" :list="community_list" @confirm="confirm"></u-select>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				form: {
					name: '',
					contact: '',
					mobile: '',
					community: '',
					community_name: '',
					type: 1,
				},
				typeList: [{
					name: "机构",
					id: 1
				}, {
					name: "经营场所",
					id: 2
				}],
				info: {},
				isDisabled: false,
				community_list: [],
				community_show: false
			};
		},
		onLoad() {
			this.get_shop_new()
			this.get_community_parent_list()
		},
		methods: {
			confirm(e) {
				this.form.community = e[0].value
				this.form.community_name = e[0].label
			},
			async get_community_parent_list() {
				try {
					let params = {}
					let res = await this.$u.api.get_community_parent_list(params)
					this.community_list = res.data
				} catch (e) {
					console.log(e);
					this.$u.toast(e.msg)
				}
			},
			async get_shop_new() {
				try {
					let params = {}
					let res = await this.$u.api.get_shop_new(params)
					this.info = res.data
					for (let key in this.form) {
						if (res.data[key]) this.form[key] = res.data[key]
					}
					if (res.data.status == 2) this.isDisabled = true
				} catch (e) {
					console.log(e);
					// this.$u.toast(e.msg)
				}
			},
			async add_shop_new() {
				try {
					for (let key in this.form) {
						if (!this.form[key]) return this.$u.toast("请完善商家信息")
					}
					let reg = /^1(3[0-9]|4[01456879]|5[0-35-9]|6[2567]|7[0-8]|8[0-9]|9[0-35-9])\d{8}$/
					if (!reg.test(this.form.mobile)) return this.$u.toast('请输入正确的手机号')
					let params = {}
					if (this.info.id) params.id = this.info.id
					let res = await this.$u.api.add_shop_new({
						type: this.form.type,
						name: this.form.name,
						contact: this.form.contact,
						mobile: this.form.mobile,
						community: this.form.community,
						...params
					})
					this.$u.toast(res.msg, 'success')
					setTimeout(() => {
						uni.redirectTo({
							url: '/pages/zzhd/apply-status'
						})
					}, 1000)
				} catch (e) {
					console.log(e);
					this.$u.toast(e.msg)
				}
			},
		}
	}
</script>

<style lang="scss" scoped>

</style>