<template>
    <view :class="['clock-add', pageTheme]" v-if="!setting.app_test">

        <view class="u-p-30">
            <view class="clock-panel" :class="'clock-panel--' + cateKind">
                <view class="clock-panel-head">
                    <text class="clock-panel-title">{{ panelCateName }}</text>
                    <text class="clock-panel-link" @click="previewCurrentCate">查看示例图</text>
                </view>
                <view v-if="isLegacyMode" class="u-flex u-flex-wrap u-m-t-10">
                    <view class="u-m-r-20 u-p-t-20" v-for="(item,index) in cate_list" :key="item.id"
                        @click="pickCate(index)">
                        <u-tag :text="item.name" mode="dark" :type="form.cate_id == item.id?'success':'info'" />
                    </view>
                </view>
                <view class="clock-textarea-wrap u-m-t-20">
                    <u-input v-model="form.content" type="textarea" height="200" maxlength="-1"
                        :placeholder="contentPlaceholder" />
                </view>
                <view class="u-p-tb-30">
                    <cl-upload v-model="form.image" :action="uploadUrl" @onSuccess="onLegacySuccess" fileType="image">
                        <template v-slot:addImg>
                            <u-image width="138" height="138"
                                src="https://admin.circular-economy.cn/static/upload.png"></u-image>
                        </template>
                    </cl-upload>
                </view>
            </view>
        </view>

        <view class="u-p-lr-60 u-p-b-24">
            <u-button type="success" hover-class="none" @click="submit()">确认提交</u-button>
        </view>

        <view class="clock-guide u-m-lr-30 u-m-b-40" v-if="sceneGuideContent">
            <text class="clock-guide-title" v-if="sceneGuideTitle">{{ sceneGuideTitle }}</text>
            <view class="clock-guide-rich">
                <u-parse :html="sceneGuideContent"></u-parse>
            </view>
            <view class="clock-guide-more" @click="goLearn">
                <text>想学更多分类知识？去看分类指导 ›</text>
            </view>
        </view>
        <view class="clock-guide-empty u-m-lr-30 u-m-b-40" v-else-if="tutorialLoaded && !sceneGuideContent">
            <text>本场景教程暂未配置，请联系管理员在后台「打卡教程」中完善。</text>
        </view>

        <u-mask :show="mask_show">
            <view class="warp">
                <view class="">
                    <view class="u-flex u-row-center">
                        <u-image width="540" height="87"
                            src="https://oss.ausite.cn/xcxkf052/admin/20250514/9eb22ce3d967570fe1884290e5e10fa8.png?watermark"></u-image>
                    </view>
                    <view class="u-w-540 u-radius-10 u-type-white-bg u-text-center">
                        <u-gap height="80"></u-gap>
                        <view class="u-flex u-row-center">
                            <u-image width="200" height="200"
                                src="https://oss.ausite.cn/xcxkf052/admin/20250514/d4c1251c3c10fc8f0c4d912bf81cb167.png?watermark"></u-image>
                        </view>
                        <view class="u-font-32 u-font-bold">{{ msg }}</view>
                        <view class="u-type-error u-font-24 u-m-t-30">请不要忘记正确投递哦~</view>
                        <view class="u-m-t-30 u-flex u-p-lr-30">
                            <view class="u-flex-1">
                                <u-button type="success" hover-class="none" :customStyle="{background: '#3974f2'}"
                                    shape="circle" @click="switchTab">兑换礼物</u-button>
                            </view>
                            <view class="u-flex-1 u-m-l-30">
                                <u-button type="warning" shape="circle" @click="navigateBack">后退</u-button>
                            </view>
                        </view>
                        <view class="u-p-lr-30 u-m-t-30">
                            <u-button type="success" hover-class="none" shape="circle"
                                @click="closeMask">继续打卡</u-button>
                        </view>
                        <u-gap height="30"></u-gap>
                    </view>
                </view>
            </view>
        </u-mask>
    </view>
</template>

