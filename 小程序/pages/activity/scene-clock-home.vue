<template>
	<view class="scene-clock-root">
		<view :class="['scf-page', hubTheme]" v-if="hub">
			<view class="scf-body scf-body--home-clock" v-if="flowStep === 'learn'">
				<view class="scf-hc-banner">
					<view class="scf-hc-banner-ph" :class="{ 'scf-hc-banner-ph--img': !!homeClockBannerImgUrl }">
						<image
							v-if="homeClockBannerImgUrl"
							class="scf-hc-banner-img"
							:src="homeClockBannerImgUrl"
							mode="widthFix"
							@error="onHomeClockBannerImgError"
						/>
						<text v-else class="scf-hc-banner-slogan">{{ homeClockBannerSlogan }}</text>
					</view>
				</view>

				<view class="scf-hc-step">
					<text class="scf-hc-step-no">①</text>
					<view class="scf-hc-step-body">
						<text class="scf-hc-step-title">{{ homeClockStep1Title }}</text>
						<text class="scf-hc-step-sub">{{ homeClockStep1Sub }}</text>
						<view class="scf-hc-cate-row">
							<view
								v-for="card in homeClockCateCards"
								:key="card.key"
								class="scf-hc-cate-card"
								:class="[
									'scf-hc-cate-card--' + card.tone,
									{ 'scf-hc-cate-card--on': homeClockCateKey === card.key },
								]"
								hover-class="scf-card--press"
								@tap="selectHomeClockCate(card)"
							>
								<view class="scf-hc-cate-illus">
									<text class="scf-hc-cate-illus-ph">{{ card.illusPh }}</text>
								</view>
								<text class="scf-hc-cate-label">{{ card.label }}</text>
							</view>
						</view>
					</view>
				</view>

				<view class="scf-hc-step">
					<text class="scf-hc-step-no">②</text>
					<view class="scf-hc-step-body">
						<text class="scf-hc-step-title">{{ homeClockStep2Title }}</text>
						<view class="scf-hc-amount-row">
							<view
								v-for="opt in homeClockAmountOptions"
								:key="opt"
								class="scf-hc-amount-chip"
								:class="{ 'scf-hc-amount-chip--on': homeClockAmount === opt }"
								hover-class="scf-card--press"
								@tap="pickHomeClockAmount(opt)"
							>{{ opt }}</view>
						</view>
						<text class="scf-hc-amount-note">● {{ homeClockAmountNote }}</text>
					</view>
				</view>

				<view class="scf-hc-step">
					<text class="scf-hc-step-no">③</text>
					<view class="scf-hc-step-body">
						<text class="scf-hc-step-title">{{ homeClockStep3Title }}</text>
						<view class="scf-hc-tip-box">
							<textarea
								class="scf-hc-tip-input"
								v-model="homeClockTip"
								:placeholder="homeClockStep3Placeholder"
								maxlength="200"
								:auto-height="false"
							/>
						</view>
					</view>
				</view>

				<view class="scf-hc-step scf-hc-step--photo">
					<text class="scf-hc-step-no">④</text>
					<view class="scf-hc-step-body">
						<view class="scf-hc-photo-head">
							<view class="scf-hc-photo-head-l">
								<text class="scf-hc-step-title">{{ homeClockStep4Title }}</text>
								<text class="scf-hc-step-sub scf-hc-step-sub--tight">{{ homeClockStep4Sub }}</text>
							</view>
							<view class="scf-hc-photo-example">
								<view class="scf-hc-photo-example-ph">
									<text class="scf-hc-photo-example-ph-t">示例</text>
								</view>
								<text class="scf-hc-photo-example-cap">{{ homeClockPhotoExampleCap }} →</text>
							</view>
						</view>
						<view
							class="scf-hc-upload"
							:class="{ 'scf-hc-upload--done': !!clockSlotUrls[0] }"
							hover-class="scf-card--press"
							@tap="onClockSlotTap(0)"
						>
							<image
								v-if="clockSlotUrls[0]"
								class="scf-hc-upload-img"
								:src="clockSlotUrls[0]"
								mode="aspectFill"
							/>
							<block v-else>
								<text class="scf-hc-upload-ico">📷</text>
								<text class="scf-hc-upload-t">{{ homeClockPhotoUploadCta }}</text>
							</block>
						</view>
						<view class="scf-hc-rules">
							<view
								class="scf-hc-rule"
								v-for="(rule, ri) in homeClockPhotoRules"
								:key="ri"
							>
								<text class="scf-hc-rule-ico">{{ rule.ok ? '✓' : '✕' }}</text>
								<text class="scf-hc-rule-t" :class="{ 'scf-hc-rule-t--ok': rule.ok }">{{ rule.text }}</text>
							</view>
						</view>
					</view>
				</view>

				<view class="scf-hc-foot">
					<view
						class="scf-hc-submit"
						:class="{ 'scf-hc-submit--disabled': clockSubmitting }"
						hover-class="scf-card--press"
						@tap="submitClock"
					>{{ clockSubmitting ? '提交中…' : homeClockSubmitCta }}</view>
					<text class="scf-hc-foot-note">{{ homeClockFootNote }}</text>
					<text class="scf-hc-foot-points">{{ homeClockFootPoints }}</text>
					<text class="scf-hc-foot-link" hover-class="scf-card--press" @tap="goResidentialHomeSetup">修改家庭点位布置图</text>
				</view>
			</view>

			<view class="scf-success-mask" v-if="clockSuccessShow" @tap="goActivityTab">
				<view class="scf-success-dialog" @tap.stop="">
					<text class="scf-success-emoji">✓</text>
					<text class="scf-success-title">{{ clockSuccessMsg || '打卡成功' }}</text>
					<text class="scf-success-sub">记得投进对的桶；积分到账可能稍有延迟，请稍后在「我的积分」查看。</text>

					<view class="scf-success-next">
						<text class="scf-success-next-k">{{ clockSuccessNextTitle }}</text>

						<view class="scf-success-tip">
							<text class="scf-success-tip-h">{{ clockSuccessOtherPickTitle }}</text>
							<text class="scf-success-tip-p">{{ clockOtherPickHint }}</text>
							<view class="scf-success-cate-chips" v-if="clockSuccessOtherPickList.length">
								<view
									v-for="item in clockSuccessOtherPickList"
									:key="item.key"
									class="scf-success-cate-chip"
									hover-class="scf-card--press"
									@tap="clockSuccessJumpOtherPick(item)"
								>
									<text>去打卡 {{ item.label }}</text>
								</view>
							</view>
						</view>

						<view class="scf-success-tip">
							<text class="scf-success-tip-h">{{ clockSuccessPromoTitle }}</text>
							<text class="scf-success-tip-p">{{ clockPromoIntro }}</text>
							<view class="scf-promo-act" v-if="clockPromoActivity" hover-class="scf-card--press" @tap="goPromoActivity">
								<text class="scf-promo-act-badge">为您推荐</text>
								<text class="scf-promo-act-title">{{ clockPromoActivity.title || clockPromoActivity.name }}</text>
								<text class="scf-promo-act-go">了解活动详情 ›</text>
							</view>
							<view class="scf-promo-more" v-if="clockPromoMoreLabel" hover-class="scf-card--press" @tap="goActivityList">
								<text>{{ clockPromoMoreLabel }}</text>
							</view>
						</view>
					</view>
					<view class="scf-habit-box" v-if="clockHabitTip">
						<text class="scf-habit-kicker">养成好习惯</text>
						<text class="scf-habit-text">{{ clockHabitTip }}</text>
					</view>
					<view class="scf-success-actions">
						<view class="scf-btn scf-btn--primary scf-btn--block" @tap="clockSuccessContinue">{{ isOwnPointHub ? (isStreetHub ? '再选点打卡' : '再选桶打卡') : '再选位置拍照' }}</view>
						<view class="scf-btn scf-btn--ghost scf-btn--block scf-success-back" @tap="goActivityTab">返回活动</view>
					</view>
				</view>
			</view>
		</view>
		<view v-else class="scene-clock-loading">
			<text class="scene-clock-loading-t">加载中…</text>
		</view>
	</view>
</template>

<script>
import sceneClockPageMixin from '@/common/sceneClock/sceneClockPageMixin.js'

export default {
	mixins: [sceneClockPageMixin],
	onLoad(option) {
		this.hubPageOnLoad('home', option || {}, { homePage: 'clock' })
	},
	onShow() {
		this.hubPageOnShow()
	},
}
</script>

<style lang="scss" scoped>
@import '@/common/sceneClock/scene-clock-styles.scss';
</style>
