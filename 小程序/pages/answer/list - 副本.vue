<template>
    <view class="">
        <view class="u-p-30" v-for="(item,index) in paper_list" :key="index" v-if="index < currentIndex">
            <view class="u-m-b-30">
                <!-- <u-button type="primary" size="mini">{{item.type_name}}</u-button> -->
                <text class="u-font-bold" style="color: #18AF38;"> {{item.question}}</text>
            </view>
            
            <!-- 单选 -->
            <view class="u-type-info-light-bg u-p-20" v-for="(item1,index1) in item.option" :key="index1" v-if="item.type==1">
                <u-radio-group v-model="item.answer" size="30" wrap active-color="#18AF38" @change="radioGroupChange($event,item1,index)">
                    <u-radio :name="item1.id">
                        <view class="u-font-bold u-font-28">
                            {{item1.option}}
                        </view>
                    </u-radio>
                </u-radio-group>
                
                <!-- 是否选择医院 -->
                <block v-if="isHospital">
                    <view class="u-m-tb-30">
                        <text class="u-font-bold" style="color: #18AF38;">请选择您常去的基础医疗机构</text>
                    </view>
                    <view class="u-type-info-light-bg u-p-20">
                        <u-input type="select" v-model="hospital" :select-open="hospitalShow" placeholder="请选择" @click="hospitalShow = true" />
                    </view>
                </block>
            </view>
            
            <!-- 多选 -->
            <view class="u-type-info-light-bg u-p-20" v-for="(item1,index1) in item.option" :key="index1" v-if="item.type==2">
                <u-checkbox-group wrap @change="checkboxChange($event,item)" active-color="#18AF38">
                    <u-checkbox v-model="item1.checked" :name="item1.id" size="30">
                        <view class="u-font-bold u-font-28">
                            {{item1.option}}
                        </view>
                    </u-checkbox>
                </u-checkbox-group>
            </view>
            
            <!-- 填空 -->
            <view class="u-type-info-light-bg u-p-20" v-for="(item1,index1) in item.option" :key="index1" v-if="item.type==3">
                <view class="u-type-info-light-bg u-radius-10 u-p-20">
                    <u-input v-model="item.answer" type="textarea" height="200" placeholder="请输入答案" clearable />
                </view>
                <!-- <view class="u-m-t-20">
                    <u-upload :action="uploadUrl" maxCount="1" width="190" height="190"
                        @on-success="success($event,item)" @on-remove="remove($event,item)">
                    </u-upload>
                </view> -->
            </view>
        </view>
        
        <u-gap height="120"></u-gap>
        <view class="submit-btn u-type-white-bg  u-p-30">
            <u-button type="primary" hover-class="none"
                :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
                @click="submit">马上提交</u-button>
        </view>
        
        <!-- 医院选择 -->
        <u-select mode="mutil-column-auto" v-model="hospitalShow" value-name="id" label-name="name" child-name="child" :list="hospitalList" @confirm="handleConfirm"></u-select>
    </view>
</template>

<script>
    import {
        uploadUrl
    } from '@/common/settings'
    export default {
        data() {
            return {
                currentIndex: 1,
                uploadUrl,
                paper_list: [],
                id: '',
                health_id:0,
                rowIndex: null,
                isHospital: false,
                hospitalShow: false,
                hospitalList: [], //医院列表
                hospital: '', //选中的医院
            }
        },

        onLoad(option) {
            this.id = option.id
            this.health_id = option.health_id
            this.find_question_list(option.id)
        },
        methods: {
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
                var answer = []
                this.paper_list.forEach(item => {
                    let data = {};
                    if(item.hospital) data.hospital = item.hospital;
                    if(item.other) data.other = item.other;
                    answer.push({
                        question_id: item.id,
                        select_option: item.answer,
                        ...data
                    })
                })
                this.$u.api.add_exam_record({
                    list: answer,
                    exam_id: this.id,
                }).then(res => {
                    this.$u.func.showToast({
                        title: res.msg,
                        icon: 'success',
                        url: '/pages/health/add?id=' + this.health_id,
                        type: 'redirectTo'
                    })
                    this.$u.toast(res.msg)
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            },
            // 单选
            radioGroupChange(e, item1, index) {
                if(item1.option_type == 2) {
                    this.$u.api.get_select_list().then(res => {
                        this.hospitalList = res.data;
                        this.isHospital = true;
                    })
                }
                this.rowIndex = index;
                this.currentIndex++;
                // this.paper_list = this.paper_list.splice(index, 0, newElement);
            },
            // 多选
            checkboxChange(e, item) {
                item.answer = []
                item.option.forEach(obj => {
                    if (obj.checked) {
                        item.answer.push(obj.id)
                    }
                })
            },
            // 答题列表
            find_question_list(id) {
                this.$u.api.find_question_list({
                    exam_id: id
                }).then(res => {
                    this.paper_list = res.data
                    this.paper_list.map(v => {
                        if (v.type == 2) {
                            this.$set(v, 'answer', [])
                        } else {
                            this.$set(v, 'answer', '')
                        }
                    })
                })
            },
            
            //是否有备注内容
            getIsOther(){
                return this.$u.test.contains(this.answerList[this.curIndex].select_option, String(this.curItem.option[this.curItem.option.length - 1].id))
            },
            
            //是否有选择医疗机构
            handleConfirm(e){
                this.hospital = e[0].label + '-' + e[1].label;
                // this.paper_list[this.rowIndex].hospital = [e[0].label, e[1].label];
            },
        }
    }
</script>

<style lang="scss">
    page {
        background: #fff;
    }
</style>