<template>
	<view class="u-p-30">
		<view class="u-type-white-bg u-radius-10 u-m-b-20 u-p-20" v-for="(item,index) in health_list" :key="index"
		        @click="wj_btn(item)">
		        <u-image width="100%" height="166" :src="item.thumb" border-radius="10"></u-image>
		        <view class="u-m-t-20">
		            <view class="">
		                <view class="u-font-bold u-font-32">
		                    {{item.name}}
		                </view>
		                <view class="u-type-info u-m-t-10" v-html="$u.func.formatTextArea(item.desc)"></view>
		            </view>
		        </view>
		        <view class="u-text-right u-m-t-20">
		            <u-button type="primary" size="mini" hover-class="none"
		                :customStyle="{background: 'linear-gradient( 145deg, #F0DF73 0%, #FFA320 100%)'}"
		                @click="wj_btn(item)">立即参与</u-button>
		        </view>
		</view>
		<u-gap height="30"></u-gap>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				health_list: [],
			}
		},
		onLoad() {
			this.get_health_home_list(2)
		},
		methods: {
			// 问卷调研按钮
			wj_btn(item) {
			    // if (item.is_exam == 1) return this.$u.toast('您已经参加过问卷')
			    this.$u.route('/pages/healthcare/participate?id=' + item.id)
			},
			//
			get_health_home_list(type) {
			    this.$u.api.get_health_home_list({
			        type: type,
			    }).then(res => {
			        this.health_list = res.data
			    }).catch(err => {
			        this.$u.toast(err.msg)
			    })
			},
		}
	}
</script>

<style>

</style>