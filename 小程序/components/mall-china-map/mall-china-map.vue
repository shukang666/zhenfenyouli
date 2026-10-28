<template>
	<view class="china-map">
		<view class="china-map__canvas-wrap">
			<canvas
				canvas-id="mallChinaMap"
				id="mallChinaMap"
				class="china-map__canvas"
				:width="canvasW"
				:height="canvasH"
				:style="{ width: canvasW + 'px', height: canvasH + 'px' }"
				disable-scroll
				@touchend="onTouchEnd"
			/>
		</view>
	</view>
</template>

<script>
	import settings from '@/common/settings.js'
	import { MAP_REGIONS } from '@/common/mallGeoMap.js'

	const REGION_ORDER = ['northwest', 'north', 'northeast', 'central', 'east', 'southwest', 'south']

	/** 分区配色：同页绿色体系，仅用深浅区分大区 */
	const REGION_PALETTE = {
		northwest: { fill: '#e4efe8', stroke: '#c5d9cc', fillOn: '#18af38', strokeOn: '#12962e', label: '#4a6358' },
		north: { fill: '#dfece4', stroke: '#bdd4c8', fillOn: '#18af38', strokeOn: '#12962e', label: '#4a6358' },
		northeast: { fill: '#e8f2ec', stroke: '#c8ddd0', fillOn: '#18af38', strokeOn: '#12962e', label: '#4a6358' },
		central: { fill: '#dceee3', stroke: '#b8d4c4', fillOn: '#18af38', strokeOn: '#12962e', label: '#4a6358' },
		east: { fill: '#e2f0e8', stroke: '#c0d8ca', fillOn: '#18af38', strokeOn: '#12962e', label: '#4a6358' },
		southwest: { fill: '#d9ebe1', stroke: '#b5cfc0', fillOn: '#18af38', strokeOn: '#12962e', label: '#4a6358' },
		south: { fill: '#e6f0ea', stroke: '#c4d9cc', fillOn: '#18af38', strokeOn: '#12962e', label: '#4a6358' },
	}

	export default {
		name: 'MallChinaMap',
		props: {
			value: {
				type: String,
				default: 'all',
			},
		},
		data() {
			return {
				canvasW: 300,
				canvasH: 260,
				ctx: null,
				mapReady: false,
				layout: null,
				chinaMap: null,
				mapLoadError: false,
			}
		},
		watch: {
			value() {
				if (this.mapReady) {
					this.drawMap()
				}
			},
		},
		mounted() {
			this.ensureMapData().then(() => {
				if (this.chinaMap) this.initCanvas()
			})
		},
		methods: {
			ensureMapData() {
				if (this.chinaMap) return Promise.resolve()
				if (this._mapLoadPromise) return this._mapLoadPromise
				const url =
					settings.sitePublicUrl +
					settings.mallChinaCanvasMapPath +
					'?v=20261028'
				this._mapLoadPromise = new Promise(resolve => {
					uni.request({
						url,
						success: res => {
							if (res.statusCode === 200 && res.data && res.data.regions) {
								this.chinaMap = res.data
							} else {
								this.mapLoadError = true
							}
							resolve()
						},
						fail: () => {
							this.mapLoadError = true
							resolve()
						},
					})
				})
				return this._mapLoadPromise
			},
			geoAspect() {
				const b = (this.chinaMap || {}).bounds || { minLng: 73, maxLng: 135, minLat: 3, maxLat: 54 }
				return (b.maxLng - b.minLng) / (b.maxLat - b.minLat)
			},
			computeLayout() {
				if (!this.chinaMap) return
				const b = this.chinaMap.bounds
				const lngSpan = b.maxLng - b.minLng
				const latSpan = b.maxLat - b.minLat
				const w = this.canvasW
				const h = this.canvasH
				const padX = 10
				const padTop = 12
				const padBottom = 18
				const pw = w - padX * 2
				const ph = h - padTop - padBottom
				const scale = Math.min(pw / lngSpan, ph / latSpan)
				const mapW = lngSpan * scale
				const mapH = latSpan * scale
				const offsetX = padX + (pw - mapW) / 2
				const offsetY = padTop + (ph - mapH) / 2
				this.layout = { scale, offsetX, offsetY, b }
			},
			initCanvas() {
				const query = uni.createSelectorQuery().in(this)
				query
					.select('.china-map')
					.boundingClientRect(rect => {
						const aspect = this.geoAspect()
						if (!rect || !rect.width) {
							this.canvasW = uni.upx2px(690)
						} else {
							this.canvasW = Math.floor(rect.width)
						}
						this.canvasH = Math.floor(this.canvasW / aspect + uni.upx2px(16))
						this.computeLayout()
						this.ctx = uni.createCanvasContext('mallChinaMap', this)
						this.mapReady = true
						this.drawMap()
					})
					.exec()
			},
			project(lng, lat) {
				const L = this.layout
				if (!L) return [0, 0]
				const x = L.offsetX + (lng - L.b.minLng) * L.scale
				const y = L.offsetY + (L.b.maxLat - lat) * L.scale
				return [x, y]
			},
			unproject(x, y) {
				const L = this.layout
				if (!L) return [0, 0]
				const lng = L.b.minLng + (x - L.offsetX) / L.scale
				const lat = L.b.maxLat - (y - L.offsetY) / L.scale
				return [lng, lat]
			},
			drawPolygon(rings, fill, stroke, lineW) {
				const ctx = this.ctx
				for (let r = 0; r < rings.length; r++) {
					const ring = rings[r]
					if (!ring || ring.length < 3) continue
					ctx.beginPath()
					for (let i = 0; i < ring.length; i++) {
						const p = this.project(ring[i][0], ring[i][1])
						if (i === 0) ctx.moveTo(p[0], p[1])
						else ctx.lineTo(p[0], p[1])
					}
					ctx.closePath()
					ctx.setFillStyle(fill)
					ctx.fill()
					ctx.setStrokeStyle(stroke)
					ctx.setLineWidth(lineW)
					ctx.stroke()
				}
			},
			regionColors(key, selected, hasFocus) {
				const pal = REGION_PALETTE[key] || REGION_PALETTE.central
				const on = selected === key
				if (on) {
					return { fill: pal.fillOn, stroke: pal.strokeOn, label: '#ffffff' }
				}
				if (hasFocus) {
					return { fill: pal.fill, stroke: pal.stroke, label: pal.label, dim: true }
				}
				return { fill: pal.fill, stroke: pal.stroke, label: pal.label, dim: false }
			},
			drawMap() {
				if (!this.ctx) return
				this.computeLayout()
				const ctx = this.ctx
				const w = this.canvasW
				const h = this.canvasH
				ctx.clearRect(0, 0, w, h)

				ctx.setFillStyle('#f7faf8')
				ctx.fillRect(0, 0, w, h)

				const selected = this.value
				const hasFocus = selected && selected !== 'all' && selected !== 'creative'
				if (!this.chinaMap) return
				const regions = this.chinaMap.regions

				for (let i = 0; i < REGION_ORDER.length; i++) {
					const key = REGION_ORDER[i]
					const rings = regions[key] || []
					const c = this.regionColors(key, selected, hasFocus)
					if (c.dim && hasFocus) {
						ctx.setGlobalAlpha(0.45)
					} else {
						ctx.setGlobalAlpha(1)
					}
					this.drawPolygon(rings, c.fill, c.stroke, selected === key ? 1.4 : 0.75)
				}
				ctx.setGlobalAlpha(1)

				ctx.setFontSize(10)
				ctx.setTextAlign('center')
				ctx.setTextBaseline('middle')
				const labels = this.chinaMap.labels || {}
				for (let i = 0; i < REGION_ORDER.length; i++) {
					const key = REGION_ORDER[i]
					const pos = labels[key]
					if (!pos) continue
					const p = this.project(pos[0], pos[1])
					const c = this.regionColors(key, selected, hasFocus)
					const on = selected === key
					ctx.setFillStyle(on ? '#ffffff' : c.label)
					if (on) {
						ctx.setFontSize(11)
					} else {
						ctx.setFontSize(10)
					}
					ctx.fillText(this.labelOf(key), p[0], p[1])
				}

				ctx.draw()
			},
			labelOf(key) {
				const r = MAP_REGIONS.find(item => item.key === key)
				return r ? r.name : key
			},
			hitRegion(lng, lat) {
				if (!this.chinaMap) return
				const regions = this.chinaMap.regions
				for (let i = REGION_ORDER.length - 1; i >= 0; i--) {
					const key = REGION_ORDER[i]
					const rings = regions[key] || []
					for (let r = 0; r < rings.length; r++) {
						if (this.pointInRing(lng, lat, rings[r])) {
							return key
						}
					}
				}
				return ''
			},
			pointInRing(lng, lat, ring) {
				let inside = false
				for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
					const xi = ring[i][0]
					const yi = ring[i][1]
					const xj = ring[j][0]
					const yj = ring[j][1]
					const denom = yj - yi
					if (denom === 0) continue
					const intersect =
						yi > lat !== yj > lat &&
						lng < ((xj - xi) * (lat - yi)) / denom + xi
					if (intersect) inside = !inside
				}
				return inside
			},
			onTouchEnd(e) {
				if (!e.changedTouches || !e.changedTouches.length) return
				const t = e.changedTouches[0]
				let x = t.x
				let y = t.y
				if (x === undefined || y === undefined) {
					const query = uni.createSelectorQuery().in(this)
					query
						.select('#mallChinaMap')
						.boundingClientRect(rect => {
							if (!rect) return
							const px = t.clientX - rect.left
							const py = t.clientY - rect.top
							this.pickRegionAt(px, py)
						})
						.exec()
					return
				}
				this.pickRegionAt(x, y)
			},
			pickRegionAt(x, y) {
				const geo = this.unproject(x, y)
				const key = this.hitRegion(geo[0], geo[1])
				if (key) {
					this.onTap(key)
				}
			},
			onTap(key) {
				this.$emit('input', key)
				this.$emit('change', key)
			},
		},
	}
</script>

<style lang="scss" scoped>
.china-map {
	position: relative;
	width: 100%;
}

.china-map__canvas-wrap {
	width: 100%;
	padding: 4rpx 0 8rpx;
	border-radius: 20rpx;
	background: #f7faf8;
	border: 1rpx solid #e8eeeb;
	overflow: hidden;
}

.china-map__canvas {
	display: block;
	margin: 0 auto;
}
</style>
