import settings, { uploadUrl } from '@/common/settings'
	import { parseUploadAssetResponse, urlToRelativePath, resolveAssetShowUrl } from '@/common/uploadAsset.js'
	import { pickRandomHabitTip } from '@/common/clockHabitTips.js'
	import { getHub, mergeGuide, normalizeClockHubKey } from '@/common/sceneClockHub.js'
	import { openSceneClockHub } from '@/common/sceneClockEntry.js'
	import {
		openSceneClockHomeSetup,
		openSceneClockHomeClock,
		finishSceneClockHomeSetupNavigation,
	} from '@/common/sceneClock/sceneClockRoutes.js'
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
		getThemeOptInEyebrow,
		getThemeOptInTitle,
		getThemeOptInDesc,
		getThemeOptInYes,
		getThemeOptInLater,
		getSceneHubCards,
		getStreetMerchantHeroTitle,
		getStreetMerchantHeroSub,
		getStreetClaimTitle,
		getStreetClaimLead,
		getStreetClaimShopNamePh,
		getStreetClaimCompanyLabel,
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
		getStreetShopSetupSlotDefs,
		getStreetSetupPageTitle,
		getStreetSetupPageSub,
		getStreetSetupFinishCta,
		getStreetClockNavTitle,
		getStreetClockShopBarHint,
		getStreetClockStep1Title,
		getStreetClockStep1Sub,
		getStreetClockStep2Title,
		getStreetClockPhotoTitle,
		getStreetClockPhotoSub,
		getStreetClockSubmitCta,
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
		getHomeFixedSetupSlots,
		getHomeFixedSetupHeroTitle,
		getHomeFixedSetupHeroSub,
		getHomeFixedSectionStartTitle,
		getHomeFixedSectionRecycleTitle,
		getHomeFixedSetupRewardHint,
		getHomeFixedSetupFinishCta,
		getHomeFixedSetupRequiredHint,
		getHomeClockNavTitle,
		getHomeClockBannerSlogan,
		getHomeClockStep1Title,
		getHomeClockStep1Sub,
		getHomeClockSortRhythmNote,
		getHomeClockLocationStepTitle,
		getHomeClockLocationStepHint,
		getHomeClockLocationLabel,
		getHomeClockStep2Title,
		getHomeClockAmountNote,
		getHomeClockStep4Title,
		getHomeClockStep4Sub,
		getHomeClockPhotoExampleCap,
		getHomeClockPhotoUploadCta,
		getHomeClockPhotoRules,
		getHomeClockSubmitCta,
		getHomeClockFootNote,
		getHomeClockFootPoints,
		getHomeClockAmountOptions,
		getOfficeClockNavTitle,
		getOfficeClockBannerTitle,
		getOfficeClockBannerSub,
		getOfficeClockBannerBadge,
		getOfficeClockRecycleAlertTitle,
		getOfficeClockRecycleAlertBody,
		getOfficeClockBuildingTitle,
		getOfficeClockBuildingPlaceholder,
		getOfficeClockCornerTitle,
		getOfficeClockCornerPendingText,
		getOfficeClockConfirmTitle,
		getOfficeClockItemTitle,
		getOfficeClockItemTags,
		getOfficeClockSaveTitle,
		getOfficeClockSaveOptions,
		getOfficeClockPhotoTitle,
		getOfficeClockPhotoUploadCta,
		getOfficeClockSubmitCta,
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
					home: true,
					office: true,
					street: true,
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
				streetShopTypeGroups: [],
				streetActiveCategoryId: 'catering',
				streetSelectedTypeKey: '',
				streetFormShopName: '',
				streetFormTypeIndex: 0,
				streetMerchantHeroImgOk: true,
				streetMerchantHeroTitle: getStreetMerchantHeroTitle(),
				streetMerchantHeroSub: getStreetMerchantHeroSub(),
				streetClaimTitle: getStreetClaimTitle(),
				streetClaimLead: getStreetClaimLead(),
				streetClaimShopNamePh: getStreetClaimShopNamePh(),
				streetClaimCompanyLabel: getStreetClaimCompanyLabel(),
				streetClaimTypeHint: getStreetClaimTypeHint(),
				streetClaimSubmit: getStreetClaimSubmit(),
				streetSetupKicker: getStreetSetupKicker(),
				streetSetupSub: getStreetSetupSub(),
				streetSetupNamePh: getStreetSetupNamePh(),
				streetSetupFootnote: getStreetSetupFootnote(),
				streetSetupNextCta: getStreetSetupNextCta(),
				streetEditShop: getStreetEditShop(),
				streetFixedSlots: [],
				streetFixedSavingKey: '',
				streetSetupPageTitle: getStreetSetupPageTitle(),
				streetSetupPageSub: getStreetSetupPageSub(),
				streetSetupFinishCta: getStreetSetupFinishCta(),
				streetClockShopBarHint: getStreetClockShopBarHint(),
				streetClockStep1Title: getStreetClockStep1Title(),
				streetClockStep1Sub: getStreetClockStep1Sub(),
				streetClockStep2Title: getStreetClockStep2Title(),
				streetClockPhotoTitle: getStreetClockPhotoTitle(),
				streetClockPhotoSub: getStreetClockPhotoSub(),
				streetClockSubmitCta: getStreetClockSubmitCta(),
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
				homeFixedSlots: [],
				homeFixedHeroTitle: getHomeFixedSetupHeroTitle(),
				homeFixedHeroSub: getHomeFixedSetupHeroSub(),
				homeFixedSectionStartTitle: getHomeFixedSectionStartTitle(),
				homeFixedSectionRecycleTitle: getHomeFixedSectionRecycleTitle(),
				homeFixedRewardHint: getHomeFixedSetupRewardHint(),
				homeFixedFinishCta: getHomeFixedSetupFinishCta(),
				homeFixedRequiredHint: getHomeFixedSetupRequiredHint(),
				homeFixedSavingKey: '',
				homeFixedHeroImgOk: true,
				homeClockBannerImgOk: true,
				homeClockPhotoExampleImgOk: true,
				homeClockCateImgOk: {
					food: true,
					other: true,
					recycle: true,
					harm: true,
				},
				/** 家庭拆分页：clock=今日真分 setup=点位布置 */
				homeHubPage: '',
				homeFixedExampleImgOk: {
					kitchen_food: true,
					kitchen_other: true,
					recycle: true,
					hazard: true,
				},
				homeClockCateKey: '',
				homeClockLocationKey: '',
				homeClockAmount: '',
				homeClockBannerSlogan: getHomeClockBannerSlogan(),
				homeClockStep1Title: getHomeClockStep1Title(),
				homeClockStep1Sub: getHomeClockStep1Sub(),
				homeClockSortRhythmNote: getHomeClockSortRhythmNote(),
				homeClockLocationStepTitle: getHomeClockLocationStepTitle(),
				homeClockLocationStepHint: getHomeClockLocationStepHint(),
				homeClockStep2Title: getHomeClockStep2Title(),
				homeClockAmountNote: getHomeClockAmountNote(),
				homeClockStep4Title: getHomeClockStep4Title(),
				homeClockStep4Sub: getHomeClockStep4Sub(),
				homeClockPhotoExampleCap: getHomeClockPhotoExampleCap(),
				homeClockPhotoUploadCta: getHomeClockPhotoUploadCta(),
				homeClockPhotoRules: getHomeClockPhotoRules(),
				homeClockSubmitCta: getHomeClockSubmitCta(),
				homeClockFootNote: getHomeClockFootNote(),
				homeClockFootPoints: getHomeClockFootPoints(),
				homeClockAmountOptions: getHomeClockAmountOptions(),
				officeBuildingKey: '',
				officeBuildingIndex: 0,
				officeBuildingPickerShow: false,
				officeBuildingSearchQuery: '',
				officeItemTags: [],
				officeSaveDuration: '',
				officeClockBannerTitle: getOfficeClockBannerTitle(),
				officeClockBannerSub: getOfficeClockBannerSub(),
				officeClockBannerBadge: getOfficeClockBannerBadge(),
				officeClockRecycleAlertTitle: getOfficeClockRecycleAlertTitle(),
				officeClockRecycleAlertBody: getOfficeClockRecycleAlertBody(),
				officeClockBuildingTitle: getOfficeClockBuildingTitle(),
				officeClockBuildingPlaceholder: getOfficeClockBuildingPlaceholder(),
				officeClockCornerTitle: getOfficeClockCornerTitle(),
				officeClockCornerPendingText: getOfficeClockCornerPendingText(),
				officeClockConfirmTitle: getOfficeClockConfirmTitle(),
				officeClockItemTitle: getOfficeClockItemTitle(),
				officeClockItemTags: getOfficeClockItemTags(),
				officeClockSaveTitle: getOfficeClockSaveTitle(),
				officeClockSaveOptions: getOfficeClockSaveOptions(),
				officeClockPhotoTitle: getOfficeClockPhotoTitle(),
				officeClockPhotoUploadCta: getOfficeClockPhotoUploadCta(),
				officeClockSubmitCta: getOfficeClockSubmitCta(),
				officeClockHeroImgOk: true,
			}
		},
		computed: {
			homeFixedHeroImgUrl() {
				if (!this.homeFixedHeroImgOk) return ''
				const base = this.scenePickCosBase
				return base + 'home-setup-hero.png'
			},
			homeClockBannerImgUrl() {
				if (!this.homeClockBannerImgOk) return ''
				const base = this.scenePickCosBase
				return base + 'home-clock-banner.png'
			},
			homeClockPhotoExampleImgUrl() {
				if (!this.homeClockPhotoExampleImgOk) return ''
				const base = this.scenePickCosBase
				return base + 'home-clock-photo-example.png'
			},
			officeClockHeroImgUrl() {
				if (!this.officeClockHeroImgOk) return ''
				const base = this.scenePickCosBase
				return base + 'office-clock-banner.jpg'
			},
			/** 固定四处布置照片均已保存到服务端 */
			homeFixedCanFinishSetup() {
				if (this.homeFixedSavingKey) return false
				return this.isHomeFixedSetupComplete()
			},
			officeRecyclePoints() {
				return (this.points || []).filter(p => this.isRecycleCateName(p.cate_name))
			},
			officeBuildingLabels() {
				return (this.officeBuildingOptions || []).map(o => o.label)
			},
			officeBuildingOptions() {
				return (this.officeRecyclePoints || [])
					.map(p => {
						const label = String(p.building_name || p.point_name || '').trim()
						const address = String(p.corner_location || p.remark || p.address || '').trim()
						const id = parseInt(p.id, 10)
						if (!label || !id) return null
						return {
							id,
							label,
							address,
							searchText: (label + ' ' + address).toLowerCase(),
						}
					})
					.filter(Boolean)
			},
			officeBuildingFilteredOptions() {
				const list = this.officeBuildingOptions || []
				const q = String(this.officeBuildingSearchQuery || '').trim().toLowerCase()
				if (!q) return list
				const parts = q.split(/\s+/).filter(Boolean)
				return list.filter(o => {
					const text = o.searchText || ''
					return parts.every(part => text.indexOf(part) >= 0)
				})
			},
			officeCornerPoint() {
				const pid = parseInt(this.pointId, 10)
				if (pid > 0) {
					return (this.officeRecyclePoints || []).find(p => parseInt(p.id, 10) === pid) || null
				}
				if (!this.officeBuildingKey) return null
				const key = this.officeBuildingKey
				return (this.officeRecyclePoints || []).find(
					p => String(p.building_name || p.point_name || '').trim() === key
				) || null
			},
			officeCornerDisplayLocation() {
				const p = this.officeCornerPoint
				if (!p) return ''
				const pending = this.officeClockCornerPendingText || '正在推进中'
				const b = String(this.officeBuildingKey || '').trim()
				const loc = String(p.corner_location || p.remark || p.address || '').trim()
				if (!loc) return pending
				if (loc && b && loc.indexOf(b) < 0) {
					return b + ' ' + loc
				}
				return loc
			},
			homeClockCateCards() {
				const defs = [
					{ key: 'food', label: '厨余垃圾', tone: 'food', match: '厨余', imgFile: 'home-cate-food.jpg', illusPh: '🥕' },
					{ key: 'other', label: '其他垃圾', tone: 'other', match: '其他', imgFile: 'home-cate-other.jpg', illusPh: '🗑️' },
					{ key: 'recycle', label: '可回收垃圾', tone: 'recycle', match: '可回收', exclude: '不可', imgFile: 'home-cate-recycle.jpg', illusPh: '♻️' },
					{ key: 'harm', label: '有害垃圾', tone: 'harm', match: '有害', imgFile: 'home-cate-harm.jpg', illusPh: '☣️' },
				]
				const list = this.cateList || []
				const base = this.scenePickCosBase
				const okMap = this.homeClockCateImgOk || {}
				return defs.map(d => {
					let cate = null
					for (let i = 0; i < list.length; i++) {
						const name = list[i].name || ''
						if (d.exclude && name.indexOf(d.exclude) >= 0) continue
						if (name.indexOf(d.match) >= 0) {
							cate = list[i]
							break
						}
					}
					const imgOk = okMap[d.key] !== false
					const imgUrl = imgOk && d.imgFile ? base + d.imgFile : ''
					return {
						...d,
						imgUrl,
						cateId: cate ? cate.id : 0,
						cateName: cate ? cate.name : d.label,
					}
				})
			},
			streetClockCateCards() {
				const defs = getStreetShopSetupSlotDefs(this.streetMerchantPrimaryCateKw())
				const list = this.cateList || []
				return defs.map(d => {
					let cate = null
					for (let i = 0; i < list.length; i++) {
						const name = list[i].name || ''
						if (d.cateKw === '可回收' && name.indexOf('不可') >= 0) continue
						if (name.indexOf(d.cateKw) >= 0) {
							cate = list[i]
							break
						}
					}
					const ready = !!this.findPointForStreetSlot(d, true)
					return {
						key: d.slotKey,
						label: d.title.replace(/投放点|收集点/g, ''),
						shortLabel: d.cateKw,
						tone: d.tone,
						tag: d.tag,
						cateId: cate ? cate.id : 0,
						cateName: cate ? cate.name : d.cateKw,
						ready,
					}
				})
			},
			streetFixedCanFinishSetup() {
				if (this.streetFixedSavingKey) return false
				return this.isStreetFixedSetupComplete()
			},
			homeClockLocationOptions() {
				const cateKey = this.homeClockCateKey
				if (!cateKey) return []
				if (this.isStreetHub) {
					const defs = getStreetShopSetupSlotDefs(this.streetMerchantPrimaryCateKw())
					const def = defs.find(s => s.slotKey === cateKey)
					if (!def) return []
					const p = this.findPointForStreetSlot(def, true)
					if (!p || !p.id) return []
					return [{
						key: def.slotKey,
						label: String(p.point_name || def.point_name || '').trim(),
						pointId: p.id,
						pointName: String(p.point_name || def.point_name || '').trim(),
						imageUrl: p.point_image || '',
					}]
				}
				const defs = getHomeFixedSetupSlots()
				let slotKeys = []
				if (cateKey === 'food') {
					slotKeys = ['kitchen_food']
				} else if (cateKey === 'other') {
					slotKeys = ['kitchen_other']
				} else if (cateKey === 'recycle') {
					slotKeys = ['recycle']
				} else if (cateKey === 'harm') {
					slotKeys = ['hazard']
				}
				const out = []
				for (let i = 0; i < slotKeys.length; i++) {
					const sk = slotKeys[i]
					const def = defs.find(s => s.slotKey === sk)
					if (!def) continue
					const p = this.findPointForHomeSlot(def, true)
					if (!p || !p.id) continue
					out.push({
						key: sk,
						label: getHomeClockLocationLabel(sk),
						pointId: p.id,
						pointName: String(p.point_name || def.point_name || '').trim(),
						imageUrl: p.point_image || '',
					})
				}
				return out
			},
			/** 无任何家庭点位数据：必须先完成本页布置 */
			homeFixedSetupMandatory() {
				if (!this.isResidentialHub) return false
				return !(this.points || []).some(p => p && p.id && !p._isDraft)
			},
			scenePickCosBase() {
				const base = settings.staticAssetBase || ''
				return base + (base.endsWith('/') ? '' : '/') + 'scene-pick/'
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
			/** 选桶高亮区：家庭固定三点位；商铺不含草稿 */
			residentialPickPoints() {
				if (this.isResidentialHub) {
					return this.homeFixedPickPoints
				}
				return (this.points || []).filter(p => p && p.id && !p._isDraft)
			},
			homeFixedSlotsStart() {
				return (this.homeFixedSlots || []).filter(s => s.section === 'start')
			},
			homeFixedKitchenUploadSlots() {
				return (this.homeFixedSlots || []).filter(s => s.groupKey === 'kitchen')
			},
			homeFixedKitchenLeadSlot() {
				const list = this.homeFixedKitchenUploadSlots
				if (!list.length) return null
				return list.find(s => s.groupRole === 'lead') || list[0]
			},
			homeFixedSlotsStartStandalone() {
				return (this.homeFixedSlotsStart || []).filter(s => !s.groupKey)
			},
			homeFixedSlotsRecycleHarm() {
				return (this.homeFixedSlots || []).filter(s => s.section === 'recycle_harm')
			},
			homeFixedPickPoints() {
				if (!this.isHomeFixedSetupComplete()) {
					return []
				}
				const defs = getHomeFixedSetupSlots()
				const out = []
				for (let i = 0; i < defs.length; i++) {
					const def = defs[i]
					const p = this.findPointForHomeSlot(def, true)
					if (p && p.id && !p._isDraft) {
						out.push(p)
					}
				}
				return out
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
				return getClockSuccessOtherCateTitle()
			},
			clockOtherPickHint() {
				const cur = this.activeCateName || ''
				const all = (this.cateList || []).map(c => c.name).filter(Boolean)
				return getClockSuccessOtherCateBody(cur, all)
			},
			clockSuccessOtherPickList() {
				const curId = Number(this.cateId)
				if (this.isStreetHub) {
					return (this.streetClockCateCards || [])
						.filter(c => c.ready && c.cateId && Number(c.cateId) !== curId)
						.map(c => ({
							key: c.key,
							kind: 'cate',
							label: c.cateName || c.shortLabel,
							raw: { id: c.cateId },
						}))
				}
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
				const t = this.streetSelectedTypeRow
				return (t && t.cate_name) || ''
			},
			streetSelectedTypeRow() {
				const key = String(this.streetSelectedTypeKey || '').trim()
				if (!key) return null
				return (this.streetShopTypes || []).find(t => t.key === key) || null
			},
			streetMerchantHeroImgUrl() {
				if (!this.streetMerchantHeroImgOk) return ''
				const base = this.scenePickCosBase
				return base + 'street-merchant-hero.png'
			},
			streetMerchantTypeChips() {
				const gid = this.streetActiveCategoryId
				const g = (this.streetShopTypeGroups || []).find(x => x.id === gid)
				return (g && g.types) || []
			},
			communityEmptyTitle() {
				return getCommunityEmptyTitle(this.isStreetHub, this.isOfficeHub)
			},
			communityEmptyDesc() {
				return getCommunityEmptyDesc(this.isStreetHub, this.isOfficeHub)
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
					return []
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
		methods: {
			hubPageOnShow() {
				if (!this.hub) return
				const editing = ['setup', 'manage', 'guide', 'survey', 'learn'].indexOf(this.flowStep) >= 0
				if (editing) return
				this.ensureExamGate().then(ok => {
					if (ok) this.initUserPoints()
				})
			},
			hubPageOnLoad(hubKey, option, pageOpts) {
				const po = pageOpts || {}
				this.homeHubPage = String(po.homePage || '').trim()
				if (this.homeHubPage === 'clock') {
					this.flowStep = 'learn'
				} else if (this.homeHubPage === 'setup') {
					this.flowStep = 'setup'
				}
				this.bootstrapHub(normalizeClockHubKey(hubKey))
			},
			goResidentialHomeSetup() {
				if (!this.isResidentialHub) return
				openSceneClockHomeSetup()
			},
			goFlowStepSurvey() {
				this.flowStep = 'survey'
			},
			goFlowStepGuide() {
				this.flowStep = 'guide'
			},
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
			onHomeFixedHeroImgError() {
				this.homeFixedHeroImgOk = false
			},
			onHomeClockBannerImgError() {
				this.homeClockBannerImgOk = false
			},
			onHomeClockCateImgError(cateKey) {
				if (!cateKey || !this.homeClockCateImgOk) return
				this.$set(this.homeClockCateImgOk, cateKey, false)
			},
			onHomeClockPhotoExampleImgError() {
				this.homeClockPhotoExampleImgOk = false
			},
			onOfficeClockHeroImgError() {
				this.officeClockHeroImgOk = false
			},
			onHomeFixedExampleImgError(slotKey) {
				if (!slotKey) return
				this.$set(this.homeFixedExampleImgOk, slotKey, false)
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
				openSceneClockHub(this, hub)
			},
			pickHomeClockAmount(opt) {
				this.homeClockAmount = opt
			},
			selectPresetTip(tip) {
				this.userChoice = tip
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
				if (this.isResidentialHub) {
					this.enterResidentialSetupFlow()
					return
				}
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
			enterResidentialSetupFlow(opts) {
				const replace = !!(opts && opts.replace)
				if (this.isResidentialHub) {
					if (this.homeHubPage === 'setup') {
						this.residentialPickMode = false
						this.residentialBinSettingsOpen = false
						this.syncHomeFixedSlots()
						this.flowStep = 'setup'
						if (this.hub && this.hub.title) {
							uni.setNavigationBarTitle({ title: this.hub.title })
						}
						return
					}
					openSceneClockHomeSetup({ replace })
					return
				}
				this.residentialPickMode = false
				this.residentialBinSettingsOpen = false
				this.syncHomeFixedSlots()
				this.flowStep = 'setup'
				if (this.hub && this.hub.title) {
					uni.setNavigationBarTitle({ title: this.hub.title })
				}
			},
			syncHomeFixedSlots() {
				const defs = getHomeFixedSetupSlots()
				const cosBase = this.scenePickCosBase
				this.homeFixedSlots = defs.map(def => {
					const np = this.findPointForHomeSlot(def)
					const raw = np ? (np.point_image_raw || '') : ''
					const show = np
						? (np.point_image || resolveAssetShowUrl(np.point_image_raw))
						: ''
					const imgKey = def.exampleImgKey || ''
					const exampleImgUrl = imgKey ? cosBase + imgKey + '.png' : ''
					return Object.assign({}, def, {
						id: np && np.id ? np.id : 0,
						point_image: raw,
						point_image_url: show,
						exampleImgUrl,
					})
				})
			},
			pointHasHomeFixedImage(p) {
				if (!p) return false
				const np = p.point_image_raw != null ? p : this.normalizeScenePoint(p)
				return !!String(np.point_image_raw || '').trim()
			},
			findPointForHomeSlot(def, requireImage) {
				if (!def) return null
				const list = (this.points || []).filter(p => p && !p._isDraft)
				const name = String(def.point_name || '').trim()
				const accept = p => !requireImage || this.pointHasHomeFixedImage(p)
				const matchName = (want, p) =>
					!!want && accept(p) && String(p.point_name || '').trim() === want
				if (name) {
					const exact = list.find(p => matchName(name, p))
					if (exact) return this.normalizeScenePoint(exact)
				}
				const legacy = def.legacyPointNames || []
				for (let li = 0; li < legacy.length; li++) {
					const ln = String(legacy[li] || '').trim()
					if (!ln) continue
					const kwLegacy = def.cateKw || ''
					const leg = list.find(p => {
						if (!matchName(ln, p)) return false
						if (!kwLegacy) return true
						return String(p.cate_name || '').indexOf(kwLegacy) >= 0
					})
					if (leg) return this.normalizeScenePoint(leg)
				}
				const kw = def.cateKw || ''
				if (kw) {
					const byCate = list.find(
						p => accept(p) && String(p.cate_name || '').indexOf(kw) >= 0
					)
					if (byCate) return this.normalizeScenePoint(byCate)
				}
				return null
			},
			isHomeFixedSetupComplete() {
				const defs = getHomeFixedSetupSlots()
				for (let i = 0; i < defs.length; i++) {
					if (!this.findPointForHomeSlot(defs[i], true)) {
						return false
					}
				}
				return true
			},
			homeFixedMissingSlotTitles() {
				const defs = getHomeFixedSetupSlots()
				const missing = []
				for (let i = 0; i < defs.length; i++) {
					if (!this.findPointForHomeSlot(defs[i], true)) {
						const d = defs[i]
						missing.push(d.uploadLabel || d.title || d.slotKey)
					}
				}
				return missing
			},
			onHomeFixedFinishAll() {
				if (this.homeFixedSavingKey) {
					this.$u.toast('照片保存中，请稍候')
					return
				}
				if (!this.isHomeFixedSetupComplete()) {
					const missing = this.homeFixedMissingSlotTitles()
					if (missing.length) {
						this.$u.toast('请先上传：' + missing.join('、'))
					} else {
						this.$u.toast('请先完成四处点位照片上传')
					}
					return
				}
				this.enterResidentialClockFlow()
			},
			enterResidentialClockFlow(opts) {
				const replace = !!(opts && opts.replace)
				if (!this.isHomeFixedSetupComplete()) {
					this.enterResidentialSetupFlow({ replace: true })
					return
				}
				if (this.homeHubPage === 'setup') {
					finishSceneClockHomeSetupNavigation()
					return
				}
				if (this.homeHubPage === 'clock') {
					this.residentialPickMode = false
					this.residentialBinSettingsOpen = false
					this.resetHomeClockForm()
					this.flowStep = 'learn'
					uni.setNavigationBarTitle({ title: getHomeClockNavTitle() })
					return
				}
				openSceneClockHomeClock({ replace })
			},
			applyResidentialHomeClockPageAfterLoad() {
				if (!this.isHomeFixedSetupComplete()) {
					openSceneClockHomeSetup({ replace: true })
					return
				}
				this.residentialPickMode = false
				this.residentialBinSettingsOpen = false
				this.flowStep = 'learn'
				uni.setNavigationBarTitle({ title: getHomeClockNavTitle() })
			},
			applyResidentialHomeSetupPageAfterLoad() {
				this.syncHomeFixedSlots()
				this.flowStep = 'setup'
				if (this.hub && this.hub.title) {
					uni.setNavigationBarTitle({ title: this.hub.title })
				}
			},
			enterOfficeClockFlow() {
				const labels = this.officeBuildingOptions || []
				if (!labels.length) {
					if ((this.points || []).length) {
						this.$u.toast('办公点位种类需为可回收物，请联系社区调整配置')
					}
					this.flowStep = 'hub_empty'
					return
				}
				this.residentialPickMode = false
				this.resetOfficeClockForm()
				this.flowStep = 'learn'
				this.subKey = 'zhenfenjiao'
				if (this.officeBuildingOptions.length === 1) {
					this.applyOfficeBuildingById(this.officeBuildingOptions[0].id)
				}
				uni.setNavigationBarTitle({ title: getOfficeClockNavTitle() })
			},
			resetOfficeClockForm() {
				this.officeBuildingKey = ''
				this.officeBuildingIndex = 0
				this.officeBuildingPickerShow = false
				this.officeBuildingSearchQuery = ''
				this.officeItemTags = []
				this.officeSaveDuration = ''
				this.pointId = 0
				this.cateId = 0
				this.activeCateName = ''
				this.clockContent = ''
				this.resetClockSlots()
			},
			isRecycleCateName(name) {
				const n = String(name || '')
				return n.indexOf('可回收') >= 0 && n.indexOf('不可') < 0
			},
			applyOfficeBuildingById(id) {
				const pid = parseInt(id, 10)
				if (!pid) return
				const p = (this.officeRecyclePoints || []).find(item => parseInt(item.id, 10) === pid)
				if (!p) return
				const key = String(p.building_name || p.point_name || '').trim()
				this.officeBuildingKey = key
				const labels = this.officeBuildingLabels || []
				const idx = labels.indexOf(key)
				this.officeBuildingIndex = idx >= 0 ? idx : 0
				this.pointId = pid
				this.cateId = p.cate_id
				this.activeCateName = p.cate_name || ''
				this.cateLabel = guideCateLabelFromName(p.cate_name)
				this.subKey = 'zhenfenjiao'
				this.loadGuide()
			},
			applyOfficeBuildingByName(name) {
				const key = String(name || '').trim()
				if (!key) return
				const p = (this.officeRecyclePoints || []).find(
					item => String(item.building_name || item.point_name || '').trim() === key
				)
				if (p) {
					this.applyOfficeBuildingById(p.id)
				}
			},
			openOfficeBuildingPicker() {
				this.officeBuildingSearchQuery = ''
				this.officeBuildingPickerShow = true
			},
			closeOfficeBuildingPicker() {
				this.officeBuildingPickerShow = false
				this.officeBuildingSearchQuery = ''
			},
			onOfficeBuildingSearchInput(e) {
				this.officeBuildingSearchQuery = (e && e.detail && e.detail.value) != null
					? String(e.detail.value)
					: ''
			},
			clearOfficeBuildingSearch() {
				this.officeBuildingSearchQuery = ''
			},
			pickOfficeBuildingOptionByIndex(idx) {
				const list = this.officeBuildingFilteredOptions || []
				const opt = list[idx]
				if (!opt || !opt.id) return
				this.applyOfficeBuildingById(opt.id)
				this.closeOfficeBuildingPicker()
			},
			toggleOfficeItemTag(tag) {
				const t = String(tag || '')
				if (!t) return
				const list = [...(this.officeItemTags || [])]
				const pos = list.indexOf(t)
				if (pos >= 0) list.splice(pos, 1)
				else list.push(t)
				this.officeItemTags = list
			},
			toggleOfficeItemTagByIndex(idx) {
				const tags = this.officeClockItemTags || []
				const tag = tags[idx]
				if (tag == null) return
				this.toggleOfficeItemTag(tag)
			},
			pickOfficeSaveDurationByIndex(idx) {
				const opts = this.officeClockSaveOptions || []
				const opt = opts[idx]
				if (opt == null) return
				this.officeSaveDuration = opt
			},
			resetHomeClockForm() {
				this.homeClockCateKey = ''
				this.homeClockLocationKey = ''
				this.homeClockAmount = ''
				this.cateId = 0
				this.activeCateName = ''
				this.pointId = 0
				this.pointName = ''
				this.clockContent = ''
				this.accumDuration = ''
				this.sortDuration = ''
				this.resetClockSlots()
			},
			selectHomeClockCate(card) {
				if (!card || !card.cateId) {
					this.$u.toast('分类种类加载中，请稍后再试')
					return
				}
				if (this.isStreetHub && card.ready === false) {
					this.$u.toast('请先在布置页上传该种类点位照片')
					return
				}
				this.homeClockCateKey = card.key
				this.homeClockLocationKey = ''
				this.pointId = 0
				this.pointName = ''
				this.cateId = card.cateId
				this.activeCateName = card.cateName
				this.cateLabel = guideCateLabelFromName(card.cateName)
				this.subKey = this.guideSubKeyForHub()
				this.loadGuide()
				const locs = this.homeClockLocationOptions
				if (locs.length) {
					this.pickHomeClockLocation(locs[0])
				} else {
					this.$u.toast('请先完成对应点位的布置照片')
				}
			},
			pickHomeClockLocation(loc) {
				if (!loc || !loc.pointId) return
				this.homeClockLocationKey = loc.key
				this.pointId = loc.pointId
				this.pointName = loc.pointName || ''
			},
			chooseHomeFixedPhoto(slotKey) {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				if (this.homeFixedSavingKey) return
				const idx = (this.homeFixedSlots || []).findIndex(s => s.slotKey === slotKey)
				if (idx < 0) return
				this.pickAndUploadPointPhoto((savePath, showUrl) => {
					this.$set(this.homeFixedSlots[idx], 'point_image', savePath)
					this.$set(this.homeFixedSlots[idx], 'point_image_url', showUrl)
					this.persistHomeFixedSlot(idx)
				})
			},
			async persistHomeFixedSlot(idx) {
				const slot = (this.homeFixedSlots || [])[idx]
				if (!slot) return
				const img = String(slot.point_image || '').trim()
				if (!img) return
				let cate = (this.cateList || []).find(c => String(c.name || '').indexOf(slot.cateKw) >= 0)
				if (!cate && this.cateList.length) {
					cate = this.cateList[this.cateIndexByKeyword(slot.cateKw)]
				}
				if (!cate) {
					this.$u.toast('分类种类加载中，请稍后再试')
					return
				}
				this.homeFixedSavingKey = slot.slotKey
				try {
					const payload = {
						hub_key: this.hubKey,
						point_name: String(slot.point_name || '').trim(),
						cate_id: cate.id,
						point_image: img,
					}
					if (slot.id) payload.id = slot.id
					const res = await this.$u.api.save_clock_scene_point(payload)
					const saved = (res && res.data) || {}
					if (saved.id) {
						this.$set(this.homeFixedSlots[idx], 'id', saved.id)
					}
					await this.initUserPoints('setup')
					this.$u.toast('已保存')
				} catch (e) {
					this.$u.toast((e && e.msg) || '保存失败，请重试')
				} finally {
					this.homeFixedSavingKey = ''
				}
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
				if (this.isResidentialHub) {
					this.enterResidentialClockFlow()
					return
				}
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
				if (this.isResidentialHub && !this.isHomeFixedSetupComplete()) {
					this.$u.toast('请先完成四处点位布置照片')
					this.enterResidentialSetupFlow()
					return
				}
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
					this.enterResidentialSetupFlow()
					if (this.hub && this.hub.title) {
						uni.setNavigationBarTitle({ title: this.hub.title })
					}
					return
				}
				if (this.isStreetHub) {
					this.enterStreetSetupFlow()
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
				if ((this.streetShopTypes || []).length && (this.streetShopTypeGroups || []).length) {
					return Promise.resolve(this.streetShopTypes)
				}
				return this.$u.api.street_shop_types().then(res => {
					const data = res.data || {}
					if (Array.isArray(data)) {
						this.streetShopTypes = data
						this.streetShopTypeGroups = []
					} else {
						this.streetShopTypes = data.list || []
						this.streetShopTypeGroups = data.groups || []
					}
					if (!this.streetActiveCategoryId && this.streetShopTypeGroups.length) {
						this.streetActiveCategoryId = this.streetShopTypeGroups[0].id
					}
					return this.streetShopTypes
				}).catch(() => {
					this.streetShopTypes = []
					this.streetShopTypeGroups = []
					return []
				})
			},
			pickStreetCategory(catId) {
				this.streetActiveCategoryId = catId
			},
			pickStreetShopTypeKey(typeKey) {
				this.streetSelectedTypeKey = typeKey
				const types = this.streetShopTypes || []
				const idx = types.findIndex(t => t.key === typeKey)
				this.streetFormTypeIndex = idx >= 0 ? idx : 0
			},
			onStreetMerchantHeroImgError() {
				this.streetMerchantHeroImgOk = false
			},
			onStreetTypePick(e) {
				const idx = Number(e.detail.value)
				this.streetFormTypeIndex = Number.isFinite(idx) ? idx : 0
			},
			fillStreetFormFromMerchant(merchant) {
				if (!merchant) return
				this.streetFormShopName = merchant.shop_name || ''
				const types = this.streetShopTypes || []
				const key = merchant.shop_type || ''
				let ti = types.findIndex(x => x.key === key)
				if (ti < 0) ti = 0
				this.streetFormTypeIndex = ti
				if (key) {
					this.streetSelectedTypeKey = key
					const row = types[ti]
					if (row && row.category) {
						this.streetActiveCategoryId = row.category
					}
				}
			},
			applyStreetMerchantToSession(merchant) {
				if (!merchant) return
				this.streetMerchant = merchant
				this.cateId = Number(merchant.cate_id) || 0
				this.activeCateName = merchant.cate_name || ''
				this.cateLabel = guideCateLabelFromName(merchant.cate_name)
				this.subKey = ''
			},
			streetMerchantShopCategory() {
				const key = (this.streetMerchant && this.streetMerchant.shop_type)
					|| this.streetSelectedTypeKey
					|| ''
				const row = (this.streetShopTypes || []).find(x => x.key === key)
				if (row && row.category) return row.category
				const t = this.streetSelectedTypeRow
				if (t && t.category) return t.category
				return 'retail'
			},
			streetMerchantPrimaryCateKw() {
				const fromMerchant = String((this.streetMerchant && this.streetMerchant.cate_name) || '').trim()
				if (fromMerchant) {
					if (fromMerchant.indexOf('厨余') >= 0) return '厨余'
					if (fromMerchant.indexOf('有害') >= 0) return '有害'
					if (fromMerchant.indexOf('可回收') >= 0 && fromMerchant.indexOf('不可') < 0) return '可回收'
					if (fromMerchant.indexOf('其他') >= 0) return '其他'
				}
				const t = this.streetSelectedTypeRow
				const cn = (t && t.cate_name) || ''
				if (cn.indexOf('厨余') >= 0) return '厨余'
				if (cn.indexOf('有害') >= 0) return '有害'
				if (cn.indexOf('可回收') >= 0 && cn.indexOf('不可') < 0) return '可回收'
				return '其他'
			},
			syncStreetFixedSlots() {
				const defs = getStreetShopSetupSlotDefs(this.streetMerchantPrimaryCateKw())
				this.streetFixedSlots = defs.map(def => {
					const np = this.findPointForStreetSlot(def)
					const raw = np ? (np.point_image_raw || '') : ''
					const show = np
						? (np.point_image || resolveAssetShowUrl(np.point_image_raw))
						: ''
					return Object.assign({}, def, {
						id: np && np.id ? np.id : 0,
						point_image: raw,
						point_image_url: show,
					})
				})
			},
			findPointForStreetSlot(def, requireImage) {
				if (!def) return null
				const list = (this.points || []).filter(p => p && !p._isDraft)
				const name = String(def.point_name || '').trim()
				const accept = p => !requireImage || this.pointHasHomeFixedImage(p)
				if (name) {
					const exact = list.find(p => accept(p) && String(p.point_name || '').trim() === name)
					if (exact) return this.normalizeScenePoint(exact)
				}
				const kw = def.cateKw || ''
				if (kw) {
					const byCate = list.find(p => {
						if (!accept(p)) return false
						const cn = String(p.cate_name || '')
						if (kw === '可回收' && cn.indexOf('不可') >= 0) return false
						return cn.indexOf(kw) >= 0
					})
					if (byCate) return this.normalizeScenePoint(byCate)
				}
				return null
			},
			isStreetFixedSetupComplete() {
				const defs = getStreetShopSetupSlotDefs(this.streetMerchantPrimaryCateKw())
				for (let i = 0; i < defs.length; i++) {
					if (!this.findPointForStreetSlot(defs[i], true)) {
						return false
					}
				}
				return defs.length > 0
			},
			enterStreetSetupFlow() {
				this.residentialPickMode = false
				this.residentialBinSettingsOpen = false
				this.syncStreetFixedSlots()
				this.flowStep = 'setup'
				uni.setNavigationBarTitle({ title: getStreetSetupPageTitle() })
			},
			enterStreetClockFlow() {
				if (!this.isStreetFixedSetupComplete()) {
					this.enterStreetSetupFlow()
					return
				}
				this.residentialPickMode = false
				this.residentialBinSettingsOpen = false
				this.resetHomeClockForm()
				this.flowStep = 'learn'
				uni.setNavigationBarTitle({ title: getStreetClockNavTitle() })
				const cards = this.streetClockCateCards || []
				if (cards.length === 1 && cards[0].ready) {
					this.selectHomeClockCate(cards[0])
				}
			},
			chooseStreetFixedPhoto(slotKey) {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				if (this.streetFixedSavingKey) return
				const idx = (this.streetFixedSlots || []).findIndex(s => s.slotKey === slotKey)
				if (idx < 0) return
				this.pickAndUploadPointPhoto((savePath, showUrl) => {
					this.$set(this.streetFixedSlots[idx], 'point_image', savePath)
					this.$set(this.streetFixedSlots[idx], 'point_image_url', showUrl)
					this.persistStreetFixedSlot(idx)
				})
			},
			async persistStreetFixedSlot(idx) {
				const slot = (this.streetFixedSlots || [])[idx]
				if (!slot) return
				const img = String(slot.point_image || '').trim()
				if (!img) return
				let cate = (this.cateList || []).find(c => {
					const name = String(c.name || '')
					if (slot.cateKw === '可回收' && name.indexOf('不可') >= 0) return false
					return name.indexOf(slot.cateKw) >= 0
				})
				if (!cate && this.cateList.length) {
					cate = this.cateList[this.cateIndexByKeyword(slot.cateKw)]
				}
				if (!cate) {
					this.$u.toast('分类种类加载中，请稍后再试')
					return
				}
				this.streetFixedSavingKey = slot.slotKey
				try {
					const payload = {
						hub_key: 'street',
						point_name: String(slot.point_name || '').trim(),
						cate_id: cate.id,
						point_image: img,
					}
					if (slot.id) payload.id = slot.id
					const res = await this.$u.api.save_clock_scene_point(payload)
					const saved = (res && res.data) || {}
					if (saved.id) {
						this.$set(this.streetFixedSlots[idx], 'id', saved.id)
					}
					await this.initStreetMerchantFlow('setup')
					this.$u.toast('已保存')
				} catch (e) {
					this.$u.toast((e && e.msg) || '保存失败，请重试')
				} finally {
					this.streetFixedSavingKey = ''
				}
			},
			onStreetFixedFinishAll() {
				if (this.streetFixedSavingKey) {
					this.$u.toast('照片保存中，请稍候')
					return
				}
				if (!this.isStreetFixedSetupComplete()) {
					this.$u.toast('请先完成全部点位的布置照片')
					return
				}
				this.enterStreetClockFlow()
			},
			routeStreetAfterMerchantLoaded(keepFlow) {
				if (keepFlow === 'guide' || keepFlow === 'survey') return
				if (keepFlow === 'learn' || keepFlow === 'pick') {
					this.enterStreetClockFlow()
					return
				}
				if (keepFlow === 'setup') {
					this.enterStreetSetupFlow()
					return
				}
				if (this.isStreetFixedSetupComplete()) {
					this.enterStreetClockFlow()
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
				const t = this.streetSelectedTypeRow
				if (!t || !t.key) {
					return this.$u.toast('请选择公司类型')
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
						if (this.homeHubPage === 'clock') {
							openSceneClockHomeSetup({ replace: true })
						} else {
							this.applyResidentialHomeSetupPageAfterLoad()
						}
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
						if (this.isResidentialHub) {
							this.syncHomeFixedSlots()
						} else {
							this.hydrateSetupRowsFromPoints()
						}
						return
					}
					if (keepFlow === 'cate' || keepFlow === 'points') {
						if (this.isResidentialHub) {
							if (this.isHomeFixedSetupComplete()) {
								this.enterResidentialClockFlow()
							} else {
								this.enterResidentialSetupFlow()
							}
						}
						return
					}
					if (this.isCommunityHub) {
						if (keepFlow === 'learn') return
						if (this.points.length > 0) {
							this.enterOfficeClockFlow()
						} else {
							this.flowStep = 'hub_empty'
						}
						return
					}
					if (this.isResidentialHub) {
						if (this.homeHubPage === 'setup') {
							if (keepFlow === 'setup' || keepFlow === 'manage') {
								this.applyResidentialHomeSetupPageAfterLoad()
								return
							}
							if (keepFlow === 'learn') {
								finishSceneClockHomeSetupNavigation()
								return
							}
							this.applyResidentialHomeSetupPageAfterLoad()
							return
						}
						if (this.homeHubPage === 'clock') {
							if (keepFlow === 'learn' || keepFlow === 'setup' || keepFlow === 'manage' || keepFlow === 'cate') {
								if (keepFlow === 'learn') {
									this.applyResidentialHomeClockPageAfterLoad()
								}
								return
							}
							this.applyResidentialHomeClockPageAfterLoad()
							return
						}
						if (keepFlow === 'setup' || keepFlow === 'manage' || keepFlow === 'cate' || keepFlow === 'learn') {
							return
						}
						if (this.isHomeFixedSetupComplete()) {
							this.enterResidentialClockFlow()
						} else {
							this.enterResidentialSetupFlow({ replace: true })
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
					if (this.isOfficeHub && ['learn', 'hub_empty'].indexOf(this.flowStep) < 0) {
						if ((this.officeBuildingOptions || []).length) {
							this.enterOfficeClockFlow()
						} else {
							this.flowStep = 'hub_empty'
						}
					}
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
						await this.initStreetMerchantFlow('learn')
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
				if (this.isResidentialHub || this.isOfficeHub) {
					this.clockSlotUrls = ['']
					return
				}
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
				if (this.isResidentialHub || this.isStreetHub) {
					if (!this.homeClockCateKey || !this.cateId) {
						return this.$u.toast('请选择今天打卡的垃圾种类')
					}
					if (!this.pointId) return this.$u.toast('请先完成对应点位的布置照片')
					if (!this.homeClockAmount) return this.$u.toast('请选择今天大约有多少')
					if (!String(this.clockSlotUrls[0] || '').trim()) {
						return this.$u.toast('请上传今日真分照片')
					}
				} else if (this.isOfficeHub) {
					if (!this.officeBuildingKey || !this.pointId) {
						return this.$u.toast('请选择办公楼宇')
					}
					if (!(this.officeItemTags || []).length) {
						return this.$u.toast('请选择今天投了什么')
					}
					if (!this.officeSaveDuration) return this.$u.toast('请选择攒了多久')
					if (!String(this.clockSlotUrls[0] || '').trim()) {
						return this.$u.toast('请上传真分投递照片')
					}
				} else {
					if (!this.cateId) return this.$u.toast('暂未匹配到垃圾种类')
					const missing = this.missingClockSlotLabel()
					if (missing) return this.$u.toast('请拍摄「' + missing + '」照片')
				}
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
				if (this.isResidentialHub || this.isStreetHub) {
					if (this.pointName) parts.push('打卡位置：' + this.pointName)
					if (this.homeClockAmount) parts.push('今日数量：' + this.homeClockAmount)
				} else if (this.isOfficeHub) {
					if (this.officeBuildingKey) parts.push('办公楼宇：' + this.officeBuildingKey)
					if ((this.officeItemTags || []).length) {
						parts.push('投放物：' + this.officeItemTags.join('、'))
					}
					if (this.officeSaveDuration) parts.push('积攒：' + this.officeSaveDuration)
				} else if (this.accumDuration || this.sortDuration) {
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
					clock_sub_scene: this.isOfficeHub
						? String(this.officeBuildingKey || 'zhenfenjiao').slice(0, 50)
						: '',
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
					this.enterResidentialClockFlow()
					return
				}
				if (this.isOfficeHub) {
					this.enterOfficeClockFlow()
					return
				}
				if (this.isStreetHub) {
					this.enterStreetClockFlow()
					return
				}
				this.flowStep = 'points'
			},
			clockSuccessJumpOtherPick(item) {
				if (!item) return
				this.clockSuccessShow = false
				this.resetHomeClockForm()
				this.flowStep = 'learn'
				const cateCards = this.isStreetHub
					? (this.streetClockCateCards || [])
					: (this.homeClockCateCards || [])
				if (item.kind === 'cate' && item.raw) {
					const card = cateCards.find(
						c => Number(c.cateId) === Number(item.raw.id)
					)
					if (card) this.selectHomeClockCate(card)
					return
				}
				if (item.kind === 'bin' && item.raw && item.raw.cate_id) {
					const card = cateCards.find(
						c => Number(c.cateId) === Number(item.raw.cate_id)
					)
					if (card) this.selectHomeClockCate(card)
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
