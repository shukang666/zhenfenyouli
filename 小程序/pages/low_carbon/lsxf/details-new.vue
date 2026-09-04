<template>
	<view class="container">
		<view class=" u-type-white-bg u-rela ">
			<u-image width="750" height="372" :src="info.thumb"></u-image>
			<view class="u-p-30">
				<view class="u-font-32 u-font-bold u-line-1 u-p-b-30 u-border-bottom">{{info.name}}</view>
				<view class="u-flex u-row-between u-m-t-30 ">
					<view class="u-flex  u-font-24 u-hui u-w-550">
						<u-icon name="clock"></u-icon>
						<text class="u-m-l-15">活动时间：{{info.date | date('yy-mm-dd')}}</text>
					</view>
					<image src="/static/images/dianhua.png" class="u-w-92 u-h-38" mode="aspectFill" @click="makePhoneCall(info.phone)"></image>
				</view>
				<view class="u-flex u-row-between u-m-t-30 ">
					<view class="u-flex  u-font-24 u-hui u-w-550">
						<u-icon name="map"></u-icon>
						<text class="u-m-l-15">{{info.address}}</text>
					</view>
					<image src="/static/images/ditu.png" class="u-w-92 u-h-38" mode="aspectFill" @click="openLocation(info)"></image>
				</view>
			</view>
		</view>
		<view class="u-p-30 u-type-white-bg u-m-t-30">
			<view class="u-m-b-30">
				<u-divider color="#FF843D">活动说明</u-divider>
			</view>
			<u-parse :html="info.content"></u-parse>
		</view>

		<u-gap height="150"></u-gap>

		<u-mask :show="tipShow">
			<view class="u-w-560 u-radius-10 u-type-white-bg u-abso-center u-p-30" v-if="showType == 2">
				<view class="u-text-center">
					<image src="/static/images/qiandaochenggong.png" class="u-w-280 u-h-188" mode="aspectFill"></image>

					<view class="u-font-32">打卡赠送 <text class="u-font-40 u-main-c u-font-bold">{{info.send_point}}</text> 积分</view>
					<view class="u-font-32">恭喜您打卡成功~</view>
				</view>
				<view class="u-m-t-30 u-flex u-row-center">
					<u-button :custom-style="{width:'400rpx'}" @click="tipShow = false">我知道了</u-button>
				</view>
			</view>
		</u-mask>

		<u-popup v-model="sign_clock_show" mode="bottom" border-radius="20" closeable>
			<view class="u-p-30">
				<view class="u-font-bold">上传打卡图片</view>
				<view class="u-m-t-30 u-hui-bg u-radius-10 u-p-30">
					<u-upload upload-text="上传图片" width="180" height="180" :action="uploadUrl" @on-success="uploadImages($event,'image_url',true)" :fileList="image_url"
						@on-remove="removeImages($event,'image_url',true)" max-count="1">
					</u-upload>
				</view>
				<view class="u-p-30">
					<u-button @click="post_new_sign_clock">确认打卡</u-button>
				</view>
			</view>
		</u-popup>

		<!-- 打卡动作表单弹窗（即时表单通知） -->
		<u-popup v-model="actionFormShow" mode="bottom" border-radius="20" closeable :closeable="false">
			<view class="u-p-30">
				<view class="u-font-bold u-font-32 u-text-center u-m-b-20">活动反馈</view>
				<view class="u-m-b-20" v-for="(field, fIdx) in actionTrigger.forms" :key="fIdx">
					<!-- 纯文本说明类型 -->
					<view v-if="field.field_type === 'text'" class="u-font-28 u-type-info u-m-b-10" style="line-height: 1.6;">
						{{field.field_label}}
					</view>
					<!-- 单选按钮类型 -->
					<view v-else-if="field.field_type === 'radio'">
						<view class="u-font-28 u-m-b-15">{{field.field_label}} <text v-if="field.is_required == 1" style="color:red;">*</text></view>
						<view class="u-flex u-flex-wrap">
							<view v-for="(opt, oIdx) in field.field_options_arr" :key="oIdx"
								class="u-m-r-20 u-m-b-15 u-p-lr-20 u-p-tb-10 u-radius-8"
								:style="actionFormData[field.field_label] && actionFormData[field.field_label].includes(opt) ? 'background:#FF843D;color:#fff;' : 'background:#f5f5f5;'"
								@click="toggleRadioOption(field.field_label, opt)">
								{{opt}}
							</view>
						</view>
					</view>
					<!-- 多行文本框类型 -->
					<view v-else-if="field.field_type === 'textarea'">
						<view class="u-font-28 u-m-b-15">{{field.field_label}} <text v-if="field.is_required == 1" style="color:red;">*</text></view>
						<u-input v-model="actionFormData[field.field_label]" type="textarea" height="150" :placeholder="'请输入' + field.field_label" border />
					</view>
				</view>
				<view class="u-p-t-20">
					<u-button type="primary" @click="submitClockActionForm">提交反馈</u-button>
				</view>
			</view>
		</u-popup>

		<view class="submit-btn u-p-30 u-flex u-row-between u-type-white-bg" v-if="!setting.app_test">
			<view class="u-p-lr-30 u-text-center u-rela">
				<image src="/static/images/yaoqing.png" class="u-w-44 u-h-44" mode="aspectFill"></image>
				<view class="u-font-26">邀请邻居</view>
				<button open-type="share" plain class="kefuBtn"></button>
			</view>
			<u-button :custom-style="{width:'480rpx'}" v-if="info.is_clock == 0" :disabled="info.is_end == 1 || info.is_sign == 1" @click="handleSign">{{info.is_sign == 1 ? '已参与' :'立即参与'}}</u-button>
			<view class="u-flex u-row-between u-w-490" v-if="info.is_clock == 1">
				<u-button :custom-style="{width:'230rpx'}" type="warning" plain @click="$u.route(`/pages/low_carbon/rank?id=${info.id}`)">
					<image src="/static/images/rank.png" class="u-w-40 u-h-40 u-m-r-15" mode="aspectFill"></image>
					<text>排行榜</text>
				</u-button>
				<u-button :custom-style="{width:'230rpx'}" @click="handleClock" :disabled="info.is_end == 1 || (info.is_sign == 1 && info.is_sign_clock == 1)">{{info.is_sign == 1 ? (info.is_sign_clock == 1 ? '已打卡' : '立即打卡') : '立即打卡'}}</u-button>
			</view>
		</view>

	</view>
