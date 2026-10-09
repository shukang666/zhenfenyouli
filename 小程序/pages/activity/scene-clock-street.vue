<template>
	<view class="scene-clock-root">
		<view :class="['scf-page', hubTheme]" v-if="hub">
		<view class="scf-body scf-body--street-merchant" v-if="isStreetHub && flowStep === 'street_merchant'">
			<view class="scf-sm-hero">
				<view class="scf-sm-hero-ph" :class="{ 'scf-sm-hero-ph--img': !!streetMerchantHeroImgUrl }">
					<image
						v-if="streetMerchantHeroImgUrl"
						class="scf-sm-hero-img"
						:src="streetMerchantHeroImgUrl"
						mode="widthFix"
						@error="onStreetMerchantHeroImgError"
					/>
					<block v-else>
						<view class="scf-sm-hero-css">
							<view class="scf-sm-hero-kitchen">
								<view class="scf-sm-bin scf-sm-bin--food"></view>
								<view class="scf-sm-bin scf-sm-bin--recycle"></view>
								<view class="scf-sm-bin scf-sm-bin--other"></view>
							</view>
						</view>
						<view class="scf-sm-hero-copy">
							<text class="scf-sm-hero-title">{{ streetMerchantHeroTitle }}</text>
							<text class="scf-sm-hero-sub">{{ streetMerchantHeroSub }}</text>
						</view>
					</block>
				</view>
			</view>

			<view class="scf-sm-card">
				<text class="scf-sm-card-title">{{ streetClaimTitle }}</text>

				<view class="scf-sm-field">
					<view class="scf-sm-field-head">
						<text class="scf-sm-field-ico">👤</text>
						<text class="scf-sm-field-label">{{ streetClaimCompanyLabel }}</text>
					</view>
					<input
						class="scf-sm-input"
						type="text"
						v-model="streetFormShopName"
						:placeholder="streetClaimShopNamePh"
						placeholder-class="scf-sm-input-ph"
						maxlength="64"
						:adjust-position="true"
					/>
				</view>

				<view class="scf-sm-type-block">
					<text class="scf-sm-type-label">{{ streetClaimTypeHint }}</text>
					<view class="scf-sm-cat-row">
						<view
							v-for="(grp, gIdx) in streetShopTypeGroups"
							:key="gIdx"
							class="scf-sm-cat-pill"
							:class="{ 'scf-sm-cat-pill--on': streetActiveCategoryId === grp.id }"
							hover-class="scf-card--press"
							@tap="pickStreetCategory(grp.id)"
						>{{ grp.name }}</view>
					</view>
					<view class="scf-sm-chip-row">
						<view
							v-for="(typ, tIdx) in streetMerchantTypeChips"
							:key="tIdx"
							class="scf-sm-chip"
							:class="{ 'scf-sm-chip--on': streetSelectedTypeKey === typ.key }"
							hover-class="scf-card--press"
							@tap="pickStreetShopTypeKey(typ.key)"
						>{{ typ.name }}</view>
					</view>
					<text class="scf-sm-cate-hint" v-if="streetFormCateName">主要垃圾种类：{{ streetFormCateName }}</text>
				</view>
			</view>

			<view class="scf-sm-foot">
				<view
					class="scf-sm-submit"
					hover-class="scf-card--press"
					@tap="submitStreetClaim"
				>{{ streetClaimSubmit }}</view>
			</view>
		</view>

		<!-- 办公：尚未配置统一回收点 -->
		<view class="scf-body" v-if="flowStep === 'hub_empty'">
			<view class="scf-panel scf-office-tip">
				<text class="scf-office-tip-title">{{ communityEmptyTitle }}</text>
				<text class="scf-info-text">{{ communityEmptyDesc }}</text>
			</view>
			<view class="scf-foot scf-foot--single">
				<view class="scf-btn scf-btn--ghost" hover-class="scf-card--press" @tap="finishOnly">返回活动</view>
			</view>
		</view>

		<!-- 首次：设置分类打卡点（仅家庭/办公等摆桶，不含「为什么要分」） -->
		<view
			class="scf-body scf-body--setup"
			:class="{
				'scf-body--pick-active': isOwnPointHub && residentialPickMode,
				'scf-body--home-fix': isResidentialHub && !residentialPickMode,
			}"
			v-if="flowStep === 'setup'"
		>
			<view class="scf-street-shop-bar" v-if="isStreetHub && streetMerchant && !residentialPickMode">
				<text class="scf-street-shop-bar-name">{{ streetMerchant.shop_name }}</text>
				<text class="scf-street-shop-bar-meta">{{ streetMerchant.shop_type_name }} · {{ streetMerchant.cate_name }}</text>
				<text class="scf-street-shop-bar-edit" @tap="editStreetMerchant">{{ streetEditShop }}</text>
			</view>
			<block v-if="isOwnPointHub && residentialPickMode && !isResidentialHub">
				<view class="scf-pick-lead">
					<text class="scf-pick-lead-t">{{ ownPointPickLead }}</text>
					<text class="scf-pick-lead-s">{{ ownPointPickSub }}</text>
				</view>
				<view class="scf-bin-grid scf-bin-grid--pick">
					<view
						class="scf-bin-tile scf-bin-tile--pick-spot"
						v-for="p in residentialPickPoints"
						:key="p.id"
						:class="'scf-bin-tile--' + cateTone(p.cate_name)"
						hover-class="scf-card--press"
						@tap.stop="pickResidentialPoint(p)"
					>
						<view class="scf-bin-tile-head">
							<view class="scf-bin-tile-photo">
								<image v-if="p.point_image" class="scf-bin-tile-photo-img" :src="p.point_image" mode="aspectFill"></image>
								<view v-else class="scf-bin-tile-photo-empty">
									<text class="scf-bin-tile-photo-ico">{{ cateEmoji(p.cate_name) }}</text>
								</view>
							</view>
							<text class="scf-bin-tile-emoji">{{ cateEmoji(p.cate_name) }}</text>
						</view>
						<view class="scf-bin-tile-body scf-bin-tile-body--readonly">
							<text class="scf-bin-tile-name-read">{{ p.point_name }}</text>
							<text class="scf-bin-tile-cate-read">{{ p.cate_name }}</text>
							<text class="scf-bin-tile-pick-go">上传成果 ›</text>
						</view>
					</view>
				</view>
				<text class="scf-pick-mask-hint">{{ ownPointPickMaskHint }}</text>
				<view class="scf-settings-fold" v-if="points.length && !isResidentialHub">
					<view
						class="scf-settings-fold-head"
						hover-class="scf-card--press"
						@tap.stop="toggleResidentialBinSettings"
					>
						<view class="scf-settings-fold-head-l">
							<text class="scf-settings-fold-title">{{ ownPointSettingsFoldTitle }}</text>
							<text class="scf-settings-fold-hint">{{ ownPointSettingsFoldHint }}</text>
						</view>
						<text class="scf-settings-fold-arrow">{{ residentialBinSettingsOpen ? '收起 ▴' : '展开 ▾' }}</text>
					</view>
					<view class="scf-settings-fold-body" v-if="residentialBinSettingsOpen" @tap.stop="stopTapBubble">
						<view class="scf-bin-grid scf-bin-grid--manage-inline">
							<view
								class="scf-bin-tile scf-bin-tile--compact"
								v-for="(p, mIdx) in points"
								:key="p._draftKey || p.id"
								:class="['scf-bin-tile--' + cateTone(manageCateName(p)), p._isDraft ? 'scf-bin-tile--draft' : '']"
							>
								<view class="scf-bin-tile-head">
									<view class="scf-bin-tile-photo" hover-class="scf-card--press" @tap="chooseManagePhoto(mIdx)">
										<image v-if="p._edit_image_url" class="scf-bin-tile-photo-img" :src="p._edit_image_url" mode="aspectFill"></image>
										<view v-else class="scf-bin-tile-photo-empty">
											<text class="scf-bin-tile-photo-ico">📷</text>
										</view>
									</view>
									<text class="scf-bin-tile-rm" @tap.stop="deleteResidentialPointByIndex(mIdx)">×</text>
								</view>
								<view class="scf-bin-tile-body">
									<input
										class="scf-bin-tile-input"
										:value="p._edit_name"
										placeholder="名称"
										maxlength="16"
										@input="onManageNameInput(mIdx, $event)"
									/>
									<view v-if="isStreetHub" class="scf-bin-tile-cate scf-bin-tile-cate--locked">
										<text class="scf-bin-tile-cate-t">{{ manageCateName(p) }}</text>
									</view>
									<picker
										v-else
										mode="selector"
										:range="catePickerLabels"
										:value="p._edit_cate_index"
										@change="onManageCateChangeByIndex(mIdx, $event)"
									>
										<view class="scf-bin-tile-cate">
											<text class="scf-bin-tile-cate-t">{{ catePickerLabels[p._edit_cate_index] || '选种类' }}</text>
											<text class="scf-bin-tile-cate-arrow">▾</text>
										</view>
									</picker>
									<view class="scf-bin-tile-save scf-bin-tile-save--inline" hover-class="scf-card--press" @tap="savePointEditByIndex(mIdx)">{{ p._isDraft ? '保存新桶位' : '保存此项' }}</view>
								</view>
							</view>
						</view>
						<view class="scf-settings-fold-actions">
							<view class="scf-settings-fold-add" v-if="!isStreetHub" hover-class="scf-card--press" @tap="addPointInResidentialSettings">＋ 添加桶位</view>
							<view class="scf-settings-fold-done" hover-class="scf-card--press" @tap="closeResidentialBinSettings">完成，返回选桶</view>
						</view>
					</view>
				</view>
			</block>
			<block v-else-if="isResidentialHub">
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
						<view class="scf-home-fix-card" v-if="homeFixedKitchenLeadSlot">
							<text class="scf-home-fix-card-title">{{ homeFixedKitchenLeadSlot.title }}</text>
							<view class="scf-home-fix-card-mid">
								<text class="scf-home-fix-card-desc">{{ homeFixedKitchenLeadSlot.desc }}</text>
								<view class="scf-home-fix-card-example">
									<image
										v-if="homeFixedKitchenLeadSlot.exampleImgUrl && homeFixedExampleImgOk[homeFixedKitchenLeadSlot.slotKey]"
										class="scf-home-fix-card-example-img"
										:src="homeFixedKitchenLeadSlot.exampleImgUrl"
										mode="aspectFit"
										@error="onHomeFixedExampleImgError(homeFixedKitchenLeadSlot.slotKey)"
									/>
									<view v-else class="scf-home-fix-card-example-ph">
										<text class="scf-home-fix-card-example-ph-t">示例图</text>
									</view>
									<text class="scf-home-fix-card-example-cap">{{ homeFixedKitchenLeadSlot.exampleCaption }}</text>
								</view>
							</view>
							<view class="scf-home-fix-upload-row">
								<view
									class="scf-home-fix-upload-col"
									v-for="slot in homeFixedKitchenUploadSlots"
									:key="slot.slotKey"
								>
									<text v-if="slot.uploadLabel" class="scf-home-fix-upload-lbl">{{ slot.uploadLabel }}</text>
									<view
										class="scf-home-fix-upload scf-home-fix-upload--half"
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
						<view
							class="scf-home-fix-card"
							v-for="slot in homeFixedSlotsStartStandalone"
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
			</block>
			<block v-else-if="isStreetHub">
				<view class="scf-st-setup">
					<view class="scf-st-setup-head">
						<text class="scf-st-setup-title">{{ streetSetupPageTitle }}</text>
						<text class="scf-st-setup-sub">{{ streetSetupPageSub }}</text>
						<view class="scf-st-setup-shop" v-if="streetMerchant">
							<text class="scf-st-setup-shop-name">{{ streetMerchant.shop_name }}</text>
							<text class="scf-st-setup-shop-type">{{ streetMerchant.shop_type_name }}</text>
						</view>
					</view>
					<view
						class="scf-st-slot"
						v-for="slot in streetFixedSlots"
						:key="slot.slotKey"
					>
						<view class="scf-st-slot-accent" :class="'scf-st-slot-accent--' + slot.tone"></view>
						<view class="scf-st-slot-main">
							<view class="scf-st-slot-head">
								<text class="scf-st-slot-tag">{{ slot.tag }}</text>
								<text class="scf-st-slot-cate">{{ slot.cateKw }}</text>
							</view>
							<text class="scf-st-slot-title">{{ slot.title }}</text>
							<text class="scf-st-slot-desc">{{ slot.desc }}</text>
							<view
								class="scf-st-upload"
								:class="{ 'scf-st-upload--done': slot.point_image_url }"
								hover-class="scf-card--press"
								@tap="chooseStreetFixedPhoto(slot.slotKey)"
							>
								<image
									v-if="slot.point_image_url"
									class="scf-st-upload-img"
									:src="slot.point_image_url"
									mode="aspectFill"
								/>
								<block v-else>
									<text class="scf-st-upload-ico">📷</text>
									<text class="scf-st-upload-t">{{ slot.uploadCta }}</text>
								</block>
								<text v-if="slot.point_image_url" class="scf-st-upload-badge">{{ slot.uploadDone }}</text>
							</view>
						</view>
					</view>
					<view class="scf-st-setup-foot">
						<view
							class="scf-st-finish"
							:class="{ 'scf-st-finish--disabled': !streetFixedCanFinishSetup }"
							hover-class="scf-card--press"
							@tap="onStreetFixedFinishAll"
						>{{ streetSetupFinishCta }}</view>
						<text class="scf-st-edit-link" @tap="editStreetMerchant">{{ streetEditShop }}</text>
					</view>
				</view>
			</block>
			<block v-else-if="!isStreetHub">
			<view class="scf-promo scf-promo--compact" :class="'scf-promo--' + hubPromoSkin">
				<text class="scf-promo-line">{{ binSetupLead }}</text>
				<text class="scf-promo-line scf-promo-line--sub">{{ binSetupLeadNote }}</text>
				<text class="scf-promo-line scf-promo-line--sub" v-if="isResidentialHub">{{ introDisplaySummary }}</text>
			</view>
			<view class="scf-home-plan" v-if="isResidentialHub && binLayoutExamples.length">
				<text class="scf-home-plan-title">{{ homePlanTitle }}</text>
				<view class="scf-floor">
					<view class="scf-floor-shell">
						<view class="scf-floor-row">
							<view
								v-for="ex in binLayoutExamplesTop"
								:key="ex.planId"
								class="scf-floor-room"
								:class="'scf-floor-room--' + ex.planId"
							>
								<text class="scf-floor-room-name">{{ ex.room }}</text>
								<view class="scf-floor-bins">
									<text
										v-for="(tag, ti) in ex.tags"
										:key="ti"
										class="scf-floor-bin"
										:class="'scf-floor-bin--' + tag.tone"
									>{{ tag.label }}</text>
								</view>
							</view>
						</view>
						<view class="scf-floor-row scf-floor-row--living">
							<view class="scf-floor-room scf-floor-room--living">
								<text class="scf-floor-room-name">{{ binLayoutLiving.room }}</text>
								<view class="scf-floor-bins">
									<text
										v-for="(tag, ti) in binLayoutLiving.tags"
										:key="ti"
										class="scf-floor-bin"
										:class="'scf-floor-bin--' + tag.tone"
									>{{ tag.label }}</text>
								</view>
							</view>
						</view>
						<view class="scf-floor-row scf-floor-row--bottom">
							<view class="scf-floor-room scf-floor-room--fixed">
								<text class="scf-floor-room-name">{{ binLayoutFixed.room }}</text>
								<view class="scf-floor-bins">
									<text
										v-for="(tag, ti) in binLayoutFixed.tags"
										:key="ti"
										class="scf-floor-bin"
										:class="'scf-floor-bin--' + tag.tone"
									>{{ tag.label }}</text>
								</view>
							</view>
							<view class="scf-floor-room scf-floor-room--entry">
								<text class="scf-floor-room-name scf-floor-room-name--entry">玄关</text>
								<text class="scf-floor-entry-hint">入户</text>
							</view>
						</view>
						<view class="scf-floor-door-mark">
							<text class="scf-floor-door-ico">⌵</text>
							<text class="scf-floor-door-t">门</text>
						</view>
					</view>
					<view class="scf-floor-legend">
						<view class="scf-floor-legend-item" v-for="ex in binLayoutExamples" :key="ex.planId">
							<text class="scf-floor-legend-dot" :class="'scf-floor-legend-dot--' + ex.planId"></text>
							<text class="scf-floor-legend-text"><text class="scf-floor-legend-room">{{ ex.room }}：</text>{{ ex.tip }}</text>
						</view>
					</view>
				</view>
				<text class="scf-home-plan-foot">{{ homePlanFoot }}</text>
			</view>
			<view class="scf-section-kicker" v-if="isResidentialHub">
				<text class="scf-section-kicker-t">拍下您家的桶</text>
				<text class="scf-section-kicker-s">仅首次需要设置，以后在选桶页「展开」里增删改</text>
			</view>
			<view class="scf-section-kicker" v-else-if="isStreetHub">
				<text class="scf-section-kicker-t">{{ streetSetupKicker }}</text>
				<text class="scf-section-kicker-s">{{ streetSetupSub }}</text>
			</view>
			<view class="scf-promo scf-promo--compact scf-promo--light" v-else-if="!isResidentialHub && !isStreetHub">
				<text class="scf-promo-line scf-promo-line--dark">{{ introDisplayLead }}</text>
				<text class="scf-promo-line scf-promo-line--subdark">{{ introDisplaySummary }}</text>
			</view>
			<view class="scf-bin-grid">
				<view
					class="scf-bin-tile"
					v-for="(row, idx) in setupRows"
					:key="idx"
					:class="'scf-bin-tile--' + cateTone(setupCateName(row))"
				>
					<view class="scf-bin-tile-head">
						<view class="scf-bin-tile-photo" hover-class="scf-card--press" @tap="chooseSetupPhoto(idx)">
							<image v-if="row.point_image_url" class="scf-bin-tile-photo-img" :src="row.point_image_url" mode="aspectFill"></image>
							<view v-else class="scf-bin-tile-photo-empty">
								<text class="scf-bin-tile-photo-ico">📷</text>
								<text class="scf-bin-tile-photo-lab">拍桶照</text>
							</view>
							<text class="scf-bin-tile-photo-badge" v-if="row.point_image_url">换图</text>
							<text class="scf-bin-tile-photo-badge scf-bin-tile-photo-badge--need" v-else>必填</text>
						</view>
						<text class="scf-bin-tile-emoji">{{ cateEmoji(setupCateName(row)) }}</text>
						<text
							class="scf-bin-tile-rm"
							v-if="setupRows.length > 1"
							@tap.stop="removeSetupRow(idx)"
						>×</text>
					</view>
					<view class="scf-bin-tile-body">
						<input
							class="scf-bin-tile-input"
							v-model="row.point_name"
							:placeholder="isStreetHub ? streetSetupNamePh : '名称，如厨房桶'"
							maxlength="16"
						/>
						<view v-if="isStreetHub" class="scf-bin-tile-cate scf-bin-tile-cate--locked">
							<text class="scf-bin-tile-cate-t">{{ setupCateName(row) || streetMerchant.cate_name }}</text>
						</view>
						<picker
							v-else
							class="scf-bin-tile-picker"
							mode="selector"
							:range="catePickerLabels"
							:value="row.cate_index"
							@change="onSetupCateChange(idx, $event)"
						>
							<view class="scf-bin-tile-cate">
								<text class="scf-bin-tile-cate-t">{{ catePickerLabels[row.cate_index] || '选四分类' }}</text>
								<text class="scf-bin-tile-cate-arrow">▾</text>
							</view>
						</picker>
						<text class="scf-bin-tile-hint">{{ cateHintLines(row) }}</text>
					</view>
				</view>
				<view class="scf-bin-tile scf-bin-tile--add" v-if="!isStreetHub" hover-class="scf-card--press" @tap="addSetupRow">
					<text class="scf-bin-tile-add-ico">＋</text>
					<text class="scf-bin-tile-add-lab">再添一处</text>
					<text class="scf-bin-tile-add-sub">扔垃圾的位置</text>
				</view>
			</view>
			<view class="scf-foot scf-foot--single scf-foot--setup">
				<text class="scf-setup-foot-hint" v-if="isResidentialHub">{{ residentialSetupFootnote }}</text>
				<text class="scf-setup-foot-hint" v-else-if="isStreetHub">{{ streetSetupFootnote }}</text>
				<view class="scf-btn scf-btn--primary" hover-class="scf-card--press" @tap="saveSetup">{{ setupSaveCta }}</view>
			</view>
			</block>
		</view>

		<view
			v-if="isOwnPointHub && residentialPickMode && !isResidentialHub && flowStep === 'setup'"
			class="scf-pick-mask"
			@tap="exitResidentialPickMode"
		></view>

		<!-- 打卡点列表（打卡入口，家庭场景在摆桶页蒙层选桶，不单独进此页） -->
		<view class="scf-body" v-if="flowStep === 'points' && !isResidentialHub && !isStreetHub && !isOfficeHub">
			<view class="scf-promo scf-promo--compact" :class="'scf-promo--' + hubPromoSkin">
				<text class="scf-promo-line">{{ isResidentialHub ? residentialPickLead : introDisplayLead }}</text>
				<text class="scf-promo-line scf-promo-line--sub">{{ isResidentialHub ? residentialPickSub : introDisplaySummary }}</text>
			</view>
			<view class="scf-points-head">
				<text class="scf-hint scf-hint--inline">{{ pointsStepHint }}</text>
				<text class="scf-manage-link" v-if="!isCommunityHub" @tap="enterManage">改分类位置</text>
			</view>
			<view class="scf-point-grid">
				<view
					v-for="(group, gIdx) in pointGroups"
					:key="group.key"
					class="scf-point-card scf-point-card--cluster"
				>
					<view class="scf-cate-hero" :class="'scf-cate-hero--' + cateTone(group.cate_name)">
						<text class="scf-cate-hero-kicker">垃圾分类</text>
						<text class="scf-cate-hero-name">{{ group.cate_name }}</text>
						<text class="scf-cate-hero-count" v-if="group.items.length > 1">{{ group.items.length }} 个点位</text>
					</view>
					<text class="scf-cate-desc-lines">{{ cateHintByName(group.cate_name) }}</text>
					<view class="scf-cluster-list">
						<view
							class="scf-cluster-item"
							v-for="(p, pIdx) in group.items"
							:key="p.id"
							hover-class="scf-card--press"
							@tap="pickClusterPoint(gIdx, pIdx)"
						>
							<image
								v-if="p.point_image"
								class="scf-cluster-photo"
								:src="p.point_image"
								mode="aspectFill"
							></image>
							<view v-else class="scf-cluster-photo scf-cluster-photo--empty">
								<text class="scf-cluster-photo-t">暂无点位照片</text>
							</view>
							<view class="scf-cluster-item-foot">
								<text class="scf-cluster-item-name">{{ p.point_name }}</text>
								<text class="scf-point-go">去拍照 ›</text>
							</view>
						</view>
					</view>
				</view>
			</view>
			<view class="scf-foot scf-foot--single" v-if="!points.length && !isCommunityHub">
				<view class="scf-btn scf-btn--primary" @tap="goSetupStep">去设置家里分类位置</view>
			</view>
		</view>

		<!-- 管理打卡点 -->
		<view class="scf-body" v-if="flowStep === 'manage'">
			<view class="scf-promo scf-promo--compact scf-promo--light">
				<text class="scf-promo-line scf-promo-line--dark">改名字、种类或照片</text>
				<text class="scf-promo-line scf-promo-line--subdark">删掉位置后，以前交过的记录还在</text>
			</view>
			<view class="scf-bin-grid">
				<view
					class="scf-bin-tile"
					v-for="(p, mIdx) in points"
					:key="p.id"
					:class="'scf-bin-tile--' + cateTone(manageCateName(p))"
				>
					<view class="scf-bin-tile-head">
						<view class="scf-bin-tile-photo" hover-class="scf-card--press" @tap="chooseManagePhoto(mIdx)">
							<image v-if="p._edit_image_url" class="scf-bin-tile-photo-img" :src="p._edit_image_url" mode="aspectFill"></image>
							<view v-else class="scf-bin-tile-photo-empty">
								<text class="scf-bin-tile-photo-ico">📷</text>
								<text class="scf-bin-tile-photo-lab">拍桶照</text>
							</view>
							<text class="scf-bin-tile-photo-badge">换图</text>
						</view>
						<text class="scf-bin-tile-emoji">{{ cateEmoji(manageCateName(p)) }}</text>
					</view>
					<view class="scf-bin-tile-body">
						<input
							class="scf-bin-tile-input"
							:value="p._edit_name"
							placeholder="名称，如厨房桶"
							maxlength="16"
							@input="onManageNameInput(mIdx, $event)"
						/>
						<picker
							class="scf-bin-tile-picker"
							mode="selector"
							:range="catePickerLabels"
							:value="p._edit_cate_index"
							@change="onManageCateChangeByIndex(mIdx, $event)"
						>
							<view class="scf-bin-tile-cate">
								<text class="scf-bin-tile-cate-t">{{ catePickerLabels[p._edit_cate_index] || p.cate_name || '选四分类' }}</text>
								<text class="scf-bin-tile-cate-arrow">▾</text>
							</view>
						</picker>
						<text class="scf-bin-tile-hint">{{ cateHintByName(manageCateName(p)) }}</text>
						<view class="scf-bin-tile-foot">
							<view class="scf-bin-tile-save" hover-class="scf-card--press" @tap="savePointEditByIndex(mIdx)">保存</view>
							<text class="scf-bin-tile-rmtxt" @tap.stop="deletePointById(p.id)">删除</text>
						</view>
					</view>
				</view>
				<view class="scf-bin-tile scf-bin-tile--add" hover-class="scf-card--press" @tap="addPointInManage">
					<text class="scf-bin-tile-add-ico">＋</text>
					<text class="scf-bin-tile-add-lab">新增点位</text>
				</view>
			</view>
			<view class="scf-foot scf-foot--single">
				<view class="scf-btn scf-btn--ghost" @tap="backFromManage">返回选位置</view>
			</view>
		</view>

		<!-- 处理指引 -->
		<view class="scf-body" v-if="flowStep === 'guide'">
			<view class="scf-promo scf-promo--compact" :class="'scf-promo--' + hubPromoSkin">
				<text class="scf-promo-line">{{ activeCateName || '分类' }} · 怎么投</text>
				<text class="scf-promo-line scf-promo-line--sub" v-if="pointName">{{ pointName }}</text>
			</view>
			<view class="scf-panel scf-panel--card">
				<view class="scf-cate-hero scf-cate-hero--panel" :class="'scf-cate-hero--' + cateTone(activeCateName)">
					<text class="scf-cate-hero-kicker">垃圾分类</text>
					<text class="scf-cate-hero-name">{{ activeCateName }}</text>
				</view>
				<text v-if="pointName" class="scf-guide-point-name">{{ pointName }}</text>
				<view class="scf-info-block">
					<text class="scf-info-title">这一步怎么做</text>
					<text class="scf-info-text">{{ guide.handle_guide }}</text>
				</view>
				<view class="scf-info-block scf-info-block--time">
					<text class="scf-info-title">什么时候扔合适</text>
					<text class="scf-info-text">{{ guide.dispose_time }}</text>
				</view>
			</view>
			<view class="scf-foot">
				<view class="scf-btn scf-btn--ghost" hover-class="scf-card--press" @tap="backFromGuide">上一步</view>
				<view class="scf-btn scf-btn--primary" hover-class="scf-card--press" @tap="goFlowStepSurvey">看明白了，继续</view>
			</view>
		</view>

		<!-- 问卷 / 技巧 -->
		<view class="scf-body" v-if="flowStep === 'survey'">
			<view class="scf-promo scf-promo--compact" :class="'scf-promo--' + hubPromoSkin">
				<text class="scf-promo-line">选一个小窍门</text>
				<text class="scf-promo-line scf-promo-line--sub">{{ guide.survey_question }}</text>
			</view>
			<view class="scf-tip-list">
				<view
					v-for="(tip, idx) in presetTips"
					:key="idx"
					class="scf-tip-chip"
					:class="{ 'scf-tip-chip--on': userChoice === tip }"
					@tap="selectPresetTip(tip)"
				>
					<text>{{ tip }}</text>
				</view>
			</view>
			<view class="scf-custom">
				<text class="scf-custom-label">也可以写一句您自己的小窍门（选填）</text>
				<textarea class="scf-textarea" v-model="customTip" placeholder="比如：外卖盒先冲一下再扔" maxlength="200" />
			</view>
			<view class="scf-foot">
				<view class="scf-btn scf-btn--ghost" hover-class="scf-card--press" @tap="goFlowStepGuide">上一步</view>
				<view class="scf-btn scf-btn--primary" hover-class="scf-card--press" @tap="goLearnStep">选好了，去拍照</view>
			</view>
		</view>

		<!-- 沿街：今日真分（与家庭同逻辑，界面独立） -->
		<view class="scf-body scf-body--shop-clock" v-if="flowStep === 'learn' && isStreetHub">
			<view class="scf-sk-shop-bar" v-if="streetMerchant">
				<view class="scf-sk-shop-bar-l">
					<text class="scf-sk-shop-name">{{ streetMerchant.shop_name }}</text>
					<text class="scf-sk-shop-hint">{{ streetClockShopBarHint }}</text>
				</view>
				<text class="scf-sk-shop-edit" @tap="editStreetMerchant">{{ streetEditShop }}</text>
			</view>

			<view class="scf-sk-panel" v-if="streetClockCateCards.length">
				<text class="scf-sk-panel-k">种类</text>
				<text class="scf-sk-panel-title">{{ streetClockStep1Title }}</text>
				<text class="scf-sk-panel-sub">{{ streetClockStep1Sub }}</text>
				<view
					v-if="streetClockCateCards.length === 1"
					class="scf-sk-single-cate"
					:class="'scf-sk-single-cate--' + streetClockCateCards[0].tone"
				>
					<text class="scf-sk-single-cate-name">{{ streetClockCateCards[0].cateName }}</text>
					<text class="scf-sk-single-cate-shop" v-if="streetMerchant">{{ streetMerchant.shop_type_name }}</text>
				</view>
			</view>

			<view class="scf-sk-panel" v-if="homeClockCateKey">
				<text class="scf-sk-panel-k">STEP 1</text>
				<text class="scf-sk-panel-title">{{ streetClockStep2Title }}</text>
				<view class="scf-sk-amount-row">
					<view
						v-for="opt in homeClockAmountOptions"
						:key="opt"
						class="scf-sk-amount-pill"
						:class="{ 'scf-sk-amount-pill--on': homeClockAmount === opt }"
						hover-class="scf-card--press"
						@tap="pickHomeClockAmount(opt)"
					>{{ opt }}</view>
				</view>
			</view>

			<view class="scf-sk-panel scf-sk-panel--photo" v-if="homeClockCateKey">
				<text class="scf-sk-panel-k">STEP 2</text>
				<text class="scf-sk-panel-title">{{ streetClockPhotoTitle }}</text>
				<text class="scf-sk-panel-sub">{{ streetClockPhotoSub }}</text>
				<view
					class="scf-sk-photo-box"
					:class="{ 'scf-sk-photo-box--done': !!clockSlotUrls[0] }"
					hover-class="scf-card--press"
					@tap="onClockSlotTap(0)"
				>
					<image v-if="clockSlotUrls[0]" class="scf-sk-photo-img" :src="clockSlotUrls[0]" mode="aspectFill" />
					<block v-else>
						<text class="scf-sk-photo-ico">📷</text>
						<text class="scf-sk-photo-t">{{ homeClockPhotoUploadCta }}</text>
					</block>
					<view
						v-if="clockSlotUrls[0]"
						class="scf-photo-del scf-photo-del--round"
						@tap.stop="clearClockSlot(0)"
					>×</view>
				</view>
				<text class="scf-sk-point-hint" v-if="pointName">对应点位：{{ pointName }}</text>
			</view>

			<view class="scf-sk-foot">
				<view
					class="scf-sk-submit"
					:class="{ 'scf-sk-submit--disabled': clockSubmitting }"
					hover-class="scf-card--press"
					@tap="submitClock"
				>{{ clockSubmitting ? '提交中…' : streetClockSubmitCta }}</view>
				<text class="scf-sk-back-setup" @tap="enterStreetSetupFlow">调整店内点位布置</text>
			</view>
		</view>

		<!-- 其他场景：拍照真分 -->
		<view class="scf-body" v-else-if="flowStep === 'learn'">
			<view class="scf-promo scf-promo--compact" :class="'scf-promo--' + hubPromoSkin">
				<text class="scf-promo-line">上传 {{ activeCateName || '分类' }} 成果</text>
				<text class="scf-promo-line scf-promo-line--sub">{{ clockPhotoIntro }}</text>
			</view>
			<view class="scf-clock-card">

				<view class="scf-clock-photo-head scf-clock-photo-head--row">
					<text class="scf-clock-photo-title">{{ isResidentialHub ? '上传分类成果照片' : '按顺序拍 3 张照片' }}</text>
					<text class="scf-clock-photo-progress">{{ clockSlotFilledCount }}/{{ clockPhotoSlotMeta.length }}</text>
				</view>

				<view class="scf-slot-row">
					<view
						v-for="(slot, sIdx) in clockPhotoSlotMeta"
						:key="slot.key"
						class="scf-slot-wrap"
					>
						<view v-if="sIdx > 0" class="scf-slot-arrow">›</view>
						<view class="scf-slot">
							<view class="scf-slot-head">
								<text class="scf-slot-step">{{ sIdx + 1 }}</text>
								<text class="scf-slot-title">{{ slot.title }}</text>
							</view>
							<text class="scf-slot-hint">{{ slot.hint }}</text>
							<view
								class="scf-slot-box"
								:class="{ 'scf-slot-box--done': !!clockSlotUrls[sIdx] }"
								hover-class="scf-card--press"
								@tap="onClockSlotTap(sIdx)"
							>
								<image
									v-if="clockSlotUrls[sIdx]"
									class="scf-slot-img"
									:src="clockSlotUrls[sIdx]"
									mode="aspectFill"
								></image>
								<view v-else class="scf-slot-placeholder">
									<text class="scf-slot-emoji">{{ slot.emoji }}</text>
									<text class="scf-slot-add-t">点击拍摄</text>
								</view>
								<view
									v-if="clockSlotUrls[sIdx]"
									class="scf-photo-del scf-slot-del"
									@tap.stop="clearClockSlot(sIdx)"
								>×</view>
							</view>
						</view>
					</view>
				</view>
				<text class="scf-clock-photo-foot">{{ clockPhotoFootnote }}</text>

				<view class="scf-duration-block" v-if="isResidentialHub">
					<text class="scf-duration-label">这些垃圾大概积累了多久？</text>
					<view class="scf-tip-list scf-tip-list--wrap">
						<view
							v-for="(opt, idx) in accumDurationOptions"
							:key="'a' + idx"
							class="scf-tip-chip"
							:class="{ 'scf-tip-chip--on': accumDuration === opt }"
							@tap="accumDuration = opt"
						>
							<text>{{ opt }}</text>
						</view>
					</view>
					<text class="scf-duration-label">分出这个效果大概用了多久？</text>
					<view class="scf-tip-list scf-tip-list--wrap">
						<view
							v-for="(opt, idx) in sortDurationOptions"
							:key="'s' + idx"
							class="scf-tip-chip"
							:class="{ 'scf-tip-chip--on': sortDuration === opt }"
							@tap="sortDuration = opt"
						>
							<text>{{ opt }}</text>
						</view>
					</view>
				</view>

				<view class="scf-clock-note">
					<text class="scf-clock-note-label">补充说明（选填）</text>
					<textarea
						class="scf-textarea scf-clock-textarea"
						v-model="clockContent"
						:placeholder="clockNotePlaceholder"
						maxlength="500"
					/>
				</view>

				<view
					class="scf-btn scf-btn--primary scf-btn--block scf-clock-submit"
					:class="{ 'scf-clock-submit--disabled': clockSubmitting }"
					hover-class="scf-card--press"
					@tap="submitClock"
				>{{ clockSubmitting ? '提交中…' : '提交本次打卡' }}</view>

				<view class="scf-learn-link" hover-class="scf-card--press" @tap="goLearn">
					<text>还想学分类知识？</text>
					<text class="scf-learn-link-go">去学习 ›</text>
				</view>
			</view>
			<view class="scf-foot scf-foot--single">
				<view class="scf-btn scf-btn--ghost" hover-class="scf-card--press" @tap="backFromLearn">{{ isResidentialHub ? '上一步' : '上一步' }}</view>
			</view>
		</view>

				<!-- 打卡成功 -->
		<view class="scf-success-mask" v-if="clockSuccessShow" @tap="goActivityTab">
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
	onLoad(option) {
		this.hubPageOnLoad('street', option || {})
	},
	onShow() {
		this.hubPageOnShow()
	},
}
</script>

<style lang="scss" scoped>
@import '@/common/sceneClock/scene-clock-styles.scss';
</style>
