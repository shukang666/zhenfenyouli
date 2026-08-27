// 全局公共方法
// 此处第二个参数vm，也就是页面使用的this

import { lbsKEY } from '@/common/settings'
import amap from './amap-wx.js';

//初始化高德SDK
const amapPlugin = new amap.AMapWX({
	key: lbsKEY
})

const install = (Vue, vm) => {
	/**
	 * 授权请求
	 *
	 * @param {*} authorizeScope 更多scope参考：https://developers.weixin.qq.com/miniprogram/dev/framework/open-ability/authorize.html
	 * @param {*} modal modal弹窗参数信息
	 * @returns
	 */
	function setAuthorize(authorizeScope, modal) {
		return new Promise((resolve, reject) => {
			if (!modal) {
				modal = {
					title: '授权',
					content: '需要您设置授权已使用相应功能',
					confirmText: '设置'
				}
			}
			uni.getSetting({
				success(res) {
					// hasAuthor === undefined  表示 初始化进入，从未授权
					// hasAuthor === true       表示 已授权
					// hasAuthor === false      表示 授权拒绝
					const hasAuthor = res.authSetting[authorizeScope]
					switch (hasAuthor) {
						case undefined:
							uni.authorize({
								scope: authorizeScope,
								success: res => {
									resolve(res)
								},
								fail: err => {
									vm.$u.toast('授权失败')
									reject(err)
								}
							})
							break
						case true:
							resolve()
							break
						case false:
							uni.showModal({
								...modal,
								success: res => {
									if (res.confirm) {
										uni.openSetting({
											success: res => {
												if (res.authSetting[authorizeScope]) {
													resolve(res)
												} else {
													reject(res)
													vm.$u.toast('授权失败')
												}
											},
											fail: err => {
												reject(err)
												vm.$u.toast('打开设置异常')
											}
										})
									} else {
										reject(res)
										vm.$u.toast('授权失败')
									}
								},
								fail: err => {
									reject(err)
									vm.$u.toast('弹窗异常')
								}
							})
							break
					}
				},
				fail: err => {
					reject(err)
					vm.$u.toast('获取当前设置异常')
				}
			})
		})
	}

	/**
	 * 获取用户当前位置信息
	 */
	function getLocation() {
		return new Promise((resolve, reject) => {
			const scope = 'scope.userLocation'
			const modal = {
				title: '授权',
				content: '需要您授权使用位置信息',
				confirmText: '设置'
			}
			setAuthorize(scope, modal).then(() => {
				amapPlugin.getRegeo({
					success: res => {
						resolve(res)
					},
					fail: err => {
						reject(err)
						vm.$u.toast('获取位置信息失败')
					}
				});
			})
			.catch(err => {
				reject(err)
			})
		})
	}
	
	/**
	 * 地点搜索
	 */
	function getInputtips(keyword, lat, lng) {
		return new Promise((resolve, reject) => {
			myAmapFun.getInputtips({
				keywords: keyword,
				location: lat + ',' + lng,
				// city: , //搜索城市
				citylimit: true, //仅返回指定城市数据
				success: function(data){
					resolve(res)
				},
				fail: function(info){
					reject(err)
					vm.$u.toast('获取位置信息失败')
				}
			})
		})
	}
	
	/**
	 * 路线规划
	 */
	function getRidingRoute(from, to) {
		return new Promise((resolve, reject) => {
			myAmapFun.getRidingRoute({
				origin: from, //出发地的经纬度坐标
				destination: to, //目的地的经纬度坐标
				success: function(data){
					resolve(res)
				},
				fail: function(info){
					reject(err)
					vm.$u.toast('获取位置信息失败')
				}
			})
		})
	}

	Vue.prototype.$u.map = {
		getLocation
	}
	// 将各个定义的方法，统一放进对象挂载到vm.$u.map(因为vm就是this，也即this.$u.map)下
}
export default {
	install
}
