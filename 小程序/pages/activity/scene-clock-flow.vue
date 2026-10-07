<template>
	<view class="scene-redirect"></view>
</template>

<script>
import { resolveLegacySceneClockFlowRedirect } from '@/common/sceneClock/sceneClockRoutes.js'

/** 兼容旧 Banner / 外链：重定向到选场景或各场景子页 */
export default {
	onLoad(option) {
		const url = resolveLegacySceneClockFlowRedirect(option || {})
		uni.redirectTo({ url })
	},
}
</script>

<style lang="scss" scoped>
.scene-redirect {
	min-height: 100vh;
	background: #f4f7f5;
}
</style>
