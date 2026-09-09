import Vue from 'vue'
import App from './App'
import store from '@/store';
// 引入全局uView
import uView from 'uview-ui'
Vue.use(uView);

Vue.config.productionTip = false

App.mpType = 'app'

// 引入uView提供的对vuex的简写法文件
let vuexStore = require('@/store/$u.mixin.js');
Vue.mixin(vuexStore);

// 引入uView对小程序分享的mixin封装
let mpShare = require('uview-ui/libs/mixin/mpShare.js');
Vue.mixin(mpShare);

const app = new Vue({
	store,
    ...App
})

// http拦截器，将此部分放在new Vue()和app.$mount()之间，才能App.vue中正常使用
import httpInterceptor from '@/common/http.interceptor.js';
Vue.use(httpInterceptor, app);

// http接口API集中管理
import httpApi from '@/common/http.api.js'
Vue.use(httpApi, app)

// 公共函数
import globalFunc from '@/utils/common.js'
Vue.use(globalFunc, app)

// 腾讯位置信息
// import qqmap from '@/utils/qqmap/index.js'
// Vue.use(qqmap, app)
// // 高德位置信息
// import amap from '@/utils/amap/index.js'
// Vue.use(amap, app)

// 微信SDK
// #ifdef H5
import weixin from '@/utils/jwx.js'
Vue.use(weixin, app)
// #endif

app.$mount()
