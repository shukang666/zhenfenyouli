<template>
	<view class="">
		<u-swiper :list="health.image" height="400" mode="none" border-radius="0"></u-swiper>
		<view class="u-p-30" v-for="(item,index) in paper_list" :key="index" v-if="item.isShow">
			<view class="u-m-b-30">
				<!-- <u-button type="primary" size="mini">{{item.type_name}}</u-button> -->
				<text class="u-font-bold u-font-36" style="color: #3370fb;">{{index+1}}.{{item.question}}</text>
			</view>

			<!-- 单选 -->
			<block v-if="item.type==1">
				<block v-for="(item1,index1) in item.option" :key="index1">
					<view class="u-type-info-light-bg u-p-15">
						<u-radio-group v-model="item.answer" size="30" wrap active-color="#3370fb"
							@change="radioGroupChange($event,item1,index)">
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
								@click="hospitalShow = true" />
						</view>
					</block>
				</block>
			</block>

			<!-- 多选 -->
			<block v-if="item.type==2">
				<view class="u-type-info-light-bg u-p-15">
					<u-checkbox-group wrap @change="checkboxChange($event,item,index)" active-color="#3370fb">
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
						<u-input v-model="item.answer" type="textarea" placeholder="请输入答案" clearable
							@input="handleInput($event, index)" />
					</view>
					<view class="u-m-t-20" v-if="item.is_thumb == 1">  
                        <u-upload :action="uploadUrl" maxCount="1" width="190" height="190"
                            @on-success="success($event,item)" @on-remove="remove($event,item)">
                        </u-upload>
                    </view>
				</view>
			</block>
		</view>
		<block v-if="paper_list.length && paper_list[paper_list.length-1].answer">
			<!--<view class="u-m-lr-30">
                <text class="u-font-bold u-font-36" style="color: #3370fb;">{{paper_list.length+1}}.您的姓名</text>
                <view class="u-type-info-light-bg u-p-15 u-m-t-30">
                    <u-input v-model="name" placeholder="请输入" clearable />
                </view>
            </view> -->
			<!-- <view class="u-m-30">
                <text class="u-font-bold u-font-36" style="color: #3370fb;">{{paper_list.length+2}}.您的联系方式</text>
                <view class="u-type-info-light-bg u-p-15 u-m-t-30">
                    <u-input v-model="mobile" placeholder="请输入" clearable disabled />
                </view>
            </view> -->
		</block>

		<view class="u-m-t-20 u-p-lr-30" v-if="health.is_secret==1">
			<u-checkbox v-model="isAgree" shape="circle" size="28" @change="checkboxAgree">
				<text class="u-font-24">我已阅读并同意</text>
				<text class="u-font-24 u-type-primary" @click.stop="navigateTo('用户服务协议', 'service')">《用户服务协议》</text>
				<text class="u-font-24 u-type-primary"
					@click.stop="$u.route('/pages/answer/secret?id='+health.id)">《隐私政策》</text>
			</u-checkbox>
		</view>

		<view class="safe-area-inset-bottom">
			<u-gap height="130"></u-gap>
		</view>
		<view class="submit-btn u-type-white-bg  u-p-30">
			<u-button type="primary" hover-class="none" @click="submit">马上提交</u-button>
		</view>

		<!-- 医院选择 -->
		<u-select mode="mutil-column-auto" v-model="hospitalShow" value-name="id" label-name="name" child-name="child"
			:list="hospitalList" @confirm="handleConfirm"></u-select>
		<!-- 弹窗 -->
		<u-mask :show="mask_show" :zoom="false">
			<view class="warp">
				<view class="">
					<view class="u-flex u-row-center">
						<u-image width="560" height="87"
							src="https://admin.circular-economy.cn/upload/xcxkf052/admin/20250605/3b586d2ee96e1dca6941c8430d1f9360.png"></u-image>
					</view>
					<view class="u-w-560 u-radius-10 u-type-white-bg u-text-center">
						<u-gap height="80"></u-gap>
						<view class="u-flex u-row-center">
							<u-image width="200" height="200"
								src="https://admin.circular-economy.cn/upload/xcxkf052/admin/20250514/d4c1251c3c10fc8f0c4d912bf81cb167.png"></u-image>
						</view>
						<view class="u-font-32 u-font-bold">
							{{msg}}
						</view>
						<view class="u-m-t-20 u-type-warning">
							进行预约获得更多积分
						</view>
						<view class="u-m-t-60 u-flex u-p-15">
							<view class="u-flex-1">
								<u-button type="success" hover-class="none" :customStyle="{background: '#3974f2'}"
									shape="circle" @click="switchTab">兑换礼物</u-button>
							</view>
							<view class="u-m-l-30 u-flex-1">
								<u-button type="error" hover-class="none" shape="circle" @click="add">退出</u-button>
							</view>
						</view>
						<u-gap height="30"></u-gap>
					</view>
				</view>
			</view>
		</u-mask>
	</view>
</template>

