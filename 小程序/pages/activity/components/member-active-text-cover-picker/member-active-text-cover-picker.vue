<template>
	<view class="tcv">
		<view
			class="tcv-main"
			:class="['tcv-layout-' + mainStyle.layout, 'tcv-align-' + mainStyle.align]"
			:style="{ background: mainStyle.gradient }"
		>
			<view class="tcv-layer tcv-layer--decor" v-if="mainStyle.layout === 'leaf'">
				<view class="tcv-blob tcv-blob--tr"></view>
				<view class="tcv-band tcv-band--bottom"></view>
				<view class="tcv-accent-bar"></view>
			</view>
			<view class="tcv-layer tcv-layer--decor" v-else-if="mainStyle.layout === 'sun'">
				<view class="tcv-sun"></view>
				<view class="tcv-band tcv-band--dark"></view>
			</view>
			<view class="tcv-layer tcv-layer--decor" v-else-if="mainStyle.layout === 'wave'">
				<view class="tcv-wave"></view>
			</view>
			<view class="tcv-layer tcv-layer--decor" v-else-if="mainStyle.layout === 'aurora'">
				<view class="tcv-aurora tcv-aurora--1"></view>
				<view class="tcv-aurora tcv-aurora--2"></view>
			</view>
			<view class="tcv-layer tcv-layer--decor" v-else-if="mainStyle.layout === 'frame'">
				<view class="tcv-frame"></view>
			</view>
			<view class="tcv-layer tcv-layer--decor" v-else-if="mainStyle.layout === 'glass'">
				<view class="tcv-glass"></view>
			</view>
			<view class="tcv-layer tcv-layer--decor" v-else-if="mainStyle.layout === 'note'">
				<view class="tcv-notepad"></view>
			</view>
			<view class="tcv-layer tcv-layer--decor" v-else-if="mainStyle.layout === 'stars'">
				<view
					class="tcv-star"
					v-for="(st, n) in starDots"
					:key="'s' + n"
					:style="{ left: st.left, top: st.top, opacity: st.opacity }"
				></view>
			</view>
			<view class="tcv-layer tcv-layer--decor" v-else-if="mainStyle.layout === 'paper'">
				<view class="tcv-paper-lines"></view>
				<view class="tcv-tape"></view>
			</view>
			<view class="tcv-layer tcv-layer--decor" v-else-if="mainStyle.layout === 'geo'">
				<view class="tcv-tri"></view>
				<view class="tcv-geo-bar"></view>
			</view>

			<view class="tcv-badge-pill" :class="'tcv-align-' + mainStyle.align">
				<text class="tcv-badge-text" :style="{ color: mainStyle.subColor }">{{ badgeText }}</text>
			</view>

			<view
				class="tcv-title-wrap"
				:class="[
					'tcv-align-' + mainStyle.align,
					mainStyle.layout === 'note' ? 'tcv-title-wrap--note' : '',
					mainStyle.layout === 'glass' ? 'tcv-title-wrap--glass' : '',
				]"
			>
				<text
					v-for="(line, i) in titleLines"
					:key="i"
					class="tcv-title-line"
					:style="{
						color: titleColorOnCard,
						fontSize: titleFontSize + 'rpx',
					}"
				>{{ line }}</text>
			</view>
		</view>

		<text class="tcv-grid-label">选一款文字封面</text>
		<scroll-view scroll-x class="tcv-scroll" :show-scrollbar="false">
			<view
				class="tcv-thumb"
				v-for="s in styles"
				:key="s.id"
				:class="{ on: s.id === innerId }"
				@tap="pick(s.id)"
			>
				<view class="tcv-thumb-inner" :class="'tcv-thumb-' + s.layout" :style="{ background: s.gradient }">
					<text class="tcv-thumb-text" :style="{ color: s.titleColor }">{{ thumbLine1 }}</text>
					<text class="tcv-thumb-text2" v-if="thumbLine2" :style="{ color: s.titleColor }">{{ thumbLine2 }}</text>
				</view>
				<text class="tcv-thumb-name">{{ s.name }}</text>
			</view>
		</scroll-view>
	</view>
</template>

<script>
import {
	MEMBER_TEXT_COVER_STYLES,
	getTextCoverStyle,
	splitTitleLines,
	titleFontSizeForLength,
} from '@/common/memberActiveTextCovers.js'

