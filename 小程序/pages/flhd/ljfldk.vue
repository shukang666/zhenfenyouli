<template>
	<view class="dk-hub" v-if="!setting.app_test">
		<view class="dk-hero">
			<text class="dk-hero-kicker">拍照前先选好场景和种类</text>
			<text class="dk-hero-title">垃圾分类打卡</text>
			<text class="dk-hero-desc">家庭、亲子、办公或沿街投放都可以。选完种类会自动带上报类型去拍照。</text>
			<view class="dk-steps">
				<view class="dk-step" :class="{ 'dk-step--on': step === 1 }">
					<text class="dk-step-n">1</text>
					<text class="dk-step-t">选场景</text>
				</view>
				<view class="dk-step-line" :class="{ 'dk-step-line--on': step === 2 }"></view>
				<view class="dk-step" :class="{ 'dk-step--on': step === 2 }">
					<text class="dk-step-n">2</text>
					<text class="dk-step-t">选种类</text>
				</view>
				<view class="dk-step-line"></view>
				<view class="dk-step">
					<text class="dk-step-n">3</text>
					<text class="dk-step-t">去拍照</text>
				</view>
			</view>
		</view>

		<view class="dk-stats" v-if="isLogin">
			<view class="dk-stat">
				<text class="dk-stat-num">{{ userInfo.point || 0 }}</text>
				<text class="dk-stat-lab">我的积分</text>
			</view>
			<view class="dk-stat-line"></view>
			<view class="dk-stat">
				<text class="dk-stat-num">{{ streak }}</text>
				<text class="dk-stat-lab">连续打卡天数</text>
			</view>
			<view class="dk-stat-line"></view>
			<view class="dk-stat">
				<text class="dk-stat-num">{{ remainText }}</text>
				<text class="dk-stat-lab">距环保达人</text>
			</view>
		</view>

		<view class="dk-gate" v-if="isLogin && gateTip" @click="goGate">
			<text class="dk-gate-t">{{ gateTip }}</text>
			<text class="dk-gate-a">去完成 ›</text>
		</view>

		<view class="dk-block" v-if="step === 1">
			<text class="dk-block-t">你在哪投放</text>
			<text class="dk-block-hint">先选一个场景，再选对应的垃圾种类</text>
			<view class="dk-scene" hover-class="dk-card--press" :hover-stay-time="80"
				v-for="item in scenes" :key="item.key" :class="'dk-scene--' + item.key" @click="pickScene(item)">
				<text class="dk-scene-name">{{ item.name }}</text>
				<text class="dk-scene-desc">{{ item.desc }}</text>
				<text class="dk-scene-go">下一步 ›</text>
			</view>
		</view>

		<view class="dk-block" v-else>
			<view class="dk-type-head">
				<view class="dk-back" hover-class="dk-back--press" :hover-stay-time="80" @click="backScene">
					<text>‹ 改场景</text>
				</view>
				<text class="dk-block-t dk-type-title">{{ selectedScene.name }}投放 · 选种类</text>
			</view>
			<text class="dk-block-hint">点一种就会带上报类型去拍照页</text>
			<view class="dk-types">
				<view class="dk-type" hover-class="dk-card--press" :hover-stay-time="80"
					v-for="item in cate_list" :key="item.id"
					:class="'dk-type--' + cateTone(item.name)"
					@click="goClock(item)">
					<text class="dk-type-name">{{ item.name }}</text>
					<text class="dk-type-hint">{{ cateHint(item.name) }}</text>
					<text class="dk-type-go">去拍照 ›</text>
				</view>
			</view>
		</view>

		<view class="dk-block">
			<text class="dk-block-t">过往记录</text>
			<view class="dk-hist-row">
				<view class="dk-hist-btn dk-hist-btn--mine" hover-class="dk-hist-btn--press" :hover-stay-time="80" @click="goMy">
					<text class="dk-hist-name">我的打卡</text>
					<text class="dk-hist-sub">只看自己交过的</text>
					<text class="dk-hist-go">查看 ›</text>
				</view>
				<view class="dk-hist-btn dk-hist-btn--pick" hover-class="dk-hist-btn--press" :hover-stay-time="80" @click="goChoice">
					<text class="dk-hist-name">精选打卡</text>
					<text class="dk-hist-sub">看看别人怎么分</text>
					<text class="dk-hist-go">查看 ›</text>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				step: 1,
				selectedScene: {},
				cate_list: [],
				scenes: [
					{ key: 'home', name: '家庭', desc: '厨房、客厅、卫生间里的日常投放' },
					{ key: 'qinzi', name: '亲子', desc: '家长带孩子一起学分类、动手投放' },
					{ key: 'office', name: '办公', desc: '工位、茶水间、会议室里的投放' },
					{ key: 'street', name: '沿街', desc: '路边、商铺门前的分类投放点' }
				]
			}
		},
		computed: {
			streak() {
				const u = this.userInfo || {}
				return u.clock_days || u.continuous_days || u.clock_count || 0
			},
			remainText() {
				const n = (this.userInfo && this.userInfo.remaining_count) || 0
				return n > 0 ? n + '天' : '已达成'
			},
			gateTip() {
				const u = this.userInfo || {}
				if (!u.is_learn) return '打卡前请先学完分类指导'
				if (!u.is_answer) return '学完了，还差一场分类考试'
				return ''
			}
		},
		onShow() {
			this.refreshUser()
			this.loadCate()
		},
		methods: {
			refreshUser() {
				if (!this.isLogin) return
				this.$u.api.getUserInfo().then(res => {
					this.$u.vuex('userInfo', res.data || {})
				}).catch(() => {})
			},
			loadCate() {
				this.$u.api.get_select_list({ type: 2 }).then(res => {
					this.cate_list = res.data || []
				}).catch(() => {})
			},
			cateTone(name) {
				const n = name || ''
				if (n.indexOf('可回收') >= 0) return 'recycle'
				if (n.indexOf('厨余') >= 0) return 'food'
				if (n.indexOf('有害') >= 0) return 'harm'
				return 'other'
			},
			cateHint(name) {
				const tone = this.cateTone(name)
				if (tone === 'recycle') return '纸张、塑料瓶、金属罐、织物'
				if (tone === 'food') return '剩菜剩饭、果皮菜叶'
				if (tone === 'harm') return '电池、灯管、过期药品'
				return '受污染纸、破碎陶瓷等不可回收'
			},
			pickScene(item) {
				this.selectedScene = item
				this.step = 2
			},
			backScene() {
				this.step = 1
			},
			goClock(item) {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				if (!this.userInfo.community_id) return this.$u.route('/pages/member/info')
				if (!item || !item.id) return this.$u.toast('请先选择垃圾种类')
				const scene = this.selectedScene.name || ''
				this.$u.route('/pages/flhd/add?cate_id=' + item.id + '&scene=' + encodeURIComponent(scene))
			},
			goGate() {
				if (!this.userInfo.is_learn) return this.$u.route('/pages/flhd/list')
				if (!this.userInfo.is_answer) return this.$u.route('/pages/answer/examine?id=1')
			},
			goMy() {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				this.$u.route('/pages/flhd/record')
			},
			goChoice() {
				this.$u.route('/pages/flhd/choice')
			}
		}
	}
