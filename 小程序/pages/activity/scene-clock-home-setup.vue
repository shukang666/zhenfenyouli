<template>
	<view class="scene-clock-root">
		<view :class="['scf-page', hubTheme]" v-if="hub">
			<view class="scf-body scf-body--setup scf-body--home-fix">
				<view class="scf-home-fix">
					<view class="scf-home-fix-hero">
						<image
							v-if="homeFixedHeroImgUrl"
							class="scf-home-fix-hero-banner-img"
							:src="homeFixedHeroImgUrl"
							mode="widthFix"
							@error="onHomeFixedHeroImgError"
						/>
						<view v-else class="scf-home-fix-hero-banner-ph">
							<text class="scf-home-fix-hero-banner-ph-t">{{ homeFixedHeroTitle }}</text>
							<text class="scf-home-fix-hero-banner-ph-s">{{ homeFixedHeroSub }}</text>
						</view>
					</view>

					<view class="scf-home-fix-sect">
						<view class="scf-home-fix-sect-head">
							<view class="scf-home-fix-sect-line"></view>
							<text class="scf-home-fix-sect-leaf">🍃</text>
							<text class="scf-home-fix-sect-t">{{ homeFixedSectionStartTitle }}</text>
							<text class="scf-home-fix-sect-leaf">🍃</text>
							<view class="scf-home-fix-sect-line"></view>
						</view>
						<text class="scf-home-fix-reward">{{ homeFixedRewardHint }}</text>
						<text class="scf-home-fix-required">{{ homeFixedRequiredHint }}</text>
						<view
							class="scf-home-fix-card"
							v-for="slot in homeFixedSlotsStart"
							:key="slot.slotKey"
						>
							<text class="scf-home-fix-card-title">{{ slot.title }}</text>
							<view class="scf-home-fix-card-mid">
								<text class="scf-home-fix-card-desc">{{ slot.desc }}</text>
								<view class="scf-home-fix-card-example">
									<image
										v-if="slot.exampleImgUrl && homeFixedExampleImgOk[slot.slotKey]"
										class="scf-home-fix-card-example-img"
										:src="slot.exampleImgUrl"
										mode="aspectFit"
										@error="onHomeFixedExampleImgError(slot.slotKey)"
									/>
									<view v-else class="scf-home-fix-card-example-ph">
										<text class="scf-home-fix-card-example-ph-t">示例图</text>
									</view>
									<text class="scf-home-fix-card-example-cap">{{ slot.exampleCaption }}</text>
								</view>
							</view>
							<view
								class="scf-home-fix-upload"
								:class="{ 'scf-home-fix-upload--done': slot.point_image_url }"
								hover-class="scf-card--press"
								@tap="chooseHomeFixedPhoto(slot.slotKey)"
							>
								<image
									v-if="slot.point_image_url"
									class="scf-home-fix-upload-img"
									:src="slot.point_image_url"
									mode="aspectFill"
								></image>
								<block v-else>
									<text class="scf-home-fix-upload-required">必填</text>
									<view class="scf-home-fix-upload-ico-wrap">
										<text class="scf-home-fix-upload-ico">📷</text>
									</view>
									<text class="scf-home-fix-upload-t">{{ slot.uploadCta }}</text>
								</block>
								<text v-if="slot.point_image_url" class="scf-home-fix-upload-badge">{{ slot.uploadDone }}</text>
							</view>
						</view>
					</view>

					<view class="scf-home-fix-sect">
						<view class="scf-home-fix-sect-head">
							<view class="scf-home-fix-sect-line"></view>
							<text class="scf-home-fix-sect-leaf">🍃</text>
							<text class="scf-home-fix-sect-t">{{ homeFixedSectionRecycleTitle }}</text>
							<text class="scf-home-fix-sect-leaf">🍃</text>
							<view class="scf-home-fix-sect-line"></view>
						</view>
						<view
							class="scf-home-fix-card"
							v-for="slot in homeFixedSlotsRecycleHarm"
							:key="slot.slotKey"
						>
							<text class="scf-home-fix-card-title">{{ slot.title }}</text>
							<view class="scf-home-fix-card-mid">
								<text class="scf-home-fix-card-desc">{{ slot.desc }}</text>
								<view class="scf-home-fix-card-example">
									<image
										v-if="slot.exampleImgUrl && homeFixedExampleImgOk[slot.slotKey]"
										class="scf-home-fix-card-example-img"
										:src="slot.exampleImgUrl"
										mode="aspectFit"
										@error="onHomeFixedExampleImgError(slot.slotKey)"
									/>
									<view v-else class="scf-home-fix-card-example-ph">
										<text class="scf-home-fix-card-example-ph-t">示例图</text>
									</view>
									<text class="scf-home-fix-card-example-cap">{{ slot.exampleCaption }}</text>
								</view>
							</view>
							<view
								class="scf-home-fix-upload"
								:class="{ 'scf-home-fix-upload--done': slot.point_image_url }"
								hover-class="scf-card--press"
								@tap="chooseHomeFixedPhoto(slot.slotKey)"
							>
								<image
									v-if="slot.point_image_url"
									class="scf-home-fix-upload-img"
									:src="slot.point_image_url"
									mode="aspectFill"
								></image>
								<block v-else>
									<text class="scf-home-fix-upload-required">必填</text>
									<view class="scf-home-fix-upload-ico-wrap">
										<text class="scf-home-fix-upload-ico">📷</text>
									</view>
									<text class="scf-home-fix-upload-t">{{ slot.uploadCta }}</text>
								</block>
								<text v-if="slot.point_image_url" class="scf-home-fix-upload-badge">{{ slot.uploadDone }}</text>
							</view>
						</view>
					</view>
				</view>
				<view class="scf-home-fix-foot">
					<view
						class="scf-home-fix-finish"
						:class="{ 'scf-home-fix-finish--disabled': !homeFixedCanFinishSetup }"
						hover-class="scf-home-fix-finish--press"
						@tap="onHomeFixedFinishAll"
					>{{ homeFixedFinishCta }}</view>
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
		this.hubPageOnLoad('home', option || {}, { homePage: 'setup' })
	},
	onShow() {
		this.hubPageOnShow()
	},
}
</script>

<style lang="scss" scoped>
@import '@/common/sceneClock/scene-clock-styles.scss';
</style>