<script>
    import { uploadUrl } from '@/common/settings'
    import {
        ADD_PAGE_META,
        clockSceneLabel,
        resolveCateKind,
        resolveTutorialSceneKey,
        buildSceneCateTitle,
        buildSceneCateSubtitle,
    } from '@/common/clockScene.js'

    /** 单种类入口（须带 cate_id）；家庭与真分角为四类自选 */
    const SINGLE_CATE_SCENES = ['qinzi', 'street']

    export default {
        data() {
            return {
                uploadUrl,
                sceneKey: '',
                sceneName: '',
                isLegacyMode: true,
                form: {
                    type: 2,
                    cate_id: 0,
                    content: '',
                    image: [],
                    clock_scene: '',
                },
                cate_list: [],
                cate_index: 0,
                pendingCateId: 0,
                currentCateName: '',
                mask_show: false,
                msg: '',
                tutorialMap: {},
                tutorialLoaded: false,
            }
        },
        computed: {
            sceneLabel() {
                return clockSceneLabel(this.sceneKey, this.sceneName)
            },
            pageMeta() {
                let key = this.sceneKey
                if (this.isLegacyMode) {
                    key = this.sceneKey === 'home' ? 'home' : 'zhenfenjiao'
                }
                return ADD_PAGE_META[key] || ADD_PAGE_META.zhenfenjiao
            },
            pageTheme() {
                return (this.pageMeta && this.pageMeta.theme) || 'clock-add--office'
            },
            pageTitle() {
                if (this.isLegacyMode && this.pageMeta.title) {
                    return this.pageMeta.title
                }
                return buildSceneCateTitle(this.sceneKey, this.sceneName, this.currentCateName)
            },
            pageSubtitle() {
                if (this.isLegacyMode && this.pageMeta.subtitle) {
                    return this.pageMeta.subtitle
                }
                return buildSceneCateSubtitle(this.sceneKey, this.currentCateName)
            },
            contentPlaceholder() {
                return (this.pageMeta && this.pageMeta.placeholder) || '请输入垃圾分类描述'
            },
            cateKind() {
                return resolveCateKind(this.currentCateName) || 'default'
            },
            tutorialSceneKey() {
                return resolveTutorialSceneKey(this.sceneKey)
            },
            sceneGuideTitle() {
                const guide = this.tutorialMap && this.tutorialMap[this.tutorialSceneKey]
                return (guide && guide.title) ? guide.title : ''
            },
            sceneGuideContent() {
                const guide = this.tutorialMap && this.tutorialMap[this.tutorialSceneKey]
                return (guide && guide.content) ? guide.content : ''
            },
            panelCateName() {
                if (this.isLegacyMode) return '上报类型'
                return this.currentCateName || '垃圾种类'
            },
        },
        onLoad(option) {
            const cateId = option.cate_id ? parseInt(option.cate_id, 10) : 0
            this.sceneKey = option.scene_key ? String(option.scene_key) : ''
            this.sceneName = option.scene ? decodeURIComponent(option.scene) : ''
            this.isLegacyMode =
                !this.sceneKey ||
                this.sceneKey === 'home' ||
                this.sceneKey === 'zhenfenjiao' ||
                this.sceneKey === 'office' ||
                SINGLE_CATE_SCENES.indexOf(this.sceneKey) < 0
            this.pendingCateId = cateId
            if (this.isLegacyMode) {
                this.form.clock_scene = this.sceneKey === 'home' ? 'home' : 'zhenfenjiao'
            } else {
                this.form.clock_scene = this.sceneKey
            }
            this.loadTutorials()
            this.get_select_list()
        },
        methods: {
            switchTab() {
                uni.switchTab({ url: '/pages/mall/mall' })
            },
            closeMask() {
                this.mask_show = false
                this.resetAfterSubmit()
            },
            navigateBack() {
                this.mask_show = false
                uni.navigateBack()
            },
            goLearn() {
                this.$u.route('/pages/flhd/list')
            },
            previewImage(urls) {
                uni.previewImage({ urls })
            },
            loadTutorials() {
                this.$u.api.get_clock_tutorial().then(res => {
                    this.tutorialMap = res.data || {}
                    this.tutorialLoaded = true
                }).catch(() => {
                    this.tutorialLoaded = true
                })
            },
            get_select_list() {
                this.$u.api.get_select_list({ type: 2 }).then(res => {
                    this.cate_list = res.data || []
                    this.applyCate()
                })
            },
            applyCate() {
                const list = this.cate_list
                if (!list.length) return
                let idx = 0
                const pending = this.pendingCateId || 0
                if (pending) {
                    for (let i = 0; i < list.length; i++) {
                        if (Number(list[i].id) === Number(pending)) {
                            idx = i
                            break
                        }
                    }
                }
                if (!this.isLegacyMode && !pending) {
                    this.$u.toast('请从打卡页选择垃圾种类')
                    setTimeout(() => uni.navigateBack(), 1500)
                    return
                }
                this.pickCate(idx)
            },
            pickCate(index) {
                const item = this.cate_list[index]
                if (!item) return
                this.cate_index = index
                this.form.cate_id = item.id
                this.currentCateName = item.name || ''
            },
            previewCurrentCate() {
                const item = this.cate_list[this.cate_index]
                if (!item || !item.waste_thumb) return this.$u.toast('暂无示例图')
                this.previewImage([item.waste_thumb])
            },
            onLegacySuccess(reslut) {
                this.form.image.push(reslut.data.url)
            },
            refreshPrevList() {
                const prev = this.$u.func.prePage()
                if (prev && typeof prev.get_forum_list === 'function') {
                    prev.forum_list = []
                    prev.page = 1
                    prev.get_forum_list()
                }
            },
            resetAfterSubmit() {
                this.form.content = ''
                this.form.image = []
                this.refreshPrevList()
            },
            submit() {
                const that = this
                const modalContent =
                    '照片需要拍清楚垃圾的内容，保持分类正确，如果错误分类可能造成环境的危害和相应的撤销积分'
                if (!that.form.image.length) return that.$u.toast('请先上传照片')
                if (!that.form.cate_id) return that.$u.toast('请选择垃圾种类')
                uni.showModal({
                    title: '提示',
                    content: modalContent,
                    success(res) {
                        if (!res.confirm) return
                        that.postOne({
                            type: 2,
                            cate_id: that.form.cate_id,
                            content: that.form.content,
                            image: that.form.image,
                                clock_scene: that.form.clock_scene || (that.sceneKey === 'home' ? 'home' : 'zhenfenjiao'),
                        }).then(msg => {
                            that.msg = msg
                            that.mask_show = true
                            that.form.content = ''
                            that.form.image = []
                            that.refreshPrevList()
                        }).catch(e => {
                            that.$u.toast(e.msg || '提交失败')
                        })
                    },
                })
            },
            postOne(payload) {
                return this.$u.api.add_forum(payload).then(res => res.msg || '发布成功')
            },
        },
    }
