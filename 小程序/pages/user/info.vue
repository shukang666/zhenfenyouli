<template>
    <view class="u-p-t-20">
		<u-cell-group>
			<u-cell-item title="头像" @click="showAvatarPop = true">
				<u-avatar :src="form.avatar"></u-avatar>
			</u-cell-item>
			<u-cell-item title="昵称" :value="form.nickname" @click="showNicknamePop = true"></u-cell-item>
			<u-cell-item title="所在小区" :value="form.district_name + '-' + form.community_name" @click="showCommunity = true"></u-cell-item>
		</u-cell-group>
		
        <!-- 修改头像弹窗 -->
        <u-popup mode="bottom" border-radius="20" closeable v-model="showAvatarPop">
            <view class="u-p-30">
                <view class="u-font-32 u-text-center">
                    编辑头像
                </view>
                <view class="  u-m-tb-50 ">
                    <button class="u-reset-button" open-type="chooseAvatar" @chooseavatar="chooseavatar">
                        <view class="u-flex-col">
                            <u-avatar :src="form.avatar"></u-avatar>
                            <text class="$u-type-info u-m-t-30">点击修改</text>
                        </view>
                    </button>
                </view>
                <view class="">
                    <u-button @click="submitBtn" type="primary" shape="circle" hover-class="none"
                        :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}">确认保存</u-button>
                </view>
            </view>
        </u-popup>
		
        <!-- 修改昵称弹窗 -->
        <u-popup mode="bottom" border-radius="20" closeable v-model="showNicknamePop">
            <view class="u-p-30">
                <view class="u-font-32 u-text-center">
                    修改昵称
                </view>
                <view class="  u-m-tb-50  u-radius-5 u-p-lr-20" style="background-color: #f7f7f7;">
                    <u-input type="nickname" v-model="form.nickname" placeholder="请输入昵称" :clearable="false" @blur="nameChange" @input="nameChange" />
                </view>
                <view class="">
                    <u-button @click="submitBtn" type="primary" shape="circle" hover-class="none"
                        :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}">确认保存</u-button>
                </view>
            </view>
        </u-popup>
		
		<!-- 选择所属社区 -->
		<u-popup mode="bottom" border-radius="20" closeable v-model="showCommunity">
		    <view class="u-p-30" :style="{ paddingBottom: keyboardHeight ? keyboardHeight + 'px' : '30rpx' }">
				<view class="u-font-32 u-text-center">
				    修改社区信息
				</view>
		        <!-- 所属社区(暂隐藏，小区搜索选中后会自动带出)
		        <view class="u-m-t-30">所属社区</view>
		        <view class="u-radius-5 u-p-lr-20 u-m-t-10" style="background-color: #f7f7f7;">
		            <u-input v-model="form.district_name" type="select" placeholder="请选择" @click="district_show = true" />
		        </view>
		        -->
				<view class="u-m-t-30">所在小区</view>
		        <view class="u-radius-5 u-p-lr-20 u-m-t-10" style="background-color: #f7f7f7;">
		            <u-input v-model="communityKeyword" type="text" placeholder="输入小区名称搜索" :clearable="true" @input="onCommunitySearch" />
		        </view>
		        <view v-if="form.community_name && !communityKeyword" class="u-m-t-10 u-p-lr-10 u-font-24" style="color: #18AF38;">
		            已选：{{form.district_name ? form.district_name + '-' : ''}}{{form.community_name}}
		        </view>
		        <view v-if="communityResults.length" class="u-m-t-10" style="max-height: 500rpx; overflow-y: auto; background-color: #fff; border: 1px solid #f0f0f0; border-radius: 10rpx;">
		            <view v-for="item in communityResults" :key="item.id"
		                @click="selectCommunity(item)"
		                class="u-p-lr-20 u-p-tb-15"
		                style="border-bottom: 1px solid #f7f7f7;">
		                <view>{{item.name}}</view>
		                <view class="u-font-24" style="color: #999;">{{item.parent_name}}</view>
		            </view>
		        </view>
		        <view class="u-m-t-30">
		            <u-button @click="submitBtn" type="primary" shape="circle" hover-class="none"
		                :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}">确认保存</u-button>
		        </view>
		    </view>
		</u-popup>

		<!-- 选择所属社区 -->
		<u-select v-model="district_show" label-name="name" value-name="id" :list="districtList" @confirm="districtConfirm"></u-select>
    </view>