export default {
	name: 'MemberActiveTextCoverPicker',
	props: {
		title: { type: String, default: '' },
		badge: { type: String, default: '邻里活动' },
		value: { type: String, default: 'forest' },
	},
	data() {
		return {
			styles: MEMBER_TEXT_COVER_STYLES,
			innerId: this.value || 'forest',
		}
	},
	computed: {
		mainStyle() {
			return getTextCoverStyle(this.innerId)
		},
		badgeText() {
			return String(this.badge || '').trim() || '邻里活动'
		},
		titleLines() {
			return splitTitleLines(this.title, 5, 14)
		},
		titleFontSize() {
			const n = String(this.title || '').trim().length
			return titleFontSizeForLength(n, true)
		},
		titleColorOnCard() {
			if (this.mainStyle.layout === 'note') return '#881337'
			return this.mainStyle.titleColor
		},
		thumbLines() {
			return splitTitleLines(this.title, 2, 7)
		},
		thumbLine1() {
			const t = String(this.title || '').trim()
			if (!t) return '活动'
			return this.thumbLines[0] || t.slice(0, 7)
		},
		thumbLine2() {
			return this.thumbLines[1] || ''
		},
		starDots() {
			return [
				{ left: '12%', top: '12%', opacity: 0.9 },
				{ left: '28%', top: '22%', opacity: 0.7 },
				{ left: '45%', top: '10%', opacity: 1 },
				{ left: '62%', top: '18%', opacity: 0.8 },
				{ left: '78%', top: '8%', opacity: 0.85 },
				{ left: '88%', top: '28%', opacity: 0.75 },
				{ left: '18%', top: '38%', opacity: 0.65 },
				{ left: '55%', top: '32%', opacity: 0.9 },
				{ left: '72%', top: '42%', opacity: 0.7 },
				{ left: '35%', top: '48%', opacity: 0.8 },
				{ left: '8%', top: '52%', opacity: 0.6 },
				{ left: '92%', top: '48%', opacity: 0.85 },
			]
		},
	},
	watch: {
		value(v) {
			if (v && v !== this.innerId) this.innerId = v
		},
	},
	methods: {
		pick(id) {
			this.innerId = id
			this.$emit('input', id)
			this.$emit('change', id)
		},
	},
}
</script>

