<template>
	<view class="u-p-30">
		<view class="" v-for="(item,index) in list" :key="index">
			<view class="u-font-bold u-font-32 u-type-primary u-m-b-30">
				{{item.name}}
			</view>
			<view class="u-p-20 u-m-b-30 u-radius-10 u-type-white-bg" v-for="(item1,index1) in item.sList" :key="index1">
				<view class="">
					<view class="u-font-32 u-font-bold">
						{{item1.sName}}
					</view>
					<view class="u-m-t-10" v-if="item1.title">
						{{item1.title}}
					</view>
					<view class="u-flex u-row-right u-m-t-20" v-if="setting.app_test != 1">
						<view class="">
							<u-button type="warning" plain size="mini" @click="navigator()">查看详情</u-button>
						</view>
						<view class="u-m-l-30">
							<u-button type="primary" size="mini" hover-class="none" @click="$u.route('/pages/sxjk/startEval')">开始测评</u-button>
						</view>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				list: [{
					name: '儿童诊断量表',
					sList: [{
						sName: '孤独症行为评定量表',
						title: '文字描述文字描述文字描述文字描述文字描述'
					}, {
						sName: '孤独症行为评定量表',
						title: '文字描述文字描述文字描述文字描述文字描述'
					}]
				}, {
					name: '孕妇诊断量表',
					sList: [{
						sName: '孤独症行为评定量表',
						title: '文字描述文字描述文字描述文字描述文字描述'
					}, {
						sName: '孤独症行为评定量表',
						title: '文字描述文字描述文字描述文字描述文字描述'
					}]
				}, {
					name: '老人诊断量表',
					sList: [{
						sName: '孤独症行为评定量表',
						title: '文字描述文字描述文字描述文字描述文字描述'
					}, {
						sName: '孤独症行为评定量表',
						title: '文字描述文字描述文字描述文字描述文字描述'
					}]
				}]
			};
		}
	}
</script>

<style lang="scss">

</style>