</template>

<script>
	import {
		uploadUrl
	} from '@/common/settings'
	export default {
	data() {
		return {
			uploadUrl,
			popup_show: false,
			image: [],
			content: '',
			info: {},
			tipShow: false,
			showType: 1,
			pid: '',
			id: '',
			sign_clock_show: false,
			form: {
				image_url: '',
			},
			image_url: [],
			// 打卡动作触发数据
			actionTrigger: null,
			actionFormShow: false,
			actionFormData: {},
		};
	},
		onLoad(option) {
			if (option.scene) {
				uni.setStorageSync('vcode', option.scene)
			}
			this.id = option.id || option.scene
			if (option.pid) this.pid = option.pid
			this.get_new_active_detail(this.id)
		},
		onHide() {
			this.tipShow = false
		},
		onShareTimeline() {
			return {
				imageUrl: this.info.thumb,
				title: this.info.name,
				path: `/pages/low_carbon/lsxf/details-new?id=${this.id}&scene=${this.userInfo.id}`
			}
		},
		onShareAppMessage() {
			return {
				imageUrl: this.info.thumb,
				title: this.info.name,
				path: `/pages/low_carbon/lsxf/details-new?id=${this.id}&scene=${this.userInfo.id}`
			}
		},
		methods: {
			// 打卡按钮 → 未登录跳登录，已登录未报名提示先报名，已报名弹打卡弹窗
			handleClock() {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				if (!this.info.is_sign) return this.$u.toast('请先报名活动')
				this.sign_clock_show = true
			},
			// 立即参与按钮 → 未登录跳登录，已登录走报名接口
			handleSign() {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				this.post_new_active_sign()
			},
			// 删除图
			removeImages(e, key, isOne) {
				if (isOne) {
					this.form[key] = ''
				} else {
					this.form[key].splice(e, 1)
				}
			},
			// 上传图
			uploadImages(e, key, isOne) {
				if (isOne) {
					this.form[key] = e.data.filepath
				} else {
					this.form[key].push(e.data.filepath)
				}
			},

		// 打卡
		async post_new_sign_clock() {
			if (!this.$u.func.checkLogin()) return
			try {
				let params = {
					sign_id: this.info.sign_id,
					...this.form
				}
				let res = await this.$u.api.post_new_sign_clock(params)
				this.sign_clock_show = false
				// this.$u.toast(res.msg)
				this.showType = 2
				this.tipShow = true

				// 检查是否有打卡动作触发
				if (res.data && res.data.action_trigger) {
					this.actionTrigger = res.data.action_trigger;
					// 即时表单通知：弹出表单弹窗
					if (this.actionTrigger.notify_type == 1) {
						this.initActionFormData();
						setTimeout(() => {
							this.tipShow = false;
							this.actionFormShow = true;
						}, 1500);
					}
					// 滞后服务消息推送：请求微信订阅授权
					else if (this.actionTrigger.notify_type == 2) {
						this.requestSubscribeMessage(this.actionTrigger.id);
						setTimeout(() => {
							this.get_new_active_detail(this.info.id)
						}, 500)
					} else {
						setTimeout(() => {
							this.get_new_active_detail(this.info.id)
						}, 500)
					}
				} else {
					setTimeout(() => {
						this.get_new_active_detail(this.info.id)
					}, 500)
				}
			} catch (e) {
				console.log(e);
				this.$u.toast(e.msg)
			}
		},
		// 初始化动作表单数据
		initActionFormData() {
			this.actionFormData = {};
			if (this.actionTrigger && this.actionTrigger.forms) {
				this.actionTrigger.forms.forEach(field => {
					if (field.field_type === 'radio') {
						this.$set(this.actionFormData, field.field_label, []);
					} else if (field.field_type === 'textarea') {
						this.$set(this.actionFormData, field.field_label, '');
					}
				});
			}
		},
		// 切换单选选项（支持多选；若仅一个选项则单选）
		toggleRadioOption(fieldLabel, option) {
			let arr = this.actionFormData[fieldLabel] || [];
			let idx = arr.indexOf(option);
			if (idx > -1) {
				arr.splice(idx, 1);
			} else {
				arr.push(option);
			}
			this.$set(this.actionFormData, fieldLabel, arr);
		},
		// 提交打卡动作表单
		async submitClockActionForm() {
			if (!this.actionTrigger) return;
			// 校验必填项
			for (let field of this.actionTrigger.forms) {
				if (field.is_required == 1 && (field.field_type === 'radio' || field.field_type === 'textarea')) {
					let val = this.actionFormData[field.field_label];
					if (!val || (Array.isArray(val) && val.length === 0) || (typeof val === 'string' && !val.trim())) {
						this.$u.toast('请填写：' + field.field_label);
						return;
					}
				}
			}
			try {
				let formObj = {};
				for (let key in this.actionFormData) {
					let v = this.actionFormData[key];
					formObj[key] = Array.isArray(v) ? v.join(',') : v;
				}
				let params = {
					action_id: this.actionTrigger.id,
					form_data: JSON.stringify(formObj)
				};
				let res = await this.$u.api.post_clock_action_form(params);
				this.$u.toast(res.msg || '提交成功');
				this.actionFormShow = false;
				this.actionTrigger = null;
				this.actionFormData = {};
			} catch (e) {
				console.log(e);
				this.$u.toast(e.msg || '提交失败');
			}
		},
		// 请求微信订阅消息授权（滞后推送用）
		requestSubscribeMessage(actionId) {
			// #ifdef MP-WEIXIN
			if (wx.requestSubscribeMessage) {
				// 后台可配置模板ID，此处先用占位，可根据需要扩展为从actionTrigger获取
				let templateIds = this.actionTrigger && this.actionTrigger.template_ids ? this.actionTrigger.template_ids : [];
				if (templateIds.length === 0) {
					// 没有模板ID则直接记录未订阅状态
					this.recordSubscribeStatus(actionId, 0);
					return;
				}
				wx.requestSubscribeMessage({
					tmplIds: templateIds,
					success: (res) => {
						let allAccepted = templateIds.every(tid => res[tid] === 'accept');
						this.recordSubscribeStatus(actionId, allAccepted ? 1 : 0);
					},
					fail: () => {
						this.recordSubscribeStatus(actionId, 0);
					}
				});
			} else {
				this.recordSubscribeStatus(actionId, 0);
			}
			// #endif
			// #ifndef MP-WEIXIN
			this.recordSubscribeStatus(actionId, 0);
			// #endif
		},
		// 记录订阅授权状态
		async recordSubscribeStatus(actionId, isSubscribed) {
			try {
				await this.$u.api.post_clock_action_subscribe({
					action_id: actionId,
					is_subscribed: isSubscribed
				});
			} catch (e) {
				console.log('记录订阅状态失败:', e);
			}
		},
			makePhoneCall(phoneNumber) {
				uni.makePhoneCall({
					phoneNumber
				})
			},
			openLocation(item) {
				uni.openLocation({
					latitude: item.lat * 1,
					longitude: item.lng * 1,
					address: item.address
				})
			},
			// 活动报名
			async post_new_active_sign() {
				if (!this.$u.func.checkLogin()) return
				try {
					let params = {
						active_id: this.info.id,
						pid: this.pid

					}
					let res = await this.$u.api.post_new_active_sign(params)
					this.$u.toast(res.msg)
					this.tipShow = false
					setTimeout(() => {
						this.get_new_active_detail(this.info.id)
					}, 500)
				} catch (e) {
					console.log(e);
					this.$u.toast(e.msg)
				}
			},
			btn() {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				this.popup_show = true
			},
			// 提交
			add_active_user() {
				if (!this.$u.func.checkLogin()) return
				if (!this.content) return this.$u.toast('请先添加作品介绍')
				if (!this.image.length) return this.$u.toast('请先添加作品照片')
				this.$u.api.add_active_user({
					active_id: this.info.id,
					image: this.image,
					content: this.content
				}).then(res => {
					this.popup_show = false
					this.$u.func.showToast({
						title: res.msg,
						icon: 'success',
					})
				}).catch(err => {
					this.$u.toast(err.msg)
				})
			},
			// 活动详情
			get_new_active_detail(id) {
				this.$u.api.get_new_active_detail({
					active_id: id
				}).then(res => {
					this.info = res.data
				})
			},
			// 上传图片
			onSuccess(reslut) {
				this.image.push(reslut.data.url)
			},
		},
		// onShareAppMessage(res) {
		// 	return {
		// 		title: this.info.name,
		// 		path: '/pages/low_carbon/lsxf/details?id=' + this.info.id,
		// 		imageUrl: this.info.thumb
		// 	}
		// },
		// onShareTimeline() {
		// 	return {
		// 		title: this.info.name,
		// 		path: `/pages/low_carbon/lsxf/details?id=${this.info.id}&pid=${this.userInfo.id}`,
		// 		imageUrl: this.info.thumb
		// 	}
		// }
	}
</script>

<style lang="scss" scoped>
	.container {
		min-height: 100vh;
		background: #F5F5F5;
	}
</style>