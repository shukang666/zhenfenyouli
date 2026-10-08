<template>
	<view class="scene-clock-root">
		<view :class="['scf-page', hubTheme, 'scf-page--office']" v-if="hub">
				<view class="scf-body" v-if="flowStep === 'hub_empty'">
			<view class="scf-panel scf-office-tip">
				<text class="scf-office-tip-title">{{ communityEmptyTitle }}</text>
				<text class="scf-info-text">{{ communityEmptyDesc }}</text>
			</view>
			<view class="scf-foot scf-foot--single">
				<view class="scf-btn scf-btn--ghost" hover-class="scf-card--press" @tap="finishOnly">返回活动</view>
			</view>
		</view>

				<view class="scf-body scf-body--office-clock" v-if="flowStep === 'learn'">
			<view class="scf-oc-banner">
				<view class="scf-oc-banner-ph" :class="{ 'scf-oc-banner-ph--img': !!officeClockHeroImgUrl }">
					<image
						v-if="officeClockHeroImgUrl"
						class="scf-oc-banner-img"
						:src="officeClockHeroImgUrl"
						mode="widthFix"
						@error="onOfficeClockHeroImgError"
					/>
					<block v-else>
						<view class="scf-oc-banner-css" aria-hidden="true">
							<view class="scf-oc-banner-css-wall"></view>
							<view class="scf-oc-banner-css-bins">
								<view class="scf-oc-css-bin scf-oc-css-bin--y"></view>
								<view class="scf-oc-css-bin scf-oc-css-bin--g"></view>
								<view class="scf-oc-css-bin scf-oc-css-bin--b"></view>
							</view>
						</view>
						<view class="scf-oc-banner-overlay">
							<text class="scf-oc-banner-title">{{ officeClockBannerTitle }}</text>
							<text class="scf-oc-banner-sub">{{ officeClockBannerSub }}</text>
							<text class="scf-oc-banner-badge">{{ officeClockBannerBadge }}</text>
						</view>
					</block>
				</view>
			</view>

			<view class="scf-oc-alert">
				<view class="scf-oc-alert-accent" aria-hidden="true"></view>
				<view class="scf-oc-alert-ico-wrap">
					<view class="scf-oc-alert-ico-ring" aria-hidden="true"></view>
					<text class="scf-oc-alert-ico">♻️</text>
				</view>
				<view class="scf-oc-alert-body">
					<text class="scf-oc-alert-kicker">办公真分 · 可回收</text>
					<text class="scf-oc-alert-title">{{ officeClockRecycleAlertTitle }}</text>
					<text class="scf-oc-alert-desc">{{ officeClockRecycleAlertBody }}</text>
				</view>
				<view class="scf-oc-alert-deco" aria-hidden="true">
					<view class="scf-oc-alert-cam">
						<view class="scf-oc-alert-cam-top"></view>
						<view class="scf-oc-alert-cam-lens"></view>
						<view class="scf-oc-alert-cam-flash"></view>
					</view>
				</view>
			</view>

			<view class="scf-oc-card">
				<text class="scf-oc-card-title">{{ officeClockBuildingTitle }}</text>
				<view class="scf-oc-picker" hover-class="scf-card--press" @tap="openOfficeBuildingPicker">
					<text class="scf-oc-picker-t" :class="{ 'scf-oc-picker-t--ph': !officeBuildingKey }">
						{{ officeBuildingKey || officeClockBuildingPlaceholder }}
					</text>
					<text class="scf-oc-picker-arrow">▾</text>
				</view>
			</view>

			<view class="scf-oc-building-mask" v-if="officeBuildingPickerShow" @tap="closeOfficeBuildingPicker">
				<view class="scf-oc-building-sheet" @tap.stop="">
					<view class="scf-oc-building-sheet-head">
						<text class="scf-oc-building-sheet-title">选择办公大楼</text>
						<view class="scf-oc-building-sheet-close" hover-class="scf-card--press" @tap="closeOfficeBuildingPicker">
							<text>×</text>
						</view>
					</view>
					<view class="scf-oc-building-search">
						<text class="scf-oc-building-search-ico">🔍</text>
						<input
							class="scf-oc-building-search-input"
							type="text"
							:value="officeBuildingSearchQuery"
							placeholder="输入大楼名或地址筛选"
							confirm-type="search"
							@input="onOfficeBuildingSearchInput"
						/>
						<view
							v-if="officeBuildingSearchQuery"
							class="scf-oc-building-search-clear"
							hover-class="scf-card--press"
							@tap="clearOfficeBuildingSearch"
						>
							<text>清除</text>
						</view>
					</view>
					<scroll-view scroll-y class="scf-oc-building-list">
						<view
							v-for="(opt, officeBIdx) in officeBuildingFilteredOptions"
							:key="opt.id"
							class="scf-oc-building-item"
							:class="{ 'scf-oc-building-item--on': pointId === opt.id }"
							hover-class="scf-card--press"
							@tap="pickOfficeBuildingOptionByIndex(officeBIdx)"
						>
							<text class="scf-oc-building-item-name">{{ opt.label }}</text>
							<text v-if="opt.address" class="scf-oc-building-item-addr">{{ opt.address }}</text>
						</view>
						<view v-if="!officeBuildingFilteredOptions.length" class="scf-oc-building-empty">
							<text>没有匹配的大楼，换个关键词试试</text>
						</view>
					</scroll-view>
				</view>
			</view>

			<view class="scf-oc-card" v-if="officeBuildingKey && officeCornerPoint">
				<text class="scf-oc-card-title">{{ officeClockCornerTitle }}</text>
				<text
					class="scf-oc-corner-loc"
					:class="{ 'scf-oc-corner-loc--pending': officeCornerDisplayLocation === officeClockCornerPendingText }"
				>{{ officeCornerDisplayLocation }}</text>
				<image
					v-if="officeCornerPoint.point_image"
					class="scf-oc-corner-img"
					:src="officeCornerPoint.point_image"
					mode="aspectFill"
				/>
				<view v-else class="scf-oc-corner-img scf-oc-corner-img--ph">
					<text class="scf-oc-corner-ph-t">{{ officeClockCornerPendingText }}</text>
				</view>
			</view>

			<view class="scf-oc-card scf-oc-card--form">
				<text class="scf-oc-form-head">{{ officeClockConfirmTitle }}</text>

				<view class="scf-oc-form-row">
					<text class="scf-oc-form-no">1</text>
					<view class="scf-oc-form-body">
						<text class="scf-oc-form-label">{{ officeClockItemTitle }}</text>
						<view class="scf-oc-chip-row">
							<view
								v-for="(tag, itemTagIdx) in officeClockItemTags"
								:key="itemTagIdx"
								class="scf-oc-chip"
								:class="{ 'scf-oc-chip--on': officeItemTags.indexOf(tag) >= 0 }"
								@tap="toggleOfficeItemTagByIndex(itemTagIdx)"
							>{{ tag }}</view>
						</view>
						<view class="scf-oc-chip-row scf-oc-chip-row--sub">
							<view
								v-for="(tag, volTagIdx) in officeClockVolumeTags"
								:key="volTagIdx"
								class="scf-oc-chip scf-oc-chip--sm"
								:class="{ 'scf-oc-chip--on': officeVolumeTag === tag }"
								@tap="pickOfficeVolumeTagByIndex(volTagIdx)"
							>{{ tag }}</view>
						</view>
					</view>
				</view>

				<view class="scf-oc-form-row">
					<text class="scf-oc-form-no">2</text>
					<view class="scf-oc-form-body">
						<text class="scf-oc-form-label">{{ officeClockSaveTitle }}</text>
						<view class="scf-oc-chip-row">
							<view
								v-for="(opt, saveOptIdx) in officeClockSaveOptions"
								:key="saveOptIdx"
								class="scf-oc-chip"
								:class="{ 'scf-oc-chip--on': officeSaveDuration === opt }"
								@tap="pickOfficeSaveDurationByIndex(saveOptIdx)"
							>{{ opt }}</view>
						</view>
					</view>
				</view>

				<view class="scf-oc-form-row">
					<text class="scf-oc-form-no">3</text>
					<view class="scf-oc-form-body">
						<text class="scf-oc-form-label">{{ officeClockPhotoTitle }}</text>
						<view
							class="scf-oc-upload"
							:class="{ 'scf-oc-upload--done': !!clockSlotUrls[0] }"
							hover-class="scf-card--press"
							@tap="onClockSlotTap(0)"
						>
							<image v-if="clockSlotUrls[0]" class="scf-oc-upload-img" :src="clockSlotUrls[0]" mode="aspectFill" />
							<block v-else>
								<text class="scf-oc-upload-ico">📷</text>
								<text class="scf-oc-upload-t">{{ officeClockPhotoUploadCta }}</text>
							</block>
						</view>
					</view>
				</view>
			</view>

			<view class="scf-oc-foot">
				<view
					class="scf-oc-submit"
					:class="{ 'scf-oc-submit--disabled': clockSubmitting }"
					hover-class="scf-card--press"
					@tap="submitClock"
				>{{ clockSubmitting ? '提交中…' : officeClockSubmitCta }}</view>
			</view>
		</view>

		<!-- 打卡成功 -->
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
		this.hubPageOnLoad('office', option || {})
	},
	onShow() {
		this.hubPageOnShow()
	},
}
</script>

<style lang="scss" scoped>
@import '@/common/sceneClock/scene-clock-styles.scss';
</style>
