<<<<<<< HEAD
#### 写在前面
>项目基础模板，集成了uview-ui和微信jssdk，封装了众多全局方法及小组件，适合开新项目使用。

# 
#### 基本功能 🚀

>1. 统一配置
>2. 集成微信公众号JSSDK，已封装部分sdk方法
>3. 集成uview-ui，采用下载安装方式，已配置好，无需npm
>4. api分模块管理和拦截器
>5. vuex和缓存实现全局变量
>6. 全局公共函数
>7. 小清新轻提示toast组件

# 
#### 组件 ★

>1. v-add-tips 右上角提示添加到我的小程序
>2. v-info 弹窗提示完善头像/昵称信息
>3. v-price-format 金额格式化显示
>4. v-swiper 轮播图(支持图片/视频)
>5. v-upload 图片上传组件(支持自定义图片压缩)

# 
#### 项目目录 &#x1F4C1;

```
│  App.vue
│  main.js
│  manifest.json
│  package-lock.json
│  package.json
│  pages.json
│  README.md
│  uni.scss
│  
├─api // api目录，把模块的api写在对应的js文件中
│  |  order.js
│  |  wxapp.js
│      
├─common
|  |  settings.js // 全局配置文件
│  |  common.style.scss  // 全局样式类
│  |  http.api.js // api集中管理，挂载所有api到$u.api中
│  |  http.interceptor.js // api拦截器
│      
├─components
│  │  iconfont.css // toast字体图标
│  │  style.components.scss // 组件公共样式
│  ├─ v-bottom-menu // 底部菜单组件，用户表单提交等场景
│  ├─ v-image // 小巧的image组件
│  ├─ v-navtab // tabs组件封装，固定在页面顶部
│  ├─ v-title // 标题组件
│  └─ v-toast // 小清新轻提示组件
│          
├─pages // 页面主目录
│          
├─static
│  │  logo.png
│  │  
│  └─tabbar // tabbar图标目录
│          
├─store
│      $u.mixin.js // vuex挂载全局变量
│      index.js // vuex主文件
│      
├─utils
│      common.js // 全局公共函数
│      jwx.js // 封装的jssdk函数
│      wx-jssdk.js // 微信jssdk
│      
└─uview-ui // uview-ui组件库
                
```


=======
# 甄分友礼小程序前端

#### 介绍
{**以下是 Gitee 平台说明，您可以替换此简介**
Gitee 是 OSCHINA 推出的基于 Git 的代码托管平台（同时支持 SVN）。专为开发者提供稳定、高效、安全的云端软件开发协作平台
无论是个人、团队、或是企业，都能够用 Gitee 实现代码托管、项目管理、协作开发。企业项目请看 [https://gitee.com/enterprises](https://gitee.com/enterprises)}

#### 软件架构
软件架构说明


#### 安装教程

1.  xxxx
2.  xxxx
3.  xxxx

#### 使用说明

1.  xxxx
2.  xxxx
3.  xxxx

#### 参与贡献

1.  Fork 本仓库
2.  新建 Feat_xxx 分支
3.  提交代码
4.  新建 Pull Request


#### 特技

1.  使用 Readme\_XXX.md 来支持不同的语言，例如 Readme\_en.md, Readme\_zh.md
2.  Gitee 官方博客 [blog.gitee.com](https://blog.gitee.com)
3.  你可以 [https://gitee.com/explore](https://gitee.com/explore) 这个地址来了解 Gitee 上的优秀开源项目
4.  [GVP](https://gitee.com/gvp) 全称是 Gitee 最有价值开源项目，是综合评定出的优秀开源项目
5.  Gitee 官方提供的使用手册 [https://gitee.com/help](https://gitee.com/help)
6.  Gitee 封面人物是一档用来展示 Gitee 会员风采的栏目 [https://gitee.com/gitee-stars/](https://gitee.com/gitee-stars/)
>>>>>>> 39143d5d2d293babd808b165394efca1af24a850
