import {
	getTextCoverStyle,
	TEXT_COVER_H,
	TEXT_COVER_W,
	titleFontSizeForLength,
} from '@/common/memberActiveTextCovers.js'

function measureTextWidth(text, ctx, fontSize) {
	ctx.setFontSize(fontSize)
	const w = ctx.measureText(text).width
	if (w && w > 0) return w
	return text.length * fontSize * 0.92
}

function wrapLinesByWidth(title, ctx, fontSize, maxWidth, maxLines) {
	const t = String(title || '').trim() || '邻里活动'
	const chars = t.split('')
	const lines = []
	let line = ''
	for (let i = 0; i < chars.length; i++) {
		const tryLine = line + chars[i]
		if (measureTextWidth(tryLine, ctx, fontSize) > maxWidth && line) {
			lines.push(line)
			line = chars[i]
			if (lines.length >= maxLines) break
		} else {
			line = tryLine
		}
	}
	if (lines.length < maxLines && line) lines.push(line)
	const consumed = lines.join('').length
	if (consumed < t.length && lines.length > 0) {
		const last = lines.length - 1
		const tail = lines[last]
		lines[last] = tail.length > 1 ? tail.slice(0, -1) + '…' : tail + '…'
	}
	return lines.length ? lines : ['邻里活动']
}

function gradientPoints(angle, w, h) {
	const rad = ((angle % 360) * Math.PI) / 180
	const cx = w / 2
	const cy = h / 2
	const len = Math.sqrt(w * w + h * h) / 2
	return {
		x0: cx - Math.cos(rad) * len,
		y0: cy - Math.sin(rad) * len,
		x1: cx + Math.cos(rad) * len,
		y1: cy + Math.sin(rad) * len,
	}
}

