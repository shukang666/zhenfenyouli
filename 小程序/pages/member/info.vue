<template>
    <view class="u-p-t-20">
		<u-cell-group>
			<u-cell-item title="头像" @click="showAvatarPop = true">
				<u-avatar :src="form.avatar"></u-avatar>
			</u-cell-item>
			<u-cell-item title="昵称" :value="form.nickname" @click="showNicknamePop = true"></u-cell-item>
			<u-cell-item title="所在小区" :value="communityText" @click="toggleCommunityDropdown"></u-cell-item>
		</u-cell-group>

		<!-- 小区下拉选择（点击所在小区后展开，选择即保存） -->
		<view v-if="showCommunityDropdown" class="community-dropdown-mask" @click="showCommunityDropdown = false">
			<view class="community-dropdown" @click.stop>
				<view class="u-radius-5 u-p-lr-20" style="background-color: #f7f7f7;">
					<u-input v-model="communityKeyword" type="text" placeholder="输入小区名称搜索" :clearable="true" @input="onCommunitySearch" />
				</view>
				<scroll-view scroll-y class="community-dropdown-list" @scrolltolower="loadMoreCommunity" lower-threshold="50">
					<view v-if="!communityResults.length && !communityLoading" class="u-text-center u-p-30 u-font-24 u-hui">
						{{ communityKeyword ? '未找到相关小区' : '请输入小区名称搜索' }}
					</view>
					<view v-for="item in communityResults" :key="item.id"
						@click="selectCommunity(item)"
						class="u-p-lr-20 u-p-tb-15 community-dropdown-item">
						<view>{{item.name}}</view>
						<view class="u-font-24" style="color: #999;">{{item.parent_name}}</view>
					</view>
					<view v-if="communityLoading" class="u-text-center u-p-tb-20 u-font-24 u-hui">加载中...</view>
					<view v-if="communityNoMore && communityResults.length" class="u-text-center u-p-tb-20 u-font-24 u-hui">没有更多了</view>
				</scroll-view>
			</view>
		</view>

		<!-- 底部确认按钮 -->
		<view class="u-p-60">
			<u-button type="primary" shape="circle" hover-class="none"
				:customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
				@click="goBack">确认</u-button>
		</view>

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
                showCommunityDropdown: false, //小区下拉选择
                communityKeyword: '', //小区搜索关键字
                communityResults: [], //小区搜索结果
                communityPage: 1, //小区列表当前页
                communityLastPage: 1, //小区列表总页数
                communityLoading: false, //小区列表加载中
                searchTimer: null, //搜索防抖
            };
        },
        computed: {
            // 所在小区显示文案
            communityText() {
                if (this.form.community_id) {
                    return (this.form.district_name ? this.form.district_name + '-' : '') + this.form.community_name
                }
                return '请选择'
            },
            // 是否已加载完所有数据
            communityNoMore() {
                return this.communityPage >= this.communityLastPage
            }
        },
        onShow() {
            this.form.avatar = this.userInfo.avatar || '';
            this.form.nickname = this.userInfo.nickname || '';
            this.form.district_name = this.userInfo.community_parent || '';
            this.form.district_id = this.userInfo.community_parent_id || 0;
            this.form.community_name = this.userInfo.community || '';
            this.form.community_id = this.userInfo.community_id || 0;
            if (this.form.avatar) this.filepath = this.$u.func.cutImgUrl(this.form.avatar);
        },
        methods: {
            // 切换小区下拉
            toggleCommunityDropdown() {
                this.showCommunityDropdown = !this.showCommunityDropdown
                if (this.showCommunityDropdown) {
                    // 展开时重置并加载首页
                    this.communityKeyword = ''
                    this.communityPage = 1
                    this.communityResults = []
                    this.loadCommunityList('', true)
                }
            },
            // 加载小区列表(isReset: 是否重置列表，搜索时为true)
            loadCommunityList(keyword, isReset = false) {
                if (this.communityLoading) return
                this.communityLoading = true
                const page = isReset ? 1 : this.communityPage
                this.$u.api.search_community({ keyword, page, page_size: 20 }).then(res => {
                    const data = res.data || {}
                    const list = data.data || []
                    if (isReset) {
                        this.communityResults = list
                        this.communityPage = 1
                    } else {
                        this.communityResults = this.communityResults.concat(list)
                    }
                    this.communityLastPage = data.last_page || 1
                }).catch(() => {}).finally(() => {
                    this.communityLoading = false
                })
            },
            // 上拉加载更多
            loadMoreCommunity() {
                if (this.communityLoading || this.communityNoMore) return
                this.communityPage++
                this.loadCommunityList(this.communityKeyword.trim(), false)
            },
            // 搜索小区(防抖)
            onCommunitySearch(value) {
                if (this.searchTimer) clearTimeout(this.searchTimer)
                const keyword = (value || '').trim()
                this.searchTimer = setTimeout(() => {
                    this.loadCommunityList(keyword, true)
                }, 300)
            },
            // 选择小区(回填+更新数据库+关闭下拉)
            selectCommunity(item) {
                this.form.community_id = item.id
                this.form.community_name = item.name
                this.form.district_id = item.pid
                this.form.district_name = item.parent_name || ''
                // 立即更新数据库
                this.$u.api.updateMember({
                    community_parent_id: this.form.district_id,
                    community_id: this.form.community_id
                }).then(res => {
                    this.$u.toast(res.msg, 'success');
                    this.showCommunityDropdown = false
                    this.communityKeyword = ''
                    this.communityResults = []
                    this.getUserInfo()
                }).catch(err => {
                    this.$u.toast(err.msg);
                })
            },
            // 更新会员信息(头像/昵称弹窗确认保存)
            submitBtn() {
                if (!this.form.nickname) return this.$u.toast('请填写昵称')
                this.$u.api.updateMember({
                    nickname: this.form.nickname,
                    avatar: this.filepath
                }).then(res => {
                    this.$u.toast(res.msg, 'success');
                    this.showAvatarPop = false
                    this.showNicknamePop = false
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
            },
            // 直接返回（等同返回键）
            goBack() {
                uni.navigateBack()
            }
        }
    }
</script>

<style lang="scss" scoped>
	.community-dropdown-mask {
		position: fixed;
		left: 0;
		right: 0;
		top: 0;
		bottom: 0;
		background-color: rgba(0, 0, 0, 0.3);
		z-index: 99;
		display: flex;
		align-items: flex-start;
		justify-content: center;
	}
	.community-dropdown {
		margin-top: 180rpx;
		width: 690rpx;
		background-color: #fff;
		border-radius: 12rpx;
		padding: 20rpx;
		box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.1);
	}
	.community-dropdown-list {
		max-height: 500rpx;
		margin-top: 10rpx;
	}
	.community-dropdown-item {
		border-bottom: 1rpx solid #f7f7f7;
		&:last-child {
			border-bottom: none;
		}
	}
</style>