<style lang="scss" scoped>
.tcv-main {
	position: relative;
	width: 100%;
	min-height: 380rpx;
	padding: 28rpx 0 36rpx;
	border-radius: 20rpx;
	overflow: hidden;
	box-shadow: 0 12rpx 32rpx rgba(22, 53, 36, 0.12);
}
.tcv-layer {
	position: absolute;
	left: 0;
	top: 0;
	right: 0;
	bottom: 0;
	pointer-events: none;
}
.tcv-blob {
	position: absolute;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.14);
}
.tcv-blob--tr {
	width: 200rpx;
	height: 200rpx;
	right: -50rpx;
	top: -40rpx;
}
.tcv-band {
	position: absolute;
	left: 0;
	right: 0;
}
.tcv-band--bottom {
	bottom: 0;
	height: 28%;
	background: rgba(16, 185, 129, 0.35);
}
.tcv-band--dark {
	bottom: 0;
	height: 22%;
	background: rgba(0, 0, 0, 0.08);
}
.tcv-accent-bar {
	position: absolute;
	left: 24rpx;
	top: 52rpx;
	width: 6rpx;
	height: 64rpx;
	border-radius: 4rpx;
	background: rgba(255, 255, 255, 0.35);
}
.tcv-sun {
	position: absolute;
	left: 50%;
	top: 48rpx;
	width: 72rpx;
	height: 72rpx;
	margin-left: -36rpx;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.35);
	box-shadow: 0 0 0 12rpx rgba(255, 255, 255, 0.12);
}
.tcv-wave {
	position: absolute;
	left: 0;
	right: 0;
	bottom: 18%;
	height: 80rpx;
	background: radial-gradient(ellipse at 30% 100%, rgba(255, 255, 255, 0.2), transparent 60%),
		radial-gradient(ellipse at 70% 110%, rgba(255, 255, 255, 0.15), transparent 55%);
}
.tcv-aurora {
	position: absolute;
	border-radius: 50%;
	filter: blur(2px);
}
.tcv-aurora--1 {
	width: 240rpx;
	height: 120rpx;
	left: 10%;
	top: 28%;
	background: rgba(196, 181, 253, 0.45);
}
.tcv-aurora--2 {
	width: 280rpx;
	height: 140rpx;
	right: 5%;
	top: 38%;
	background: rgba(167, 139, 250, 0.3);
}
.tcv-frame {
	position: absolute;
	left: 20rpx;
	top: 20rpx;
	right: 20rpx;
	bottom: 20rpx;
	border: 4rpx solid rgba(252, 211, 77, 0.55);
	box-shadow: inset 0 0 0 4rpx rgba(252, 211, 77, 0.2);
}
.tcv-glass {
	position: absolute;
	left: 8%;
	top: 30%;
	width: 84%;
	height: 42%;
	background: rgba(255, 255, 255, 0.42);
	border: 2rpx solid rgba(255, 255, 255, 0.55);
	border-radius: 12rpx;
}
.tcv-notepad {
	position: absolute;
	left: 10%;
	top: 26%;
	width: 80%;
	height: 48%;
	background: rgba(255, 255, 255, 0.92);
	border-radius: 8rpx;
	box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.08);
}
.tcv-notepad::after {
	content: '';
	position: absolute;
	left: 8%;
	right: 8%;
	top: 18%;
	height: 70%;
	background: repeating-linear-gradient(
		transparent,
		transparent 34rpx,
		rgba(254, 205, 211, 0.9) 34rpx,
		rgba(254, 205, 211, 0.9) 36rpx
	);
}
.tcv-star {
	position: absolute;
	width: 6rpx;
	height: 6rpx;
	border-radius: 50%;
	background: #fff;
}
.tcv-paper-lines {
	position: absolute;
	left: 12%;
	right: 12%;
	top: 15%;
	bottom: 10%;
	background: repeating-linear-gradient(
		transparent,
		transparent 26rpx,
		rgba(120, 53, 15, 0.08) 26rpx,
		rgba(120, 53, 15, 0.08) 28rpx
	);
}
.tcv-tape {
	position: absolute;
	right: 10%;
	top: 6%;
	width: 80rpx;
	height: 28rpx;
	background: rgba(251, 191, 36, 0.45);
	transform: rotate(8deg);
}
.tcv-tri {
	position: absolute;
	right: 0;
	top: 0;
	width: 0;
	height: 0;
	border-style: solid;
	border-width: 0 120rpx 120rpx 0;
	border-color: transparent rgba(255, 255, 255, 0.2) transparent transparent;
}
.tcv-geo-bar {
	position: absolute;
	left: 12%;
	top: 22%;
	right: 12%;
	height: 4rpx;
	background: rgba(255, 255, 255, 0.35);
}
.tcv-badge-pill {
	position: relative;
	z-index: 2;
	margin: 0 32rpx;
	display: inline-block;
	padding: 8rpx 22rpx;
	border-radius: 999rpx;
	background: rgba(255, 255, 255, 0.22);
}
.tcv-badge-pill.tcv-align-center {
	display: block;
	width: fit-content;
	margin-left: auto;
	margin-right: auto;
}
.tcv-badge-text {
	font-size: 22rpx;
	font-weight: 600;
}
.tcv-title-wrap {
	position: relative;
	z-index: 2;
	margin: 36rpx 32rpx 0;
	display: flex;
	flex-direction: column;
	gap: 6rpx;
}
.tcv-title-wrap.tcv-align-center {
	align-items: center;
	text-align: center;
}
.tcv-title-wrap--note {
	margin-top: 24rpx;
	padding: 0 8%;
}
.tcv-title-wrap--glass {
	margin-top: 48rpx;
}
.tcv-title-line {
	display: block;
	width: 100%;
	font-weight: 700;
	line-height: 1.35;
	letter-spacing: 0.5rpx;
	white-space: normal;
	word-break: break-all;
	overflow: visible;
}
.tcv-align-center .tcv-title-line {
	text-align: center;
}
.tcv-grid-label {
	display: block;
	margin-top: 24rpx;
	font-size: 24rpx;
	color: #5f7166;
}
.tcv-scroll {
	white-space: nowrap;
	margin-top: 16rpx;
}
.tcv-thumb {
	display: inline-block;
	width: 148rpx;
	margin-right: 14rpx;
	vertical-align: top;
}
.tcv-thumb.on .tcv-thumb-inner {
	box-shadow: 0 0 0 4rpx #18af38;
}
.tcv-thumb-inner {
	height: 128rpx;
	border-radius: 14rpx;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 10rpx 8rpx;
	overflow: hidden;
}
.tcv-thumb-text {
	font-size: 22rpx;
	font-weight: 700;
	text-align: center;
	line-height: 1.25;
	max-width: 100%;
}
.tcv-thumb-text2 {
	font-size: 18rpx;
	font-weight: 600;
	margin-top: 4rpx;
	opacity: 0.9;
	text-align: center;
	line-height: 1.2;
}
.tcv-thumb-name {
	display: block;
	margin-top: 8rpx;
	text-align: center;
	font-size: 20rpx;
	color: #7a8a80;
}
.tcv-thumb.on .tcv-thumb-name {
	color: #16864b;
	font-weight: 600;
}
</style>