function drawDecor(ctx, decor, w, h, style) {
	const light = 'rgba(255,255,255,0.12)'
	const light2 = 'rgba(255,255,255,0.2)'
	switch (decor) {
		case 'leaf':
			ctx.setFillStyle('rgba(255,255,255,0.15)')
			ctx.beginPath()
			ctx.arc(w * 0.82, h * 0.15, w * 0.2, 0, 2 * Math.PI)
			ctx.fill()
			ctx.setFillStyle('rgba(16,185,129,0.35)')
			ctx.fillRect(0, h * 0.72, w, h * 0.28)
			ctx.setFillStyle('rgba(255,255,255,0.25)')
			ctx.fillRect(w * 0.08, h * 0.1, 6, 56)
			break
		case 'sun':
			ctx.setFillStyle('rgba(255,255,255,0.25)')
			ctx.beginPath()
			ctx.arc(w * 0.5, h * 0.22, w * 0.12, 0, 2 * Math.PI)
			ctx.fill()
			for (let i = 0; i < 8; i++) {
				const a = (i * Math.PI) / 4
				ctx.setStrokeStyle('rgba(255,255,255,0.2)')
				ctx.setLineWidth(3)
				ctx.beginPath()
				ctx.moveTo(w * 0.5 + Math.cos(a) * w * 0.14, h * 0.22 + Math.sin(a) * w * 0.14)
				ctx.lineTo(w * 0.5 + Math.cos(a) * w * 0.2, h * 0.22 + Math.sin(a) * w * 0.2)
				ctx.stroke()
			}
			ctx.setFillStyle('rgba(0,0,0,0.08)')
			ctx.fillRect(0, h * 0.78, w, h * 0.22)
			break
		case 'wave':
			ctx.setFillStyle('rgba(255,255,255,0.1)')
			ctx.beginPath()
			ctx.arc(w * 0.2, h * 0.85, w * 0.35, 0, Math.PI * 2)
			ctx.fill()
			ctx.beginPath()
			ctx.arc(w * 0.75, h * 0.9, w * 0.28, 0, Math.PI * 2)
			ctx.fill()
			ctx.setStrokeStyle('rgba(255,255,255,0.25)')
			ctx.setLineWidth(2)
			for (let y = h * 0.55; y < h * 0.75; y += 18) {
				ctx.beginPath()
				ctx.moveTo(0, y)
				ctx.bezierCurveTo(w * 0.25, y - 12, w * 0.75, y + 12, w, y)
				ctx.stroke()
			}
			break
		case 'aurora':
			ctx.setFillStyle('rgba(255,255,255,0.08)')
			ctx.fillRect(w * 0.1, h * 0.25, w * 0.8, h * 0.08)
			ctx.setFillStyle('rgba(196,181,253,0.35)')
			ctx.beginPath()
			ctx.arc(w * 0.3, h * 0.35, w * 0.25, 0, Math.PI * 2)
			ctx.fill()
			ctx.setFillStyle('rgba(167,139,250,0.25)')
			ctx.beginPath()
			ctx.arc(w * 0.7, h * 0.4, w * 0.3, 0, Math.PI * 2)
			ctx.fill()
			break
		case 'frame':
			ctx.setStrokeStyle('rgba(252,211,77,0.55)')
			ctx.setLineWidth(4)
			ctx.strokeRect(w * 0.07, h * 0.07, w * 0.86, h * 0.86)
			ctx.strokeRect(w * 0.1, h * 0.1, w * 0.8, h * 0.8)
			ctx.setFillStyle('rgba(252,211,77,0.15)')
			ctx.fillRect(w * 0.07, h * 0.07, w * 0.86, 8)
			break
		case 'glass':
			ctx.setFillStyle('rgba(255,255,255,0.45)')
			ctx.fillRect(w * 0.08, h * 0.32, w * 0.84, h * 0.38)
			ctx.setStrokeStyle('rgba(255,255,255,0.6)')
			ctx.setLineWidth(2)
			ctx.strokeRect(w * 0.08, h * 0.32, w * 0.84, h * 0.38)
			break
		case 'note':
			ctx.setFillStyle('rgba(255,255,255,0.95)')
			ctx.fillRect(w * 0.1, h * 0.28, w * 0.8, h * 0.42)
			ctx.setFillStyle('#fecdd3')
			for (let i = 0; i < 5; i++) {
				ctx.fillRect(w * 0.12, h * 0.32 + i * 36, w * 0.76, 2)
			}
			break
		case 'stars':
			ctx.setFillStyle('#ffffff')
			for (let i = 0; i < 24; i++) {
				const sx = (w * (i * 17 + 11)) % w
				const sy = (h * (i * 23 + 7)) % (h * 0.55)
				const r = i % 3 === 0 ? 3 : 2
				ctx.beginPath()
				ctx.arc(sx, sy, r, 0, 2 * Math.PI)
				ctx.fill()
			}
			ctx.setFillStyle('rgba(99,102,241,0.4)')
			ctx.beginPath()
			ctx.arc(w * 0.5, h * 0.18, w * 0.08, 0, Math.PI * 2)
			ctx.fill()
			break
		case 'paper':
			ctx.setStrokeStyle('rgba(120,53,15,0.12)')
			ctx.setLineWidth(1)
			for (let y = h * 0.15; y < h; y += 28) {
				ctx.beginPath()
				ctx.moveTo(w * 0.12, y)
				ctx.lineTo(w * 0.88, y)
				ctx.stroke()
			}
			ctx.setFillStyle('rgba(251,191,36,0.35)')
			ctx.fillRect(w * 0.78, h * 0.08, w * 0.12, h * 0.12)
			break
		case 'geo':
			ctx.setFillStyle(light2)
			ctx.beginPath()
			ctx.moveTo(w * 0.65, 0)
			ctx.lineTo(w, 0)
			ctx.lineTo(w, h * 0.35)
			ctx.fill()
			ctx.setFillStyle(light)
			ctx.beginPath()
			ctx.moveTo(0, h * 0.65)
			ctx.lineTo(0, h)
			ctx.lineTo(w * 0.4, h)
			ctx.fill()
			ctx.setStrokeStyle('rgba(255,255,255,0.3)')
			ctx.setLineWidth(3)
			ctx.beginPath()
			ctx.moveTo(w * 0.15, h * 0.2)
			ctx.lineTo(w * 0.85, h * 0.2)
			ctx.stroke()
			break
		default:
			ctx.setFillStyle(light)
			ctx.beginPath()
			ctx.arc(w * 0.85, h * 0.12, w * 0.22, 0, 2 * Math.PI)
			ctx.fill()
	}
}