</script>

<style lang="scss" scoped>
.clock-add {
    min-height: 100vh;
    padding-bottom: 40rpx;
}
.clock-add--home {
    background: linear-gradient(180deg, #e8f5ea 0%, #f5f7f6 280rpx);
}
.clock-add--qinzi {
    background: linear-gradient(180deg, #fff3e8 0%, #faf8f5 280rpx);
}
.clock-add--street {
    background: linear-gradient(180deg, #e8f4ff 0%, #f5f8fa 280rpx);
}
.clock-add--office {
    background: linear-gradient(180deg, #eceff5 0%, #f6f7f9 280rpx);
}
.clock-add-hero {
    margin: 24rpx 28rpx 0;
    padding: 32rpx 28rpx;
    border-radius: 24rpx;
    background: #fff;
    box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.06);
}
.clock-add-kicker {
    display: block;
    font-size: 24rpx;
    color: #888;
}
.clock-add-title {
    display: block;
    margin-top: 8rpx;
    font-size: 40rpx;
    font-weight: 800;
    color: #222;
}
.clock-add-sub {
    display: block;
    margin-top: 12rpx;
    font-size: 26rpx;
    line-height: 1.5;
    color: #666;
}
.clock-panel {
    margin-bottom: 24rpx;
    padding: 28rpx;
    border-radius: 20rpx;
    background: #fff;
    box-shadow: 0 6rpx 20rpx rgba(0, 0, 0, 0.05);
}
.clock-panel-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
}
.clock-panel-title {
    font-size: 32rpx;
    font-weight: 700;
    color: #222;
}
.clock-panel-link {
    font-size: 24rpx;
    color: #18af38;
}
.clock-panel--recycle .clock-panel-title { color: #1a8f4a; }
.clock-panel--harm .clock-panel-title { color: #c45c2a; }
.clock-panel--food .clock-panel-title { color: #2a7fc4; }
.clock-panel--other .clock-panel-title { color: #666; }
.clock-textarea-wrap {
    padding: 16rpx 20rpx;
    border-radius: 12rpx;
    background: #f5f6f7;
}
.clock-guide {
    padding: 28rpx;
    border-radius: 20rpx;
    background: #fff;
    box-shadow: 0 6rpx 20rpx rgba(0, 0, 0, 0.05);
}
.clock-guide-title {
    display: block;
    font-size: 30rpx;
    font-weight: 700;
    color: #222;
}
.clock-guide-label {
    display: block;
    margin-top: 20rpx;
    font-size: 26rpx;
    font-weight: 600;
    color: #444;
}
.clock-guide-rich {
    margin-top: 12rpx;
    font-size: 26rpx;
    line-height: 1.6;
    color: #666;
}
.clock-guide-more {
    margin-top: 24rpx;
    padding-top: 20rpx;
    border-top: 1rpx solid #eee;
    font-size: 26rpx;
    color: #18af38;
}
.clock-guide-empty {
    padding: 28rpx;
    border-radius: 20rpx;
    background: #fff;
    font-size: 26rpx;
    color: #999;
    text-align: center;
}
.warp {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
}
</style>
