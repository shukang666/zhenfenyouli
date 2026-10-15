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

				<view class="scf-hc-rhythm-note">
					<text class="scf-hc-rhythm-note-t">{{ homeClockSortRhythmNote }}</text>
				</view>

				<view class="scf-hc-step">
					<text class="scf-hc-step-no">①</text>
					<view class="scf-hc-step-body">
						<view class="scf-ps-q">
							<text class="scf-ps-q-t">{{ pointSetupQuestionText }}</text>
							<text class="scf-ps-q-link" hover-class="scf-ps-q-link--press" @tap="goPointSetupGuide">{{ pointSetupGuideLinkText }}</text>
						</view>
						<view class="scf-ps-ans-row">
							<view
								class="scf-ps-ans"
								:class="{ 'scf-ps-ans--on': setupPointAnswer === 'yes' }"
								hover-class="scf-card--press"
								@tap="pickSetupPointAnswer('yes')"
							>是</view>
							<view
								class="scf-ps-ans"
								:class="{ 'scf-ps-ans--off': setupPointAnswer === 'no' }"
								hover-class="scf-card--press"
								@tap="pickSetupPointAnswer('no')"
							>否</view>
						</view>
						<text v-if="setupPointAnswer !== 'yes'" class="scf-ps-gate-hint">{{ pointSetupNoHint }}</text>
					</view>
				</view>

				<view class="scf-hc-step" :class="{ 'scf-ps-gate-lock': !canEditZhenfenSteps }">
					<text class="scf-hc-step-no">②</text>
					<view class="scf-hc-step-body">
						<text class="scf-hc-step-title">{{ homeClockStep2MultiTitle }}</text>
						<text class="scf-hc-step-sub">{{ homeClockStep2MultiSub }}</text>

						<view class="scf-hc-preset-row">
							<view
								v-for="preset in homeClockCatePresets"
								:key="preset.id"
								class="scf-hc-preset"
								:class="{ 'scf-hc-preset--on': homeClockCatePresetId === preset.id }"
								hover-class="scf-card--press"
								@tap="applyHomeClockCatePreset(preset)"
							>
								<text class="scf-hc-preset-label">{{ preset.label }}</text>
								<text class="scf-hc-preset-hint">{{ preset.hint }}</text>
							</view>
						</view>
					</view>
				</view>

				<view class="scf-hc-step scf-hc-step--photo" :class="{ 'scf-ps-gate-lock': !canEditZhenfenSteps }">
					<text class="scf-hc-step-no">③</text>
					<view class="scf-hc-step-body">

						<view class="scf-hc-photo-head">
							<view class="scf-hc-photo-head-l">
								<text class="scf-hc-step-title">{{ homeClockStep3PhotoTitle }}</text>
								<text class="scf-hc-step-sub scf-hc-step-sub--tight">{{ homeClockStep4Sub }}</text>
							</view>
							<view class="scf-hc-photo-example">
								<image
									v-if="homeClockPhotoExampleImgUrl"
									class="scf-hc-photo-example-img"
									:src="homeClockPhotoExampleImgUrl"
									mode="aspectFill"
									@error="onHomeClockPhotoExampleImgError"
								/>
								<view v-else class="scf-hc-photo-example-ph">
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
							<view
								v-if="clockSlotUrls[0]"
								class="scf-photo-del scf-photo-del--round"
								@tap.stop="clearClockSlot(0)"
							>×</view>
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
						:class="{ 'scf-hc-submit--disabled': clockSubmitting || !canEditZhenfenSteps }"
						hover-class="scf-card--press"
						@tap="submitClock"
					>{{ clockSubmitting ? '提交中…' : homeClockSubmitCta }}</view>
					<text class="scf-hc-foot-note">{{ homeClockFootNote }}</text>
					<text class="scf-hc-foot-points">{{ homeClockFootPoints }}</text>
				</view>

			</view>

			<view class="scf-success-mask" v-if="isClockSuccessVisible" @tap="goActivityTab">

				<view class="scf-success-dialog" @tap.stop="">

					<text class="scf-success-emoji">✓</text>

					<text class="scf-success-title">{{ clockSuccessMsg || '真分成功' }}</text>

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

									<text>去真分 {{ item.label }}</text>

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

	computed: {
		isClockSuccessVisible() {
			return this.clockSuccessShow === true
		},
	},

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