function drawBadgePill(ctx, badge, style, w, h) {
	const fs = 24
	ctx.setFontSize(fs)
	const tw = measureTextWidth(badge, ctx, fs)
	const padX = 20
	const pillW = tw + padX * 2
	const pillH = 40
	const x = style.align === 'center' ? (w - pillW) / 2 : w * 0.1
	const y = h * 0.1
	ctx.setFillStyle('rgba(255,255,255,0.22)')
	ctx.fillRect(x, y, pillW, pillH)
	ctx.setFillStyle(style.subColor || 'rgba(255,255,255,0.9)')
	ctx.setTextAlign('left')
	ctx.fillText(badge, x + padX, y + 28)
}

export function drawMemberActiveTextCover(canvasId, opts = {}) {
	const title = String(opts.title || '').trim() || '邻里活动'
	const badge = String(opts.badge || '邻里活动').trim() || '邻里活动'
	const style = getTextCoverStyle(opts.styleId)
	const w = TEXT_COVER_W
	const h = TEXT_COVER_H
	const decor = (style.canvas && style.canvas.decor) || 'default'

	return new Promise((resolve, reject) => {
		const ctx = uni.createCanvasContext(canvasId)
		const g = style.canvas || {}
		const pts = gradientPoints(g.angle || 135, w, h)
		const grd = ctx.createLinearGradient(pts.x0, pts.y0, pts.x1, pts.y1)
		grd.addColorStop(0, g.c0 || '#18af38')
		grd.addColorStop(0.55, g.c1 || '#7dd4a0')
		grd.addColorStop(1, g.c2 || '#ecfdf5')
		ctx.setFillStyle(grd)
		ctx.fillRect(0, 0, w, h)

		drawDecor(ctx, decor, w, h, style)

		drawBadgePill(ctx, badge, style, w, h)

		const titleFs = titleFontSizeForLength(title.length, false)
		ctx.setFillStyle(style.titleColor || '#fff')
		ctx.setTextAlign(style.align === 'center' ? 'center' : 'left')
		const padX = w * 0.1
		const maxW = w - padX * 2
		const maxLines = decor === 'note' ? 4 : 5
		const lines = wrapLinesByWidth(title, ctx, titleFs, maxW, maxLines)

		const noteOffset = decor === 'note' ? h * 0.06 : 0
		const lineH = titleFs * 1.32
		const blockH = lines.length * lineH
		let startY = h * 0.44 - blockH / 2 + titleFs + noteOffset

		if (decor === 'note') {
			ctx.setFillStyle('#881337')
		} else {
			ctx.setFillStyle(style.titleColor || '#fff')
		}

		lines.forEach((line) => {
			const x = style.align === 'center' ? w / 2 : padX
			ctx.setFontSize(titleFs)
			ctx.fillText(line, x, startY)
			startY += lineH
		})

		ctx.draw(false, () => {
			setTimeout(() => {
				uni.canvasToTempFilePath({
					canvasId,
					x: 0,
					y: 0,
					width: w,
					height: h,
					destWidth: w,
					destHeight: h,
					fileType: 'jpg',
					quality: 0.92,
					success: (res) => resolve(res.tempFilePath),
					fail: (err) => reject(err || new Error('生成封面失败')),
				})
			}, 150)
		})
	})
}
