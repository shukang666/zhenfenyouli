/**
 * PRD campaignArtwork()：SVG viewBox + 2048×1152 图源，与 首页预览.html / 页面源码/app.js 一致。
 */
import prdNavigatorCampaignImage from '@/common/home-prd-navigator-campaign-image.js'
import { resolveMpImageSource } from '@/common/resolveMpImageDataUri.js'
import settings from '@/common/settings.js'

const IMG_BASE = settings.staticAssetBase + 'planning/'
/** 与 PRD campaignArtwork viewBox 一致 */
export const CAMPAIGN_VIEW_BOX = { x: 98, y: 560, w: 402, h: 235 }
export const CAMPAIGN_SOURCE_SIZE = { w: 2048, h: 1152 }

export function buildCampaignArtworkSvgMarkup(imageHref) {
	const href = String(imageHref || '')
	const vb = CAMPAIGN_VIEW_BOX
	const { w: iw, h: ih } = CAMPAIGN_SOURCE_SIZE
	return (
		'<svg class="campaign-source-image" xmlns="http://www.w3.org/2000/svg" ' +
		`viewBox="${vb.x} ${vb.y} ${vb.w} ${vb.h}" preserveAspectRatio="xMidYMid slice" ` +
		'role="img" aria-label="沿用领航员征集海报中的居民垃圾分类画面">' +
		`<image href="${href}" xlink:href="${href}" width="${iw}" height="${ih}"/>` +
		'</svg>'
	)
}

/** 小程序：base64 → 本地 webp → canvas 按 viewBox 导出（等同 SVG 裁切） */
export function renderCampaignArtworkForMp(vm, canvasSelector, containerSelector) {
	return new Promise(async (resolve, reject) => {
		let localWebp
		try {
			localWebp = await resolveMpImageSource(prdNavigatorCampaignImage)
		} catch (e) {
			reject(e)
			return
		}
		const query = uni.createSelectorQuery().in(vm)
		query.select(containerSelector).boundingClientRect()
		query.select(canvasSelector).fields({ node: true, size: true })
		query.exec(async (res) => {
			const rect = res[0]
			const canvasRes = res[1]
			if (!rect || !rect.width || !canvasRes || !canvasRes.node) {
				reject(new Error('campaign canvas layout not ready'))
				return
			}
			const cssW = rect.width
			const cssH = (cssW * CAMPAIGN_VIEW_BOX.h) / CAMPAIGN_VIEW_BOX.w
			const canvas = canvasRes.node
			const ctx = canvas.getContext('2d')
			const dpr = Math.min(uni.getSystemInfoSync().pixelRatio || 2, 2)
			canvas.width = Math.round(cssW * dpr)
			canvas.height = Math.round(cssH * dpr)
			ctx.scale(dpr, dpr)
			const img = canvas.createImage()
			img.onload = () => {
				const vb = CAMPAIGN_VIEW_BOX
				ctx.drawImage(img, vb.x, vb.y, vb.w, vb.h, 0, 0, cssW, cssH)
				uni.canvasToTempFilePath({
					canvas,
					destWidth: canvas.width,
					destHeight: canvas.height,
					fileType: 'webp',
					quality: 0.92,
					success: (r) => resolve(r.tempFilePath),
					fail: (err) => reject(err),
				})
			}
			img.onerror = (err) => reject(err || new Error('campaign source load fail'))
			img.src = localWebp
		})
	})
}

export const CAMPAIGN_ART_SVG_URL = IMG_BASE + 'home-prd-campaign-art.svg'

export function getCampaignArtworkFallbackSrc() {
	return CAMPAIGN_ART_SVG_URL
}
