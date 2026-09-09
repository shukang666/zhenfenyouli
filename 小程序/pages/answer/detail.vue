<template>
	<view class="">
		<view class="u-p-30" v-for="(item,index) in list" :key="index">
			<view class="u-m-b-30">
				<text class="u-font-bold u-font-36 u-type-primary">{{index+1}}.{{item.question}}</text>
			</view>

			<!-- 单选 -->
			<block v-if="item.type==1">
				<block v-for="(item1,index1) in item.option" :key="index1">
					<view class="u-type-info-light-bg u-p-15">
						<u-radio-group disabled disabledColor="#303133" v-model="item.select_option" size="30" wrap
							active-color="#3370fb">
							<u-radio :name="item1.id" style="width: 100%;">
								<view class="u-font-bold u-font-36">
									{{item1.option}}
								</view>
							</u-radio>
						</u-radio-group>
					</view>

					<!-- 是否选择医院 -->
					<block v-if="item1.isHospital">
						<view class="u-m-tb-30">
							<text class="u-font-bold u-font-36"
								style="color: #3370fb;">{{index+1}}.1.请选择您常去的基础医疗机构</text>
						</view>
						<view class="u-type-info-light-bg u-p-15">
							<u-input type="select" v-model="hospital" :select-open="hospitalShow" placeholder="请选择"
								disabled />
						</view>
					</block>
				</block>
			</block>

			<!-- 多选 -->
			<block v-if="item.type==2">
				<view class="u-type-info-light-bg u-p-15">
					<u-checkbox-group disabled wrap active-color="#3370fb">
						<u-checkbox v-model="item1.checked" :name="item1.id" size="30"
							v-for="(item1,index1) in item.option" :key="index1">
							<view class="u-font-bold u-font-36 u-m-b-8">
								{{item1.option}}
							</view>
						</u-checkbox>
					</u-checkbox-group>
					<!-- 是否补充内容 -->
					<block v-if="$u.test.contains(item.answer, item.option[item.option.length-1].id) && item.is_other">
						<view class="u-p-tb-30">
							<text class="u-font-bold u-font-36" style="color: #3370fb;">补充内容</text>
						</view>
						<view class="u-type-white-bg u-p-15">
							<u-input type="text" v-model="item.other" placeholder="请输入" />
						</view>
					</block>
				</view>
			</block>

			<!-- 填空 -->
			<block v-if="item.type==3">
				<view class="u-type-info-light-bg u-p-15">
					<view class="">
						<u-input v-model="item.answer" type="textarea" placeholder="请输入答案" clearable disabled />
					</view>
					<view class="u-m-t-20" v-if="item.is_thumb == 1 && item.image_answer">
						<!-- <u-upload :action="uploadUrl" maxCount="1" width="190" height="190"
                            @on-success="success($event,item)" @on-remove="remove($event,item)">
                        </u-upload> -->
						<u-image width="190" height="190" :src="item.image_answer"></u-image>
					</view>
				</view>
			</block>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				list: [],
			}
		},

		onLoad(options) {
			this.my_exam_list(options.id)
		},
		methods: {
			my_exam_list(id) {
				this.$u.api.my_exam_list({
					id
				}).then(res => {
					this.list = res.data
				}).catch(err => {
					this.$u.toast(err.msg)
				})
			},
		}
	}
</script>

<style lang="scss">
	page {
		background: #fff;

		.warp {
			display: flex;
			align-items: center;
			justify-content: center;
			height: 100%;

		}

		.u-icon__icon {
			// color: #303133 !important;
		}
	}
</style>