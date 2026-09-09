<template>
	<view class="container">
		<!-- 沉浸式封面区域 -->
		<view class="hero-section">
			<u-image width="750" height="420" :src="info.thumb" mode="aspectFill"></u-image>
			<view class="hero-overlay"></view>
			
			<!-- 返回按钮 -->
			<view class="back-btn" @click="navigateBack">
				<text class="back-icon">‹</text>
			</view>
			
			<!-- 封面上的标题信息 -->
			<view class="hero-content">
				<view class="hero-badge" v-if="info.is_clock == 1">
					<text class="badge-icon">🎯</text>
					<text class="badge-text">挑战活动</text>
				</view>
				<text class="hero-title">{{info.name}}</text>
				<text class="hero-subtitle">{{subtitle}}</text>
				
				<!-- 快速统计 -->
				<view class="hero-stats">
					<view class="stat-item">
						<text class="stat-value">{{info.send_point || 0}}</text>
						<text class="stat-label">积分奖励</text>
					</view>
					<view class="stat-divider"></view>
					<view class="stat-item">
						<text class="stat-value">{{participantsCount}}</text>
						<text class="stat-label">人已参与</text>
					</view>
				</view>
			</view>
		</view>
		
		<!-- 内容区域 -->
		<view class="content-section">
			<!-- 活动时间地点卡片 -->
			<view class="info-card glass-card">
				<view class="info-item">
					<view class="info-icon-wrap">
						<text class="info-icon">📅</text>
					</view>
					<view class="info-content">
						<text class="info-label">活动时间</text>
						<text class="info-value">{{activityTimeText}}</text>
					</view>
				</view>
				<view class="info-item" @click="openLocation(info)">
					<view class="info-icon-wrap">
						<text class="info-icon">📍</text>
					</view>
					<view class="info-content">
						<text class="info-label">活动地点</text>
						<text class="info-value u-line-1">{{info.address || '详见活动群通知'}}</text>
					</view>
					<text class="info-arrow">›</text>
				</view>
				<view class="info-item" @click="makePhoneCall(info.phone)" v-if="info.phone">
					<view class="info-icon-wrap">
						<text class="info-icon">📞</text>
					</view>
					<view class="info-content">
						<text class="info-label">咨询电话</text>
						<text class="info-value">{{info.phone}}</text>
					</view>
					<text class="info-arrow">›</text>
				</view>
			</view>
			
		<!-- 活动流程 -->
		<view class="flow-card glass-card">
			<view class="section-header">
				<text class="section-title">活动流程</text>
			</view>
			<view class="flow-list">
				<view class="flow-item">
					<view class="flow-number">1</view>
					<view class="flow-content">
						<text class="flow-title">{{info.is_clock == 1 ? '每日打卡' : '报名参与'}}</text>
						<text class="flow-desc">{{info.is_clock == 1 ? '完成垃圾分类打卡任务' : '点击下方按钮，加入活动'}}</text>
					</view>
				</view>
				<view class="flow-item">
					<view class="flow-number">2</view>
					<view class="flow-content">
						<text class="flow-title">领取奖励</text>
						<text class="flow-desc">获得积分，解锁徽章</text>
					</view>
				</view>
			</view>
		</view>
			
			<!-- 活动说明 -->
			<view class="desc-card glass-card">
				<view class="section-header">
					<text class="section-title">活动说明</text>
				</view>
				<view class="desc-content">
					<u-parse :html="info.content"></u-parse>
				</view>
			</view>
		</view>
		
		<!-- 底部固定按钮 -->
		<view class="bottom-bar glass-footer">
			<view class="share-btn" @click="handleShare">
				<text class="share-icon">分享</text>
			</view>
			<!-- 非打卡活动：报名按钮（扫码参加活动统一叫"签到"） -->
			<view class="action-area" v-if="info.is_clock == 0">
				<u-button 
					v-if="info.is_sign == 0"
					class="action-btn primary-btn"
					:disabled="info.is_end == 1"
					@click="handleSign"
				>
					{{info.is_end == 1 ? '活动已结束' : (isScanJoin ? '立即签到' : '立即报名')}}
				</u-button>
				<u-button 
					v-else
					class="action-btn success-btn"
					disabled
				>
					{{isScanJoin ? '已签到 ✓' : '已报名 ✓'}}
				</u-button>
			</view>
			<!-- 打卡活动：排行榜 + 打卡 -->
			<view class="action-area clock-actions" v-if="info.is_clock == 1">
				<u-button class="action-btn rank-btn" @click="goToRank">
					<text class="btn-icon">🏆</text>
					<text>排行榜</text>
				</u-button>
				<u-button 
					class="action-btn primary-btn"
					:disabled="info.is_end == 1 || (info.is_sign == 1 && info.is_sign_clock == 1)"
					@click="handleClock"
				>
					{{clockBtnText}}
				</u-button>
			</view>
		</view>
		
		<!-- 打卡成功弹窗 -->
		<u-mask :show="tipShow" @click="tipShow = false">
			<view class="success-modal glass-card" @tap.stop.prevent>
				<view class="success-icon">
					<text class="success-emoji">🎉</text>
				</view>
				<view class="success-title">{{isScanJoin ? '签到成功!' : '打卡成功!'}}</view>
				<view class="success-point">
					<text class="point-label">获得积分</text>
					<text class="point-value">+{{info.send_point}}</text>
				</view>
				<view class="success-tip">{{isScanJoin ? '扫码签到成功，感谢参与~' : '继续保持，积少成多~'}}</view>
				<view class="success-btn" @click="tipShow = false">我知道了</view>
			</view>
		</u-mask>
		
		<!-- 打卡弹窗 -->
		<u-popup v-model="sign_clock_show" mode="bottom" border-radius="24" closeable>
			<view class="clock-popup">
				<view class="popup-header">
					<text class="popup-title">上传打卡照片</text>
					<text class="popup-subtitle">记录你的环保行动瞬间</text>
				</view>
				<view class="upload-area">
					<u-upload 
						upload-text="拍照/选择图片" 
						width="180" 
						height="180" 
						:action="uploadUrl" 
						@on-success="uploadImages($event,'image_url',true)" 
						:fileList="image_url"
						@on-remove="removeImages($event,'image_url',true)" 
						max-count="1"
					></u-upload>
				</view>
				<u-button class="confirm-btn" @click="post_new_sign_clock">确认打卡</u-button>
			</view>
		</u-popup>
		
		<!-- 打卡动作表单弹窗（即时表单通知�?-->
		<u-popup v-model="actionFormShow" mode="bottom" border-radius="24" closeable :closeable="false">
			<view class="action-form-popup">
				<view class="popup-header">
					<text class="popup-title">活动反馈</text>
					<text class="popup-subtitle">帮助我们做得更好</text>
				</view>
				<view class="form-fields">
					<view class="form-field" v-for="(field, fIdx) in actionTrigger.forms" :key="fIdx">
						<!-- 纯文本说明类�?-->
						<view v-if="field.field_type === 'text'" class="field-text">
							{{field.field_label}}
						</view>
						<!-- 单选按钮类�?-->
						<view v-else-if="field.field_type === 'radio'">
							<view class="field-label">{{field.field_label}} <text v-if="field.is_required == 1" class="required">*</text></view>
							<view class="radio-options">
								<view v-for="(opt, oIdx) in field.field_options_arr" :key="oIdx"
									class="radio-option"
									:class="{selected: actionFormData[field.field_label] && actionFormData[field.field_label].includes(opt)}"
									@click="toggleRadioOption(field.field_label, opt)">
									{{opt}}
								</view>
							</view>
						</view>
						<!-- 多行文本框类�?-->
						<view v-else-if="field.field_type === 'textarea'">
							<view class="field-label">{{field.field_label}} <text v-if="field.is_required == 1" class="required">*</text></view>
							<u-input v-model="actionFormData[field.field_label]" type="textarea" height="150" :placeholder="'请输入' + field.field_label" border />
						</view>
					</view>
				</view>
				<u-button class="confirm-btn" @click="submitClockActionForm">提交反馈</u-button>
			</view>
		</u-popup>
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
		computed: {
			// 副标�?- 根据活动类型生成
			subtitle() {
				if (this.info.is_clock == 1) {
					return '每天30秒，让社区更绿色';
				}
				return '参与绿色行动，共建美好家园';
			},
		// 参与人数 - 由后端 detail 接口返回真实报名人数
		participantsCount() {
			return this.info.active_count || 0;
		},
		// 是否扫码参加活动（join_type=2：仅能通过活动二维码扫码进入，签到免传照片）
		isScanJoin() {
			return this.info.join_type == 2;
		},
		// 打卡/签到按钮文本（扫码参加活动显示"立即签到"）
		clockBtnText() {
			const done = this.info.is_sign == 1 && this.info.is_sign_clock == 1;
			if (this.isScanJoin) {
				return done ? '已签到 ✓' : '立即签到';
			}
			return done ? '已打卡 ✓' : '立即打卡';
		},
		// 活动时间文本 - 根据活动类型显示不同格式
		activityTimeText() {
			if (this.info.is_clock == 1) {
				// 打卡活动：使�?clock_start_time �?clock_end_time
				let startTime = '';
				let endTime = '';
				if (this.info.clock_start_time) {
					startTime = this.formatDate(this.info.clock_start_time);
				}
				if (this.info.clock_end_time) {
					endTime = this.formatDate(this.info.clock_end_time);
				}
				if (startTime && endTime) {
					return startTime + ' ~ ' + endTime;
				} else if (startTime) {
					return startTime + ' ~ 不限';
				}
				return '不限';
			} else {
				// 非打卡活动：使用 date 字段
				if (this.info.date) {
					let dateStr = this.formatDate(this.info.date);
					return dateStr + ' ~ 不限';
				}
				return '不限';
			}
		}
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
			// 格式化日期为 yyyy.mm.dd 格式
			formatDate(timestamp) {
				if (!timestamp) return '';
				// 如果是字符串格式的时间，先转为时间戳
				let ts = timestamp;
				if (typeof ts === 'string') {
					// 处理 "2026-09-08" 格式
					if (ts.indexOf('-') > -1) {
						ts = new Date(ts.replace(/-/g, '/')).getTime();
					} else {
						ts = parseInt(ts);
					}
				}
				// 如果是秒级时间戳，转为毫秒级
				if (ts < 10000000000) {
					ts = ts * 1000;
				}
				const date = new Date(ts);
				const year = date.getFullYear().toString();
				const month = (date.getMonth() + 1).toString().padStart(2, '0');
				const day = date.getDate().toString().padStart(2, '0');
				return year + '.' + month + '.' + day;
			},
			// 返回上一�?
			navigateBack() {
				uni.navigateBack();
			},
			// 分享
			handleShare() {
				uni.showToast({
					title: '点击右上角分享',
					icon: 'none'
				});
			},
			// 跳转排行�?
			goToRank() {
				this.$u.route(`/pages/low_carbon/rank?id=${this.info.id}`);
			},
		// 上传组件状态变化回�?
		handleUploadChange() {},
		// 打卡/签到按钮（打卡活动不需要先报名，直接打卡）
		handleClock() {
			if (!this.isLogin) return this.$u.route('/pages/public/login')
			if (this.isScanJoin) {
				// 扫码参加活动：免上传照片，点击直接签到（走同一个打卡接口，数据逻辑不变）
				this.post_new_sign_clock()
				return
			}
			this.sign_clock_show = true
		},
		// 立即参与按钮
		handleSign() {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				this.post_new_active_sign()
			},
			// 删除�?
			removeImages(e, key, isOne) {
				if (isOne) {
					this.form[key] = ''
				} else {
					this.form[key].splice(e, 1)
				}
			},
			// 上传�?
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
					this.showType = 2
					this.tipShow = true

					// 检查是否有打卡动作触发
					if (res.data && res.data.action_trigger) {
						this.actionTrigger = res.data.action_trigger;
						// 即时表单通知：弹出表单弹�?
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
			// 初始化动作表单数�?
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
			// 切换单选选项
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
				// 校验必填�?
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
			// 请求微信订阅消息授权
			requestSubscribeMessage(actionId) {
				// #ifdef MP-WEIXIN
				if (wx.requestSubscribeMessage) {
					let templateId = this.actionTrigger && this.actionTrigger.template_id ? this.actionTrigger.template_id : '';
					if (!templateId) {
						this.recordSubscribeStatus(actionId, 0);
						return;
					}
					wx.requestSubscribeMessage({
						tmplIds: [templateId],
						success: (res) => {
							let accepted = res[templateId] === 'accept';
							this.recordSubscribeStatus(actionId, accepted ? 1 : 0);
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
			// 记录订阅授权状�?
			async recordSubscribeStatus(actionId, isSubscribed) {
				try {
					await this.$u.api.post_clock_action_subscribe({
						action_id: actionId,
						is_subscribed: isSubscribed
					});
				} catch (e) {
					console.log('记录订阅状态失�?', e);
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
			// 提交作品
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
	}
</script>

<style lang="scss" scoped>
	.container {
		min-height: 100vh;
		background: #F5F5F7;
		padding-bottom: 200rpx;
	}
	
	// 沉浸式封�?
	.hero-section {
		position: relative;
		width: 750rpx;
		height: 420rpx;
		overflow: hidden;
		
		.hero-overlay {
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: linear-gradient(180deg, transparent 0%, rgba(0,0,0,0.5) 100%);
		}
		
		.back-btn {
			position: absolute;
			top: 80rpx;
			left: 30rpx;
			width: 64rpx;
			height: 64rpx;
			border-radius: 50%;
			background: rgba(255,255,255,0.25);
			backdrop-filter: blur(10rpx);
			display: flex;
			align-items: center;
		justify-content: center;
			z-index: 10;
			
			.back-icon {
				font-size: 48rpx;
				color: #fff;
				line-height: 1;
			}
		}
		
		.hero-content {
			position: absolute;
			left: 30rpx;
			right: 30rpx;
			bottom: 30rpx;
			z-index: 5;
			
			.hero-badge {
				display: inline-flex;
				align-items: center;
				background: rgba(255,255,255,0.2);
				backdrop-filter: blur(10rpx);
				padding: 8rpx 16rpx;
				border-radius: 20rpx;
				margin-bottom: 16rpx;
				
				.badge-icon {
					font-size: 24rpx;
					margin-right: 6rpx;
				}
				
				.badge-text {
					font-size: 22rpx;
					color: #fff;
					font-weight: 500;
				}
			}
			
			.hero-title {
				font-size: 40rpx;
				font-weight: 700;
				color: #fff;
				line-height: 1.3;
				margin-bottom: 8rpx;
				text-shadow: 0 2rpx 8rpx rgba(0,0,0,0.3);
			}
			
			.hero-subtitle {
				font-size: 26rpx;
				color: rgba(255,255,255,0.85);
				line-height: 1.4;
				margin-bottom: 20rpx;
			}
			
			.hero-stats {
				display: flex;
				align-items: center;
				background: rgba(255,255,255,0.9);
				backdrop-filter: blur(20rpx);
				border-radius: 20rpx;
				padding: 20rpx 0;
				
				.stat-item {
					flex: 1;
					display: flex;
					flex-direction: column;
					align-items: center;
					
					.stat-value {
						font-size: 32rpx;
						font-weight: 700;
						color: #1D1D1F;
						line-height: 1.2;
					}
					
					.stat-label {
						font-size: 22rpx;
						color: #86868B;
						margin-top: 4rpx;
					}
				}
				
				.stat-divider {
					width: 1rpx;
					height: 40rpx;
					background: rgba(0,0,0,0.08);
				}
			}
		}
	}
	
	// 内容�?
	.content-section {
		padding: 24rpx;
	}
	
	// 玻璃卡片
	.glass-card {
		background: rgba(255,255,255,0.75);
		backdrop-filter: blur(20rpx);
		-webkit-backdrop-filter: blur(20rpx);
		border-radius: 24rpx;
		margin-bottom: 24rpx;
		box-shadow: 0 4rpx 20rpx rgba(0,0,0,0.04);
	}
	
	// 信息卡片
	.info-card {
		padding: 30rpx;
		
		.info-item {
			display: flex;
			align-items: center;
			padding: 20rpx 0;
			border-bottom: 1rpx solid rgba(0,0,0,0.05);
			
			&:last-child {
				border-bottom: none;
			}
			
			.info-icon-wrap {
				width: 64rpx;
				height: 64rpx;
				border-radius: 16rpx;
				background: rgba(52,199,89,0.1);
				display: flex;
				align-items: center;
				justify-content: center;
				margin-right: 20rpx;
				
				.info-icon {
					font-size: 32rpx;
				}
			}
			
			.info-content {
				flex: 1;
				margin-right: 16rpx;
				display: flex;
				justify-content: space-between;
				align-items: center;
				
				.info-label {
					font-size: 24rpx;
					color: #86868B;
					flex-shrink: 0;
				}
				
				.info-value {
					font-size: 28rpx;
					color: #1D1D1F;
					font-weight: 500;
					text-align: right;
				}
			}
			
			.info-arrow {
				font-size: 32rpx;
				color: #C7C7CC;
			}
		}
	}
	
	// 活动流程
	.flow-card {
		padding: 30rpx;
		
		.section-header {
			margin-bottom: 24rpx;
			
			.section-title {
				font-size: 30rpx;
				font-weight: 600;
				color: #1D1D1F;
			}
		}
		
		.flow-list {
			.flow-item {
				display: flex;
				align-items: center;
				margin-bottom: 24rpx;
				
				&:last-child {
					margin-bottom: 0;
				}
				
				.flow-number {
					width: 48rpx;
					height: 48rpx;
					border-radius: 50%;
					background: linear-gradient(135deg, #34C759 0%, #30D158 100%);
					color: #fff;
					font-size: 24rpx;
					font-weight: 600;
					display: flex;
					align-items: center;
					justify-content: center;
					margin-right: 20rpx;
				}
				
				.flow-content {
					flex: 1;
					
					.flow-title {
						font-size: 28rpx;
						font-weight: 500;
						color: #1D1D1F;
						margin-bottom: 4rpx;
					}
					
					.flow-desc {
						font-size: 24rpx;
						color: #86868B;
					}
				}
			}
		}
	}
	
	// 活动说明
	.desc-card {
		padding: 30rpx;
		
		.section-header {
			margin-bottom: 24rpx;
			
			.section-title {
				font-size: 30rpx;
				font-weight: 600;
				color: #1D1D1F;
			}
		}
		
		.desc-content {
			font-size: 26rpx;
			color: #3C3C43;
			line-height: 1.6;
		}
	}
	
	// 底部操作�?
	.bottom-bar {
		position: fixed;
		bottom: 0;
		left: 0;
		right: 0;
		padding: 24rpx 30rpx;
		padding-bottom: calc(24rpx + constant(safe-area-inset-bottom));
		padding-bottom: calc(24rpx + env(safe-area-inset-bottom));
		display: flex;
		align-items: center;
		gap: 20rpx;
		background: rgba(255,255,255,0.85);
		backdrop-filter: blur(30rpx);
		-webkit-backdrop-filter: blur(30rpx);
		box-shadow: 0 -4rpx 20rpx rgba(0,0,0,0.05);
		
		.share-btn {
			width: 88rpx;
			height: 88rpx;
			border-radius: 50%;
			background: rgba(0,0,0,0.05);
			display: flex;
			align-items: center;
			justify-content: center;
			
			.share-icon {
				font-size: 24rpx;
				color: #86868B;
			}
		}
		
		.action-area {
			flex: 1;
			display: flex;
			gap: 16rpx;
			
			.action-btn {
				flex: 1;
				height: 88rpx;
				border-radius: 44rpx;
				font-size: 28rpx;
				font-weight: 600;
			}
			
			.primary-btn {
				background: linear-gradient(135deg, #34C759 0%, #30D158 100%);
				color: #fff;
				border: none;
			}
			
			.success-btn {
				background: rgba(52,199,89,0.15);
				color: #34C759;
				border: none;
			}
			
			.rank-btn {
				background: rgba(255,149,0,0.1);
				color: #FF9500;
				border: none;
				display: flex;
				align-items: center;
				justify-content: center;
				gap: 8rpx;
				
				.btn-icon {
					font-size: 28rpx;
				}
			}
		}
		
		.clock-actions {
			.action-btn:first-child {
				flex: 0.6;
			}
		}
	}
	
	// 打卡成功弹窗
	.success-modal {
		width: 560rpx;
		padding: 50rpx 40rpx;
		border-radius: 32rpx;
		background: rgba(255,255,255,0.95);
		backdrop-filter: blur(30rpx);
		text-align: center;
		position: absolute;
		top: 50%;
		left: 50%;
		transform: translate(-50%, -50%);
		
		.success-icon {
			margin-bottom: 24rpx;
			
			.success-emoji {
				font-size: 80rpx;
			}
		}
		
		.success-title {
			font-size: 36rpx;
			font-weight: 700;
			color: #1D1D1F;
			margin-bottom: 16rpx;
		}
		
		.success-point {
			display: flex;
			align-items: baseline;
			justify-content: center;
			gap: 8rpx;
			margin-bottom: 12rpx;
			
			.point-label {
				font-size: 26rpx;
				color: #86868B;
			}
			
			.point-value {
				font-size: 48rpx;
				font-weight: 700;
				color: #FF9500;
			}
		}
		
		.success-tip {
			font-size: 24rpx;
			color: #86868B;
			margin-bottom: 32rpx;
		}
		
		.success-btn {
			display: flex;
			align-items: center;
			justify-content: center;
			width: 100%;
			height: 80rpx;
			border-radius: 40rpx;
			background: linear-gradient(135deg, #34C759 0%, #30D158 100%);
			color: #fff;
			font-size: 28rpx;
			font-weight: 600;
		}
	}
	
	// 打卡弹窗
	.clock-popup {
		padding: 40rpx 30rpx;
		padding-bottom: calc(40rpx + constant(safe-area-inset-bottom));
		padding-bottom: calc(40rpx + env(safe-area-inset-bottom));
		
		.popup-header {
			text-align: center;
			margin-bottom: 32rpx;
			
			.popup-title {
				font-size: 32rpx;
				font-weight: 600;
				color: #1D1D1F;
				margin-bottom: 8rpx;
			}
			
			.popup-subtitle {
				font-size: 24rpx;
				color: #86868B;
			}
		}
		
		.upload-area {
			display: flex;
			justify-content: center;
			margin-bottom: 40rpx;
		}
		
		.confirm-btn {
			width: 100%;
			height: 88rpx;
			border-radius: 44rpx;
			background: linear-gradient(135deg, #34C759 0%, #30D158 100%);
			color: #fff;
			font-size: 28rpx;
			font-weight: 600;
		}
	}
	
	// 动作表单弹窗
	.action-form-popup {
		padding: 40rpx 30rpx;
		padding-bottom: calc(40rpx + constant(safe-area-inset-bottom));
		padding-bottom: calc(40rpx + env(safe-area-inset-bottom));
		max-height: 70vh;
		
		.popup-header {
			text-align: center;
			margin-bottom: 32rpx;
			
			.popup-title {
				font-size: 32rpx;
				font-weight: 600;
				color: #1D1D1F;
				margin-bottom: 8rpx;
			}
			
			.popup-subtitle {
				font-size: 24rpx;
				color: #86868B;
			}
		}
		
		.form-fields {
			max-height: 50vh;
			overflow-y: auto;
			
			.form-field {
				margin-bottom: 32rpx;
				
				.field-text {
					font-size: 28rpx;
					color: #3C3C43;
					line-height: 1.6;
				}
				
				.field-label {
					font-size: 28rpx;
					color: #1D1D1F;
					font-weight: 500;
					margin-bottom: 16rpx;
					
					.required {
						color: #FF3B30;
					}
				}
				
				.radio-options {
					display: flex;
					flex-wrap: wrap;
					gap: 16rpx;
					
					.radio-option {
						padding: 14rpx 28rpx;
						border-radius: 24rpx;
						background: rgba(0,0,0,0.04);
						font-size: 26rpx;
						color: #3C3C43;
						border: 2rpx solid transparent;
						transition: all 0.2s;
						
						&.selected {
							background: rgba(52,199,89,0.1);
							color: #34C759;
							border-color: #34C759;
						}
					}
				}
			}
		}
		
		.confirm-btn {
			width: 100%;
			height: 88rpx;
			border-radius: 44rpx;
			background: linear-gradient(135deg, #34C759 0%, #30D158 100%);
			color: #fff;
			font-size: 28rpx;
			font-weight: 600;
		}
	}
</style>

