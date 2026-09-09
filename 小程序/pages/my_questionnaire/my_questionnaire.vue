<template>
	<view>
		<view class="u-p-30">
			<view class="u-type-white-bg u-p-30 u-radius-10 u-m-b-30" v-for="(item,index) in exam_list" :key="index"
				@click="navigateTo('/pages/answer/detail?id=' + item.id)">
				<view class="u-font-32 u-font-bold">
					{{item.name}}
				</view>
				<view class="u-flex u-row-between u-type-info u-m-t-10">
					<view class="u-font-26">
						提交时间：{{item.create_time|date('yy-mm-dd hh:MM')}}
					</view>
					<!-- <view class="">
                        <u-button type="success" size="mini" hover-class="none" @click="add_comment()">查看详情
                        </u-button>
                    </view> -->
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				exam_list: [],
				page: 1,
				total: 0
			};
		},
		onLoad() {
			this.my_exam_list()
		},
		methods: {
			my_exam_list() {
				this.$u.api.my_exam_list({
					keyword: this.keyword,
					page: this.page,
				}).then(res => {
					this.exam_list.push(...res.data.data)
					this.total = res.data.total
				}).catch(err => {
					this.$u.toast(err.msg)
				})
			},
			navigateTo(url) {
				this.$u.route(url)
			}
		},
		onReachBottom() {
			if (this.exam_list.length >= this.total) return
			this.page++
			this.my_exam_list()
		}
	}
</script>

<style lang="scss">

</style>