</script>

<style lang="scss" scoped>
.dk-hub {
	min-height: 100vh;
	padding: 24rpx 28rpx 80rpx;
	background: #e8f0ea;
}
.dk-hero {
	padding: 36rpx 32rpx 32rpx;
	border-radius: 28rpx;
	background: linear-gradient(145deg, #6dca7c 0%, #18AF38 78%);
	box-shadow: 0 16rpx 36rpx rgba(24, 175, 56, 0.26);
}
.dk-hero-kicker {
	display: block;
	font-size: 24rpx;
	color: rgba(255, 255, 255, 0.86);
}
.dk-hero-title {
	display: block;
	margin-top: 10rpx;
	font-size: 44rpx;
	font-weight: 800;
	color: #fff;
}
.dk-hero-desc {
	display: block;
	margin-top: 12rpx;
	font-size: 26rpx;
	line-height: 1.55;
	color: rgba(255, 255, 255, 0.92);
}
.dk-steps {
	margin-top: 28rpx;
	display: flex;
	align-items: center;
}
.dk-step {
	display: flex;
	align-items: center;
	opacity: 0.62;
}
.dk-step--on {
	opacity: 1;
}
.dk-step-n {
	width: 36rpx;
	height: 36rpx;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.28);
	color: #fff;
	font-size: 22rpx;
	font-weight: 800;
	text-align: center;
	line-height: 36rpx;
}
.dk-step--on .dk-step-n {
	background: #fff;
	color: #148a2e;
}
.dk-step-t {
	margin-left: 8rpx;
	font-size: 22rpx;
	font-weight: 700;
	color: #fff;
}
.dk-step-line {
	flex: 1;
	height: 4rpx;
	margin: 0 12rpx;
	border-radius: 4rpx;
	background: rgba(255, 255, 255, 0.28);
}
.dk-step-line--on {
	background: #fff;
}
.dk-stats {
	margin-top: 24rpx;
	padding: 28rpx 12rpx;
	border-radius: 20rpx;
	background: #fff;
	display: flex;
	align-items: center;
}
.dk-stat {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
}
.dk-stat-num {
	font-size: 36rpx;
	font-weight: 800;
	color: #148a2e;
}
.dk-stat-lab {
	margin-top: 6rpx;
	font-size: 22rpx;
	color: #8a958c;
}
.dk-stat-line {
	width: 1rpx;
	height: 56rpx;
	background: #e8eee9;
}
.dk-gate {
	margin-top: 16rpx;
	padding: 22rpx 24rpx;
	border-radius: 16rpx;
	background: #fff7e6;
	display: flex;
	align-items: center;
	justify-content: space-between;
}
.dk-gate-t {
	flex: 1;
	font-size: 26rpx;
	color: #8a5a00;
}
.dk-gate-a {
	margin-left: 16rpx;
	font-size: 24rpx;
	font-weight: 700;
	color: #c47b00;
}
.dk-block {
	margin-top: 28rpx;
}
.dk-block-t {
	display: block;
	margin-bottom: 16rpx;
	font-size: 30rpx;
	font-weight: 700;
	color: #243028;
}
.dk-block-hint {
	display: block;
	margin: -8rpx 0 16rpx;
	font-size: 22rpx;
	color: #8a958c;
}
.dk-card--press {
	transform: scale(0.97);
	opacity: 0.92;
}
.dk-scene {
	position: relative;
	overflow: hidden;
	margin-bottom: 18rpx;
	padding: 36rpx 28rpx 32rpx;
	border-radius: 24rpx;
	transition: transform 0.1s ease, opacity 0.1s ease;
}
.dk-scene::before {
	content: '';
	position: absolute;
	width: 180rpx;
	height: 180rpx;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.12);
	top: -70rpx;
	right: -40rpx;
	pointer-events: none;
}
.dk-scene::after {
	content: '';
	position: absolute;
	left: 0;
	bottom: 0;
	width: 100%;
	height: 8rpx;
	background: rgba(255, 255, 255, 0.22);
	pointer-events: none;
}
.dk-scene--home {
	background: linear-gradient(141deg, #D4A017 0%, #A36B08 100%);
	box-shadow: 0 10rpx 24rpx rgba(163, 107, 8, 0.38);
}
.dk-scene--qinzi {
	background: linear-gradient(141deg, #F0938A 0%, #D94F6A 100%);
	box-shadow: 0 10rpx 24rpx rgba(217, 79, 106, 0.38);
}
.dk-scene--office {
	background: linear-gradient(141deg, #2B8BB8 0%, #156A90 100%);
	box-shadow: 0 10rpx 24rpx rgba(21, 106, 144, 0.38);
}
.dk-scene--street {
	background: linear-gradient(141deg, #18AF38 0%, #0C7A24 100%);
	box-shadow: 0 10rpx 24rpx rgba(12, 122, 36, 0.38);
}
.dk-scene-name {
	position: relative;
	z-index: 1;
	display: block;
	font-size: 40rpx;
	font-weight: 800;
	color: #fff;
}
.dk-scene-desc {
	position: relative;
	z-index: 1;
	display: block;
	margin-top: 10rpx;
	font-size: 24rpx;
	color: rgba(255, 255, 255, 0.92);
}
.dk-scene-go {
	position: relative;
	z-index: 1;
	display: block;
	margin-top: 20rpx;
	font-size: 24rpx;
	font-weight: 700;
	color: #fff;
}
.dk-type-head {
	display: flex;
	align-items: center;
	margin-bottom: 8rpx;
}
.dk-back {
	margin-right: 16rpx;
	padding: 8rpx 16rpx;
	border-radius: 999rpx;
	background: #fff;
	font-size: 24rpx;
	font-weight: 700;
	color: #148a2e;
}
.dk-back--press {
	opacity: 0.8;
}
.dk-type-title {
	margin-bottom: 0;
	flex: 1;
}
.dk-types {
	display: flex;
	flex-wrap: wrap;
	justify-content: space-between;
}
.dk-type {
	position: relative;
	overflow: hidden;
	width: 48%;
	margin-bottom: 20rpx;
	padding: 28rpx 22rpx 24rpx;
	border-radius: 22rpx;
	min-height: 220rpx;
	transition: transform 0.1s ease, opacity 0.1s ease;
}
.dk-type::before {
	content: '';
	position: absolute;
	width: 120rpx;
	height: 120rpx;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.12);
	top: -40rpx;
	right: -28rpx;
	pointer-events: none;
}
.dk-type--recycle {
	background: linear-gradient(141deg, #2B8BB8 0%, #156A90 100%);
	box-shadow: 0 10rpx 22rpx rgba(21, 106, 144, 0.38);
}
.dk-type--food {
	background: linear-gradient(141deg, #C48A3A 0%, #8B5A14 100%);
	box-shadow: 0 10rpx 22rpx rgba(139, 90, 20, 0.38);
}
.dk-type--harm {
	background: linear-gradient(141deg, #E75E5E 0%, #B32626 100%);
	box-shadow: 0 10rpx 22rpx rgba(179, 38, 38, 0.38);
}
.dk-type--other {
	background: linear-gradient(141deg, #8A959C 0%, #5C656C 100%);
	box-shadow: 0 10rpx 22rpx rgba(92, 101, 108, 0.38);
}
.dk-type-name {
	position: relative;
	z-index: 1;
	display: block;
	font-size: 30rpx;
	font-weight: 800;
	color: #fff;
}
.dk-type-hint {
	position: relative;
	z-index: 1;
	display: block;
	margin-top: 10rpx;
	font-size: 22rpx;
	line-height: 1.45;
	color: rgba(255, 255, 255, 0.9);
}
.dk-type-go {
	position: relative;
	z-index: 1;
	display: block;
	margin-top: 22rpx;
	font-size: 22rpx;
	font-weight: 700;
	color: #fff;
}
.dk-hist-row {
	display: flex;
	margin: 0 -10rpx;
}
.dk-hist-btn {
	position: relative;
	overflow: hidden;
	flex: 1;
	margin: 0 10rpx;
	padding: 32rpx 24rpx 28rpx;
	border-radius: 24rpx;
	transition: transform 0.1s ease, opacity 0.1s ease;
}
.dk-hist-btn::before {
	content: '';
	position: absolute;
	width: 140rpx;
	height: 140rpx;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.22);
	top: -50rpx;
	right: -36rpx;
	pointer-events: none;
}
.dk-hist-btn::after {
	content: '';
	position: absolute;
	left: 0;
	bottom: 0;
	width: 100%;
	height: 8rpx;
	background: rgba(255, 255, 255, 0.32);
	pointer-events: none;
}
.dk-hist-btn--mine {
	background: linear-gradient(141deg, #A4DB88 0%, #18AF38 100%);
	box-shadow: 0 10rpx 24rpx rgba(24, 175, 56, 0.28);
}
.dk-hist-btn--pick {
	background: linear-gradient(141deg, #7dd3c7 0%, #2aa8a0 100%);
	box-shadow: 0 10rpx 24rpx rgba(42, 168, 160, 0.26);
}
.dk-hist-btn--press {
	transform: scale(0.96);
	opacity: 0.92;
}
.dk-hist-name {
	position: relative;
	z-index: 1;
	display: block;
	font-size: 32rpx;
	font-weight: 800;
	color: #fff;
}
.dk-hist-sub {
	position: relative;
	z-index: 1;
	display: block;
	margin-top: 10rpx;
	font-size: 22rpx;
	color: rgba(255, 255, 255, 0.88);
}
.dk-hist-go {
	position: relative;
	z-index: 1;
	display: block;
	margin-top: 20rpx;
	font-size: 24rpx;
	font-weight: 700;
	color: #fff;
}
</style>
