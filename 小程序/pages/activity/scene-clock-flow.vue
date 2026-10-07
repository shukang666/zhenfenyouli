<template>
	<!-- uni-app 小程序要求单根节点，否则可能白屏且无报错 -->
	<view class="scene-clock-root">
	<!-- 原型 Screen 2：欢迎参与真分打卡 -->
	<view v-if="flowStep === 'theme_intro'" class="zf-intro">
		<view class="zf-intro-hero">
			<view class="zf-intro-sky"></view>
			<view class="zf-intro-hill zf-intro-hill--back"></view>
			<view class="zf-intro-hill zf-intro-hill--front"></view>
			<view class="zf-intro-people">
				<text class="zf-intro-people-ico">👨‍👩‍👧</text>
				<text class="zf-intro-people-ico">♻️</text>
				<text class="zf-intro-people-ico">🌳</text>
			</view>
			<view class="zf-intro-hero-copy">
				<text class="zf-intro-hero-l1">{{ themeHeroSlogan.line1 }}</text>
				<text class="zf-intro-hero-l2">{{ themeHeroSlogan.line2 }}</text>
			</view>
		</view>
		<view class="zf-intro-sheet">
			<text class="zf-intro-welcome">{{ themeHeadline }}</text>
			<text class="zf-intro-sub">{{ themeSubline }}</text>
			<view class="zf-intro-step" v-for="step in themeSteps" :key="step.num">
				<view class="zf-intro-step-no">{{ step.num }}</view>
				<view class="zf-intro-step-body">
					<view class="zf-intro-step-head">
						<text class="zf-intro-step-ico">{{ step.icon }}</text>
						<text class="zf-intro-step-title">{{ step.title }}</text>
					</view>
					<text class="zf-intro-step-desc">{{ step.desc }}</text>
				</view>
			</view>
		</view>
		<view class="zf-foot">
			<view class="zf-btn zf-btn--primary" hover-class="zf-btn--press" @tap="onThemeStart">{{ themeCta }}</view>
		</view>
	</view>

	<!-- 选场景首屏（欢迎 + 三场景卡片，配图 COS 可后续替换） -->
	<view v-else-if="flowStep === 'theme_scenes'" class="zf-scenes zf-scenes--pick">
		<view class="zf-pick-welcome">
			<image
				v-if="scenePickWelcomeImgUrl"
				class="zf-pick-welcome__img"
				:src="scenePickWelcomeImgUrl"
				mode="widthFix"
				@error="onScenePickWelcomeImgError"
			/>
			<view v-else class="zf-pick-ph zf-pick-ph--welcome">
				<text class="zf-pick-welcome__txt">{{ scenePickWelcomeBanner }}</text>
			</view>
		</view>

		<view class="zf-pick-title-block">
			<image
				v-if="scenePickTitleImgSrc"
				class="zf-pick-title__img"
				:src="scenePickTitleImgSrc"
				mode="widthFix"
				@error="onScenePickTitleImgError"
			/>
			<text v-else class="zf-pick-title__txt">{{ scenePickMainTitle }}</text>
		</view>

		<view class="zf-pick-list">
			<view
				class="zf-pick-card"
				v-for="(item, sIdx) in sceneHubCardsForPick"
				:key="item.hub"
				hover-class="zf-pick-card--press"
				:hover-stay-time="80"
				@tap="pickThemeHubByIndex(sIdx)"
			>
				<view class="zf-pick-card__illus">
					<image
						v-if="item.illusSrc"
						class="zf-pick-card__img"
						:src="item.illusSrc"
						mode="aspectFill"
						:data-illus-key="item.illusKey"
						@error="onScenePickCardImgErrorEvt"
					/>
					<view v-else class="zf-pick-ph zf-pick-ph--card">
						<text class="zf-pick-ph-label">场景配图</text>
					</view>
				</view>
				<view class="zf-pick-card__body">
					<text class="zf-pick-card__title">{{ item.title }}</text>
					<text class="zf-pick-card__desc">{{ item.desc }}</text>
					<view class="zf-pick-card__go" aria-hidden="true">
						<text class="zf-pick-card__go-ico">›</text>
					</view>
				</view>
			</view>
		</view>
	</view>

	<!-- 原型 Screen 5：今日真分任务说明 -->
	<view v-else-if="flowStep === 'theme_brief'" class="zf-brief">
		<text class="zf-brief-tagline">{{ themeBriefTagline }}</text>
		<view class="zf-brief-card">
			<text class="zf-brief-card-title">{{ themeBriefTaskTitle }}</text>
			<view class="zf-brief-tasks">
				<view class="zf-brief-task" v-for="(t, i) in themeBriefTasks" :key="i">
					<view class="zf-brief-task-ico-wrap">
						<text class="zf-brief-task-ico">{{ t.icon }}</text>
					</view>
					<text class="zf-brief-task-label">{{ t.label }}</text>
					<text class="zf-brief-task-sub">{{ t.sub }}</text>
				</view>
			</view>
		</view>
		<view class="zf-brief-scene">
			<text class="zf-brief-scene-k">您选的场景</text>
			<text class="zf-brief-scene-v">{{ pendingHubLabel }}</text>
		</view>
		<view class="zf-foot zf-foot--inline">
			<view class="zf-btn zf-btn--primary" hover-class="zf-btn--press" @tap="startHubFlow">{{ themeBriefCta }}</view>
		</view>
	</view>

	<view v-else-if="hub" :class="['scf-page', hubTheme]">
		<view class="scf-hero">
			<text class="scf-hero-kicker">{{ hub.title }}</text>
			<text class="scf-hero-title">{{ stepTitle }}</text>
			<view class="scf-steps" v-if="visibleStepLabels.length">
				<view
					v-for="(lab, idx) in visibleStepLabels"
					:key="idx"
					class="scf-step"
					:class="{ 'scf-step--on': stepIndex >= idx, 'scf-step--cur': stepIndex === idx }"
				>
					<text class="scf-step-num">{{ idx + 1 }}</text>
					<text class="scf-step-lab">{{ lab }}</text>
				</view>
			</view>
		</view>

		<!-- 沿街：认领商铺（与家庭身份独立） -->
		<view class="scf-body scf-body--street" v-if="isStreetHub && flowStep === 'street_merchant'">
			<view class="scf-panel scf-street-claim">
				<text class="scf-street-claim-title">{{ streetClaimTitle }}</text>
				<text class="scf-info-text scf-street-claim-lead">{{ streetClaimLead }}</text>
				<view class="scf-street-field">
					<text class="scf-street-label">商铺名称</text>
					<input
						class="scf-street-input"
						type="text"
						v-model="streetFormShopName"
						:placeholder="streetClaimShopNamePh"
						placeholder-class="scf-street-input-ph"
						maxlength="64"
						:adjust-position="true"
					/>
				</view>
				<view class="scf-street-field">
					<text class="scf-street-label">商铺类型</text>
					<text class="scf-street-field-hint">{{ streetClaimTypeHint }}</text>
					<picker mode="selector" :range="streetShopTypeLabels" :value="streetFormTypeIndex" @change="onStreetTypePick">
						<view class="scf-street-picker">{{ streetShopTypeLabels[streetFormTypeIndex] || '请选择类型' }}</view>
					</picker>
					<text class="scf-street-cate-tag" v-if="streetFormCateName">主要种类：{{ streetFormCateName }}</text>
				</view>
			</view>
			<view class="scf-foot scf-foot--single">
				<view class="scf-btn scf-btn--primary" hover-class="scf-card--press" @tap="submitStreetClaim">{{ streetClaimSubmit }}</view>
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
			:class="{ 'scf-body--pick-active': isOwnPointHub && residentialPickMode }"
			v-if="flowStep === 'setup'"
		>
			<view class="scf-street-shop-bar" v-if="isStreetHub && streetMerchant && !residentialPickMode">
				<text class="scf-street-shop-bar-name">{{ streetMerchant.shop_name }}</text>
				<text class="scf-street-shop-bar-meta">{{ streetMerchant.shop_type_name }} · {{ streetMerchant.cate_name }}</text>
				<text class="scf-street-shop-bar-edit" @tap="editStreetMerchant">{{ streetEditShop }}</text>
			</view>
			<block v-if="isOwnPointHub && residentialPickMode">
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
				<view class="scf-settings-fold" v-if="points.length">
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
			<block v-else>
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
						<view class="scf-floor-legend-item" v-for="ex in binLayoutExamples" :key="'lg-' + ex.planId">
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
			v-if="isOwnPointHub && residentialPickMode && flowStep === 'setup'"
			class="scf-pick-mask"
			@tap="exitResidentialPickMode"
		></view>

		<!-- 打卡点列表（打卡入口，家庭场景在摆桶页蒙层选桶，不单独进此页） -->
		<view class="scf-body" v-if="flowStep === 'points' && !isResidentialHub && !isStreetHub">
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
				<view class="scf-btn scf-btn--primary" hover-class="scf-card--press" @tap="flowStep = 'survey'">看明白了，继续</view>
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
					@tap="userChoice = tip"
				>
					<text>{{ tip }}</text>
				</view>
			</view>
			<view class="scf-custom">
				<text class="scf-custom-label">也可以写一句您自己的小窍门（选填）</text>
				<textarea class="scf-textarea" v-model="customTip" placeholder="比如：外卖盒先冲一下再扔" maxlength="200" />
			</view>
			<view class="scf-foot">
				<view class="scf-btn scf-btn--ghost" hover-class="scf-card--press" @tap="flowStep = 'guide'">上一步</view>
				<view class="scf-btn scf-btn--primary" hover-class="scf-card--press" @tap="goLearnStep">选好了，去拍照</view>
			</view>
		</view>

		<!-- 拍照打卡（原跳转 add 页，现页内完成） -->
		<view class="scf-body" v-if="flowStep === 'learn'">
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
	import settings, { uploadUrl } from '@/common/settings'
	import { parseUploadAssetResponse, urlToRelativePath, resolveAssetShowUrl } from '@/common/uploadAsset.js'
	import { pickRandomHabitTip } from '@/common/clockHabitTips.js'
	import { getHub, mergeGuide, normalizeClockHubKey } from '@/common/sceneClockHub.js'
	import { buildSceneCateSubtitle, guideCateLabelFromName, resolveCateKind, SECTION_HINTS } from '@/common/clockScene.js'
	import {
		getStepTitles,
		getIntroLead,
		getIntroSummary,
		getIntroBlocks,
		getSetupIntroLine,
		getWhyIntroLead,
		getWhyIntroSummary,
		getCommunityEmptyTitle,
		getCommunityEmptyDesc,
		getCommunityIntroLead,
		getCommunityPointsDesc,
		getPointsPickSummary,
		getPointsStepHint,
		getThemeIntroHeadline,
		getThemeIntroSubline,
		getThemeIntroSteps,
		getThemeIntroCta,
		getScenePickNavTitle,
		getScenePickKicker,
		getScenePickHint,
		getScenePickWelcomeBanner,
		getScenePickMainTitle,
		getThemeOptInEyebrow,
		getThemeOptInTitle,
		getThemeOptInDesc,
		getThemeOptInYes,
		getThemeOptInLater,
		getSceneHubCards,
		getStreetClaimTitle,
		getStreetClaimLead,
		getStreetClaimShopNamePh,
		getStreetClaimTypeHint,
		getStreetClaimSubmit,
		getStreetSetupKicker,
		getStreetSetupSub,
		getStreetSetupNamePh,
		getStreetSetupFootnote,
		getStreetSetupNextCta,
		getStreetPickLead,
		getStreetPickSub,
		getStreetPickMaskHint,
		getStreetBinSettingsFoldTitle,
		getStreetBinSettingsFoldHint,
		getStreetEditShop,
		getThemePromoPillars,
		getThemeIntroHeroSlogan,
		getThemeBriefTagline,
		getThemeBriefTaskTitle,
		getThemeBriefTasks,
		getThemeBriefCta,
		getBinLayoutExamples,
		getHomePlanTitle,
		getHomePlanFoot,
		getBinSetupLead,
		getBinSetupLeadNote,
		getAccumDurationOptions,
		getSortDurationOptions,
		getClockSuccessNextTitle,
		getClockSuccessOtherCateTitle,
		getClockSuccessOtherCateBody,
		getClockSuccessOtherBinTitle,
		getClockSuccessOtherBinBody,
		getResidentialPickLead,
		getResidentialPickSub,
		getResidentialSetupNextCta,
		getResidentialSetupFootnote,
		getResidentialBinSettingsFoldTitle,
		getResidentialBinSettingsFoldHint,
		getResidentialPickMaskHint,
		getClockSuccessPromoTitle,
		getClockSuccessPromoBody,
		getClockSuccessPromoEmptyBody,
		getClockSuccessPromoMoreLabel,
	} from '@/common/sceneClockUserCopy.js'

	export default {
		data() {
			return {
				hubKey: '',
				hub: null,
				flowStep: 'points',
				pointsBootstrapped: false,
				points: [],
				pointsLoading: false,
				residentialPickMode: false,
				residentialBinSettingsOpen: false,
				setupRows: [{ point_name: '', cate_index: 0, point_image: '', point_image_url: '' }],
				cateList: [],
				pointId: 0,
				pointName: '',
				subKey: '',
				cateLabel: '',
				activeCateName: '',
				cateId: 0,
				guide: {
					handle_guide: '',
					dispose_time: '',
					survey_question: '',
					preset_tips: [],
					learn_path: '/pages/dtsh/knowledge',
				},
				userChoice: '',
				customTip: '',
				clockSlotUrls: ['', '', ''],
				clockContent: '',
				clockSubmitting: false,
				clockSuccessShow: false,
				clockSuccessMsg: '',
				clockHabitTip: '',
				uploadUrl,
				introExpanded: false,
				themeHeadline: getThemeIntroHeadline(),
				themeSubline: getThemeIntroSubline(),
				themeSteps: getThemeIntroSteps(),
				themeCta: getThemeIntroCta(),
				scenePickKicker: getScenePickKicker(),
				scenePickHint: getScenePickHint(),
				themeOptInReveal: true,
				/** null=加载中，默认展示「欢迎回来」图；false=从未场景真分；true=已有记录 */
				scenePickHasClock: null,
				scenePickImgOk: {
					welcomeNew: true,
					welcomeBack: true,
					title: false,
					home: false,
					office: false,
					street: false,
				},
				themeOptInEyebrow: getThemeOptInEyebrow(),
				themeOptInTitle: getThemeOptInTitle(),
				themeOptInDesc: getThemeOptInDesc(),
				themeOptInYes: getThemeOptInYes(),
				themeOptInLater: getThemeOptInLater(),
				sceneHubCards: getSceneHubCards(),
				themePillars: getThemePromoPillars(),
				themeHeroSlogan: getThemeIntroHeroSlogan(),
				themeBriefTagline: getThemeBriefTagline(),
				themeBriefTaskTitle: getThemeBriefTaskTitle(),
				themeBriefTasks: getThemeBriefTasks(),
				themeBriefCta: getThemeBriefCta(),
				pendingHubKey: '',
				streetMerchant: null,
				streetShopTypes: [],
				streetFormShopName: '',
				streetFormTypeIndex: 0,
				streetClaimTitle: getStreetClaimTitle(),
				streetClaimLead: getStreetClaimLead(),
				streetClaimShopNamePh: getStreetClaimShopNamePh(),
				streetClaimTypeHint: getStreetClaimTypeHint(),
				streetClaimSubmit: getStreetClaimSubmit(),
				streetSetupKicker: getStreetSetupKicker(),
				streetSetupSub: getStreetSetupSub(),
				streetSetupNamePh: getStreetSetupNamePh(),
				streetSetupFootnote: getStreetSetupFootnote(),
				streetSetupNextCta: getStreetSetupNextCta(),
				streetEditShop: getStreetEditShop(),
				binLayoutExamples: [],
				binSetupLead: getBinSetupLead(),
				binSetupLeadNote: getBinSetupLeadNote(),
				homePlanTitle: getHomePlanTitle(),
				homePlanFoot: getHomePlanFoot(),
				accumDuration: '',
				sortDuration: '',
				accumDurationOptions: getAccumDurationOptions(),
				sortDurationOptions: getSortDurationOptions(),
				clockPromoActivity: null,
				clockPromoList: [],
			}
		},
		computed: {
			scenePickCosBase() {
				const base = settings.staticAssetBase || ''
				return base + (base.endsWith('/') ? '' : '/') + 'scene-pick/'
			},
			scenePickMainTitle() {
				return getScenePickMainTitle()
			},
			scenePickWelcomeBanner() {
				if (this.scenePickHasClock === false) {
					return getScenePickWelcomeBanner(false)
				}
				return getScenePickWelcomeBanner(true)
			},
			scenePickWelcomeImgUrl() {
				const ok = this.scenePickImgOk || {}
				const base = this.scenePickCosBase
				if (this.scenePickHasClock === false) {
					return ok.welcomeNew ? base + 'welcome-new.png' : ''
				}
				if (ok.welcomeBack) {
					return base + 'welcome-back.png'
				}
				return ''
			},
			scenePickTitleImgSrc() {
				const ok = this.scenePickImgOk || {}
				return ok.title ? this.scenePickCosBase + 'title.png' : ''
			},
			sceneHubCardsForPick() {
				const ok = this.scenePickImgOk || {}
				const base = this.scenePickCosBase
				return (this.sceneHubCards || []).map(item => {
					const key = item.illusKey || ''
					const illusSrc = key && ok[key] ? base + key + '.png' : ''
					return Object.assign({}, item, { illusSrc })
				})
			},
			isResidentialHub() {
				return this.hubKey === 'home'
			},
			isOwnPointHub() {
				return this.isResidentialHub || (this.isStreetHub && !!this.streetMerchant)
			},
			setupSaveCta() {
				if (this.isResidentialHub) return this.residentialSetupNextCta
				if (this.isStreetHub) return this.streetSetupNextCta
				return '保存，去选位置拍照'
			},
			ownPointPickLead() {
				return this.isStreetHub ? getStreetPickLead() : this.residentialPickLead
			},
			ownPointPickSub() {
				return this.isStreetHub ? getStreetPickSub() : this.residentialPickSub
			},
			ownPointPickMaskHint() {
				return this.isStreetHub ? getStreetPickMaskHint() : this.residentialPickMaskHint
			},
			ownPointSettingsFoldTitle() {
				return this.isStreetHub ? getStreetBinSettingsFoldTitle() : this.residentialBinSettingsFoldTitle
			},
			ownPointSettingsFoldHint() {
				return this.isStreetHub ? getStreetBinSettingsFoldHint() : this.residentialBinSettingsFoldHint
			},
			/** 选桶高亮区：不含折叠里未保存的草稿 */
			residentialPickPoints() {
				return (this.points || []).filter(p => p && p.id && !p._isDraft)
			},
			binLayoutExamplesTop() {
				const list = this.binLayoutExamples || []
				return list.filter(ex => ex.planId === 'kitchen' || ex.planId === 'bath')
			},
			binLayoutLiving() {
				const list = this.binLayoutExamples || []
				return list.find(ex => ex.planId === 'living') || { room: '客厅', tags: [] }
			},
			binLayoutFixed() {
				const list = this.binLayoutExamples || []
				return list.find(ex => ex.planId === 'fixed') || { room: '固定一处', tags: [] }
			},
			pendingHubLabel() {
				const card = (this.sceneHubCards || []).find(c => c.hub === this.pendingHubKey)
				return (card && card.title) || '未选择'
			},
			introBlocks() {
				return getIntroBlocks()
			},
			introInsightCards() {
				const iconMap = { what: '📦', benefit: '♻️', policy: '🤝', vision: '⭐' }
				return (this.introBlocks || []).map(b => ({
					...b,
					icon: iconMap[b.id] || '💡',
				}))
			},
			whyIntroLead() {
				return getWhyIntroLead()
			},
			whyIntroSummary() {
				return getWhyIntroSummary()
			},
			hubPromoSkin() {
				if (this.hubKey === 'office') return 'office'
				if (this.hubKey === 'street') return 'street'
				return 'home'
			},
			introDisplayLead() {
				if (this.flowStep === 'points' && this.isCommunityHub) {
					return getCommunityIntroLead(this.isStreetHub)
				}
				return getIntroLead()
			},
			introDisplaySummary() {
				if (this.flowStep === 'setup') {
					return getSetupIntroLine()
				}
				if (this.flowStep === 'points' && this.isCommunityHub) {
					return getCommunityPointsDesc()
				}
				if (this.flowStep === 'points' && this.points.length) {
					return getPointsPickSummary()
				}
				return getIntroSummary()
			},
			pointsStepHint() {
				return getPointsStepHint(this.isCommunityHub)
			},
			clockPhotoIntro() {
				const sk = this.hub && this.hub.sceneKey ? this.hub.sceneKey : this.hubKey
				const cateLine = buildSceneCateSubtitle(sk, this.activeCateName)
				if (this.isCommunityHub) {
					const tail = '下面按顺序拍 3 张：手里的垃圾、对准桶口、已经扔进去。'
					return (cateLine ? cateLine + '。' : '') + tail
				}
				const tail = '请拍清楚本次「' + (this.activeCateName || '分类') + '」的分好成果，不必拍分之前的混装样。'
				return (cateLine ? cateLine + '。' : '') + tail
			},
			clockPhotoFootnote() {
				if (this.isCommunityHub) {
					return '三张都拍清楚再点提交，方便社区帮您核对。'
				}
				return '至少上传 1 张分类成果图；第二张投放入桶为选填。'
			},
			clockPhotoSlotMeta() {
				if (this.isCommunityHub) {
					return [
						{ key: 'before', title: '扔之前', hint: '看清是啥垃圾', emoji: '📦' },
						{ key: 'aim', title: '对准桶', hint: '桶口拍清楚', emoji: '🎯' },
						{ key: 'after', title: '扔完了', hint: '桶里拍一下', emoji: '✅' },
					]
				}
				return [
					{ key: 'result', title: '分类成果', hint: '分好的垃圾拍清楚', emoji: '♻️', required: true },
					{ key: 'inbin', title: '投放入桶', hint: '选填：投进对应桶后', emoji: '✅', required: false },
				]
			},
			clockSuccessNextTitle() {
				return getClockSuccessNextTitle()
			},
			clockSuccessOtherCateTitle() {
				return getClockSuccessOtherCateTitle()
			},
			clockSuccessPromoTitle() {
				return getClockSuccessPromoTitle()
			},
			residentialPickLead() {
				return getResidentialPickLead()
			},
			residentialPickSub() {
				return getResidentialPickSub()
			},
			residentialSetupNextCta() {
				return getResidentialSetupNextCta()
			},
			residentialSetupFootnote() {
				return getResidentialSetupFootnote()
			},
			residentialBinSettingsFoldTitle() {
				return getResidentialBinSettingsFoldTitle()
			},
			residentialBinSettingsFoldHint() {
				return getResidentialBinSettingsFoldHint()
			},
			residentialPickMaskHint() {
				return getResidentialPickMaskHint()
			},
			clockSuccessOtherPickTitle() {
				return this.isResidentialHub ? getClockSuccessOtherBinTitle() : getClockSuccessOtherCateTitle()
			},
			clockOtherPickHint() {
				if (this.isResidentialHub) {
					const cur = this.pointName || this.activeCateName || ''
					const all = (this.points || []).map(p => p.point_name).filter(Boolean)
					return getClockSuccessOtherBinBody(cur, all)
				}
				const cur = this.activeCateName || ''
				const all = (this.cateList || []).map(c => c.name).filter(Boolean)
				return getClockSuccessOtherCateBody(cur, all)
			},
			clockSuccessOtherPickList() {
				if (this.isResidentialHub) {
					const curId = Number(this.pointId)
					return (this.points || [])
						.filter(p => p && p.id && Number(p.id) !== curId)
						.map(p => ({
							key: 'bin-' + p.id,
							kind: 'bin',
							label: p.point_name || p.cate_name || '桶',
							raw: p,
						}))
				}
				const curId = Number(this.cateId)
				return (this.cateList || [])
					.filter(c => c && c.id && Number(c.id) !== curId)
					.map(c => ({
						key: 'cate-' + c.id,
						kind: 'cate',
						label: c.name,
						raw: c,
					}))
			},
			clockPromoIntro() {
				return this.clockPromoActivity
					? getClockSuccessPromoBody()
					: getClockSuccessPromoEmptyBody()
			},
			clockPromoMoreLabel() {
				if (!this.clockPromoActivity || this.clockPromoList.length <= 1) return ''
				return getClockSuccessPromoMoreLabel(this.clockPromoList.length - 1)
			},
			clockSlotFilledCount() {
				return (this.clockSlotUrls || []).filter(u => !!String(u || '').trim()).length
			},
			clockImages() {
				return (this.clockSlotUrls || []).filter(u => !!String(u || '').trim())
			},
			clockNotePlaceholder() {
				if (this.pointName) return '可补充「' + this.pointName + '」投放说明'
				return '可补充投放位置或心得（选填）'
			},
			isOfficeHub() {
				return this.hubKey === 'office'
			},
			isStreetHub() {
				return this.hubKey === 'street'
			},
			isCommunityHub() {
				return this.isOfficeHub
			},
			streetShopTypeLabels() {
				return (this.streetShopTypes || []).map(t => t.name || t.key)
			},
			streetFormCateName() {
				const t = (this.streetShopTypes || [])[this.streetFormTypeIndex]
				return (t && t.cate_name) || ''
			},
			communityEmptyTitle() {
				return getCommunityEmptyTitle(this.isStreetHub, this.isOfficeHub)
			},
			communityEmptyDesc() {
				return getCommunityEmptyDesc()
			},
			presetTips() {
				const tips = this.guide && this.guide.preset_tips
				return Array.isArray(tips) ? tips : []
			},
			hubTheme() {
				return (this.hub && this.hub.theme) || 'scf--home'
			},
			catePickerLabels() {
				return (this.cateList || []).map(c => c.name)
			},
			stepTitle() {
				const map = getStepTitles()
				if (this.isResidentialHub && this.residentialPickMode) {
					return map.points || map.cate || '选桶打卡'
				}
				if (map[this.flowStep]) return map[this.flowStep]
				if (this.hub && this.hub.title) return this.hub.title
				return getScenePickNavTitle()
			},
			visibleStepLabels() {
				if (['manage', 'hub_empty', 'street_merchant'].indexOf(this.flowStep) >= 0) return []
				if (this.flowStep === 'points' && !this.isResidentialHub) return []
				if (this.isOwnPointHub && this.isStreetHub && ['guide', 'survey', 'learn'].indexOf(this.flowStep) >= 0) {
					return ['怎么扔', '小窍门', '拍照']
				}
				if (this.isOwnPointHub && this.isStreetHub && (this.flowStep === 'setup' || this.residentialPickMode)) {
					return ['设投放点', '选点打卡', '拍照提交']
				}
				if (this.isResidentialHub) {
					return ['摆好桶', '选桶打卡', '上传成果']
				}
				if (['setup'].indexOf(this.flowStep) >= 0) return []
				return ['怎么扔', '小窍门', '拍照']
			},
			stepIndex() {
				if (this.isOwnPointHub && (this.isResidentialHub || this.isStreetHub)) {
					if (this.flowStep === 'learn') return 2
					if (this.residentialPickMode) return 1
					if (this.flowStep === 'setup') return 0
					return 0
				}
				const order = ['guide', 'survey', 'learn']
				const i = order.indexOf(this.flowStep)
				return i >= 0 ? i : 0
			},
			/** 同类型垃圾多个打卡点合并展示 */
			pointGroups() {
				const list = this.points || []
				const map = {}
				const keys = []
				list.forEach(p => {
					const key = p.cate_id ? 'id-' + p.cate_id : 'n-' + (p.cate_name || '')
					if (!map[key]) {
						map[key] = {
							key,
							cate_id: p.cate_id,
							cate_name: p.cate_name || '',
							items: [],
						}
						keys.push(key)
					}
					map[key].items.push(p)
				})
				return keys.map(k => map[k])
			},
		},
		onLoad(option) {
			const opt = option || {}
			const mode = String(opt.mode || opt.entry || '').trim()
			console.log('[SCF-DEBUG] scene-clock-flow onLoad', {
				rawOption: opt,
				mode,
				hub: opt.hub,
				isLogin: this.isLogin,
				flowStepBefore: this.flowStep,
			})
			if (mode === 'theme' || mode === 'intro') {
				this.resetThemeOptIn()
				this.flowStep = 'theme_scenes'
				console.log('[SCF-DEBUG] scene-clock-flow → theme_scenes')
				uni.setNavigationBarTitle({ title: getScenePickNavTitle() })
				return
			}
			const hubRaw = opt.hub != null ? String(opt.hub).trim() : ''
			// 未带 hub：与 Banner 配置 scene-clock-flow 无参一致，先进选场景（勿默认家庭）
			if (!hubRaw) {
				this.resetThemeOptIn()
				this.flowStep = 'theme_scenes'
				console.log('[SCF-DEBUG] scene-clock-flow → theme_scenes (no hub param)')
				uni.setNavigationBarTitle({ title: getScenePickNavTitle() })
				return
			}
			const hubKey = normalizeClockHubKey(hubRaw)
			console.log('[SCF-DEBUG] scene-clock-flow onLoad bootstrapHub', hubKey)
			this.bootstrapHub(hubKey)
		},
		onShow() {
			console.log('[SCF-DEBUG] scene-clock-flow onShow', {
				flowStep: this.flowStep,
				hubKey: this.hubKey,
				hasHub: !!this.hub,
				isLogin: this.isLogin,
				community_id: this.userInfo && this.userInfo.community_id,
			})
			if (this.flowStep === 'theme_scenes') {
				this.loadScenePickWelcomeState()
				this.ensureThemeSceneGate()
				return
			}
			if (['theme_intro', 'theme_brief'].indexOf(this.flowStep) >= 0) return
			if (!this.hub) {
				console.log('[SCF-DEBUG] scene-clock-flow onShow skip: no hub')
				return
			}
			const editing = ['setup', 'manage', 'guide', 'survey', 'learn'].indexOf(this.flowStep) >= 0
			if (editing) {
				console.log('[SCF-DEBUG] scene-clock-flow onShow skip: editing step', this.flowStep)
				return
			}
			this.ensureExamGate().then(ok => {
				console.log('[SCF-DEBUG] scene-clock-flow onShow ensureExamGate', ok)
				if (ok) this.initUserPoints()
			})
		},
		methods: {
			scfDbg(label, payload) {
				console.log('[SCF-DEBUG] scene-clock-flow', label, payload || '')
			},
			bootstrapHub(hubKey) {
				this.hubKey = normalizeClockHubKey(hubKey)
				this.hub = getHub(this.hubKey)
				this.scfDbg('bootstrapHub start', {
					hubKey: this.hubKey,
					hubTitle: this.hub && this.hub.title,
					flowStep: this.flowStep,
				})
				if (!this.hub) {
					this.scfDbg('bootstrapHub invalid hub')
					this.$u.toast('场景无效')
					setTimeout(() => uni.navigateBack(), 800)
					return
				}
				this.binLayoutExamples = getBinLayoutExamples()
				uni.setNavigationBarTitle({ title: this.hub.title })
				this.subKey = ''
				this.loadCateList().then(() => {
					this.scfDbg('bootstrapHub loadCateList done', { cateCount: (this.cateList || []).length })
					this.ensureExamGate().then(ok => {
						this.scfDbg('bootstrapHub ensureExamGate', ok)
						if (ok) this.initUserPoints()
					})
				})
			},
			ensureThemeSceneGate() {
				if (!this.isLogin) return
				this.$u.api.getUserInfo().then(res => {
					const info = res.data || {}
					this.$u.vuex('userInfo', info)
					if (!info.is_learn || !info.is_answer) {
						uni.showModal({
							title: '还差一步',
							content: '第一次打卡前，请先完成学习与考试（大约1分钟）。',
							showCancel: false,
							confirmText: '去学考',
							confirmColor: '#38B061',
							success: (r) => {
								if (r.confirm) uni.navigateTo({ url: '/pages/flhd/pre_learn' })
							},
						})
					}
				}).catch(() => {})
			},
			async onThemeStart() {
				if (!this.isLogin) {
					return this.$u.route('/pages/public/login')
				}
				let info = this.userInfo || {}
				try {
					const res = await this.$u.api.getUserInfo()
					info = res.data || {}
					this.$u.vuex('userInfo', info)
				} catch (e) {}
				if (!info.is_learn || !info.is_answer) {
					uni.showModal({
						title: '还差一步',
						content: '第一次打卡前，请先完成学习与考试（大约1分钟）。',
						showCancel: false,
						confirmText: '去学考',
						confirmColor: '#38B061',
						success: (res) => {
							if (res.confirm) uni.navigateTo({ url: '/pages/flhd/pre_learn' })
						},
					})
					return
				}
				this.resetThemeOptIn()
				this.flowStep = 'theme_scenes'
				uni.setNavigationBarTitle({ title: getScenePickNavTitle() })
			},
			resetThemeOptIn() {
				this.themeOptInReveal = true
			},
			loadScenePickWelcomeState() {
				if (!this.isLogin) {
					this.scenePickHasClock = false
					return Promise.resolve()
				}
				return this.$u.api.get_scene_clock_pick_state().then(res => {
					const data = res.data || {}
					this.scenePickHasClock = Number(data.has_scene_clock) === 1
				}).catch(() => {})
			},
			onScenePickWelcomeImgError() {
				const key = this.scenePickHasClock === false ? 'welcomeNew' : 'welcomeBack'
				if (this.scenePickImgOk) {
					this.$set(this.scenePickImgOk, key, false)
				}
			},
			onScenePickTitleImgError() {
				if (this.scenePickImgOk) {
					this.$set(this.scenePickImgOk, 'title', false)
				}
			},
			onScenePickCardImgErrorEvt(e) {
				const ds = (e && e.currentTarget && e.currentTarget.dataset) || {}
				const key = ds.illusKey || ds['illus-key'] || ''
				if (!key || !this.scenePickImgOk) return
				this.$set(this.scenePickImgOk, key, false)
			},
			confirmThemeOptIn() {
				this.themeOptInReveal = true
			},
			declineThemeOptIn() {
				uni.navigateBack({
					fail: () => {
						this.$u.toast('下次再来一起真分')
					},
				})
			},
			pickThemeHub(hub) {
				this.scfDbg('pickThemeHub', { hub, isLogin: this.isLogin })
				if (!this.isLogin) {
					return this.$u.route('/pages/public/login')
				}
				this.bootstrapHub(hub)
			},
			pickThemeHubByIndex(sIdx) {
				const card = (this.sceneHubCards || [])[sIdx]
				if (!card || !card.hub) return
				this.pickThemeHub(card.hub)
			},
			startHubFlow() {
				if (!this.pendingHubKey) {
					this.$u.toast('请先选一个场景')
					this.flowStep = 'theme_scenes'
					return
				}
				this.bootstrapHub(this.pendingHubKey)
			},
			ensureExamGate() {
				if (!this.isLogin) {
					this.scfDbg('ensureExamGate skip (not login) → pass')
					return Promise.resolve(true)
				}
				return this.$u.api.getUserInfo().then(res => {
					const info = res.data || {}
					this.$u.vuex('userInfo', info)
					this.scfDbg('ensureExamGate user', {
						is_learn: info.is_learn,
						is_answer: info.is_answer,
						community_id: info.community_id,
					})
					if (Number(info.is_learn) !== 1 || Number(info.is_answer) !== 1) {
						this.scfDbg('ensureExamGate BLOCK → pre_learn')
						this.$u.toast('请先完成学考一体')
						setTimeout(() => uni.navigateTo({ url: '/pages/flhd/pre_learn' }), 500)
						return false
					}
					this.scfDbg('ensureExamGate pass')
					return true
				}).catch((e) => {
					this.scfDbg('ensureExamGate api error → pass', e && e.message)
					return true
				})
			},
			setupCateName(row) {
				const cate = this.cateList[row.cate_index]
				return (cate && cate.name) || ''
			},
			manageCateName(p) {
				const cate = this.cateList[p._edit_cate_index]
				return (cate && cate.name) || p.cate_name || ''
			},
			cateHintByName(name) {
				const kind = resolveCateKind(name)
				return SECTION_HINTS[kind] || '按社区要求正确投放'
			},
			cateHintLines(row) {
				return this.cateHintByName(this.setupCateName(row))
			},
			goSetupStep() {
				this.residentialPickMode = false
				if (this.hydrateSetupRowsFromPoints()) {
					this.flowStep = 'setup'
					return
				}
				if (!this.setupRows.length) {
					this.prefillSetupRowsFromExamples()
				}
				this.flowStep = 'setup'
			},
			normalizeScenePoint(p) {
				if (!p) return p
				const raw = String(p.point_image_raw || urlToRelativePath(p.point_image || '') || '').trim()
				let display = String(p.point_image || '').trim()
				if (!display && raw) display = resolveAssetShowUrl(raw)
				else if (display && !/^https?:\/\//i.test(display) && raw) display = resolveAssetShowUrl(raw)
				return { ...p, point_image_raw: raw, point_image: display }
			},
			hydrateSetupRowsFromPoints() {
				const list = (this.points || []).filter(p => p && String(p.point_name || '').trim())
				if (!list.length) return false
				this.setupRows = list.map(p => {
					const np = this.normalizeScenePoint(p)
					let idx = 0
					for (let i = 0; i < this.cateList.length; i++) {
						if (Number(this.cateList[i].id) === Number(np.cate_id)) {
							idx = i
							break
						}
					}
					return {
						id: np.id,
						point_name: np.point_name,
						cate_index: idx,
						point_image: np.point_image_raw || '',
						point_image_url: np.point_image || resolveAssetShowUrl(np.point_image_raw),
					}
				})
				return true
			},
			enterResidentialSetupFlow() {
				this.residentialPickMode = false
				this.residentialBinSettingsOpen = false
				if (!this.hydrateSetupRowsFromPoints()) {
					this.prefillSetupRowsFromExamples()
				}
				this.flowStep = 'setup'
			},
			stopTapBubble() {},
			toggleResidentialBinSettings() {
				this.residentialBinSettingsOpen = !this.residentialBinSettingsOpen
				if (this.residentialBinSettingsOpen) {
					this.points = (this.points || []).map(p => this.decoratePointForEdit(p))
				}
			},
			deleteResidentialPointByIndex(mIdx) {
				const p = (this.points || [])[mIdx]
				if (!p) return
				if (p._isDraft || !p.id) {
					this.points.splice(mIdx, 1)
					return
				}
				this.deletePointById(p.id)
			},
			createResidentialDraftPoint() {
				return {
					id: 0,
					_isDraft: true,
					_draftKey: 'd' + Date.now(),
					point_name: '',
					cate_id: 0,
					cate_name: '',
					point_image: '',
					point_image_raw: '',
					_edit_name: '',
					_edit_cate_index: 0,
					_edit_image: '',
					_edit_image_url: '',
				}
			},
			closeResidentialBinSettings() {
				if ((this.points || []).some(p => p && p._isDraft)) {
					this.points = (this.points || []).filter(p => !p._isDraft)
				}
				this.residentialBinSettingsOpen = false
				this.initUserPoints('pick')
			},
			addPointInResidentialSettings() {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				if ((this.points || []).some(p => p && p._isDraft)) {
					return this.$u.toast('请先保存或取消当前新桶位')
				}
				this.residentialBinSettingsOpen = true
				this.residentialPickMode = true
				this.points = [...(this.points || []), this.createResidentialDraftPoint()]
			},
			cateIndexByKeyword(keyword) {
				const list = this.cateList || []
				for (let i = 0; i < list.length; i++) {
					const name = list[i].name || ''
					if (name.indexOf(keyword) >= 0) return i
				}
				return 0
			},
			prefillSetupRowsFromExamples() {
				if ((this.setupRows || []).some(r => r.point_name && String(r.point_name).trim())) {
					return
				}
				const presets = [
					{ point_name: '厨房厨余桶', kw: '厨余' },
					{ point_name: '客厅可回收桶', kw: '可回收' },
				]
				this.setupRows = presets.map(p => ({
					point_name: p.point_name,
					cate_index: this.cateIndexByKeyword(p.kw),
					point_image: '',
					point_image_url: '',
				}))
			},
			enterResidentialPickMode() {
				if (!this.points.length) {
					this.$u.toast('请先设置分类桶')
					return
				}
				this.residentialPickMode = true
				this.residentialBinSettingsOpen = false
				this.flowStep = 'setup'
			},
			exitResidentialPickMode() {
				this.residentialPickMode = false
				this.residentialBinSettingsOpen = false
			},
			pickResidentialPoint(p) {
				if (!p || p.id == null) return
				this.residentialPickMode = false
				this.pointId = p.id
				this.pointName = p.point_name || ''
				this.cateId = p.cate_id
				this.activeCateName = p.cate_name || ''
				this.cateLabel = guideCateLabelFromName(p.cate_name)
				this.subKey = this.guideSubKeyForHub()
				this.loadGuide()
				this.accumDuration = ''
				this.sortDuration = ''
				this.resetClockSlots()
				this.clockContent = ''
				this.flowStep = 'learn'
			},
			pickResidentialCate(c) {
				if (!c || !c.id) return
				const match = (this.points || []).find(p => Number(p.cate_id) === Number(c.id))
				if (match) {
					this.pickResidentialPoint(match)
					return
				}
				this.cateId = c.id
				this.activeCateName = c.name || ''
				this.cateLabel = guideCateLabelFromName(c.name)
				this.pointId = 0
				this.pointName = ''
			},
			backFromLearn() {
				if (this.isResidentialHub) {
					this.flowStep = 'setup'
					this.enterResidentialPickMode()
					return
				}
				if (this.isStreetHub) {
					this.flowStep = 'setup'
					this.residentialPickMode = (this.points || []).length > 0
					return
				}
				this.flowStep = 'survey'
			},
			guideSubKeyForHub() {
				return this.hubKey === 'office' ? 'zhenfenjiao' : ''
			},
			loadCateList() {
				return this.$u.api.get_select_list({ type: 2 }).then(res => {
					this.cateList = res.data || []
				}).catch(() => {})
			},
			ensureStreetShopTypes() {
				if ((this.streetShopTypes || []).length) {
					return Promise.resolve(this.streetShopTypes)
				}
				return this.$u.api.street_shop_types().then(res => {
					this.streetShopTypes = res.data || []
					return this.streetShopTypes
				}).catch(() => {
					this.streetShopTypes = []
					return []
				})
			},
			onStreetTypePick(e) {
				const idx = Number(e.detail.value)
				this.streetFormTypeIndex = Number.isFinite(idx) ? idx : 0
			},
			fillStreetFormFromMerchant(merchant) {
				if (!merchant) return
				this.streetFormShopName = merchant.shop_name || ''
				const types = this.streetShopTypes || []
				let ti = types.findIndex(x => x.key === merchant.shop_type)
				if (ti < 0) ti = 0
				this.streetFormTypeIndex = ti
			},
			applyStreetMerchantToSession(merchant) {
				if (!merchant) return
				this.streetMerchant = merchant
				this.cateId = Number(merchant.cate_id) || 0
				this.activeCateName = merchant.cate_name || ''
				this.cateLabel = guideCateLabelFromName(merchant.cate_name)
				this.subKey = ''
			},
			enterStreetSetupFlow() {
				this.residentialPickMode = false
				this.residentialBinSettingsOpen = false
				let cateIdx = 0
				const cid = this.streetMerchant && Number(this.streetMerchant.cate_id)
				for (let i = 0; i < (this.cateList || []).length; i++) {
					if (Number(this.cateList[i].id) === cid) {
						cateIdx = i
						break
					}
				}
				if (!this.hydrateSetupRowsFromPoints()) {
					this.setupRows = [{ point_name: '', cate_index: cateIdx, point_image: '', point_image_url: '' }]
				} else {
					this.setupRows = this.setupRows.map(r => ({ ...r, cate_index: cateIdx }))
				}
				this.flowStep = 'setup'
			},
			routeStreetAfterMerchantLoaded(keepFlow) {
				if (keepFlow === 'learn' || this.flowStep === 'learn') return
				if (keepFlow === 'guide' || keepFlow === 'survey') return
				if (keepFlow === 'manage') {
					this.points = (this.points || []).map(p => this.decoratePointForEdit(p))
					this.flowStep = 'manage'
					return
				}
				if (keepFlow === 'pick') {
					this.flowStep = 'setup'
					this.residentialPickMode = this.points.length > 0
					this.residentialBinSettingsOpen = false
					return
				}
				if (keepFlow === 'setup') {
					this.enterStreetSetupFlow()
					return
				}
				if (this.points.length > 0) {
					this.flowStep = 'setup'
					this.residentialPickMode = true
					this.residentialBinSettingsOpen = false
				} else {
					this.enterStreetSetupFlow()
				}
			},
			initStreetMerchantFlow(keepFlow) {
				this.pointsLoading = true
				return this.ensureStreetShopTypes().then(() => {
					return this.$u.api.street_shop_my_merchant()
				}).then(res => {
					const merchant = res.data || null
					if (!merchant || !merchant.id) {
						this.streetMerchant = null
						this.flowStep = 'street_merchant'
						return
					}
					this.applyStreetMerchantToSession(merchant)
					return this.$u.api.list_clock_scene_point({ hub_key: 'street' }).then(pr => {
						this.points = (pr.data || []).map(p => this.normalizeScenePoint(p))
						this.routeStreetAfterMerchantLoaded(keepFlow)
					})
				}).catch(err => {
					this.scfDbg('initStreetMerchantFlow error', err && (err.msg || err.message))
					this.flowStep = 'street_merchant'
				}).finally(() => {
					this.pointsLoading = false
					this.pointsBootstrapped = true
				})
			},
			submitStreetClaim() {
				if (!this.isLogin) {
					return this.$u.route('/pages/public/login')
				}
				const shopName = String(this.streetFormShopName || '').trim()
				if (!shopName) {
					return this.$u.toast('请填写商铺名称')
				}
				const t = (this.streetShopTypes || [])[this.streetFormTypeIndex]
				if (!t || !t.key) {
					return this.$u.toast('请选择商铺类型')
				}
				this.$u.api.street_shop_save_merchant({
					shop_name: shopName,
					shop_type: t.key,
				}).then(res => {
					const merchant = res.data || null
					if (!merchant) {
						return this.$u.toast('保存失败')
					}
					this.applyStreetMerchantToSession(merchant)
					this.$u.toast(res.msg || '已保存')
					return this.initStreetMerchantFlow('setup')
				}).catch(e => {
					this.$u.toast((e && e.msg) || '保存失败')
				})
			},
			editStreetMerchant() {
				if (!this.streetMerchant) return
				this.fillStreetFormFromMerchant(this.streetMerchant)
				this.residentialPickMode = false
				this.flowStep = 'street_merchant'
			},
			initUserPoints(keepFlow) {
				this.scfDbg('initUserPoints start', {
					keepFlow,
					hubKey: this.hubKey,
					isResidentialHub: this.isResidentialHub,
					isCommunityHub: this.isCommunityHub,
					flowStepBefore: this.flowStep,
				})
				if (!this.isLogin) {
					if (this.isResidentialHub) {
						this.prefillSetupRowsFromExamples()
						this.flowStep = 'setup'
					} else if (this.isStreetHub) {
						this.flowStep = 'street_merchant'
					} else {
						this.flowStep = this.isCommunityHub ? 'hub_empty' : 'setup'
					}
					this.scfDbg('initUserPoints not login → flowStep', this.flowStep)
					return Promise.resolve()
				}
				if (!this.userInfo.community_id) {
					this.scfDbg('initUserPoints BLOCK → member/info (no community)')
					this.$u.route('/pages/member/info')
					return Promise.resolve()
				}
				if (this.isStreetHub) {
					return this.initStreetMerchantFlow(keepFlow)
				}
				this.pointsLoading = true
				return this.$u.api.list_clock_scene_point({ hub_key: this.hubKey }).then(res => {
					this.points = (res.data || []).map(p => this.normalizeScenePoint(p))
					this.pointsBootstrapped = true
					if (keepFlow === 'manage') {
						this.points = this.points.map(p => this.decoratePointForEdit(p))
						this.flowStep = 'manage'
						return
					}
					if (keepFlow === 'points') {
						this.flowStep = this.points.length ? 'points' : 'setup'
						return
					}
					if (keepFlow === 'setup') {
						this.hydrateSetupRowsFromPoints()
						return
					}
					if (keepFlow === 'cate' || keepFlow === 'points') {
						if (this.isResidentialHub) {
							this.flowStep = 'setup'
							this.residentialPickMode = this.points.length > 0 && (keepFlow === 'points' || keepFlow === 'cate')
						}
						return
					}
					if (this.isCommunityHub) {
						this.flowStep = this.points.length ? 'points' : 'hub_empty'
						return
					}
					if (this.isResidentialHub) {
						if (keepFlow === 'setup' || keepFlow === 'manage' || keepFlow === 'cate' || keepFlow === 'learn') {
							return
						}
						if (keepFlow === 'pick') {
							this.flowStep = 'setup'
							this.residentialPickMode = true
							this.residentialBinSettingsOpen = false
							return
						}
						if (keepFlow === 'points') {
							this.flowStep = 'setup'
							this.residentialPickMode = this.points.length > 0
							return
						}
						if (this.flowStep === 'learn') return
						if (this.flowStep === 'manage') return
						if (this.flowStep === 'setup' && this.residentialPickMode) return
						// data 默认 flowStep=points，家庭场景不展示 points 页，此处勿因 points 提前 return
						if (this.points.length > 0) {
							this.flowStep = 'setup'
							this.residentialPickMode = true
							this.residentialBinSettingsOpen = false
						} else {
							this.enterResidentialSetupFlow()
						}
						return
					}
					if (this.flowStep === 'setup' || this.flowStep === 'manage') {
						return
					}
					this.flowStep = this.points.length ? 'points' : 'setup'
					if (this.flowStep === 'setup' && !this.setupRows.length) {
						this.setupRows = [{ point_name: '', cate_index: 0, point_image: '', point_image_url: '' }]
					}
				}).catch((err) => {
					this.scfDbg('initUserPoints list_clock_scene_point error', err && (err.msg || err.message))
					if (keepFlow === 'manage' || keepFlow === 'setup') return
					this.flowStep = this.isCommunityHub ? 'hub_empty' : 'setup'
				}).finally(() => {
					this.pointsLoading = false
					this.scfDbg('initUserPoints done', {
						flowStep: this.flowStep,
						residentialPickMode: this.residentialPickMode,
						pointsCount: (this.points || []).length,
					})
				})
			},
			cateTone(name) {
				return resolveCateKind(name) || 'other'
			},
			cateEmoji(name) {
				const k = resolveCateKind(name)
				const map = { food: '🍃', recycle: '♻️', harm: '☣️', other: '🗑️' }
				return map[k] || '🗂️'
			},
			addSetupRow() {
				this.setupRows.push({ point_name: '', cate_index: 0, point_image: '', point_image_url: '' })
			},
			chooseSetupPhoto(idx) {
				this.pickAndUploadPointPhoto((savePath, showUrl) => {
					this.$set(this.setupRows[idx], 'point_image', savePath)
					this.$set(this.setupRows[idx], 'point_image_url', showUrl)
				})
			},
			chooseManagePhoto(mIdx) {
				this.pickAndUploadPointPhoto((savePath, showUrl) => {
					const p = this.points[mIdx]
					if (!p) return
					this.$set(p, '_edit_image', savePath)
					this.$set(p, '_edit_image_url', showUrl)
				})
			},
			pickAndUploadPointPhoto(onDone) {
				uni.chooseImage({
					count: 1,
					sourceType: ['camera', 'album'],
					success: (res) => {
						const filePath = (res.tempFilePaths || [])[0]
						if (!filePath) return
						uni.showLoading({ title: '上传中…' })
						uni.uploadFile({
							url: this.uploadUrl,
							filePath,
							name: 'file',
							formData: { filetype: 'image' },
							success: (up) => {
								try {
									const data = JSON.parse(up.data)
									if (data.code != 1) {
										return this.$u.toast(data.msg || '上传失败')
									}
									const parsed = parseUploadAssetResponse(data)
									if (!parsed.savePath) {
										return this.$u.toast('上传失败')
									}
									onDone(parsed.savePath, parsed.showUrl)
								} catch (e) {
									this.$u.toast('上传失败')
								}
							},
							fail: () => this.$u.toast('上传失败'),
							complete: () => uni.hideLoading(),
						})
					},
				})
			},
			confirmDeleteScenePoint(id, onRemoved) {
				const pointId = Number(id)
				if (!pointId) {
					if (typeof onRemoved === 'function') onRemoved()
					return
				}
				uni.showModal({
					title: '提示',
					content: '确定删掉这个位置？删掉后下次进来不会再显示。',
					success: async (res) => {
						if (!res.confirm) return
						try {
							await this.$u.api.delete_clock_scene_point({ id: pointId })
							this.points = (this.points || []).filter(p => Number(p.id) !== pointId)
							this.$u.toast('已删除')
							if (typeof onRemoved === 'function') onRemoved()
						} catch (e) {
							this.$u.toast((e && e.msg) || '删除失败，请重试')
						}
					},
				})
			},
			removeSetupRow(idx) {
				const row = this.setupRows[idx]
				if (!row) return
				const removeLocal = () => {
					this.setupRows.splice(idx, 1)
					if (!this.setupRows.length) {
						this.setupRows.push({
							point_name: '',
							cate_index: 0,
							point_image: '',
							point_image_url: '',
						})
					}
				}
				if (!row.id) {
					removeLocal()
					return
				}
				this.confirmDeleteScenePoint(row.id, removeLocal)
			},
			onSetupCateChange(idx, e) {
				const v = parseInt(e.detail.value, 10)
				if (!isNaN(v)) this.setupRows[idx].cate_index = v
			},
			async saveSetup() {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				const rows = this.setupRows.filter(r => r.point_name && String(r.point_name).trim())
				if (!rows.length) {
					return this.$u.toast(this.isStreetHub ? '请填写投放点名称' : '请至少添一个扔垃圾的地方')
				}
				try {
					for (const row of rows) {
						let cate = this.cateList[row.cate_index]
						if (this.isStreetHub && this.streetMerchant) {
							cate = (this.cateList || []).find(c => Number(c.id) === Number(this.streetMerchant.cate_id))
						}
						if (!cate) return this.$u.toast('请选择垃圾种类')
						const img = String(row.point_image || '').trim()
						if (!img) return this.$u.toast('请为每个点位上传照片')
						const payload = {
							hub_key: this.hubKey,
							point_name: String(row.point_name).trim(),
							cate_id: cate.id,
							point_image: img,
						}
						if (row.id) payload.id = row.id
						const res = await this.$u.api.save_clock_scene_point(payload)
						const saved = (res && res.data) || {}
						if (!saved.point_image_raw && !saved.point_image) {
							console.warn('save_clock_scene_point: point_image empty in response', saved)
						}
					}
					this.$u.toast('已保存')
					if (this.isResidentialHub) {
						await this.initUserPoints('pick')
						return
					}
					if (this.isStreetHub) {
						await this.initStreetMerchantFlow('pick')
						return
					}
					this.setupRows = [{ point_name: '', cate_index: 0, point_image: '', point_image_url: '' }]
					await this.initUserPoints('points')
				} catch (e) {
					this.$u.toast((e && e.msg) || '保存失败，请重试')
				}
			},
			enterManage() {
				this.points = (this.points || []).map(p => this.decoratePointForEdit(p))
				this.flowStep = 'manage'
			},
			decoratePointForEdit(p) {
				const np = this.normalizeScenePoint(p)
				let idx = 0
				for (let i = 0; i < this.cateList.length; i++) {
					if (Number(this.cateList[i].id) === Number(np.cate_id)) {
						idx = i
						break
					}
				}
				const raw = np.point_image_raw || ''
				const display = np.point_image || resolveAssetShowUrl(raw)
				return {
					...np,
					_edit_name: np.point_name,
					_edit_cate_index: idx,
					_edit_image: raw,
					_edit_image_url: display,
				}
			},
			onManageNameInput(idx, e) {
				const val = e.detail && e.detail.value !== undefined ? e.detail.value : ''
				if (!this.points[idx]) return
				this.$set(this.points[idx], '_edit_name', val)
			},
			onManageCateChangeByIndex(idx, e) {
				const v = parseInt(e.detail.value, 10)
				if (isNaN(v) || !this.points[idx]) return
				this.$set(this.points[idx], '_edit_cate_index', v)
			},
			async savePointEditByIndex(idx) {
				const p = this.points[idx]
				if (!p) return
				const name = String(p._edit_name || '').trim()
				if (!name) return this.$u.toast('请填写点位名称')
				let cate = this.cateList[p._edit_cate_index]
				if (this.isStreetHub && this.streetMerchant) {
					cate = (this.cateList || []).find(c => Number(c.id) === Number(this.streetMerchant.cate_id))
				}
				if (!cate) return this.$u.toast('请选择种类')
				const img = String(p._edit_image || p.point_image || '').trim()
				if (!img) return this.$u.toast('请上传点位照片')
				const payload = {
					hub_key: this.hubKey,
					point_name: name,
					cate_id: cate.id,
					point_image: img,
				}
				if (p.id && !p._isDraft) {
					payload.id = p.id
				}
				await this.$u.api.save_clock_scene_point(payload)
				this.$u.toast('已保存')
				if (this.isOwnPointHub && this.residentialPickMode) {
					const reload = this.isStreetHub
						? () => this.initStreetMerchantFlow('pick')
						: () => this.initUserPoints('pick')
					reload().then(() => {
						this.residentialBinSettingsOpen = true
						this.points = (this.points || []).map(item => this.decoratePointForEdit(item))
					})
					return
				}
				if (this.isStreetHub) {
					this.initStreetMerchantFlow('manage')
					return
				}
				this.initUserPoints('manage')
			},
			deletePointById(id) {
				this.confirmDeleteScenePoint(id, () => {
					if (this.isOwnPointHub && this.residentialPickMode) {
						const reload = this.isStreetHub
							? () => this.initStreetMerchantFlow('pick')
							: () => this.initUserPoints('pick')
						reload().then(() => {
							if (!this.points.length) {
								this.residentialPickMode = false
								this.residentialBinSettingsOpen = false
								if (this.isStreetHub) {
									this.enterStreetSetupFlow()
								} else {
									this.enterResidentialSetupFlow()
								}
								return
							}
							if (this.residentialBinSettingsOpen) {
								this.points = (this.points || []).map(p => this.decoratePointForEdit(p))
							}
						})
						return
					}
					if (this.isStreetHub) {
						this.initStreetMerchantFlow('manage')
						return
					}
					this.initUserPoints('manage')
				})
			},
			addPointInManage() {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				this.setupRows = [{ point_name: '', cate_index: 0, point_image: '', point_image_url: '' }]
				this.flowStep = 'setup'
			},
			backFromManage() {
				if (this.isOwnPointHub) {
					this.flowStep = 'setup'
					this.residentialPickMode = this.points.length > 0
					this.residentialBinSettingsOpen = false
					return
				}
				this.flowStep = this.points.length ? 'points' : 'setup'
			},
			pickClusterPoint(gIdx, pIdx) {
				const groups = this.pointGroups || []
				const group = groups[gIdx]
				const p = group && group.items && group.items[pIdx]
				if (!p || p.id == null) return
				if (this.isOwnPointHub) {
					this.pickResidentialPoint(p)
					return
				}
				this.pickPointById(p.id)
			},
			pickPointById(id) {
				const pid = Number(id)
				const p = (this.points || []).find(item => Number(item.id) === pid)
				if (!p) return
				this.pointId = p.id
				this.pointName = p.point_name
				this.cateId = p.cate_id
				this.activeCateName = p.cate_name || ''
				this.cateLabel = guideCateLabelFromName(p.cate_name)
				this.subKey = this.guideSubKeyForHub()
				this.loadGuide()
				this.flowStep = 'guide'
			},
			loadGuide() {
				const def = mergeGuide(null, this.hubKey, this.subKey, this.cateLabel)
				this.guide = def
				this.$u.api.get_clock_scene_guide({
					hub_key: this.hubKey,
					sub_key: this.subKey,
					cate_label: this.cateLabel,
				}).then(res => {
					const row = res.data
					if (!row || typeof row !== 'object') return
					if (row.handle_guide || (row.preset_tips && row.preset_tips.length)) {
						this.guide = mergeGuide(row, this.hubKey, this.subKey, this.cateLabel)
					}
				}).catch(() => {})
			},
			backFromGuide() {
				if (this.isStreetHub) {
					this.flowStep = 'setup'
					this.residentialPickMode = (this.points || []).length > 0
					return
				}
				this.flowStep = 'points'
			},
			goLearnStep() {
				if (!this.userChoice && !this.customTip.trim()) {
					return this.$u.toast('请选择或填写一种方法')
				}
				this.$u.api.submit_clock_scene_survey({
					hub_key: this.hubKey,
					sub_key: this.subKey,
					cate_label: this.cateLabel,
					user_choice: this.userChoice,
					custom_tip: this.customTip,
				}).catch(() => {})
				this.resetClockSlots()
				this.clockContent = ''
				this.flowStep = 'learn'
			},
			goLearn() {
				const path = this.guide.learn_path || '/pages/dtsh/knowledge'
				this.$u.route(path)
			},
			resolveClockScene() {
				const k = this.hub && this.hub.sceneKey ? String(this.hub.sceneKey) : ''
				if (k === 'home') return 'home'
				if (k === 'zhenfenjiao' || this.hubKey === 'office') return 'zhenfenjiao'
				if (k === 'street') return k
				return 'home'
			},
			resetClockSlots() {
				const n = this.isCommunityHub ? 3 : 2
				this.clockSlotUrls = Array(n).fill('')
			},
			onClockSlotTap(slotIdx) {
				const url = this.clockSlotUrls[slotIdx]
				if (url) {
					this.previewClockImages(slotIdx)
					return
				}
				this.chooseClockSlot(slotIdx)
			},
			chooseClockSlot(slotIdx) {
				uni.chooseImage({
					count: 1,
					sourceType: ['camera', 'album'],
					success: (res) => {
						const path = (res.tempFilePaths || [])[0]
						if (path) this.uploadClockSlotImage(slotIdx, path)
					},
				})
			},
			uploadClockSlotImage(slotIdx, filePath) {
				uni.showLoading({ title: '上传中…' })
				uni.uploadFile({
					url: this.uploadUrl,
					filePath,
					name: 'file',
					formData: { filetype: 'image' },
					success: (res) => {
						try {
							const data = JSON.parse(res.data)
							if (data.code == 1 && data.data && data.data.url) {
								this.$set(this.clockSlotUrls, slotIdx, data.data.url)
							} else {
								this.$u.toast(data.msg || '上传失败')
							}
						} catch (e) {
							this.$u.toast('上传失败')
						}
					},
					fail: () => this.$u.toast('上传失败'),
					complete: () => uni.hideLoading(),
				})
			},
			clearClockSlot(slotIdx) {
				this.$set(this.clockSlotUrls, slotIdx, '')
			},
			previewClockImages(index) {
				const urls = this.clockImages
				if (!urls.length) return
				const current = this.clockSlotUrls[index] || urls[0]
				uni.previewImage({ urls, current })
			},
			missingClockSlotLabel() {
				const meta = this.clockPhotoSlotMeta
				for (let i = 0; i < meta.length; i++) {
					if (meta[i].required === false) continue
					if (!String(this.clockSlotUrls[i] || '').trim()) {
						return meta[i].title
					}
				}
				return ''
			},
			submitClock() {
				if (this.clockSubmitting) return
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				if (!this.userInfo.community_id) return this.$u.route('/pages/member/info')
				if (!this.cateId) return this.$u.toast('暂未匹配到垃圾种类')
				if (this.isResidentialHub) {
					if (!this.accumDuration) return this.$u.toast('请选择积累了多久')
					if (!this.sortDuration) return this.$u.toast('请选择分拣用了多久')
				}
				const missing = this.missingClockSlotLabel()
				if (missing) return this.$u.toast('请拍摄「' + missing + '」照片')
				const that = this
				uni.showModal({
					title: '提示',
					content: '请按真实情况拍照。分错了或照片不对，积分可能拿不到。',
					success(res) {
						if (!res.confirm) return
						that.doSubmitClock()
					},
				})
			},
			buildClockContentText() {
				const parts = []
				const note = String(this.clockContent || '').trim()
				if (note) parts.push(note)
				if (this.isResidentialHub) {
					if (this.accumDuration) parts.push('积累时长：' + this.accumDuration)
					if (this.sortDuration) parts.push('分拣用时：' + this.sortDuration)
				}
				return parts.join('\n')
			},
			loadClockPromoActivities() {
				this.clockPromoActivity = null
				this.clockPromoList = []
				this.$u.api.get_activity_tab_home({ page: 1, page_size: 10 }).then(res => {
					const data = res.data || {}
					const merged = []
					;(data.weekly_list || []).forEach(a => merged.push(a))
					;(data.light_list || []).forEach(a => merged.push(a))
					;(data.active_list || []).forEach(a => merged.push(a))
					const seen = {}
					const uniq = merged.filter(a => {
						const id = a.id || a.active_id
						if (!id || seen[id]) return false
						seen[id] = true
						return true
					})
					this.clockPromoList = uniq
					if (uniq.length) {
						const idx = Math.floor(Math.random() * Math.min(uniq.length, 5))
						this.clockPromoActivity = uniq[idx]
					}
				}).catch(() => {})
			},
			goPromoActivity() {
				const a = this.clockPromoActivity
				if (!a) return
				const id = a.id || a.active_id
				if (!id) return
				this.clockSuccessShow = false
				this.$u.route('/pages/activity/lsxf/details-new?id=' + id)
			},
			goActivityList() {
				this.clockSuccessShow = false
				this.$u.route('/pages/activity/active-list')
			},
			doSubmitClock() {
				this.clockSubmitting = true
				const payload = {
					type: 2,
					cate_id: this.cateId,
					content: this.buildClockContentText(),
					image: this.clockImages,
					clock_scene: this.resolveClockScene(),
					clock_sub_scene: '',
					clock_point_id: this.pointId || 0,
				}
				if (this.isStreetHub && this.streetMerchant && this.streetMerchant.id) {
					payload.street_merchant_id = this.streetMerchant.id
				}
				this.$u.api.add_forum(payload).then(res => {
					this.clockSuccessMsg = res.msg || '打卡成功'
					this.scenePickHasClock = true
					this.clockHabitTip = pickRandomHabitTip()
					this.loadClockPromoActivities()
					this.clockSuccessShow = true
					this.$u.api.getUserInfo().then(r => {
						this.$u.vuex('userInfo', r.data || {})
					}).catch(() => {})
				}).catch(e => {
					this.$u.toast((e && e.msg) || '提交失败')
				}).finally(() => {
					this.clockSubmitting = false
				})
			},
			clockSuccessContinue() {
				this.clockSuccessShow = false
				this.resetClockSlots()
				this.clockContent = ''
				this.accumDuration = ''
				this.sortDuration = ''
				if (this.isResidentialHub) {
					this.initUserPoints('points').then(() => {
						this.enterResidentialPickMode()
					})
					return
				}
				if (this.isStreetHub) {
					this.initStreetMerchantFlow('pick')
					return
				}
				this.flowStep = 'points'
			},
			clockSuccessJumpOtherPick(item) {
				if (!item) return
				this.clockSuccessShow = false
				this.resetClockSlots()
				this.clockContent = ''
				this.accumDuration = ''
				this.sortDuration = ''
				if (item.kind === 'bin' && item.raw) {
					this.pickResidentialPoint(item.raw)
					return
				}
				if (item.kind === 'cate' && item.raw) {
					this.pickResidentialCate(item.raw)
					this.flowStep = 'points'
				}
			},
			goActivityTab() {
				this.clockSuccessShow = false
				uni.switchTab({ url: '/pages/low_carbon/low_carbon' })
			},
			finishOnly() {
				this.goActivityTab()
			},
		},
	}
</script>

<style lang="scss" scoped>
.scf-page {
	min-height: 100vh;
	padding-bottom: calc(32rpx + env(safe-area-inset-bottom));
}
.scf--home {
	background: linear-gradient(180deg, #e8f5ea 0%, #f5f7f6 280rpx);
}
.scf--qinzi {
	background: linear-gradient(180deg, #ffe8e4 0%, #faf8f5 280rpx);
}
.scf--office {
	background: linear-gradient(180deg, #e4eef5 0%, #f5f7f9 280rpx);
}
.scf--street {
	background: linear-gradient(180deg, #dff5e8 0%, #f4f7f5 280rpx);
}
.scf-street-claim-title {
	display: block;
	font-size: 40rpx;
	font-weight: 800;
	color: #142018;
	margin-bottom: 12rpx;
}
.scf-street-claim-lead {
	display: block;
	margin-bottom: 24rpx;
	font-weight: 400;
	font-size: 28rpx;
	line-height: 1.6;
	color: #5a6b62;
}
.scf-street-field {
	margin-bottom: 28rpx;
}
.scf-street-label {
	display: block;
	font-size: 30rpx;
	font-weight: 700;
	color: #142018;
	margin-bottom: 8rpx;
}
.scf-street-field-hint {
	display: block;
	font-size: 26rpx;
	color: #6a756c;
	line-height: 1.5;
	margin-bottom: 12rpx;
}
.scf-street-input {
	display: block;
	width: 100%;
	box-sizing: border-box;
	min-height: 96rpx;
	height: 96rpx;
	padding: 0 24rpx;
	line-height: 96rpx;
	background: #f5f8f6;
	border-radius: 16rpx;
	font-size: 30rpx;
	color: #142018;
}
.scf-street-input-ph {
	font-size: 28rpx;
	line-height: 96rpx;
	color: #9aa5a0;
}
.scf-street-picker {
	width: 100%;
	box-sizing: border-box;
	min-height: 96rpx;
	padding: 28rpx 24rpx;
	line-height: 1.45;
	background: #f5f8f6;
	border-radius: 16rpx;
	font-size: 30rpx;
	color: #142018;
}
.scf-street-cate-tag {
	display: inline-block;
	margin-top: 12rpx;
	padding: 8rpx 16rpx;
	background: rgba(24, 175, 56, 0.12);
	color: #18af38;
	border-radius: 999rpx;
	font-size: 26rpx;
}
.scf-street-drop-list {
	display: flex;
	flex-direction: column;
	gap: 16rpx;
}
.scf-street-drop-item {
	padding: 20rpx 22rpx;
	border-radius: 16rpx;
	border: 2rpx solid #e0e8e3;
	background: #fff;
}
.scf-street-drop-item--on {
	border-color: #18af38;
	background: rgba(24, 175, 56, 0.06);
}
.scf-street-drop-name {
	display: block;
	font-size: 30rpx;
	font-weight: 700;
	color: #142018;
}
.scf-street-drop-addr {
	display: block;
	margin-top: 8rpx;
	font-size: 26rpx;
	color: #6a756c;
	line-height: 1.45;
}
.scf-street-empty,
.scf-street-loading {
	font-size: 28rpx;
	color: #8a9590;
	padding: 16rpx 0;
}
.scf-street-shop-bar {
	margin-bottom: 20rpx;
	padding: 20rpx 22rpx;
	background: #fff;
	border-radius: 16rpx;
	box-shadow: 0 6rpx 20rpx rgba(20, 40, 28, 0.06);
}
.scf-street-shop-bar-name {
	display: block;
	font-size: 32rpx;
	font-weight: 700;
	color: #142018;
}
.scf-street-shop-bar-meta {
	display: block;
	margin-top: 6rpx;
	font-size: 26rpx;
	color: #6a756c;
}
.scf-street-shop-bar-edit {
	display: inline-block;
	margin-top: 12rpx;
	font-size: 26rpx;
	color: #18af38;
}
.scf-bin-tile-cate--locked {
	opacity: 0.95;
}
.scf-street-shop-k {
	font-size: 26rpx;
	color: #18af38;
	font-weight: 700;
}
.scf-street-shop-name {
	display: block;
	margin-top: 8rpx;
	font-size: 40rpx;
	font-weight: 800;
	color: #142018;
}
.scf-street-shop-meta {
	display: block;
	margin-top: 10rpx;
	font-size: 28rpx;
	color: #5a6b62;
}
.scf-street-drop-title {
	font-size: 28rpx;
	font-weight: 700;
	color: #142018;
	margin-bottom: 12rpx;
}
.scf-street-drop-main {
	display: block;
	font-size: 34rpx;
	font-weight: 700;
	color: #142018;
}
.scf-street-drop-sub {
	display: block;
	margin-top: 10rpx;
	font-size: 28rpx;
	color: #6a756c;
	line-height: 1.5;
}
.scf-street-drop-actions {
	margin-top: 20rpx;
}
.scf-btn--inline {
	display: inline-flex;
	padding: 16rpx 28rpx;
}
.scf-foot--street-hub {
	display: flex;
	flex-direction: column;
	align-items: stretch;
	gap: 16rpx;
}
.scf-link-muted {
	text-align: center;
	font-size: 28rpx;
	color: #8a9590;
	padding: 8rpx;
}
.scf-hero {
	padding: 28rpx 28rpx 16rpx;
}
.scf-hero-kicker {
	font-size: 28rpx;
	color: #6a756c;
}
.scf-hero-title {
	display: block;
	margin-top: 8rpx;
	font-size: 44rpx;
	font-weight: 800;
	color: #142018;
}
.scf-steps {
	display: flex;
	flex-wrap: wrap;
	gap: 8rpx;
	margin-top: 20rpx;
}
.scf-step {
	display: flex;
	align-items: center;
	gap: 6rpx;
	padding: 6rpx 12rpx;
	border-radius: 999rpx;
	background: rgba(255, 255, 255, 0.65);
	opacity: 0.55;
}
.scf-step--on {
	opacity: 1;
}
.scf-step--cur {
	background: #fff;
	box-shadow: 0 4rpx 12rpx rgba(20, 32, 24, 0.08);
}
.scf-step-num {
	font-size: 24rpx;
	font-weight: 700;
	color: #18af38;
}
.scf-step-lab {
	font-size: 24rpx;
	color: #3d4a42;
}
.scf-body {
	padding: 8rpx 24rpx 24rpx;
}

/* 与 theme_scenes 的 zf-promo 同系视觉，用于流程内各步 */
.scf-promo {
	margin-bottom: 24rpx;
	padding: 32rpx 24rpx 28rpx;
	border-radius: 24rpx;
	background: linear-gradient(160deg, #3d9b5c 0%, #2a7d48 100%);
	box-shadow: 0 12rpx 36rpx rgba(42, 125, 72, 0.22);
}
.scf-promo--compact {
	padding: 28rpx 24rpx;
}
.scf-promo--home {
	background: linear-gradient(160deg, #3d9b5c 0%, #2a7d48 100%);
}
.scf-promo--office {
	background: linear-gradient(160deg, #4a7fd4 0%, #2f5f9e 100%);
	box-shadow: 0 12rpx 36rpx rgba(47, 95, 158, 0.22);
}
.scf-promo--street {
	background: linear-gradient(160deg, #e8a045 0%, #c9782a 100%);
	box-shadow: 0 12rpx 36rpx rgba(201, 120, 42, 0.22);
}
.scf-promo--light {
	background: #fff;
	box-shadow: 0 8rpx 28rpx rgba(20, 40, 28, 0.08);
}
.scf-promo-line {
	display: block;
	text-align: center;
	font-size: 34rpx;
	font-weight: 700;
	color: #fff;
	line-height: 1.45;
}
.scf-promo-line--sub {
	margin-top: 10rpx;
	font-size: 28rpx;
	font-weight: 500;
	opacity: 0.92;
}
.scf-promo-line--dark {
	color: #142018;
}
.scf-promo-line--subdark {
	color: #5f7166;
	font-size: 28rpx;
	font-weight: 500;
	margin-top: 8rpx;
}
.scf-promo-pillars {
	display: flex;
	justify-content: space-between;
	margin-top: 24rpx;
	padding-top: 20rpx;
	border-top: 1rpx solid rgba(255, 255, 255, 0.25);
}
.scf-promo-pillars--grid {
	flex-wrap: wrap;
	justify-content: space-between;
	gap: 16rpx 0;
	border-top: none;
	padding-top: 24rpx;
}
.scf-promo-pillars--mini {
	margin-top: 20rpx;
	padding-top: 20rpx;
}
.scf-promo-pillar {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
}
.scf-promo-pillar--tile {
	flex: 0 0 48%;
	align-items: flex-start;
	background: rgba(255, 255, 255, 0.14);
	border-radius: 18rpx;
	padding: 18rpx 16rpx 20rpx;
	box-sizing: border-box;
}
.scf-promo-pillar-ico {
	font-size: 36rpx;
}
.scf-promo-pillar-lab {
	margin-top: 8rpx;
	font-size: 24rpx;
	font-weight: 700;
	color: rgba(255, 255, 255, 0.98);
	line-height: 1.35;
}
.scf-promo-pillar-desc {
	margin-top: 8rpx;
	font-size: 22rpx;
	color: rgba(255, 255, 255, 0.88);
	line-height: 1.45;
}
.scf-section-kicker {
	display: flex;
	align-items: baseline;
	justify-content: space-between;
	margin: 8rpx 4rpx 16rpx;
}
.scf-section-kicker-t {
	font-size: 30rpx;
	font-weight: 700;
	color: #142018;
}
.scf-section-kicker-s {
	font-size: 24rpx;
	color: #6a756c;
}
.scf-home-plan {
	margin-bottom: 28rpx;
}
.scf-home-plan-title {
	display: block;
	font-size: 30rpx;
	font-weight: 700;
	color: #243028;
	margin-bottom: 16rpx;
}
.scf-home-plan-foot {
	display: block;
	margin-top: 16rpx;
	font-size: 26rpx;
	color: #6a756c;
	line-height: 1.5;
	text-align: center;
}
.scf-floor {
	border-radius: 20rpx;
	overflow: hidden;
	background: #f7f4ee;
	box-shadow: 0 8rpx 28rpx rgba(48, 40, 28, 0.07);
}
.scf-floor-shell {
	position: relative;
	padding: 20rpx 20rpx 28rpx;
	border: 5rpx solid #3d342c;
	border-radius: 6rpx;
	margin: 16rpx 16rpx 0;
	background: #faf7f2;
	background-image: linear-gradient(
			90deg,
			rgba(61, 52, 44, 0.03) 1px,
			transparent 1px
		),
		linear-gradient(rgba(61, 52, 44, 0.03) 1px, transparent 1px);
	background-size: 24rpx 24rpx;
}
.scf-floor-row {
	display: flex;
	width: 100%;
}
.scf-floor-row--living {
	margin-top: 0;
}
.scf-floor-row--bottom {
	margin-top: 0;
}
.scf-floor-room {
	flex: 1;
	min-height: 128rpx;
	padding: 18rpx 16rpx 16rpx;
	box-sizing: border-box;
	border: 3rpx solid #3d342c;
	margin: -1.5rpx;
	display: flex;
	flex-direction: column;
	justify-content: center;
}
.scf-floor-room--kitchen {
	background: rgba(255, 248, 240, 0.92);
}
.scf-floor-room--bath {
	background: rgba(240, 246, 252, 0.92);
}
.scf-floor-room--living {
	flex: none;
	width: 100%;
	min-height: 112rpx;
	background: rgba(238, 248, 240, 0.92);
}
.scf-floor-room--fixed {
	background: rgba(255, 244, 236, 0.92);
}
.scf-floor-room--entry {
	background: rgba(245, 242, 236, 0.75);
	align-items: center;
}
.scf-floor-room-name {
	font-size: 26rpx;
	font-weight: 700;
	color: #2c2824;
	letter-spacing: 2rpx;
}
.scf-floor-room-name--entry {
	font-size: 24rpx;
	color: #6a6258;
}
.scf-floor-entry-hint {
	margin-top: 8rpx;
	font-size: 22rpx;
	color: #9a9084;
}
.scf-floor-bins {
	display: flex;
	flex-wrap: wrap;
	gap: 8rpx;
	margin-top: 12rpx;
}
.scf-floor-bin {
	padding: 4rpx 12rpx;
	border-radius: 6rpx;
	font-size: 20rpx;
	font-weight: 600;
	line-height: 1.35;
	border: 2rpx solid rgba(0, 0, 0, 0.08);
}
.scf-floor-bin--kitchen {
	color: #5c4a12;
	background: #f5e6a8;
}
.scf-floor-bin--other {
	color: #4a4a4a;
	background: #e8e6e2;
}
.scf-floor-bin--recycle {
	color: #1e5c32;
	background: #c8ebd4;
}
.scf-floor-bin--hazard {
	color: #8a3b12;
	background: #ffd4b8;
}
.scf-floor-door-mark {
	position: absolute;
	left: 62%;
	bottom: -14rpx;
	transform: translateX(-50%);
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 0 12rpx;
	background: #f7f4ee;
}
.scf-floor-door-ico {
	font-size: 22rpx;
	line-height: 1;
	color: #3d342c;
	font-weight: 700;
}
.scf-floor-door-t {
	font-size: 20rpx;
	color: #6a6258;
	margin-top: 2rpx;
}
.scf-floor-legend {
	padding: 20rpx 24rpx 22rpx;
	border-top: 2rpx solid #e5dfd4;
	background: #fff;
}
.scf-floor-legend-item {
	display: flex;
	align-items: flex-start;
	margin-bottom: 12rpx;
}
.scf-floor-legend-item:last-child {
	margin-bottom: 0;
}
.scf-floor-legend-dot {
	flex-shrink: 0;
	width: 12rpx;
	height: 12rpx;
	border-radius: 50%;
	margin-top: 12rpx;
	margin-right: 14rpx;
	background: #9a9084;
}
.scf-floor-legend-dot--kitchen {
	background: #c9a227;
}
.scf-floor-legend-dot--bath {
	background: #6a9ec4;
}
.scf-floor-legend-dot--living {
	background: #3d9a5c;
}
.scf-floor-legend-dot--fixed {
	background: #d97840;
}
.scf-floor-legend-text {
	flex: 1;
	font-size: 24rpx;
	color: #5a665e;
	line-height: 1.5;
}
.scf-floor-legend-room {
	font-weight: 700;
	color: #2a342e;
	margin-right: 8rpx;
}
.scf-cate-scene-list {
	display: flex;
	flex-direction: column;
	gap: 16rpx;
}
.scf-cate-scene-card {
	margin-bottom: 0;
	border: 2rpx solid transparent;
}
.scf-cate-scene-card--on {
	border-color: #38b061;
	box-shadow: 0 8rpx 28rpx rgba(56, 176, 97, 0.2);
}
.scf-cate-icon--recycle {
	background: linear-gradient(145deg, #e3f2fa 0%, #b8d9ef 100%) !important;
}
.scf-cate-icon--food {
	background: linear-gradient(145deg, #e8f9ee 0%, #b8e6c8 100%) !important;
}
.scf-cate-icon--harm {
	background: linear-gradient(145deg, #fce8f0 0%, #f0c4d8 100%) !important;
}
.scf-cate-icon--other {
	background: linear-gradient(145deg, #eef0f3 0%, #d5dae2 100%) !important;
}
.scf-cate-check {
	font-size: 36rpx;
	font-weight: 800;
	color: #38b061;
	margin-left: 12rpx;
}
.scf-panel--card {
	border-radius: 20rpx;
	box-shadow: 0 8rpx 28rpx rgba(20, 40, 28, 0.08);
}
.scf-hint {
	display: block;
	font-size: 30rpx;
	color: #4a564e;
	margin-bottom: 16rpx;
	line-height: 1.55;
}
.scf-hint--inline {
	margin-bottom: 0;
	flex: 1;
}
.scf-points-intro {
	margin-bottom: 16rpx;
}
.scf-office-tip-title {
	display: block;
	font-size: 32rpx;
	font-weight: 800;
	color: #142018;
	margin-bottom: 12rpx;
}
.scf-points-head {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 12rpx;
}
.scf-manage-link {
	font-size: 26rpx;
	color: #18af38;
}
.scf-pick-mask {
	position: fixed;
	left: 0;
	top: 0;
	right: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.72);
	z-index: 500;
}
.scf-pick-lead {
	position: relative;
	z-index: 502;
	margin-bottom: 24rpx;
	padding: 0 8rpx;
}
.scf-pick-lead-t {
	display: block;
	font-size: 38rpx;
	font-weight: 700;
	color: #fff;
	line-height: 1.4;
}
.scf-pick-lead-s {
	display: block;
	margin-top: 10rpx;
	font-size: 30rpx;
	color: rgba(255, 255, 255, 0.88);
	line-height: 1.45;
}
.scf-body--pick-active {
	position: relative;
	z-index: 501;
}
.scf-bin-grid--pick {
	position: relative;
	z-index: 502;
}
.scf-bin-tile--pick-spot {
	box-shadow: 0 0 0 4rpx #fff, 0 12rpx 36rpx rgba(24, 175, 56, 0.4);
	border: 2rpx solid rgba(24, 175, 56, 0.85) !important;
}
.scf-bin-tile-body--readonly {
	pointer-events: none;
}
.scf-bin-tile-name-read {
	display: block;
	font-size: 30rpx;
	font-weight: 700;
	color: #142018;
}
.scf-bin-tile-cate-read {
	display: block;
	margin-top: 8rpx;
	font-size: 24rpx;
	color: #5a665e;
}
.scf-bin-tile-pick-go {
	display: block;
	margin-top: 12rpx;
	font-size: 26rpx;
	font-weight: 600;
	color: #18af38;
}
.scf-pick-mask-hint {
	display: block;
	position: relative;
	z-index: 502;
	margin-top: 28rpx;
	text-align: center;
	font-size: 26rpx;
	color: rgba(255, 255, 255, 0.92);
	line-height: 1.5;
	padding: 0 32rpx;
}
.scf-settings-fold {
	position: relative;
	z-index: 502;
	margin-top: 28rpx;
	border-radius: 18rpx;
	background: rgba(255, 255, 255, 0.96);
	overflow: hidden;
	box-shadow: 0 8rpx 28rpx rgba(0, 0, 0, 0.12);
}
.scf-settings-fold-head {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 22rpx 24rpx;
}
.scf-settings-fold-head-l {
	flex: 1;
	min-width: 0;
	padding-right: 16rpx;
}
.scf-settings-fold-title {
	display: block;
	font-size: 30rpx;
	font-weight: 700;
	color: #142018;
}
.scf-settings-fold-hint {
	display: block;
	margin-top: 6rpx;
	font-size: 24rpx;
	color: #6a756c;
	line-height: 1.4;
}
.scf-settings-fold-arrow {
	flex-shrink: 0;
	font-size: 26rpx;
	font-weight: 600;
	color: #18af38;
}
.scf-settings-fold-body {
	padding: 0 20rpx 20rpx;
	border-top: 1rpx solid #e8ece9;
}
.scf-bin-grid--manage-inline {
	display: flex;
	flex-direction: column;
	gap: 16rpx;
	max-height: 48vh;
	overflow-y: auto;
	padding-top: 16rpx;
}
.scf-bin-tile--compact {
	box-shadow: 0 4rpx 16rpx rgba(36, 48, 40, 0.08);
}
.scf-bin-tile--draft {
	border: 2rpx dashed rgba(24, 175, 56, 0.55);
}
.scf-bin-tile-save--inline {
	margin-top: 12rpx;
	padding: 12rpx 0;
	text-align: center;
	font-size: 26rpx;
	font-weight: 700;
	color: #18af38;
	background: rgba(24, 175, 56, 0.1);
	border-radius: 10rpx;
}
.scf-settings-fold-actions {
	margin-top: 16rpx;
	display: flex;
	flex-direction: column;
	gap: 12rpx;
}
.scf-settings-fold-add,
.scf-settings-fold-done {
	padding: 18rpx;
	text-align: center;
	border-radius: 12rpx;
	font-size: 28rpx;
	font-weight: 600;
}
.scf-settings-fold-add {
	color: #18af38;
	background: rgba(24, 175, 56, 0.08);
	border: 2rpx dashed rgba(24, 175, 56, 0.35);
}
.scf-settings-fold-done {
	color: #fff;
	background: linear-gradient(141deg, #a4db88 0%, #18af38 100%);
}
.scf-foot--setup {
	flex-direction: column;
	align-items: stretch;
}
.scf-setup-foot-hint {
	display: block;
	margin-bottom: 16rpx;
	padding: 0 8rpx;
	text-align: center;
	font-size: 26rpx;
	line-height: 1.55;
	color: #6a756c;
}
.scf-res-bin-list {
	display: flex;
	flex-direction: column;
	gap: 20rpx;
}
.scf-res-bin-card {
	display: flex;
	align-items: stretch;
	background: #fff;
	border-radius: 18rpx;
	overflow: hidden;
	box-shadow: 0 8rpx 24rpx rgba(20, 40, 28, 0.08);
	border: 2rpx solid rgba(24, 175, 56, 0.12);
}
.scf-res-bin-photo {
	width: 200rpx;
	min-height: 160rpx;
	flex-shrink: 0;
	background: #f0f4f1;
}
.scf-res-bin-photo--empty {
	display: flex;
	align-items: center;
	justify-content: center;
}
.scf-res-bin-photo-t {
	font-size: 56rpx;
}
.scf-res-bin-body {
	flex: 1;
	min-width: 0;
	padding: 24rpx 24rpx 20rpx;
	display: flex;
	flex-direction: column;
	justify-content: center;
}
.scf-res-bin-name {
	font-size: 32rpx;
	font-weight: 700;
	color: #142018;
	line-height: 1.35;
}
.scf-res-bin-cate {
	margin-top: 8rpx;
	font-size: 24rpx;
	color: #5a665e;
}
.scf-res-bin-go {
	margin-top: 16rpx;
	font-size: 26rpx;
	font-weight: 600;
	color: #18af38;
}
.scf-point-grid {
	display: flex;
	flex-direction: column;
	gap: 14rpx;
}
.scf-point-card {
	background: #fff;
	border-radius: 18rpx;
	padding: 20rpx;
	box-shadow: 0 8rpx 24rpx rgba(20, 40, 28, 0.08);
}
.scf-point-card--cluster {
	padding-bottom: 12rpx;
}
.scf-cate-hero-count {
	display: block;
	margin-top: 8rpx;
	font-size: 22rpx;
	opacity: 0.9;
}
.scf-cluster-list {
	margin-top: 16rpx;
	display: flex;
	flex-direction: column;
	gap: 12rpx;
}
.scf-cluster-item {
	display: flex;
	flex-direction: column;
	border-radius: 14rpx;
	overflow: hidden;
	background: #f5f8f6;
}
.scf-cluster-photo {
	width: 100%;
	height: 320rpx;
	display: block;
	background: #e8eee9;
}
.scf-cluster-photo--empty {
	display: flex;
	align-items: center;
	justify-content: center;
}
.scf-cluster-photo-t {
	font-size: 26rpx;
	color: #8a958c;
}
.scf-cluster-item-foot {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 18rpx 20rpx;
	gap: 16rpx;
}
.scf-cluster-item-name {
	flex: 1;
	min-width: 0;
	font-size: 30rpx;
	font-weight: 600;
	color: #243028;
}
.scf-cluster-item-foot .scf-point-go {
	flex-shrink: 0;
	margin-top: 0;
}
.scf-bin-grid {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 14rpx;
	margin-bottom: 8rpx;
}
.scf-bin-tile {
	background: #fff;
	border-radius: 18rpx;
	overflow: hidden;
	box-shadow: 0 6rpx 22rpx rgba(20, 40, 28, 0.07);
	border: 2rpx solid #e8eeea;
	display: flex;
	flex-direction: column;
	min-width: 0;
}
.scf-bin-tile--recycle { border-color: rgba(21, 106, 144, 0.28); }
.scf-bin-tile--food { border-color: rgba(26, 122, 50, 0.28); }
.scf-bin-tile--harm { border-color: rgba(154, 51, 102, 0.28); }
.scf-bin-tile--other { border-color: rgba(74, 85, 104, 0.22); }
.scf-bin-tile-head {
	position: relative;
}
.scf-bin-tile-photo {
	position: relative;
	height: 188rpx;
	background: linear-gradient(165deg, #eef3ef 0%, #dfe8e2 100%);
}
.scf-bin-tile-photo-img {
	width: 100%;
	height: 100%;
	display: block;
}
.scf-bin-tile-photo-empty {
	height: 100%;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	gap: 6rpx;
}
.scf-bin-tile-photo-ico {
	font-size: 40rpx;
	line-height: 1;
}
.scf-bin-tile-photo-lab {
	font-size: 22rpx;
	color: #5a6a60;
	font-weight: 600;
}
.scf-bin-tile-photo-badge {
	position: absolute;
	top: 10rpx;
	right: 10rpx;
	z-index: 1;
	font-size: 20rpx;
	color: #fff;
	background: rgba(0, 0, 0, 0.42);
	padding: 4rpx 12rpx;
	border-radius: 999rpx;
}
.scf-bin-tile-photo-badge--need {
	background: rgba(196, 92, 92, 0.88);
}
.scf-bin-tile-emoji {
	position: absolute;
	left: 10rpx;
	bottom: -22rpx;
	z-index: 2;
	width: 52rpx;
	height: 52rpx;
	line-height: 52rpx;
	text-align: center;
	font-size: 28rpx;
	background: #fff;
	border-radius: 16rpx;
	box-shadow: 0 4rpx 14rpx rgba(20, 40, 28, 0.12);
}
.scf-bin-tile-rm {
	position: absolute;
	top: 8rpx;
	left: 8rpx;
	z-index: 2;
	width: 44rpx;
	height: 44rpx;
	line-height: 40rpx;
	text-align: center;
	font-size: 36rpx;
	font-weight: 300;
	color: #fff;
	background: rgba(0, 0, 0, 0.38);
	border-radius: 50%;
}
.scf-bin-tile-body {
	padding: 28rpx 12rpx 14rpx;
	flex: 1;
	display: flex;
	flex-direction: column;
	min-width: 0;
}
.scf-bin-tile-input {
	width: 100%;
	box-sizing: border-box;
	font-size: 30rpx;
	font-weight: 600;
	color: #142018;
	padding: 10rpx 12rpx;
	background: #f4f7f5;
	border-radius: 12rpx;
}
.scf-bin-tile-picker {
	width: 100%;
	margin-top: 8rpx;
}
.scf-bin-tile-cate {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 8rpx;
	padding: 10rpx 12rpx;
	border-radius: 12rpx;
	background: #eef8f0;
}
.scf-bin-tile--recycle .scf-bin-tile-cate { background: #e8f2f8; }
.scf-bin-tile--food .scf-bin-tile-cate { background: #e8f9ee; }
.scf-bin-tile--harm .scf-bin-tile-cate { background: #fceef4; }
.scf-bin-tile--other .scf-bin-tile-cate { background: #eef0f3; }
.scf-bin-tile-cate-t {
	flex: 1;
	min-width: 0;
	font-size: 28rpx;
	font-weight: 600;
	color: #243028;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
.scf-bin-tile-cate-arrow {
	flex-shrink: 0;
	font-size: 22rpx;
	color: #18af38;
}
.scf-bin-tile-hint {
	margin-top: 8rpx;
	font-size: 20rpx;
	line-height: 1.45;
	color: #6a756c;
	overflow: hidden;
	display: -webkit-box;
	-webkit-line-clamp: 3;
	-webkit-box-orient: vertical;
	flex: 1;
}
.scf-bin-tile-foot {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 10rpx;
	margin-top: 12rpx;
}
.scf-bin-tile-save {
	flex: 1;
	text-align: center;
	height: 64rpx;
	line-height: 64rpx;
	border-radius: 32rpx;
	font-size: 24rpx;
	font-weight: 700;
	color: #fff;
	background: linear-gradient(90deg, #45c56a, #38b061);
}
.scf-bin-tile-rmtxt {
	flex-shrink: 0;
	font-size: 24rpx;
	color: #c45c5c;
	padding: 8rpx;
}
.scf-bin-tile--add {
	min-height: 280rpx;
	border: 2rpx dashed #b8dfc4;
	background: linear-gradient(165deg, #f8fcf9 0%, #eef8f1 100%);
	box-shadow: none;
	align-items: center;
	justify-content: center;
	text-align: center;
	padding: 20rpx 16rpx;
	box-sizing: border-box;
}
.scf-bin-tile-add-ico {
	font-size: 48rpx;
	font-weight: 300;
	color: #38b061;
	line-height: 1;
}
.scf-bin-tile-add-lab {
	margin-top: 10rpx;
	font-size: 28rpx;
	font-weight: 700;
	color: #18af38;
}
.scf-bin-tile-add-sub {
	margin-top: 6rpx;
	font-size: 22rpx;
	color: #6a756c;
}
.scf-point-photo {
	width: 100%;
	height: 320rpx;
	border-radius: 14rpx;
	margin-bottom: 14rpx;
}
.scf-cate-desc-lines {
	display: block;
	margin-top: 12rpx;
	font-size: 24rpx;
	line-height: 1.5;
	color: #6a756c;
	min-height: 72rpx;
}
.scf-intro {
	background: #fff;
	border-radius: 20rpx;
	padding: 28rpx 28rpx 24rpx;
	margin-bottom: 20rpx;
	box-shadow: 0 6rpx 28rpx rgba(20, 40, 28, 0.06);
	border-left: 6rpx solid #18af38;
}
.scf-intro--compact {
	border-left-color: #2b8bb8;
}
.scf-intro-lead {
	display: block;
	font-size: 38rpx;
	font-weight: 800;
	color: #142018;
	line-height: 1.45;
}
.scf-intro-summary {
	display: block;
	margin-top: 16rpx;
	font-size: 30rpx;
	color: #3d4840;
	line-height: 1.55;
}
.scf-intro-toggle {
	margin-top: 22rpx;
	padding-top: 20rpx;
	border-top: 1rpx solid #eef0ee;
	display: flex;
	align-items: center;
	justify-content: space-between;
}
.scf-intro-toggle-t {
	font-size: 28rpx;
	color: #18af38;
	font-weight: 600;
}
.scf-intro--compact .scf-intro-toggle-t {
	color: #156a90;
}
.scf-intro-chevron {
	font-size: 24rpx;
	color: #9aa59d;
	margin-left: 12rpx;
}
.scf-intro-detail {
	margin-top: 16rpx;
}
.scf-intro-block {
	padding: 20rpx 0;
	border-bottom: 1rpx solid #f0f2f0;
	&:last-child {
		border-bottom: none;
		padding-bottom: 0;
	}
}
.scf-intro-block-title {
	display: block;
	font-size: 30rpx;
	font-weight: 700;
	color: #243028;
	margin-bottom: 10rpx;
}
.scf-intro-block-body {
	display: block;
	font-size: 28rpx;
	color: #5a665e;
	line-height: 1.55;
}
.scf-intro-detail--always {
	margin-top: 16rpx;
}
.scf-bin-guide {
	margin: 0 24rpx 20rpx;
	padding: 24rpx;
	background: #fff;
	border-radius: 20rpx;
	box-shadow: 0 8rpx 24rpx rgba(20, 40, 28, 0.06);
}
.scf-bin-guide-title {
	display: block;
	font-size: 30rpx;
	font-weight: 600;
	color: #1a2e22;
	margin-bottom: 16rpx;
	line-height: 1.45;
}
.scf-bin-example {
	display: flex;
	align-items: flex-start;
	gap: 16rpx;
	padding: 16rpx 0;
	border-bottom: 1rpx solid #eef2ef;
}
.scf-bin-example:last-child {
	border-bottom: none;
}
.scf-bin-example-emoji {
	font-size: 40rpx;
	line-height: 1.2;
}
.scf-bin-example-room {
	display: block;
	font-size: 28rpx;
	font-weight: 600;
	color: #243528;
}
.scf-bin-example-tip {
	display: block;
	font-size: 26rpx;
	color: #5f7166;
	margin-top: 6rpx;
	line-height: 1.45;
}
.scf-cate-grid {
	display: flex;
	flex-direction: column;
	gap: 16rpx;
	padding: 0 24rpx 24rpx;
}
.scf-cate-pick {
	padding: 24rpx;
	border-radius: 20rpx;
	background: #fff;
	border: 2rpx solid #e8ece9;
}
.scf-cate-pick--on {
	border-color: #38b061;
	box-shadow: 0 8rpx 20rpx rgba(56, 176, 97, 0.15);
}
.scf-cate-pick-name {
	display: block;
	font-size: 32rpx;
	font-weight: 700;
	color: #1a2e22;
}
.scf-cate-pick-sub {
	display: block;
	font-size: 26rpx;
	color: #5f7166;
	margin-top: 8rpx;
	line-height: 1.4;
}
.scf-duration-block {
	margin-top: 24rpx;
}
.scf-duration-label {
	display: block;
	font-size: 28rpx;
	font-weight: 600;
	color: #243528;
	margin: 16rpx 0 12rpx;
}
.scf-tip-list--wrap {
	flex-wrap: wrap;
}
.scf-success-next {
	margin-top: 24rpx;
	padding-top: 8rpx;
	text-align: left;
}
.scf-success-next-k {
	display: block;
	font-size: 28rpx;
	font-weight: 700;
	color: #142018;
	margin-bottom: 16rpx;
	text-align: center;
}
.scf-success-tip {
	margin-bottom: 20rpx;
	padding: 20rpx 22rpx;
	background: #f7faf8;
	border-radius: 16rpx;
	border: 1rpx solid #e8efe9;
}
.scf-success-tip-h {
	display: block;
	font-size: 28rpx;
	font-weight: 700;
	color: #243528;
}
.scf-success-tip-p {
	display: block;
	margin-top: 10rpx;
	font-size: 26rpx;
	color: #5f7166;
	line-height: 1.55;
}
.scf-success-cate-chips {
	margin-top: 16rpx;
	display: flex;
	flex-wrap: wrap;
	gap: 12rpx;
}
.scf-success-cate-chip {
	padding: 10rpx 20rpx;
	border-radius: 999rpx;
	background: #fff;
	border: 1rpx solid #c8ebd4;
	font-size: 24rpx;
	color: #2d8a4a;
	font-weight: 600;
}
.scf-promo-act {
	margin-top: 16rpx;
	padding: 20rpx 22rpx;
	background: #fff;
	border-radius: 14rpx;
	border: 1rpx solid #d4eadc;
	box-shadow: 0 4rpx 16rpx rgba(56, 176, 97, 0.08);
	text-align: left;
}
.scf-promo-act-badge {
	display: inline-block;
	font-size: 22rpx;
	color: #fff;
	background: #38b061;
	padding: 4rpx 14rpx;
	border-radius: 8rpx;
}
.scf-promo-act-title {
	display: block;
	font-size: 30rpx;
	font-weight: 600;
	color: #1a2e22;
	margin-top: 12rpx;
	line-height: 1.4;
}
.scf-promo-act-go {
	display: block;
	font-size: 26rpx;
	color: #38b061;
	margin-top: 10rpx;
	font-weight: 600;
}
.scf-promo-more {
	margin-top: 14rpx;
	text-align: center;
	font-size: 26rpx;
	color: #38b061;
}
.scf-setup-block {
	margin-bottom: 20rpx;
}
.scf-setup-form {
	background: #fff;
	border-radius: 16rpx;
	padding: 16rpx;
	box-shadow: 0 4rpx 16rpx rgba(20, 40, 28, 0.05);
}
.scf-setup-label {
	display: block;
	margin-top: 12rpx;
	margin-bottom: 8rpx;
	font-size: 24rpx;
	font-weight: 600;
	color: #4a564e;
}
.scf-setup-input--block,
.scf-setup-picker--block {
	width: 100%;
	box-sizing: border-box;
}
.scf-point-upload {
	margin-top: 8rpx;
	height: 320rpx;
	border-radius: 14rpx;
	overflow: hidden;
	border: 2rpx dashed #c5d0c8;
	background: #f7faf8;
}
.scf-point-upload-img {
	width: 100%;
	height: 100%;
}
.scf-point-upload-placeholder {
	height: 100%;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
}
.scf-cate-hero {
	border-radius: 16rpx;
	padding: 20rpx 22rpx;
	color: #fff;
}
.scf-cate-hero--panel {
	margin-bottom: 16rpx;
}
.scf-cate-hero-kicker {
	display: block;
	font-size: 22rpx;
	opacity: 0.88;
	letter-spacing: 2rpx;
}
.scf-cate-hero-name {
	display: block;
	margin-top: 6rpx;
	font-size: 44rpx;
	font-weight: 800;
	line-height: 1.25;
	letter-spacing: 1rpx;
}
.scf-cate-hero--recycle {
	background: linear-gradient(141deg, #2b8bb8, #156a90);
}
.scf-cate-hero--food {
	background: linear-gradient(141deg, #3d9e4a, #1a7a32);
}
.scf-cate-hero--harm {
	background: linear-gradient(141deg, #c45c8a, #9a3366);
}
.scf-cate-hero--other {
	background: linear-gradient(141deg, #7a8494, #4a5568);
}
.scf-point-name {
	display: block;
	margin-top: 14rpx;
	font-size: 28rpx;
	font-weight: 600;
	color: #4a564e;
}
.scf-guide-point-name {
	display: block;
	margin-bottom: 18rpx;
	font-size: 28rpx;
	color: #6a756c;
}
.scf-point-go {
	display: block;
	margin-top: 12rpx;
	font-size: 26rpx;
	color: #18af38;
	font-weight: 600;
}
.scf-setup-row,
.scf-manage-card {
	background: #fff;
	border-radius: 16rpx;
	padding: 16rpx;
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	gap: 10rpx;
}
.scf-setup-input {
	flex: 1;
	min-width: 200rpx;
	font-size: 28rpx;
	padding: 12rpx 16rpx;
	background: #f5f7f6;
	border-radius: 10rpx;
}
.scf-setup-picker {
	font-size: 26rpx;
	padding: 12rpx 16rpx;
	background: #eef8f0;
	color: #18af38;
	border-radius: 10rpx;
}
.scf-setup-del {
	color: #e75e5e;
	font-size: 26rpx;
	padding: 8rpx;
}
.scf-setup-add {
	margin-top: 16rpx;
	text-align: center;
	font-size: 28rpx;
	color: #18af38;
	padding: 16rpx;
}
.scf-manage-actions {
	width: 100%;
	display: flex;
	gap: 24rpx;
	margin-top: 8rpx;
}
.scf-manage-save {
	color: #18af38;
	font-size: 26rpx;
}
.scf-manage-del {
	color: #e75e5e;
	font-size: 26rpx;
}
.scf-cate-grid {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 14rpx;
}
.scf-cate-card {
	border-radius: 18rpx;
	padding: 22rpx 18rpx;
	color: #fff;
	min-height: 100rpx;
	display: flex;
	flex-direction: column;
	justify-content: space-between;
}
.scf-cate-card--recycle {
	background: linear-gradient(141deg, #2b8bb8, #156a90);
}
.scf-cate-card--food {
	background: linear-gradient(141deg, #3d9e4a, #1a7a32);
}
.scf-cate-card--harm {
	background: linear-gradient(141deg, #c45c8a, #9a3366);
}
.scf-cate-card--other {
	background: linear-gradient(141deg, #7a8494, #4a5568);
}
.scf-cate-name {
	font-size: 30rpx;
	font-weight: 700;
}
.scf-cate-go {
	font-size: 24rpx;
	opacity: 0.9;
	margin-top: 8rpx;
}
.scf-panel {
	background: #fff;
	border-radius: 20rpx;
	padding: 24rpx;
	box-shadow: 0 8rpx 24rpx rgba(20, 40, 28, 0.06);
}
.scf-panel-tag {
	display: inline-block;
	font-size: 22rpx;
	font-weight: 700;
	color: #18af38;
	background: rgba(24, 175, 56, 0.12);
	padding: 6rpx 16rpx;
	border-radius: 999rpx;
	margin-bottom: 16rpx;
}
.scf-info-block {
	margin-bottom: 20rpx;
}
.scf-info-block--time {
	padding-top: 16rpx;
	border-top: 1rpx solid #eef0ee;
}
.scf-info-title {
	display: block;
	font-size: 30rpx;
	font-weight: 700;
	color: #243028;
	margin-bottom: 8rpx;
}
.scf-info-text {
	font-size: 30rpx;
	color: #4a564e;
	line-height: 1.65;
}
.scf-tip-list {
	display: flex;
	flex-direction: column;
	gap: 12rpx;
}
.scf-tip-chip {
	background: #fff;
	border-radius: 14rpx;
	padding: 18rpx 20rpx;
	border: 2rpx solid transparent;
	font-size: 26rpx;
	color: #3d4a42;
}
.scf-tip-chip--on {
	border-color: #18af38;
	background: rgba(24, 175, 56, 0.08);
}
.scf-custom {
	margin-top: 20rpx;
	background: #fff;
	border-radius: 16rpx;
	padding: 16rpx;
}
.scf-custom-label {
	display: block;
	font-size: 24rpx;
	color: #7a857c;
	margin-bottom: 8rpx;
}
.scf-cate-hero--compact {
	padding: 16rpx 20rpx;
	margin-bottom: 16rpx;
}
.scf-cate-hero--compact .scf-cate-hero-name {
	font-size: 36rpx;
	margin-top: 0;
}
.scf-clock-card {
	background: #fff;
	border-radius: 20rpx;
	padding: 24rpx;
	box-shadow: 0 8rpx 24rpx rgba(20, 40, 28, 0.08);
}
.scf-clock-desc {
	display: block;
	font-size: 26rpx;
	color: #6a756c;
	line-height: 1.55;
	margin-bottom: 20rpx;
}
.scf-clock-photo-head {
	margin-bottom: 16rpx;
}
.scf-clock-photo-title {
	font-size: 28rpx;
	font-weight: 700;
	color: #243028;
}
.scf-clock-photo-progress {
	font-size: 24rpx;
	color: #18af38;
	font-weight: 600;
}
.scf-clock-photo-head--row {
	display: flex;
	flex-direction: row;
	align-items: center;
	justify-content: space-between;
}
.scf-slot-row {
	display: flex;
	align-items: flex-start;
}
.scf-slot-wrap {
	flex: 1;
	min-width: 0;
	display: flex;
	align-items: flex-start;
}
.scf-slot-arrow {
	flex-shrink: 0;
	width: 20rpx;
	margin-top: 108rpx;
	font-size: 26rpx;
	color: #b8c4bc;
	line-height: 1;
	text-align: center;
}
.scf-slot {
	flex: 1;
	min-width: 0;
}
.scf-slot-head {
	display: flex;
	align-items: center;
	gap: 8rpx;
	margin-bottom: 6rpx;
}
.scf-slot-step {
	width: 32rpx;
	height: 32rpx;
	line-height: 32rpx;
	text-align: center;
	border-radius: 50%;
	background: #e8f5eb;
	color: #18af38;
	font-size: 20rpx;
	font-weight: 700;
	flex-shrink: 0;
}
.scf-slot-title {
	font-size: 24rpx;
	font-weight: 700;
	color: #243028;
}
.scf-slot-hint {
	display: block;
	font-size: 20rpx;
	color: #8a958c;
	line-height: 1.35;
	min-height: 54rpx;
	margin-bottom: 10rpx;
}
.scf-slot-box {
	position: relative;
	width: 100%;
	height: 200rpx;
	border-radius: 16rpx;
	border: 2rpx dashed #c5d0c8;
	background: #f7faf8;
	overflow: hidden;
	box-sizing: border-box;
}
.scf-slot-box--done {
	border-style: solid;
	border-color: #a8ddb8;
}
.scf-slot-img {
	width: 100%;
	height: 100%;
}
.scf-slot-placeholder {
	height: 100%;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 8rpx;
}
.scf-slot-emoji {
	font-size: 40rpx;
	line-height: 1.1;
}
.scf-slot-add-t {
	margin-top: 10rpx;
	font-size: 20rpx;
	color: #18af38;
}
.scf-photo-del {
	position: absolute;
	top: 0;
	right: 0;
	width: 44rpx;
	height: 44rpx;
	line-height: 40rpx;
	text-align: center;
	background: rgba(0, 0, 0, 0.55);
	color: #fff;
	font-size: 32rpx;
	z-index: 2;
}
.scf-slot-del {
	border-radius: 0 0 0 12rpx;
}
.scf-clock-photo-foot {
	display: block;
	margin-top: 16rpx;
	font-size: 22rpx;
	color: #8a958c;
	line-height: 1.5;
}
.scf-clock-note {
	margin-top: 24rpx;
}
.scf-clock-note-label {
	display: block;
	font-size: 26rpx;
	font-weight: 600;
	color: #243028;
	margin-bottom: 10rpx;
}
.scf-clock-textarea {
	min-height: 160rpx;
	background: #f5f7f6;
	border-radius: 12rpx;
	padding: 16rpx;
	box-sizing: border-box;
}
.scf-clock-submit {
	margin-top: 28rpx;
}
.scf-clock-submit--disabled {
	opacity: 0.65;
}
.scf-learn-link {
	margin-top: 24rpx;
	padding-top: 20rpx;
	border-top: 1rpx solid #eef0ee;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 8rpx;
	font-size: 26rpx;
	color: #7a857c;
}
.scf-learn-link-go {
	color: #18af38;
	font-weight: 600;
}
.scf-success-mask {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	z-index: 999;
	background: rgba(0, 0, 0, 0.52);
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 40rpx;
}
.scf-success-dialog {
	width: 100%;
	max-width: 600rpx;
	background: #fff;
	border-radius: 24rpx;
	padding: 40rpx 32rpx 32rpx;
	text-align: center;
}
.scf-success-emoji {
	display: block;
	width: 96rpx;
	height: 96rpx;
	line-height: 96rpx;
	margin: 0 auto 16rpx;
	border-radius: 50%;
	background: rgba(24, 175, 56, 0.12);
	color: #18af38;
	font-size: 48rpx;
	font-weight: 800;
}
.scf-success-title {
	font-size: 36rpx;
	font-weight: 800;
	color: #142018;
}
.scf-success-sub {
	display: block;
	margin-top: 10rpx;
	font-size: 26rpx;
	color: #6a756c;
}
.scf-habit-box {
	margin-top: 20rpx;
	padding: 16rpx;
	background: #f5faf6;
	border-radius: 12rpx;
	text-align: left;
}
.scf-habit-kicker {
	display: block;
	font-size: 22rpx;
	color: #18af38;
	font-weight: 600;
}
.scf-habit-text {
	display: block;
	margin-top: 6rpx;
	font-size: 26rpx;
	color: #4a564e;
	line-height: 1.5;
}
.scf-success-actions {
	margin-top: 28rpx;
	display: flex;
	flex-direction: column;
	gap: 16rpx;
}
.scf-success-back {
	margin-top: 0;
}
.scf-foot {
	margin-top: 28rpx;
	display: flex;
	gap: 16rpx;
	justify-content: space-between;
}
.scf-foot--single .scf-btn {
	flex: 1;
}
.scf-btn {
	flex: 1;
	text-align: center;
	padding: 24rpx 16rpx;
	border-radius: 14rpx;
	font-size: 32rpx;
	font-weight: 600;
}
.scf-btn--block {
	flex: none;
	width: 100%;
}
.scf-btn--primary {
	color: #fff;
	background: linear-gradient(141deg, #a4db88 0%, #18af38 100%);
}
.scf-btn--blue {
	color: #fff;
	background: linear-gradient(141deg, #5aa8ff 0%, #1877f2 100%);
}
.scf-btn--ghost {
	color: #555;
	background: #f0f2f1;
}
.scf-textarea {
	width: 100%;
	min-height: 160rpx;
	font-size: 26rpx;
	color: #243028;
	line-height: 1.5;
}
.scf-card--press {
	opacity: 0.92;
	transform: scale(0.99);
}

.scene-clock-root {
	min-height: 100vh;
}
.scene-clock-loading {
	min-height: 100vh;
	display: flex;
	align-items: center;
	justify-content: center;
	background: #f4f7f5;
}
.scene-clock-loading-t {
	font-size: 28rpx;
	color: #8a9a90;
}

/* 真分主题流（原型 Screen 2 / 4 / 5） */
.zf-foot {
	position: fixed;
	left: 0;
	right: 0;
	bottom: 0;
	padding: 20rpx 40rpx calc(24rpx + env(safe-area-inset-bottom));
	background: linear-gradient(180deg, rgba(255, 255, 255, 0) 0%, #fff 35%);
	z-index: 10;
}
.zf-foot--inline {
	position: static;
	padding: 32rpx 0 0;
	background: none;
}
.zf-btn {
	height: 96rpx;
	line-height: 96rpx;
	text-align: center;
	border-radius: 48rpx;
	font-size: 32rpx;
	font-weight: 600;
}
.zf-btn--primary {
	color: #fff;
	background: linear-gradient(90deg, #45c56a 0%, #38b061 100%);
	box-shadow: 0 12rpx 28rpx rgba(56, 176, 97, 0.35);
}
.zf-btn--press {
	opacity: 0.92;
	transform: scale(0.99);
}

.zf-intro {
	min-height: 100vh;
	background: #f5f8f6;
	padding-bottom: calc(160rpx + env(safe-area-inset-bottom));
}
.zf-intro-hero {
	position: relative;
	height: 360rpx;
	overflow: hidden;
	background: linear-gradient(180deg, #7ed4a0 0%, #4caf50 55%, #3d9b5c 100%);
}
.zf-intro-sky {
	position: absolute;
	inset: 0;
	background: linear-gradient(180deg, #b8e6cf 0%, transparent 55%);
}
.zf-intro-hill {
	position: absolute;
	bottom: -20rpx;
	border-radius: 50% 50% 0 0;
}
.zf-intro-hill--back {
	left: -20%;
	width: 70%;
	height: 120rpx;
	background: rgba(255, 255, 255, 0.15);
}
.zf-intro-hill--front {
	right: -10%;
	width: 85%;
	height: 160rpx;
	background: rgba(255, 255, 255, 0.22);
}
.zf-intro-people {
	position: absolute;
	left: 0;
	right: 0;
	bottom: 48rpx;
	display: flex;
	justify-content: center;
	gap: 48rpx;
}
.zf-intro-people-ico {
	font-size: 56rpx;
	filter: drop-shadow(0 4rpx 8rpx rgba(0, 0, 0, 0.12));
}
.zf-intro-hero-copy {
	position: absolute;
	left: 40rpx;
	right: 40rpx;
	top: 48rpx;
}
.zf-intro-hero-l1 {
	display: block;
	font-size: 48rpx;
	font-weight: 800;
	color: #fff;
	text-shadow: 0 4rpx 12rpx rgba(0, 60, 30, 0.25);
}
.zf-intro-hero-l2 {
	display: block;
	margin-top: 12rpx;
	font-size: 28rpx;
	color: rgba(255, 255, 255, 0.95);
	line-height: 1.45;
}
.zf-intro-sheet {
	margin: -40rpx 32rpx 0;
	position: relative;
	z-index: 2;
	background: #fff;
	border-radius: 24rpx;
	padding: 36rpx 32rpx 24rpx;
	box-shadow: 0 16rpx 48rpx rgba(24, 100, 60, 0.1);
}
.zf-intro-welcome {
	display: block;
	font-size: 36rpx;
	font-weight: 700;
	color: #1a1a1a;
	text-align: center;
}
.zf-intro-sub {
	display: block;
	margin-top: 12rpx;
	margin-bottom: 28rpx;
	font-size: 28rpx;
	color: #888;
	text-align: center;
	line-height: 1.5;
}
.zf-intro-step {
	display: flex;
	padding: 20rpx 0;
	border-top: 1rpx solid #f0f2f1;
}
.zf-intro-step-no {
	width: 44rpx;
	height: 44rpx;
	line-height: 44rpx;
	text-align: center;
	border-radius: 50%;
	background: #38b061;
	color: #fff;
	font-size: 24rpx;
	font-weight: 700;
	margin-right: 20rpx;
	flex-shrink: 0;
}
.zf-intro-step-body {
	flex: 1;
	min-width: 0;
}
.zf-intro-step-head {
	display: flex;
	align-items: center;
	margin-bottom: 6rpx;
}
.zf-intro-step-ico {
	font-size: 30rpx;
	margin-right: 8rpx;
}
.zf-intro-step-title {
	font-size: 32rpx;
	font-weight: 600;
	color: #222;
}
.zf-intro-step-desc {
	font-size: 28rpx;
	color: #666;
	line-height: 1.55;
}

.zf-scenes {
	min-height: 100vh;
	background: #f5f8f6;
	padding: 24rpx 32rpx calc(48rpx + env(safe-area-inset-bottom));
	box-sizing: border-box;
}
.zf-scenes--pick {
	background: #faf7f0;
	padding: 20rpx 28rpx calc(40rpx + env(safe-area-inset-bottom));
}
.zf-pick-welcome {
	margin-bottom: 24rpx;
	border-radius: 20rpx;
	overflow: hidden;
}
.zf-pick-welcome__img {
	display: block;
	width: 100%;
	vertical-align: top;
}
.zf-pick-ph {
	box-sizing: border-box;
}
.zf-pick-ph--welcome {
	min-height: 160rpx;
	padding: 28rpx 24rpx;
	background: linear-gradient(135deg, #6ecf7a 0%, #2aab4a 55%, #1d9438 100%);
	display: flex;
	align-items: center;
	justify-content: center;
	border-radius: 20rpx;
}
.zf-pick-welcome__txt {
	font-size: 34rpx;
	font-weight: 700;
	color: #fff;
	text-align: center;
	line-height: 1.45;
	text-shadow: 0 2rpx 8rpx rgba(0, 48, 16, 0.15);
}
.zf-pick-title-block {
	margin-bottom: 28rpx;
	text-align: center;
}
.zf-pick-title__img {
	display: block;
	width: 100%;
	vertical-align: top;
}
.zf-pick-title__txt {
	display: block;
	font-size: 40rpx;
	font-weight: 800;
	color: #1a7a32;
	line-height: 1.35;
	letter-spacing: 1rpx;
}
.zf-pick-list {
	display: flex;
	flex-direction: column;
	gap: 20rpx;
}
.zf-pick-card {
	display: flex;
	align-items: stretch;
	background: #fff;
	border-radius: 20rpx;
	overflow: hidden;
	box-shadow: 0 8rpx 28rpx rgba(28, 56, 36, 0.08);
}
.zf-pick-card--press {
	opacity: 0.94;
}
.zf-pick-card__illus {
	width: 220rpx;
	flex-shrink: 0;
	background: #eef6f0;
}
.zf-pick-card__img {
	width: 100%;
	height: 100%;
	min-height: 200rpx;
	display: block;
}
.zf-pick-ph--card {
	min-height: 200rpx;
	height: 100%;
	display: flex;
	align-items: center;
	justify-content: center;
	background: linear-gradient(160deg, #f0f8f2 0%, #dceee2 100%);
}
.zf-pick-ph-label {
	font-size: 24rpx;
	color: #8aa892;
}
.zf-pick-card__body {
	flex: 1;
	min-width: 0;
	padding: 22rpx 20rpx 22rpx 16rpx;
	position: relative;
	display: flex;
	flex-direction: column;
	justify-content: center;
}
.zf-pick-card__title {
	display: block;
	font-size: 32rpx;
	font-weight: 800;
	color: #1a7a32;
	line-height: 1.35;
	margin-bottom: 10rpx;
}
.zf-pick-card__desc {
	display: block;
	font-size: 24rpx;
	color: #4a564e;
	line-height: 1.5;
	padding-right: 56rpx;
}
.zf-pick-card__go {
	position: absolute;
	right: 16rpx;
	bottom: 20rpx;
	width: 56rpx;
	height: 56rpx;
	border-radius: 999rpx;
	background: linear-gradient(180deg, #5fe06f 0%, #18af38 100%);
	display: flex;
	align-items: center;
	justify-content: center;
	box-shadow: 0 6rpx 16rpx rgba(24, 175, 56, 0.35);
}
.zf-pick-card__go-ico {
	font-size: 40rpx;
	line-height: 1;
	color: #fff;
	font-weight: 300;
	margin-top: -4rpx;
}
.zf-promo--scene-head {
	margin-top: 0;
	margin-bottom: 0;
	padding: 28rpx 24rpx 32rpx;
}
.zf-scenes-pick {
	margin-top: 28rpx;
	padding: 24rpx 20rpx 20rpx;
	background: #fff;
	border-radius: 24rpx;
	box-shadow: 0 10rpx 32rpx rgba(20, 40, 28, 0.08);
	overflow: hidden;
}
.zf-opt-in {
	transition: opacity 0.32s ease, transform 0.32s ease, max-height 0.38s ease;
	max-height: 520rpx;
	opacity: 1;
	transform: translateY(0);
}
.zf-opt-in--hide {
	opacity: 0;
	transform: translateY(-12rpx) scale(0.98);
	max-height: 0;
	padding: 0;
	margin: 0;
	overflow: hidden;
	pointer-events: none;
}
.zf-opt-in-eyebrow {
	display: block;
	font-size: 26rpx;
	font-weight: 700;
	color: #18af38;
	letter-spacing: 2rpx;
	margin-bottom: 10rpx;
}
.zf-opt-in-title {
	display: block;
	font-size: 38rpx;
	font-weight: 700;
	color: #142018;
	line-height: 1.4;
	margin-bottom: 16rpx;
}
.zf-opt-in-desc {
	display: block;
	font-size: 30rpx;
	color: #5a6b62;
	line-height: 1.55;
	margin-bottom: 28rpx;
}
.zf-opt-in-yes {
	width: 100%;
	box-sizing: border-box;
	text-align: center;
	margin-bottom: 16rpx;
}
.zf-opt-in-later {
	display: block;
	text-align: center;
	font-size: 28rpx;
	color: #8a9590;
	padding: 12rpx 0 4rpx;
}
.zf-opt-in-later--press {
	opacity: 0.65;
}
.zf-scenes-reveal {
	max-height: 0;
	opacity: 0;
	transform: translateY(16rpx);
	overflow: hidden;
	transition: opacity 0.4s ease 0.06s, transform 0.4s ease 0.06s, max-height 0.45s ease;
	pointer-events: none;
}
.zf-scenes-reveal--show {
	max-height: 1200rpx;
	opacity: 1;
	transform: translateY(0);
	pointer-events: auto;
}
.zf-scenes-kicker {
	display: block;
	font-size: 28rpx;
	font-weight: 700;
	color: #18af38;
	margin-bottom: 8rpx;
	letter-spacing: 2rpx;
}
.zf-scenes-hint {
	display: block;
	font-size: 34rpx;
	font-weight: 700;
	color: #142018;
	margin-bottom: 20rpx;
	line-height: 1.45;
}
.zf-scenes-pick .zf-scene-row {
	margin-bottom: 0;
}
.zf-scene-row {
	display: flex;
	align-items: stretch;
	gap: 12rpx;
	margin-bottom: 20rpx;
}
.zf-scene-card--pick {
	flex: 1;
	min-width: 0;
	flex-direction: column;
	align-items: center;
	text-align: center;
	padding: 18rpx 10rpx 16rpx;
	margin-bottom: 0;
}
.zf-scene-card--pick .zf-scene-icon-wrap {
	width: 72rpx;
	height: 72rpx;
	border-radius: 18rpx;
	margin-right: 0;
	margin-bottom: 10rpx;
}
.zf-scene-card--pick .zf-scene-icon {
	font-size: 38rpx;
}
.zf-scene-card--pick .zf-scene-copy {
	width: 100%;
}
.zf-scene-card--pick .zf-scene-title {
	font-size: 24rpx;
	line-height: 1.35;
}
.zf-scene-card--pick .zf-scene-desc {
	margin-top: 6rpx;
	font-size: 20rpx;
	line-height: 1.35;
	color: #888;
	overflow: hidden;
	display: -webkit-box;
	-webkit-line-clamp: 2;
	-webkit-box-orient: vertical;
}
.zf-scene-card {
	display: flex;
	align-items: center;
	background: #fff;
	border-radius: 20rpx;
	padding: 28rpx 24rpx;
	margin-bottom: 20rpx;
	box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.05);
}
.zf-scene-card--press {
	opacity: 0.96;
}
.zf-scene-icon-wrap {
	width: 96rpx;
	height: 96rpx;
	border-radius: 20rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-right: 24rpx;
	flex-shrink: 0;
}
.zf-scene-card--home .zf-scene-icon-wrap {
	background: linear-gradient(145deg, #e8f9ee 0%, #c8ead4 100%);
}
.zf-scene-card--qinzi .zf-scene-icon-wrap {
	background: linear-gradient(145deg, #fff0f5 0%, #ffd6e5 100%);
}
.zf-scene-card--office .zf-scene-icon-wrap {
	background: linear-gradient(145deg, #eef4ff 0%, #cfe0ff 100%);
}
.zf-scene-card--street .zf-scene-icon-wrap {
	background: linear-gradient(145deg, #fff6eb 0%, #ffe0b8 100%);
}
.zf-scene-icon {
	font-size: 44rpx;
}
.zf-scene-copy {
	flex: 1;
	min-width: 0;
}
.zf-scene-title {
	display: block;
	font-size: 36rpx;
	font-weight: 700;
	color: #1a1a1a;
}
.zf-scene-desc {
	display: block;
	margin-top: 8rpx;
	font-size: 28rpx;
	color: #888;
}
.zf-scene-arrow {
	font-size: 40rpx;
	color: #ccc;
	margin-left: 12rpx;
}
.zf-promo {
	margin-top: 32rpx;
	padding: 32rpx 24rpx;
	border-radius: 24rpx;
	background: linear-gradient(160deg, #3d9b5c 0%, #2a7d48 100%);
}
.zf-promo-line {
	display: block;
	text-align: center;
	font-size: 34rpx;
	font-weight: 700;
	color: #fff;
	line-height: 1.45;
}
.zf-promo-line--sub {
	margin-top: 10rpx;
	font-size: 28rpx;
	font-weight: 500;
	opacity: 0.92;
}
.zf-promo-pillars {
	display: flex;
	justify-content: space-between;
	margin-top: 28rpx;
	padding-top: 24rpx;
	border-top: 1rpx solid rgba(255, 255, 255, 0.25);
}
.zf-promo-pillars--grid {
	flex-wrap: wrap;
	justify-content: space-between;
	gap: 16rpx 0;
	border-top: none;
	padding-top: 24rpx;
	margin-top: 24rpx;
}
.zf-promo-pillars--mini {
	margin-top: 20rpx;
	padding-top: 20rpx;
}
.zf-promo-pillar {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
}
.zf-promo-pillar--tile {
	flex: 0 0 48%;
	align-items: flex-start;
	background: rgba(255, 255, 255, 0.14);
	border-radius: 18rpx;
	padding: 18rpx 16rpx 20rpx;
	box-sizing: border-box;
}
.zf-promo-pillar-ico {
	font-size: 36rpx;
}
.zf-promo-pillar-lab {
	margin-top: 8rpx;
	font-size: 28rpx;
	font-weight: 700;
	color: rgba(255, 255, 255, 0.98);
	line-height: 1.35;
}
.zf-promo-pillar-desc {
	margin-top: 8rpx;
	font-size: 26rpx;
	color: rgba(255, 255, 255, 0.9);
	line-height: 1.5;
}
.zf-promo-pillar--tile .zf-promo-pillar-lab {
	font-size: 28rpx;
}
.zf-promo-pillars--mini .zf-promo-pillar-lab {
	font-size: 26rpx;
	font-weight: 600;
}

.zf-brief {
	min-height: 100vh;
	background: linear-gradient(180deg, #e8f7ee 0%, #f5f8f6 45%);
	padding: 40rpx 32rpx calc(48rpx + env(safe-area-inset-bottom));
	box-sizing: border-box;
}
.zf-brief-tagline {
	display: block;
	font-size: 34rpx;
	font-weight: 700;
	color: #2d8a52;
	text-align: center;
	line-height: 1.5;
	padding: 16rpx 20rpx 32rpx;
}
.zf-brief-card {
	background: #fff;
	border-radius: 24rpx;
	padding: 32rpx 24rpx 36rpx;
	box-shadow: 0 12rpx 40rpx rgba(56, 176, 97, 0.12);
}
.zf-brief-card-title {
	display: block;
	text-align: center;
	font-size: 32rpx;
	font-weight: 700;
	color: #222;
	margin-bottom: 32rpx;
}
.zf-brief-tasks {
	display: flex;
	justify-content: space-between;
}
.zf-brief-task {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 0 8rpx;
}
.zf-brief-task-ico-wrap {
	width: 100rpx;
	height: 100rpx;
	border-radius: 50%;
	background: linear-gradient(145deg, #f0faf4 0%, #d4f0de 100%);
	display: flex;
	align-items: center;
	justify-content: center;
	margin-bottom: 12rpx;
}
.zf-brief-task-ico {
	font-size: 40rpx;
}
.zf-brief-task-label {
	font-size: 26rpx;
	font-weight: 600;
	color: #333;
}
.zf-brief-task-sub {
	margin-top: 6rpx;
	font-size: 22rpx;
	color: #999;
	text-align: center;
}
.zf-brief-scene {
	margin-top: 28rpx;
	padding: 24rpx 28rpx;
	background: rgba(56, 176, 97, 0.1);
	border-radius: 16rpx;
	display: flex;
	align-items: center;
	justify-content: space-between;
}
.zf-brief-scene-k {
	font-size: 26rpx;
	color: #5a6b62;
}
.zf-brief-scene-v {
	font-size: 28rpx;
	font-weight: 700;
	color: #2a9d52;
}
</style>
