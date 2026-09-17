<template>
	<view class="u-p-30">
		<view class="u-type-error-light-bg u-type-error u-p-20 u-radius-6 u-m-b-30" v-if="reason_msg.status==3">
			拒绝原因：{{reason_msg.msg}}
		</view>
		<u-form :model="form" ref="uForm">
			<view class="u-type-white-bg u-radius-10 u-p-lr-30">
				<u-form-item label="姓名" prop="name" labelWidth="150">
					<u-input v-model="form.name" placeholder="请输入" input-align="right" />
				</u-form-item>
				<u-form-item label="联系电话" prop="mobile" labelWidth="150">
					<u-input v-model="form.mobile" placeholder="请输入" input-align="right" />
				</u-form-item>
				<u-form-item label="所在小区" prop="community" labelWidth="160">
					<u-input v-model="form.community" placeholder="请选择" input-align="right" disabled />
				</u-form-item>
				<u-form-item label="申请理由" prop="reason" labelWidth="150" label-position="bottom">
					<u-input v-model="form.reason" placeholder="请输入" />
				</u-form-item>
					<u-form-item label="申请身份" labelWidth="150" label-position="bottom">
						<view class="u-flex">
							<u-tag text="督导员" mode="dark" type="success" />
							<text class="u-font-24 u-type-info u-m-l-20">申请后同时拥有督导员和指导员的全部权限</text>
						</view>
					</u-form-item>
			<u-form-item label="期望参加活动" labelWidth="150" label-position="bottom">
				<view class="u-flex">
					<view class="u-m-r-20" v-for="(item,index) in type_list" :key="index" @click="form.active_type = item.type">
						<u-tag :text="item.name" :mode="form.active_type==item.type?'dark':'plain'" :type="form.active_type==item.type?'success':'info'" />
					</view>
				</view>
			</u-form-item>
				<!-- 				<u-form-item label="签名" prop="reason" labelWidth="150">
					<view class="u-flex u-row-right u-text-right u-font-24 u-hui" @click="$u.route(`/pages/zzhd/sign-name`)">
						<image :src="form.sign_image" class="u-w-150 " mode="widthFix" v-if="form.sign_image"></image>
						<text v-else>前往签名</text>
						<u-icon name="arrow-right"></u-icon>
					</view>
				</u-form-item> -->
				<view class="u-m-t-30 u-p-30">
					<!-- <u-button type="primary" hover-class="none" :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}" @click="submit()">确认提交</u-button> -->
					<u-button :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}" @click="goContract">下一步</u-button>
				</view>
			</view>
		</u-form>


		<!-- 选择类型 -->
		<u-select v-model="cate_show" label-name="name" value-name="id" :list="cate_list" @confirm="confirm"></u-select>
	</view>
</template>

<script>
export default {
	data() {
		return {
			form: {
				name: '',
				mobile: '',
				community: '',
				reason: '',
				active_type: 1,
				role_type: 1,        // 固定为1，后端统一处理为督导员
				community_id: 0,
				sign_image: '',
			},
				rules: {
					reason: [{
						required: true,
						message: '请输入申请理由',
						trigger: ['change', 'blur'],
					}],
					community: [{
						required: true,
						message: '请输入所在小区',
						trigger: ['change', 'blur'],
					}],
					mobile: [{
						required: true,
						message: '请输入联系电话',
						trigger: ['change', 'blur'],
					}],
					name: [{
						required: true,
						message: '请输入姓名',
						trigger: ['change', 'blur'],
					}],
				},
				type_list: [{
					name: '小区点位巡检',
					type: 1
				}, {
					name: '桶边督导',
					type: 2
				}, {
					name: '宣传活动',
					type: 3
				}],
				reason_msg: {},
				cate_show: false,
				cate_list: [],

			};
		},
		onLoad(op) {
			// 统一为督导员申请，不再区分角色
			uni.setNavigationBarTitle({ title: '督导员申请' });
			this.form.mobile = this.userInfo.mobile
			this.form.name = this.userInfo.true_name
			this.form.community = this.userInfo.community
			this.form.community_id = this.userInfo.community_id
			this.get_check()
			this.get_community_list()
			// uni.$on('getNameImg', (res) => {
			// 	this.form.sign_image = res
			// })

		},
		methods: {
			goContract() {
				if (!this.$u.func.checkLogin()) return
				this.$refs.uForm.validate(valid => {
					if (valid) {
						uni.navigateTo({
							url: `/pages/zzhd/contract?form=${JSON.stringify(this.form)}`,
							fail(e) {
								console.log(e);
							}
						})
					} else {
						console.log('验证失败');
					}
				});
			},

			// 选择小区
			confirm(e) {
				this.form.community_id = e[0].value
				this.form.community = e[0].label
			},
			// 小区列表
			get_community_list() {
				this.$u.api.get_community_list().then(res => {
					this.cate_list = res.data
				})
			},
		// 审核详情
		get_check() {
			this.$u.api.get_check().then(res => {
				this.form.mobile = res.data.mobile
				this.form.name = res.data.name
				// 不再区分角色，统一使用督导员身份
				this.form.active_type = res.data.active_type
				this.form.reason = res.data.reason
				this.form.community = res.data.community
				this.form.community_id = res.data.community_id
				this.form.id = res.data.id
				this.reason_msg = res.data
			})
		},
			// 提交
			submit() {
				if (!this.$u.func.checkLogin()) return
				this.$refs.uForm.validate(valid => {
					if (valid) {
						this.$u.api.add_check(this.form).then(res => {
							this.$u.func.showToast({
								title: res.msg,
								icon: 'success',
								back: true
							})
						}).catch(err => {
							this.$u.toast(err.msg)
						})
					} else {
						console.log('验证失败');
					}
				});
			}
		},
		// 必须要在onReady生命周期，因为onLoad生命周期组件可能尚未创建完毕
		onReady() {
			this.$refs.uForm.setRules(this.rules);
		}
	}
</script>

<style lang="scss">

</style>