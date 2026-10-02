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
				<view class="hero-badge" v-if="isOfflineEvent">
					<text class="badge-icon">📍</text>
					<text class="badge-text">线下活动</text>
				</view>
				<view class="hero-badge" v-else-if="info.is_clock_challenge == 1">
					<text class="badge-icon">🎁</text>
					<text class="badge-text">打卡兑礼</text>
				</view>
				<view class="hero-badge" v-else-if="info.is_clock == 1">
					<text class="badge-icon">🎯</text>
					<text class="badge-text">打卡活动</text>
				</view>
				<text class="hero-title">{{info.name}}</text>
				<text class="hero-subtitle">{{subtitle}}</text>
				
				<!-- 快速统计 -->
				<view class="hero-stats">
					<view class="stat-item">
						<text class="stat-value">{{ heroPointValue }}</text>
						<text class="stat-label">{{ heroPointLabel }}</text>
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

		<!-- 配置式：规则/安全独立子页（field:flow 等，非 Web 式 H5） -->
		<view class="doc-links glass-card" v-if="info.doc_rules_field || info.doc_safety_field">
			<view class="doc-link-item" v-if="info.doc_rules_field" @click="openActiveDoc(info.doc_rules_field, '活动规则')">
				<text class="doc-link-icon">📋</text>
				<text class="doc-link-text">活动规则</text>
				<text class="info-arrow">›</text>
			</view>
			<view class="doc-link-item" v-if="info.doc_safety_field" @click="openActiveDoc(info.doc_safety_field, '安全注意事项')">
				<text class="doc-link-icon">⚠️</text>
				<text class="doc-link-text">安全注意事项</text>
				<text class="info-arrow">›</text>
			</view>
		</view>

		<!-- 打卡兑礼：礼品列表 -->
		<view class="redeem-goods glass-card" v-if="info.is_clock_challenge == 1 && redeemGoodsList.length">
			<view class="section-header">
				<view class="section-bar section-bar--gold"></view>
				<text class="section-title">可兑换礼品</text>
			</view>
			<text class="redeem-goods-tip">在本活动累计打卡满 {{ info.clock_challenge_days }} 天（不要求连续）后，可兑换以下礼品</text>
			<view class="redeem-goods-list">
				<view class="redeem-goods-item" v-for="g in redeemGoodsList" :key="g.id" @click="goRedeemGoods(g.id)">
					<u-image width="120" height="120" :src="g.thumb" mode="aspectFill" border-radius="16"></u-image>
					<view class="redeem-goods-meta">
						<text class="redeem-goods-name u-line-2">{{ g.goods_name }}</text>
						<text class="redeem-goods-price">
							<text v-if="Number(g.price)">{{ g.price }}元</text>
							<text v-if="Number(g.price) && g.point"> + </text>
							<text v-if="g.point">{{ g.point }}积分</text>
						</text>
					</view>
					<text class="redeem-goods-arrow">›</text>
				</view>
			</view>
		</view>

		<!-- 打卡兑礼进度（已报名用户） -->
		<view class="challenge-progress glass-card" v-if="info.is_clock_challenge == 1 && info.is_sign == 1">
			<view class="section-header">
				<view class="section-bar section-bar--gold"></view>
				<text class="section-title">我的打卡进度</text>
			</view>
			<view class="challenge-progress-row">
				<text class="challenge-progress-text">累计打卡 {{ clockChallengeProgress }}/{{ info.clock_challenge_days }} 天</text>
				<text class="challenge-reward-tip" v-if="info.clock_challenge_can_redeem == 1">已满足兑礼条件</text>
			</view>
			<view class="challenge-bar-track">
				<view class="challenge-bar-fill" :style="{ width: clockChallengePercent + '%' }"></view>
			</view>
			<text class="challenge-done-tip" v-if="info.clock_challenge_can_redeem == 1 && redeemGoodsList.length">前往上方礼品或积分商城兑换</text>
		</view>

		<!-- 活动概览（适合人群 + 活动时长） -->
		<view class="overview-card glass-card" v-if="info.suit_people || info.duration">
			<view class="section-header">
				<view class="section-bar"></view>
				<text class="section-title">活动概览</text>
			</view>
			<view class="overview-list">
				<view class="overview-item" v-if="info.suit_people">
					<text class="overview-label">适合人群</text>
					<text class="overview-value">{{info.suit_people}}</text>
				</view>
				<view class="overview-item" v-if="info.duration">
					<text class="overview-label">活动时长</text>
					<text class="overview-value">{{info.duration}}</text>
				</view>
			</view>
		</view>

		<!-- 活动流程（后台富文本配置；若已作规则独立子页则主页不重复展示） -->
		<view class="desc-card glass-card" v-if="info.flow && info.doc_rules_field !== 'flow'">
			<view class="section-header">
				<view class="section-bar"></view>
				<text class="section-title">活动流程</text>
			</view>
			<view class="desc-content">
				<u-parse :html="info.flow"></u-parse>
			</view>
		</view>

		<!-- 你将获得（后台富文本配置；若已作安全须知独立子页则主页不重复展示） -->
		<view class="desc-card glass-card gain-card" v-if="info.gain && info.doc_safety_field !== 'gain'">
			<view class="section-header">
				<view class="section-bar section-bar--gold"></view>
				<text class="section-title">你将获得</text>
			</view>
			<view class="desc-content">
				<u-parse :html="info.gain"></u-parse>
			</view>
		</view>

		<!-- 活动流程 -->
		<view class="flow-card glass-card">
			<view class="section-header">
				<view class="section-bar"></view>
				<text class="section-title">活动流程</text>
			</view>
			<view class="flow-list">
				<view class="flow-item">
					<view class="flow-number">1</view>
					<view class="flow-content">
						<text class="flow-title">{{ flowStep1Title }}</text>
						<text class="flow-desc">{{ flowStep1Desc }}</text>
					</view>
				</view>
				<view class="flow-item">
					<view class="flow-number">2</view>
					<view class="flow-content">
						<text class="flow-title">领取奖励</text>
						<text class="flow-desc">{{ flowStep2Desc }}</text>
					</view>
				</view>
			</view>
		</view>
			
			<!-- 活动说明 -->
			<view class="desc-card glass-card">
				<view class="section-header">
					<view class="section-bar"></view>
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
				<u-icon name="share" size="36" color="#18AF38"></u-icon>
				<text class="share-icon">分享</text>
			</view>
			<!-- 线下活动：报名 + 限时定位签到 -->
			<view class="action-area" v-if="isOfflineEvent">
				<u-button
					v-if="info.is_sign == 0"
					class="action-btn primary-btn"
					:disabled="info.is_end == 1"
					@click="handleSign"
				>
					{{ info.is_end == 1 ? '活动已结束' : '我要报名' }}
				</u-button>
				<u-button v-else-if="info.is_sign_clock == 1" class="action-btn success-btn" disabled>
					已签到 ✓
				</u-button>
				<u-button v-else-if="info.offline_sign_ended == 1" class="action-btn" disabled>
					签到已结束
				</u-button>
				<u-button v-else-if="info.offline_sign_wait == 1" class="action-btn" disabled>
					签到未开始
				</u-button>
				<u-button v-else class="action-btn primary-btn" @click="openOfflineSignPopup">
					签到
				</u-button>
			</view>
			<!-- 非打卡活动：报名按钮（扫码参加活动统一叫"签到"） -->
			<view class="action-area" v-else-if="info.is_clock == 0">
				<u-button 
					v-if="info.is_sign == 0"
					class="action-btn primary-btn"
					:disabled="info.is_end == 1"
					@click="handleSign"
				>
					{{info.is_end == 1 ? '活动已结束' : (isScanJoin ? '立即签到' : '我要报名')}}
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
			<view class="action-area clock-actions" v-else-if="info.is_clock == 1 || info.is_clock_challenge == 1">
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
		<u-mask :show="tipShow" @click="closeTip">
			<view class="success-wrap">
				<view class="success-modal" @tap.stop.prevent>
					<view class="success-icon">
						<text class="success-emoji">🎉</text>
					</view>
					<view class="success-title">{{isOfflineEvent || isScanJoin ? '签到成功!' : '打卡成功!'}}</view>
					<view class="success-point">
						<text class="point-label">获得积分</text>
						<text class="point-value">+{{info.send_point}}</text>
					</view>
					<view class="success-tip">{{isScanJoin ? '扫码签到成功，感谢参与~' : '继续保持，积少成多~'}}</view>
					<view class="success-btn" @tap.stop="closeTip">我知道了</view>
				</view>
			</view>
		</u-mask>
		
		<!-- 线下活动签到弹窗 -->
		<u-popup v-model="offline_sign_show" mode="bottom" border-radius="24" closeable>
			<view class="clock-popup offline-sign-popup">
				<view class="popup-header">
					<text class="popup-title">现场签到</text>
					<text class="popup-subtitle">需在活动地点 500 米范围内</text>
				</view>
				<view class="offline-loc-hint u-font-24 u-type-info u-m-b-20">
					{{ offlineLocHint || '正在获取您的位置…' }}
				</view>
				<u-button class="confirm-btn" :loading="offlineSignLoading" @click="submitOfflineLocationSign">确认签到</u-button>
				<view class="offline-pwd-entry" v-if="info.has_offline_password == 1" @click="offlinePwdShow = !offlinePwdShow">
					定位困难？口令签到
				</view>
				<view v-if="offlinePwdShow && info.has_offline_password == 1" class="offline-pwd-box">
					<u-input v-model="offlinePassword" type="password" placeholder="请输入签到口令" border />
					<u-button class="confirm-btn u-m-t-20" size="medium" :loading="offlineSignLoading" @click="submitOfflinePasswordSign">提交口令</u-button>
				</view>
			</view>
		</u-popup>

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
				<view class="clock-share-field">
					<view class="field-label">
						经验分享
						<text class="field-optional">选填</text>
					</view>
					<u-input
						v-model="form.experience_share"
						type="textarea"
						height="150"
						maxlength="500"
						placeholder="分享本次参与的小心得、做法或感受"
						border
					/>
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
					<view class="form-field" v-for="(field, fIdx) in actionFormFields" :key="fIdx">
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
	import { clearPendingAfterLogin } from '@/common/scanSignEntry.js'
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
				offline_sign_show: false,
				offlineSignLoading: false,
				offlineLocHint: '',
				offlineLat: '',
				offlineLng: '',
				offlinePwdShow: false,
				offlinePassword: '',
				form: {
					image_url: '',
					experience_share: '',
				},
				image_url: [],
				// 打卡动作触发数据
				actionTrigger: null,
				actionFormShow: false,
				actionFormData: {},
			};
		},
		computed: {
			// 活动反馈表单字段：actionTrigger 初始为 null，这里兜底成空数组，
			// 避免首次 render 访问 null.forms 抛错，导致内联事件 e0/e1 注册不上
			actionFormFields() {
				return (this.actionTrigger && this.actionTrigger.forms) || [];
			},
			redeemGoodsList() {
				return (this.info && this.info.redeem_goods_list) || [];
			},
			subtitle() {
				if (Number(this.info.is_date_pending) === 1) {
					return '具体时间确定后将另行通知';
				}
				if (this.info.is_clock_challenge == 1) {
					const n = this.redeemGoodsList.length;
					if (n > 0) {
						return `累计打卡 ${this.info.clock_challenge_days || 0} 天，可兑换 ${n} 款礼品`;
					}
					return `累计打卡 ${this.info.clock_challenge_days || 0} 天后可兑换礼品（请在商品中绑定本活动）`;
				}
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
		isOfflineEvent() {
			return Number(this.info.event_kind) === 2;
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
			if (Number(this.info.is_date_pending) === 1) {
				if (this.isOfflineEvent) {
					const fmt = (ts) => {
						if (!ts) return '';
						const d = new Date(ts * 1000);
						const p = (n) => String(n).padStart(2, '0');
						return `${d.getFullYear()}.${p(d.getMonth() + 1)}.${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}`;
					};
					const s = fmt(this.info.offline_sign_start);
					const e = fmt(this.info.offline_sign_end);
					if (s && e) {
						return `现场签到 ${s} ~ ${e}（活动截止日期待定）`;
					}
				}
				return '活动时间待定，敬请关注后续通知';
			}
			if (this.isOfflineEvent) {
				const fmt = (ts) => {
					if (!ts) return '';
					const d = new Date(ts * 1000);
					const p = (n) => String(n).padStart(2, '0');
					return `${d.getFullYear()}.${p(d.getMonth() + 1)}.${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}`;
				};
				const s = fmt(this.info.offline_sign_start);
				const e = fmt(this.info.offline_sign_end);
				if (s && e) return `签到 ${s} ~ ${e}`;
				if (this.info.date) return '截止至' + this.formatDate(this.info.date);
				return '详见活动说明';
			}
			if (this.info.is_clock == 1 || this.info.is_clock_challenge == 1) {
				// 打卡活动：使用 clock_start_time 与 clock_end_time
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
					return '截止至' +dateStr;
				}
				return '不限';
			}
		},
		heroPointValue() {
			if (Number(this.info.is_clock_challenge) === 1) {
				const n = this.redeemGoodsList.length;
				return n > 0 ? n : (this.info.clock_challenge_days || 0);
			}
			return this.info.send_point || 0;
		},
		heroPointLabel() {
			if (Number(this.info.is_clock_challenge) === 1) {
				return this.redeemGoodsList.length > 0 ? '款礼品' : '天可兑礼';
			}
			return '积分奖励';
		},
		clockChallengeProgress() {
			return Number(this.info.clock_challenge_progress) || 0;
		},
		clockChallengePercent() {
			const total = Number(this.info.clock_challenge_days) || 0;
			if (total <= 0) return 0;
			const cur = this.clockChallengeProgress;
			return Math.min(100, Math.round((cur / total) * 100));
		},
		flowStep1Title() {
			if (this.info.is_clock == 1 || this.info.is_clock_challenge == 1) return '每日打卡';
			return '报名参与';
		},
		flowStep1Desc() {
			if (Number(this.info.is_clock_challenge) === 1) {
				return `在活动期内打卡，累计满 ${this.info.clock_challenge_days || 0} 天即可兑换礼品`;
			}
			if (this.info.is_clock == 1) return '完成垃圾分类打卡任务';
			return '点击下方按钮，加入活动';
		},
		flowStep2Desc() {
			if (Number(this.info.is_clock_challenge) === 1) {
				return '满足天数后，在礼品列表或积分商城兑换（商品需绑定本活动）';
			}
			return '获得积分，解锁徽章';
		}
	},
		onLoad(option) {
			const scene = option.scene ? String(option.scene) : ''
			let sceneVal = scene
			try {
				if (sceneVal) sceneVal = decodeURIComponent(sceneVal)
			} catch (e) {}
			// 分享链接带 id + scene=邀请人；活动码只有 scene=活动ID，不能把活动ID写成邀请码
			if (option.id && sceneVal) {
				uni.setStorageSync('vcode', sceneVal)
			}
			this.id = option.id || sceneVal
			if (option.pid) this.pid = option.pid
			// 应用内点进详情靠登录后 navigateBack；仅扫码旧页 stub 会写 pending_after_login
			if (this.isLogin) clearPendingAfterLogin()
			this.get_new_active_detail(this.id)
		},
		onHide() {
			this.tipShow = false
		},
		onShow() {
			if (this.id) {
				this.get_new_active_detail(this.id)
			}
		},
		onShareTimeline() {
			return {
				imageUrl: this.info.thumb,
				title: this.info.name,
				path: `/pages/activity/lsxf/details-new?id=${this.id}&scene=${this.userInfo.id}`
			}
		},
		onShareAppMessage() {
			return {
				imageUrl: this.info.thumb,
				title: this.info.name,
				path: `/pages/activity/lsxf/details-new?id=${this.id}&scene=${this.userInfo.id}`
			}
		},
		methods: {
			// 关闭打卡/签到成功弹窗
			closeTip() {
				this.tipShow = false;
			},
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
			goRedeemGoods(goodsId) {
				if (!goodsId) return;
				this.$u.route('/pages/shop/details', { id: goodsId });
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
				this.$u.route(`/pages/activity/rank?id=${this.info.id}`);
			},
		// 上传组件状态变化回�?
		handleUploadChange() {},
		// 打卡/签到按钮（打卡活动不需要先报名，直接打卡）
		openOfflineSignPopup() {
			if (!this.isLogin) return this.$u.route('/pages/public/login')
			this.offlinePwdShow = false
			this.offlinePassword = ''
			this.offline_sign_show = true
			this.refreshOfflineLocation()
		},
		refreshOfflineLocation() {
			this.offlineLocHint = '正在获取您的位置…'
			this.offlineLat = ''
			this.offlineLng = ''
			uni.getLocation({
				type: 'gcj02',
				success: (res) => {
					this.offlineLat = res.latitude
					this.offlineLng = res.longitude
					const dist = this.calcDistanceMeters(
						res.latitude, res.longitude,
						this.info.lat * 1, this.info.lng * 1
					)
					if (dist <= 500) {
						this.offlineLocHint = `已在签到范围内（约 ${Math.round(dist)} 米）`
					} else {
						this.offlineLocHint = `当前距活动地点约 ${Math.round(dist)} 米，需在 500 米内`
					}
				},
				fail: () => {
					this.offlineLocHint = '未能获取定位，请开启位置权限或尝试口令签到'
				}
			})
		},
		calcDistanceMeters(lat1, lng1, lat2, lng2) {
			const R = 6371000
			const dLat = (lat2 - lat1) * Math.PI / 180
			const dLng = (lng2 - lng1) * Math.PI / 180
			const a = Math.sin(dLat / 2) ** 2
				+ Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLng / 2) ** 2
			return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))
		},
		async submitOfflineLocationSign() {
			if (!this.offlineLat || !this.offlineLng) {
				this.refreshOfflineLocation()
				return this.$u.toast('请先允许获取位置')
			}
			this.offlineSignLoading = true
			try {
				await this.$u.api.post_offline_event_sign({
					active_id: this.info.id,
					lat: this.offlineLat,
					lng: this.offlineLng,
					sign_mode: 'location'
				})
				this.offline_sign_show = false
				this.showType = 2
				this.tipShow = true
				setTimeout(() => this.get_new_active_detail(this.info.id), 500)
			} catch (e) {
				this.$u.toast(e.msg || '签到失败')
			} finally {
				this.offlineSignLoading = false
			}
		},
		async submitOfflinePasswordSign() {
			if (!this.offlinePassword) return this.$u.toast('请输入口令')
			this.offlineSignLoading = true
			try {
				await this.$u.api.post_offline_event_sign({
					active_id: this.info.id,
					password: this.offlinePassword,
					sign_mode: 'password'
				})
				this.offline_sign_show = false
				this.showType = 2
				this.tipShow = true
				setTimeout(() => this.get_new_active_detail(this.info.id), 500)
			} catch (e) {
				this.$u.toast(e.msg || '签到失败')
			} finally {
				this.offlineSignLoading = false
			}
		},
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
					this.form.experience_share = ''
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
			openActiveDoc(field, title) {
				const f = String(field || '').trim()
				if (!f || !this.info.id) return
				const t = encodeURIComponent(title || '说明')
				uni.navigateTo({
					url: `/pages/activity/lsxf/active-doc?active_id=${this.info.id}&field=${f}&title=${t}`
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
					const data = res.data || {}
					if (this.$u.func.isWebDisplayActive(data)) {
						const pageUrl = this.$u.func.resolveWebActivityPageUrl(data, this.id)
						const path = this.$u.func.buildH5WrapperPath(pageUrl)
						uni.redirectTo({ url: path })
						return
					}
					this.info = data
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
		background: #eef4f0;
		padding-bottom: 200rpx;
	}
	
	// 沉浸式封�?
	.hero-section {
		position: relative;
		width: 750rpx;
		height: 520rpx;
		overflow: hidden;
		
		.hero-overlay {
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: linear-gradient(180deg, rgba(0,0,0,0.12) 0%, rgba(14,127,38,0.28) 42%, rgba(12,70,28,0.82) 100%);
		}
		
		.back-btn {
			position: absolute;
			top: 80rpx;
			left: 30rpx;
			width: 64rpx;
			height: 64rpx;
			border-radius: 50%;
			background: rgba(0,0,0,0.32);
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
				background: rgba(255,213,106,0.92);
				padding: 8rpx 16rpx;
				border-radius: 20rpx;
				margin-bottom: 16rpx;
				
				.badge-icon {
					font-size: 24rpx;
					margin-right: 6rpx;
				}
				
				.badge-text {
					font-size: 22rpx;
					color: #7a5a00;
					font-weight: 700;
				}
			}
			
			.hero-title {
				font-size: 40rpx;
				font-weight: 700;
				color: #fff;
				line-height: 1.3;
				margin-bottom: 8rpx;
				text-shadow: none;
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
				background: #fff;
				border-radius: 24rpx;
				padding: 22rpx 0;
				box-shadow: 0 12rpx 28rpx rgba(0,0,0,0.12);
				
				.stat-item {
					flex: 1;
					display: flex;
					flex-direction: column;
					align-items: center;
					
					.stat-value {
						font-size: 36rpx;
						font-weight: 700;
						color: #18AF38;
						line-height: 1.2;
					}
					
					.stat-label {
						font-size: 22rpx;
						color: #6a7b6e;
						margin-top: 4rpx;
					}
				}
				
				.stat-divider {
					width: 2rpx;
					height: 44rpx;
					background: #e8f0ea;
				}
			}
		}
	}
	
	// 内容�?
	.content-section {
		padding: 24rpx 24rpx 8rpx;
	}

	.section-header {
		display: flex;
		align-items: center;
		margin-bottom: 20rpx;
	}
	.section-bar {
		width: 8rpx;
		height: 28rpx;
		border-radius: 8rpx;
		background: linear-gradient(180deg, #2bc24e 0%, #18af38 100%);
		margin-right: 12rpx;
		flex-shrink: 0;
	}
	.section-bar--gold {
		background: linear-gradient(180deg, #f6d365 0%, #dda017 100%);
	}
	.section-title {
		font-size: 30rpx;
		font-weight: 700;
		color: #1c2b1e;
	}

	.glass-card {
		background: #fff;
		border-radius: 28rpx;
		margin-bottom: 20rpx;
		box-shadow: 0 10rpx 28rpx rgba(16, 48, 24, 0.06);
	}

	.challenge-progress {
		padding: 24rpx 28rpx 28rpx;
	}
	.challenge-progress-row {
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin-bottom: 16rpx;
	}
	.challenge-progress-text {
		font-size: 28rpx;
		font-weight: 600;
		color: #1c2b1e;
	}
	.challenge-reward-tip {
		font-size: 24rpx;
		color: #18AF38;
		font-weight: 600;
	}
	.challenge-bar-track {
		height: 16rpx;
		border-radius: 999rpx;
		background: #e8f3eb;
		overflow: hidden;
	}
	.challenge-bar-fill {
		height: 100%;
		border-radius: 999rpx;
		background: linear-gradient(90deg, #A4DB88 0%, #18AF38 100%);
		transition: width 0.3s ease;
	}
	.challenge-done-tip {
		display: block;
		margin-top: 14rpx;
		font-size: 24rpx;
		color: #18AF38;
	}

	.redeem-goods {
		padding: 24rpx 28rpx 28rpx;
	}
	.redeem-goods-tip {
		display: block;
		font-size: 24rpx;
		color: #5a6b5e;
		line-height: 1.5;
		margin-bottom: 20rpx;
	}
	.redeem-goods-item {
		display: flex;
		align-items: center;
		padding: 20rpx 0;
		border-top: 1rpx solid #eef3ef;
	}
	.redeem-goods-item:first-of-type {
		border-top: none;
		padding-top: 0;
	}
	.redeem-goods-meta {
		flex: 1;
		margin-left: 20rpx;
		min-width: 0;
	}
	.redeem-goods-name {
		font-size: 28rpx;
		font-weight: 600;
		color: #1c2b1e;
	}
	.redeem-goods-price {
		display: block;
		margin-top: 10rpx;
		font-size: 24rpx;
		color: #e54d42;
		font-weight: 600;
	}
	.redeem-goods-arrow {
		font-size: 36rpx;
		color: #b0bdb3;
		margin-left: 12rpx;
	}

	.doc-links {
		padding: 8rpx 28rpx;
		margin-top: 24rpx;

		.doc-link-item {
			display: flex;
			align-items: center;
			padding: 26rpx 0;
			border-bottom: 1rpx solid #eef3ef;

			&:last-child {
				border-bottom: none;
			}
		}

		.doc-link-icon {
			font-size: 34rpx;
			margin-right: 16rpx;
		}

		.doc-link-text {
			flex: 1;
			font-size: 30rpx;
			color: #1b4332;
			font-weight: 500;
		}
	}

	.info-card {
		padding: 8rpx 28rpx 8rpx;

		.info-item {
			display: flex;
			align-items: center;
			padding: 22rpx 0;
			border-bottom: 1rpx solid #eef3ef;

			&:last-child {
				border-bottom: none;
			}

			.info-icon-wrap {
				width: 72rpx;
				height: 72rpx;
				border-radius: 22rpx;
				background: #e8f8ec;
				display: flex;
				align-items: center;
				justify-content: center;
				margin-right: 20rpx;

				.info-icon {
					font-size: 32rpx;
				}
			}

			&:nth-child(2) .info-icon-wrap {
				background: #e8f2ff;
			}
			&:nth-child(3) .info-icon-wrap {
				background: #fff1e6;
			}

			.info-content {
				flex: 1;
				min-width: 0;
				margin-right: 12rpx;
				display: flex;
				flex-direction: column;

				.info-label {
					font-size: 22rpx;
					color: #8a9b8d;
				}

				.info-value {
					margin-top: 6rpx;
					font-size: 28rpx;
					color: #1c2b1e;
					font-weight: 600;
				}
			}

			.info-arrow {
				font-size: 36rpx;
				color: #c5d0c7;
			}
		}
	}

.overview-card {
	padding: 28rpx 28rpx 12rpx;

	.overview-list {
		.overview-item {
			display: flex;
			align-items: center;
			padding: 18rpx 0;
			border-bottom: 1rpx solid #eef3ef;

			&:last-child {
				border-bottom: none;
			}

			.overview-label {
				font-size: 24rpx;
				color: #8a9b8d;
				flex-shrink: 0;
				width: 160rpx;
			}

			.overview-value {
				font-size: 28rpx;
				color: #1c2b1e;
				font-weight: 600;
				flex: 1;
			}
		}
	}
}

.gain-card {
	.section-title {
		color: #b88912;
	}
}

.flow-card {
	padding: 28rpx;

		.flow-list {
			.flow-item {
				display: flex;
				align-items: flex-start;
				margin-bottom: 24rpx;
				
				&:last-child {
					margin-bottom: 0;
				}
				
				.flow-number {
					width: 48rpx;
					height: 48rpx;
					border-radius: 50%;
					background: linear-gradient(135deg, #2bc24e 0%, #18af38 100%);
					color: #fff;
					font-size: 24rpx;
					font-weight: 700;
					display: flex;
					align-items: center;
					justify-content: center;
					margin-right: 20rpx;
					flex-shrink: 0;
					box-shadow: 0 6rpx 14rpx rgba(24, 175, 56, 0.22);
				}
				
				.flow-content {
					flex: 1;
					padding-top: 4rpx;
					
					.flow-title {
						font-size: 28rpx;
						font-weight: 700;
						color: #1c2b1e;
						margin-bottom: 4rpx;
					}
					
					.flow-desc {
						font-size: 24rpx;
						color: #6a7b6e;
					}
				}
			}
		}
	}

	.desc-card {
		padding: 28rpx;
		
		.desc-content {
			font-size: 26rpx;
			color: #3d4a40;
			line-height: 1.7;
		}
	}
	
	// 底部操作�?
	.bottom-bar {
		position: fixed;
		bottom: 0;
		left: 0;
		right: 0;
		padding: 16rpx 24rpx;
		padding-bottom: calc(16rpx + constant(safe-area-inset-bottom));
		padding-bottom: calc(16rpx + env(safe-area-inset-bottom));
		display: flex;
		align-items: center;
		background: rgba(255,255,255,0.96);
		box-shadow: 0 -8rpx 24rpx rgba(16, 48, 24, 0.08);
		z-index: 20;
		
		.share-btn {
			width: 96rpx;
			height: 88rpx;
			margin-right: 16rpx;
			border-radius: 24rpx;
			background: #e8f8ec;
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			flex-shrink: 0;
			
			.share-icon {
				margin-top: 2rpx;
				font-size: 18rpx;
				color: #18AF38;
				font-weight: 600;
			}
		}
		
		.action-area {
			flex: 1;
			display: flex;
			min-width: 0;
			
			.action-btn {
				flex: 1;
				height: 88rpx;
				margin-left: 12rpx;
				border-radius: 44rpx !important;
				font-size: 28rpx;
				font-weight: 700;
			}
			.action-btn:first-child {
				margin-left: 0;
			}
			
			.primary-btn {
				background: linear-gradient(135deg, #2bc24e 0%, #18af38 100%) !important;
				color: #fff !important;
				border: none !important;
			}

			.success-btn {
				background: #e8f8ec !important;
				color: #18AF38 !important;
				border: none !important;
			}

			.rank-btn {
				background: #fff4d8 !important;
				color: #b88912 !important;
				border: none !important;
				display: flex;
				align-items: center;
				justify-content: center;

				.btn-icon {
					font-size: 28rpx;
					margin-right: 6rpx;
				}
			}
		}
		
		.clock-actions {
			.action-btn:first-child {
				flex: 0.62;
			}
		}
	}
	
	// 打卡成功弹窗
	.success-wrap {
		display: flex;
		align-items: center;
		justify-content: center;
		width: 100%;
		height: 100%;
	}
	
	.success-modal {
		width: 560rpx;
		padding: 50rpx 40rpx;
		border-radius: 32rpx;
		background: #fff;
		text-align: center;
		box-shadow: 0 16rpx 40rpx rgba(24, 175, 56, 0.16);
		
		.success-icon {
			margin-bottom: 24rpx;
			
			.success-emoji {
				font-size: 80rpx;
			}
		}
		
		.success-title {
			font-size: 36rpx;
			font-weight: 700;
			color: #1c2b1e;
			margin-bottom: 16rpx;
		}
		
		.success-point {
			display: flex;
			align-items: baseline;
			justify-content: center;
			margin-bottom: 12rpx;
			
			.point-label {
				font-size: 26rpx;
				color: #6a7b6e;
				margin-right: 8rpx;
			}
			
			.point-value {
				font-size: 48rpx;
				font-weight: 700;
				color: #18AF38;
			}
		}
		
		.success-tip {
			font-size: 24rpx;
			color: #8a9b8d;
			margin-bottom: 32rpx;
		}
		
		.success-btn {
			display: flex;
			align-items: center;
			justify-content: center;
			width: 100%;
			height: 80rpx;
			border-radius: 40rpx;
			background: linear-gradient(135deg, #2bc24e 0%, #18af38 100%);
			color: #fff;
			font-size: 28rpx;
			font-weight: 700;
		}
	}

	.offline-pwd-entry {
		margin-top: 28rpx;
		text-align: center;
		font-size: 22rpx;
		color: #b0b8b2;
		text-decoration: underline;
	}

	.offline-pwd-box {
		margin-top: 24rpx;
	}

	.offline-loc-hint {
		line-height: 1.5;
		padding: 0 10rpx;
	}

	.clock-popup {
		padding: 40rpx 30rpx;
		padding-bottom: calc(40rpx + constant(safe-area-inset-bottom));
		padding-bottom: calc(40rpx + env(safe-area-inset-bottom));
		
		.popup-header {
			text-align: center;
			margin-bottom: 32rpx;
			
			.popup-title {
				font-size: 32rpx;
				font-weight: 700;
				color: #1c2b1e;
				margin-bottom: 8rpx;
			}
			
			.popup-subtitle {
				font-size: 24rpx;
				color: #8a9b8d;
			}
		}
		
		.upload-area {
			display: flex;
			justify-content: center;
			margin-bottom: 32rpx;
		}

		.clock-share-field {
			margin-bottom: 32rpx;

			.field-label {
				font-size: 28rpx;
				color: #1c2b1e;
				font-weight: 600;
				margin-bottom: 16rpx;
			}

			.field-optional {
				margin-left: 8rpx;
				font-size: 22rpx;
				font-weight: 400;
				color: #8a9b8d;
			}
		}
		
		.confirm-btn {
			width: 100%;
			height: 88rpx;
			border-radius: 44rpx !important;
			background: linear-gradient(135deg, #2bc24e 0%, #18af38 100%) !important;
			color: #fff !important;
			font-size: 28rpx;
			font-weight: 700;
			border: none !important;
		}
	}

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
				font-weight: 700;
				color: #1c2b1e;
				margin-bottom: 8rpx;
			}
			
			.popup-subtitle {
				font-size: 24rpx;
				color: #8a9b8d;
			}
		}
		
		.form-fields {
			max-height: 50vh;
			overflow-y: auto;
			
			.form-field {
				margin-bottom: 32rpx;
				
				.field-text {
					font-size: 28rpx;
					color: #3d4a40;
					line-height: 1.6;
				}
				
				.field-label {
					font-size: 28rpx;
					color: #1c2b1e;
					font-weight: 600;
					margin-bottom: 16rpx;
					
					.required {
						color: #E85D4C;
					}
				}
				
				.radio-options {
					display: flex;
					flex-wrap: wrap;
					
					.radio-option {
						padding: 14rpx 28rpx;
						margin-right: 16rpx;
						margin-bottom: 16rpx;
						border-radius: 24rpx;
						background: #f3f8f4;
						font-size: 26rpx;
						color: #3d4a40;
						border: 2rpx solid transparent;
						
						&.selected {
							background: #e8f8ec;
							color: #18AF38;
							border-color: #18AF38;
							font-weight: 700;
						}
					}
				}
			}
		}
		
		.confirm-btn {
			width: 100%;
			height: 88rpx;
			border-radius: 44rpx !important;
			background: linear-gradient(135deg, #2bc24e 0%, #18af38 100%) !important;
			color: #fff !important;
			font-size: 28rpx;
			font-weight: 700;
			border: none !important;
		}
	}
</style>