</template>

<script>
    import {
        uploadUrl
    } from '@/common/settings'
    export default {
        data() {
            return {
                uploadUrl,
                form: {
                    avatar: '',
                    nickname: '',
                    district_id: 0,
                    district_name: '',
                    community_id: 0,
                    community_name: '',
                },
                filepath: '', //图片保存所需路径
                showAvatarPop: false, // 修改头像弹窗
                showNicknamePop: false, //修改昵称弹窗
                showCommunity: false, //修改小区弹窗
				district_show: false,
				districtList: [], //社区列表
                communityKeyword: '', //小区搜索关键字
                communityResults: [], //小区搜索结果
                searchTimer: null, //搜索防抖
                keyboardHeight: 0, //键盘高度(弹起时顶起弹窗内容)
            };
        },
        onShow() {
            this.form.avatar = this.userInfo.avatar || '';
            this.form.nickname = this.userInfo.nickname || '';
            this.form.district_name = this.userInfo.community_parent || '';
            this.form.district_id = this.userInfo.community_parent_id || 0;
            this.form.community_name = this.userInfo.community || '';
            this.form.community_id = this.userInfo.community_id || 0;
            if (this.form.avatar) this.filepath = this.$u.func.cutImgUrl(this.form.avatar);
            this.get_community_parent_list()
        },
        mounted() {
            // 监听键盘高度，弹起时顶起社区弹窗内容避免遮挡搜索结果
            uni.onKeyboardHeightChange(res => {
                this.keyboardHeight = res.height || 0
            })
        },
        methods: {
			// 选择所属社区(手动选择时清空已选小区，需重新搜索)
			districtConfirm(e) {
				this.form.district_id = e[0].value
				this.form.district_name = e[0].label
				this.form.community_id = 0
				this.form.community_name = ''
				this.communityKeyword = ''
				this.communityResults = []
			},
			// 获取社区列表
			get_community_parent_list() {
				this.$u.api.get_community_parent_list().then(res => {
					this.districtList = res.data
				})
			},
            // 搜索小区(防抖)
            onCommunitySearch(value) {
                if (this.searchTimer) clearTimeout(this.searchTimer)
                if (!value || !value.trim()) {
                    this.communityResults = []
                    return
                }
                this.searchTimer = setTimeout(() => {
                    this.$u.api.search_community({keyword: value.trim()}).then(res => {
                        this.communityResults = res.data || []
                    })
                }, 300)
            },
            // 选择小区(自动带出社区)
            selectCommunity(item) {
                this.form.community_id = item.id
                this.form.community_name = item.name
                this.form.district_id = item.pid
                this.form.district_name = item.parent_name || ''
                this.communityKeyword = ''
                this.communityResults = []
            },
            // 更新会员信息
            submitBtn() {
                if (!this.form.nickname) return this.$u.toast('请填写昵称')
                if (!this.form.community_id) return this.$u.toast('请选择所在小区')
                this.$u.api.updateMember({
                    nickname: this.form.nickname,
                    avatar: this.filepath,
                    community_parent_id:this.form.district_id,
                    community_id:this.form.community_id
                }).then(res => {
                    this.$u.toast(res.msg, 'success');
                    this.getUserInfo()
                }).catch(err => {
                    this.$u.toast(err.msg);
                })
            },
            // 获取用户信息
            getUserInfo() {
                this.$u.api.getUserInfo().then(res => {
                    this.$u.vuex('userInfo', res.data)
                }).catch(err => {
                    this.$u.vuex('userInfo', {})
                    this.$u.vuex('isLogin', false)
                })
            },
            // 获取用户头像
            chooseavatar(e) {
                var that = this;
                that.form.avatar = e.detail.avatarUrl;
                uni.uploadFile({
                    url: uploadUrl,
                    filePath: e.detail.avatarUrl,
                    name: 'file',
                    success: (uploadFileRes) => {
                        let res = JSON.parse(uploadFileRes.data)
                        that.filepath = res.data.filepath;
                    },
                    fail: (err) => {
                        console.log(err)
                    }
                });
            },
            // 获取用户昵称
            nameChange(value) {
                this.form.nickname = value;
            }
        }
    }
</script>

<style lang="scss">

</style>
