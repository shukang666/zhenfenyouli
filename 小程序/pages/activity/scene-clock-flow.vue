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

	<!-- 原型 Screen 4：先读「为什么要分」，再点选场景 -->
	<view v-else-if="flowStep === 'theme_scenes'" class="zf-scenes">
		<view class="zf-promo zf-promo--scene-head">
			<text class="zf-promo-line">{{ whyIntroLead }}</text>
			<text class="zf-promo-line zf-promo-line--sub">{{ whyIntroSummary }}</text>
			<view class="zf-promo-pillars zf-promo-pillars--grid">
				<view class="zf-promo-pillar zf-promo-pillar--tile" v-for="block in introInsightCards" :key="block.id">
					<text class="zf-promo-pillar-ico">{{ block.icon }}</text>
					<text class="zf-promo-pillar-lab">{{ block.title }}</text>
					<text class="zf-promo-pillar-desc">{{ block.body }}</text>
				</view>
			</view>
			<view class="zf-promo-pillars zf-promo-pillars--mini">
				<view class="zf-promo-pillar" v-for="(p, i) in themePillars" :key="'tp' + i">
					<text class="zf-promo-pillar-ico">{{ p.icon }}</text>
					<text class="zf-promo-pillar-lab">{{ p.label }}</text>
				</view>
			</view>
		</view>
		<view class="zf-scenes-pick">
			<text class="zf-scenes-kicker">选场景</text>
			<text class="zf-scenes-hint">{{ scenePickHint }}</text>
			<view class="zf-scene-row">
				<view
					class="zf-scene-card zf-scene-card--pick"
					v-for="(item, sIdx) in sceneHubCards"
					:key="item.hub"
					:class="'zf-scene-card--' + item.theme"
					hover-class="zf-scene-card--press"
					:hover-stay-time="80"
					@tap="pickThemeHubByIndex(sIdx)"
				>
					<view class="zf-scene-icon-wrap">
						<text class="zf-scene-icon">{{ item.emoji }}</text>
					</view>
					<view class="zf-scene-copy">
						<text class="zf-scene-title">{{ item.title }}</text>
						<text class="zf-scene-desc">{{ item.desc }}</text>
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

		<!-- 沿街/办公：尚未配置统一回收点 -->
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
		<view class="scf-body" v-if="flowStep === 'setup'">
			<view class="scf-promo scf-promo--compact" :class="'scf-promo--' + hubPromoSkin">
				<text class="scf-promo-line">{{ binSetupLead }}</text>
				<text class="scf-promo-line scf-promo-line--sub">{{ binSetupLeadNote }}</text>
				<text class="scf-promo-line scf-promo-line--sub" v-if="isResidentialHub">{{ introDisplaySummary }}</text>
			</view>
			<view class="scf-room-list" v-if="isResidentialHub && binLayoutExamples.length">
				<view class="zf-scene-card scf-room-card" v-for="(ex, eIdx) in binLayoutExamples" :key="eIdx">
					<view class="zf-scene-icon-wrap scf-room-icon">
						<text class="zf-scene-icon">{{ ex.emoji }}</text>
					</view>
					<view class="zf-scene-copy">
						<text class="zf-scene-title">{{ ex.room }}</text>
						<text class="zf-scene-desc">{{ ex.cate }} · {{ ex.tip }}</text>
					</view>
				</view>
			</view>
			<view class="scf-section-kicker" v-if="isResidentialHub">
				<text class="scf-section-kicker-t">拍下您家的桶</text>
				<text class="scf-section-kicker-s">名称和种类可随时改</text>
			</view>
			<view class="scf-promo scf-promo--compact scf-promo--light" v-else-if="!isResidentialHub">
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
							placeholder="名称，如厨房桶"
							maxlength="16"
						/>
						<picker
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
				<view class="scf-bin-tile scf-bin-tile--add" hover-class="scf-card--press" @tap="addSetupRow">
					<text class="scf-bin-tile-add-ico">＋</text>
					<text class="scf-bin-tile-add-lab">再添一处</text>
					<text class="scf-bin-tile-add-sub">扔垃圾的位置</text>
				</view>
			</view>
			<view class="scf-skip-link" v-if="isResidentialHub && points.length" @tap="goResidentialPickStep">{{ residentialSkipToPickLabel }}</view>
			<view class="scf-foot scf-foot--single">
				<view class="scf-btn scf-btn--primary" hover-class="scf-card--press" @tap="saveSetup">{{ isResidentialHub ? residentialSetupNextCta : '保存，去选位置拍照' }}</view>
			</view>
		</view>

		<!-- 打卡点列表（打卡入口） -->
		<view class="scf-body" v-if="flowStep === 'points'">
			<view class="scf-promo scf-promo--compact" :class="'scf-promo--' + hubPromoSkin">
				<text class="scf-promo-line">{{ isResidentialHub ? residentialPickLead : introDisplayLead }}</text>
				<text class="scf-promo-line scf-promo-line--sub">{{ isResidentialHub ? residentialPickSub : introDisplaySummary }}</text>
			</view>
			<view class="scf-points-head">
				<text class="scf-hint scf-hint--inline">{{ pointsStepHint }}</text>
				<text class="scf-manage-link" v-if="!isCommunityHub" @tap="enterManage">改分类位置</text>
			</view>
			<view class="scf-res-bin-list" v-if="isResidentialHub && points.length">
				<view
					v-for="p in points"
					:key="p.id"
					class="scf-res-bin-card"
					:class="'scf-res-bin-card--' + cateTone(p.cate_name)"
					hover-class="scf-card--press"
					@tap="pickResidentialPoint(p)"
				>
					<image v-if="p.point_image" class="scf-res-bin-photo" :src="p.point_image" mode="aspectFill"></image>
					<view v-else class="scf-res-bin-photo scf-res-bin-photo--empty">
						<text class="scf-res-bin-photo-t">{{ cateEmoji(p.cate_name) }}</text>
					</view>
					<view class="scf-res-bin-body">
						<text class="scf-res-bin-name">{{ p.point_name }}</text>
						<text class="scf-res-bin-cate">{{ p.cate_name }}</text>
						<text class="scf-res-bin-go">上传成果 ›</text>
					</view>
				</view>
			</view>
			<view class="scf-point-grid" v-else>
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
			<view class="scf-foot" v-if="isResidentialHub && points.length">
				<view class="scf-btn scf-btn--ghost" hover-class="scf-card--press" @tap="goSetupStep">上一步</view>
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
					<view class="scf-btn scf-btn--primary scf-btn--block" @tap="clockSuccessContinue">{{ isResidentialHub ? '再选桶打卡' : '再选位置拍照' }}</view>
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
	import { uploadUrl } from '@/common/settings'
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
		getScenePickHint,
		getSceneHubCards,
		getThemePromoPillars,
		getThemeIntroHeroSlogan,
		getThemeBriefTagline,
		getThemeBriefTaskTitle,
		getThemeBriefTasks,
		getThemeBriefCta,
		getBinLayoutExamples,
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
		getResidentialSkipToPickLabel,
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
				scenePickHint: getScenePickHint(),
				sceneHubCards: getSceneHubCards(),
				themePillars: getThemePromoPillars(),
				themeHeroSlogan: getThemeIntroHeroSlogan(),
				themeBriefTagline: getThemeBriefTagline(),
				themeBriefTaskTitle: getThemeBriefTaskTitle(),
				themeBriefTasks: getThemeBriefTasks(),
				themeBriefCta: getThemeBriefCta(),
				pendingHubKey: '',
				binLayoutExamples: [],
				binSetupLead: getBinSetupLead(),
				binSetupLeadNote: getBinSetupLeadNote(),
				accumDuration: '',
				sortDuration: '',
				accumDurationOptions: getAccumDurationOptions(),
				sortDurationOptions: getSortDurationOptions(),
				clockPromoActivity: null,
				clockPromoList: [],
			}
		},
		computed: {
			isResidentialHub() {
				return this.hubKey === 'home'
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
			residentialSkipToPickLabel() {
				return getResidentialSkipToPickLabel()
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
				return this.isOfficeHub || this.isStreetHub
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
				if (map[this.flowStep]) return map[this.flowStep]
				if (this.hub && this.hub.title) return this.hub.title
				return getScenePickNavTitle()
			},
			visibleStepLabels() {
				if (['manage', 'hub_empty'].indexOf(this.flowStep) >= 0) return []
				if (this.flowStep === 'points' && !this.isResidentialHub) return []
				if (this.isResidentialHub) {
					return ['摆好桶', '选桶打卡', '上传成果']
				}
				if (['setup'].indexOf(this.flowStep) >= 0) return []
				return ['怎么扔', '小窍门', '拍照']
			},
			stepIndex() {
				if (this.isResidentialHub) {
					const order = ['setup', 'points', 'learn']
					const i = order.indexOf(this.flowStep)
					return i >= 0 ? i : 0
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
			if (mode === 'theme' || mode === 'intro') {
				this.flowStep = 'theme_scenes'
				uni.setNavigationBarTitle({ title: getScenePickNavTitle() })
				return
			}
			const hubKey = normalizeClockHubKey(opt.hub ? String(opt.hub) : 'home')
			this.bootstrapHub(hubKey)
		},
		onShow() {
			if (this.flowStep === 'theme_scenes') {
				this.ensureThemeSceneGate()
				return
			}
			if (['theme_intro', 'theme_brief'].indexOf(this.flowStep) >= 0) return
			if (!this.hub) return
			const editing = ['setup', 'manage', 'guide', 'survey', 'learn'].indexOf(this.flowStep) >= 0
			if (editing) return
			this.ensureExamGate().then(ok => {
				if (ok) this.initUserPoints()
			})
		},
		methods: {
			bootstrapHub(hubKey) {
				this.hubKey = normalizeClockHubKey(hubKey)
				this.hub = getHub(this.hubKey)
				if (!this.hub) {
					this.$u.toast('场景无效')
					setTimeout(() => uni.navigateBack(), 800)
					return
				}
				this.binLayoutExamples = getBinLayoutExamples()
				uni.setNavigationBarTitle({ title: this.hub.title })
				this.subKey = ''
				this.loadCateList().then(() => {
					this.ensureExamGate().then(ok => {
						if (ok) this.initUserPoints()
					})
				})
			},
			ensureThemeSceneGate() {
				if (!this.isLogin) return
				this.$u.api.getUserInfo().then(res => {
					const info = res.data || {}
					this.$u.vuex('userInfo', info)
					if (!info.is_learn) {
						uni.showModal({
							title: '还差一步',
							content: '第一次打卡前，需要先看一遍垃圾分类说明（大约几分钟）。看完就能回来继续。',
							showCancel: false,
							confirmText: '去学习',
							confirmColor: '#38B061',
							success: (r) => {
								if (r.confirm) this.$u.route('/pages/flhd/list')
							},
						})
						return
					}
					if (!info.is_answer) {
						uni.showModal({
							title: '再答几道题',
							content: '说明已经看完了，做一套简单选择题通过后即可打卡攒积分。',
							showCancel: false,
							confirmText: '去答题',
							confirmColor: '#38B061',
							success: (r) => {
								if (r.confirm) this.$u.route('/pages/answer/examine?id=1')
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
				if (!info.is_learn) {
					uni.showModal({
						title: '还差一步',
						content: '第一次打卡前，需要先看一遍垃圾分类说明（大约几分钟）。看完就能回来继续。',
						showCancel: false,
						confirmText: '去学习',
						confirmColor: '#38B061',
						success: (res) => {
							if (res.confirm) this.$u.route('/pages/flhd/list')
						},
					})
					return
				}
				if (!info.is_answer) {
					uni.showModal({
						title: '再答几道题',
						content: '说明已经看完了，做一套简单选择题通过后即可打卡攒积分。',
						showCancel: false,
						confirmText: '去答题',
						confirmColor: '#38B061',
						success: (res) => {
							if (res.confirm) this.$u.route('/pages/answer/examine?id=1')
						},
					})
					return
				}
				this.flowStep = 'theme_scenes'
				uni.setNavigationBarTitle({ title: getScenePickNavTitle() })
			},
			pickThemeHub(hub) {
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
				if (!this.isLogin) return Promise.resolve(true)
				return this.$u.api.getUserInfo().then(res => {
					this.$u.vuex('userInfo', res.data || {})
					if (Number(res.data.is_answer) !== 1) {
						this.$u.toast('请先看完分类说明并通过小测验')
						setTimeout(() => this.$u.route('/pages/flhd/ljfldk'), 500)
						return false
					}
					return true
				}).catch(() => true)
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
				if (!this.hydrateSetupRowsFromPoints()) {
					this.prefillSetupRowsFromExamples()
				}
				this.flowStep = 'setup'
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
			goResidentialPickStep() {
				if (!this.points.length) {
					this.$u.toast('请先设置分类桶')
					this.goSetupStep()
					return
				}
				this.flowStep = 'points'
			},
			pickResidentialPoint(p) {
				if (!p || p.id == null) return
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
					this.flowStep = 'points'
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
			initUserPoints(keepFlow) {
				if (!this.isLogin) {
					if (this.isResidentialHub) {
						this.prefillSetupRowsFromExamples()
						this.flowStep = 'setup'
					} else {
						this.flowStep = this.isCommunityHub ? 'hub_empty' : 'setup'
					}
					return Promise.resolve()
				}
				if (!this.userInfo.community_id) {
					this.$u.route('/pages/member/info')
					return Promise.resolve()
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
						if (keepFlow === 'points' && this.isResidentialHub) {
							this.flowStep = this.points.length ? 'points' : 'setup'
						}
						return
					}
					if (this.isCommunityHub) {
						this.flowStep = this.points.length ? 'points' : 'hub_empty'
						return
					}
					if (this.isResidentialHub) {
						if (keepFlow === 'setup' || keepFlow === 'manage' || keepFlow === 'cate' || keepFlow === 'points' || keepFlow === 'learn') {
							return
						}
						if (['setup', 'points', 'learn'].indexOf(this.flowStep) >= 0) {
							return
						}
						this.enterResidentialSetupFlow()
						return
					}
					if (this.flowStep === 'setup' || this.flowStep === 'manage') {
						return
					}
					this.flowStep = this.points.length ? 'points' : 'setup'
					if (this.flowStep === 'setup' && !this.setupRows.length) {
						this.setupRows = [{ point_name: '', cate_index: 0, point_image: '', point_image_url: '' }]
					}
				}).catch(() => {
					if (keepFlow === 'manage' || keepFlow === 'setup') return
					this.flowStep = this.isCommunityHub ? 'hub_empty' : 'setup'
				}).finally(() => {
					this.pointsLoading = false
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
				if (!rows.length) return this.$u.toast('请至少添一个扔垃圾的地方')
				try {
					for (const row of rows) {
						const cate = this.cateList[row.cate_index]
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
						await this.initUserPoints('points')
						this.flowStep = this.points.length ? 'points' : 'setup'
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
				const cate = this.cateList[p._edit_cate_index]
				if (!cate) return this.$u.toast('请选择种类')
				const img = String(p._edit_image || p.point_image || '').trim()
				if (!img) return this.$u.toast('请上传点位照片')
				await this.$u.api.save_clock_scene_point({
					id: p.id,
					hub_key: this.hubKey,
					point_name: name,
					cate_id: cate.id,
					point_image: img,
				})
				this.$u.toast('已保存')
				this.initUserPoints('manage')
			},
			deletePointById(id) {
				this.confirmDeleteScenePoint(id, () => {
					this.initUserPoints('manage')
				})
			},
			addPointInManage() {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				this.setupRows = [{ point_name: '', cate_index: 0, point_image: '', point_image_url: '' }]
				this.flowStep = 'setup'
			},
			backFromManage() {
				this.flowStep = this.points.length ? 'points' : 'setup'
			},
			pickClusterPoint(gIdx, pIdx) {
				const groups = this.pointGroups || []
				const group = groups[gIdx]
				const p = group && group.items && group.items[pIdx]
				if (!p || p.id == null) return
				if (this.isResidentialHub) {
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
				this.$u.api.add_forum({
					type: 2,
					cate_id: this.cateId,
					content: this.buildClockContentText(),
					image: this.clockImages,
					clock_scene: this.resolveClockScene(),
					clock_sub_scene: '',
					clock_point_id: this.pointId || 0,
				}).then(res => {
					this.clockSuccessMsg = res.msg || '打卡成功'
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
.scf-hero {
	padding: 28rpx 28rpx 16rpx;
}
.scf-hero-kicker {
	font-size: 24rpx;
	color: #6a756c;
}
.scf-hero-title {
	display: block;
	margin-top: 8rpx;
	font-size: 40rpx;
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
	font-size: 20rpx;
	font-weight: 700;
	color: #18af38;
}
.scf-step-lab {
	font-size: 20rpx;
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
	font-size: 30rpx;
	font-weight: 700;
	color: #fff;
	line-height: 1.45;
}
.scf-promo-line--sub {
	margin-top: 10rpx;
	font-size: 24rpx;
	font-weight: 500;
	opacity: 0.92;
}
.scf-promo-line--dark {
	color: #142018;
}
.scf-promo-line--subdark {
	color: #5f7166;
	font-size: 24rpx;
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
.scf-room-list {
	margin-bottom: 20rpx;
}
.scf-room-card {
	margin-bottom: 16rpx;
	box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.05);
}
.scf-room-icon {
	background: linear-gradient(145deg, #e8f9ee 0%, #c8ead4 100%) !important;
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
	font-size: 28rpx;
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
	font-size: 26rpx;
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
	font-size: 24rpx;
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
.scf-skip-link {
	text-align: center;
	font-size: 28rpx;
	color: #38b061;
	padding: 16rpx 24rpx 8rpx;
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
	font-size: 26rpx;
	font-weight: 700;
	color: #243028;
	margin-bottom: 8rpx;
}
.scf-info-text {
	font-size: 26rpx;
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
	padding: 22rpx 16rpx;
	border-radius: 14rpx;
	font-size: 28rpx;
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
	font-size: 24rpx;
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
	font-size: 28rpx;
	font-weight: 600;
	color: #222;
}
.zf-intro-step-desc {
	font-size: 24rpx;
	color: #666;
	line-height: 1.55;
}

.zf-scenes {
	min-height: 100vh;
	background: #f5f8f6;
	padding: 24rpx 32rpx calc(48rpx + env(safe-area-inset-bottom));
	box-sizing: border-box;
}
.zf-promo--scene-head {
	margin-top: 0;
	margin-bottom: 0;
}
.zf-scenes-pick {
	margin-top: 28rpx;
	padding: 24rpx 20rpx 20rpx;
	background: #fff;
	border-radius: 24rpx;
	box-shadow: 0 10rpx 32rpx rgba(20, 40, 28, 0.08);
}
.zf-scenes-kicker {
	display: block;
	font-size: 26rpx;
	font-weight: 700;
	color: #18af38;
	margin-bottom: 8rpx;
	letter-spacing: 2rpx;
}
.zf-scenes-hint {
	display: block;
	font-size: 30rpx;
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
	font-size: 32rpx;
	font-weight: 700;
	color: #1a1a1a;
}
.zf-scene-desc {
	display: block;
	margin-top: 8rpx;
	font-size: 24rpx;
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
	font-size: 30rpx;
	font-weight: 700;
	color: #fff;
	line-height: 1.45;
}
.zf-promo-line--sub {
	margin-top: 10rpx;
	font-size: 24rpx;
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
	font-size: 24rpx;
	font-weight: 700;
	color: rgba(255, 255, 255, 0.98);
	line-height: 1.35;
}
.zf-promo-pillar-desc {
	margin-top: 8rpx;
	font-size: 22rpx;
	color: rgba(255, 255, 255, 0.88);
	line-height: 1.45;
}
.zf-promo-pillar--tile .zf-promo-pillar-lab {
	font-size: 24rpx;
}
.zf-promo-pillars--mini .zf-promo-pillar-lab {
	font-size: 22rpx;
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