<script>
	import {
		uploadUrl
	} from '@/common/settings'
	export default {
		data() {
			return {
				swiper_list: [],
				uploadUrl,
				paper_list: [],
				health: {},
				rowIndex: null,
				hospitalShow: false,
				hospitalList: [], //医院列表
				hospital: '', //选中的医院
				isAgree: false,
				name: '',
				mobile: '',
				mask_show: false,
				msg: ''
			}
		},

		onLoad(option) {
			this.find_exam_topic_list(option.id)
			this.find_question_list(option.id)
			this.mobile = this.userInfo.mobile || ''
		},
		methods: {
			// 预约
			add() {
				// uni.redirectTo({
				//     url: '/pages/healthcare/add?id=' + this.health.id
				// })
				uni.navigateBack({
					delta: 3
				})
			},
			// 
			switchTab() {
				uni.switchTab({
					url: '/pages/mall/mall'
				})
			},
			// 答题详情
			find_exam_topic_list(id) {
				this.$u.api.find_exam_topic_list({
					id
				}).then(res => {
					this.health = res.data
				})
			},
			// 添加图片
			success(e, item) {
				this.$set(item, 'image_answer', e.data.filepath)
			},
			// 移除图片
			remove(e, item) {
				item.image_answer = ''
			},
			// 提交
			submit() {
				if (!this.isAgree && this.health.is_secret == 1) return this.$u.toast('请勾选并同意协议')
				var answer = []
				for (const item of this.paper_list) {
					if (item.type == 2) {
						if (item.isShow && !item.answer.length) return this.$u.toast('请先选择答案')
					} else {
						if (item.isShow && !item.answer) return this.$u.toast('请先选择答案')
					}
					for (const obj of item.option) {
						if (obj.isHospital && !this.hospital) return this.$u.toast('请先选择医疗机构')
					}
					let data = {};
					if (item.hospital) data.hospital = item.hospital;
					if (item.other) data.other = item.other;
					answer.push({
						question_id: item.id,
						select_option: item.answer,
						image_answer: item.image_answer,
						...data
					})
				}
				// if (this.paper_list[this.paper_list.length - 1].answer) {
				//     if (!this.name) return this.$u.toast('请先填写姓名')
				//     if (!this.mobile) return this.$u.toast('请先填写手机号')
				// }
				this.$u.api.add_exam_record({
					list: answer,
					exam_id: this.health.id,
					name: this.name,
					mobile: this.mobile
				}).then(res => {
					this.msg = res.msg
					this.mask_show = true
				}).catch(err => {
					this.$u.toast(err.msg)
				})
			},
			// 单选 option_type 0默认下一题 1指定题 2选择医院 3直接提交
			radioGroupChange(e, item1, index) {
				//隐藏当前选项是否医院选择
				let option = this.paper_list[index].option;
				option.map(v => {
					return v.isHospital = false
				});

				//隐藏当前选项之后选项的显示状态
				this.paper_list.forEach((obj, val) => {
					if (val > index) obj.isShow = false
				})

				if (item1.option_type == 2 && index == this.paper_list.length - 1) {
					this.$u.api.get_select_list().then(res => {
						this.hospitalList = res.data;
						this.$set(item1, 'isHospital', true);
					})
					this.rowIndex = index;
				} else {
					if (index == this.paper_list.length - 1) return
					//获取下一级选项的键值
					let next_id = this.paper_list[index + 1].id
					let val = this.paper_list.findIndex(item => item.id == next_id);

					if (item1.option_type == 3) {
						this.paper_list[val].isShow = false;
						return
					} else if (item1.option_type == 2) {
						this.$u.api.get_select_list().then(res => {
							this.hospitalList = res.data;
							this.$set(item1, 'isHospital', true);
						})
						this.rowIndex = index;
					} else if (item1.option_type == 1) {
						val = this.paper_list.findIndex(item => item.id == item1.next);
					} else {
						this.paper_list[val].isShow = false;
					}
					this.paper_list[val].isShow = true;
				}
			},
			// 多选
			checkboxChange(e, item, index) {
				item.answer = e;
				if (index == this.paper_list.length - 1) return
				let next_id = this.paper_list[index + 1].id
				let val = this.paper_list.findIndex(item => item.id == next_id);
				this.paper_list[val].isShow = true;
			},
			//填空
			handleInput(value, index) {
				if (index == this.paper_list.length - 1) return
				let next_id = this.paper_list[index + 1].id
				let val = this.paper_list.findIndex(item => item.id == next_id);
				if (value.trim()) {
					this.paper_list[val].isShow = true;
				} else {
					this.paper_list[val].isShow = false;
				}
			},
			// 答题列表
			find_question_list(id) {
				this.$u.api.find_question_list({
					exam_id: id
				}).then(res => {
					this.paper_list = res.data
					if (!this.paper_list.length) return
					this.paper_list.map(v => {
						this.$set(v, 'isShow', false)
						if (v.type == 2) {
							this.$set(v, 'answer', [])
						} else {
							this.$set(v, 'answer', '')
						}
					})
					this.paper_list[0].isShow = true;
				})
			},

			//是否有选择医疗机构
			handleConfirm(e) {
				this.hospital = e[0].label + '-' + e[1].label;
				this.paper_list[this.rowIndex].hospital = [e[0].label, e[1].label];
			},

			//同意协议
			checkboxAgree(e) {
				this.isAgree = e.value;
			},

			//跳转
			navigateTo(title, name) {
				this.$u.route('/pages/public/about', {
					title,
					name
				})
			}
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
	}
</style>