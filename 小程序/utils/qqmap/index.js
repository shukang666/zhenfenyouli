// 全局公共方法
// 此处第二个参数vm，也就是页面使用的this

import { lbsKEY } from '@/common/settings'
import QQMapWX from './qqmap-wx-jssdk.js';

//初始化腾讯SDK
const qqmapsdk = new QQMapWX({
	key: lbsKEY
});

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
	function getLocation(params) {
		return new Promise((resolve, reject) => {
			const scope = 'scope.userLocation'
			const modal = {
				title: '授权',
				content: '需要您授权使用位置信息',
				confirmText: '设置'
			}
			let data = params ? params : {};
			setAuthorize(scope, modal).then(() => {
				qqmapsdk.reverseGeocoder({
					...data,
					success: function(res) {
						resolve(res)
					},
					fail: function(err) {
						reject(err)
						vm.$u.toast('获取位置信息失败')
					}
				})
			})
			.catch(err => {
				reject(err)
			})
		})
	}
	
	/**
	 * 地点搜索
	 * keyword： 必填
	 * location： 非必填 （例：location: '39.984060,116.307520'）（例：location: {latitude: 39.984060,longitude: 116.307520}）
	 * page_index： 非必填 第x页，默认第1页
	 * page_size：非必填 每页条目数，最大限制为20条，默认值10
	 * region：非必填 指定地区名称，不自动扩大范围，如北京市,
	 * auto_extend：非必填 取值1：[默认]自动扩大范围； 取值0：不扩大。 仅适用于默认周边搜索以及制定地区名称搜索。
	 * filter：非必填 最多支持五个分类 filter: 'category=' + encodeURI('公交站')
	 */
	function search(params){
		return new Promise((resolve, reject) => {
			let data = params ? params : {};
			qqmapsdk.search({
				...data,
				auto_extend: 0,
				success: function (res) { //搜索成功后的回调
					resolve(res)
				},
				fail: function (err) {
					reject(err)
					vm.$u.toast('获取位置信息失败')
				}
			});
		})
	}
	
	/**
	 * 路线规划
	 */
	function direction(go, to) {
		return new Promise((resolve, reject) => {
			qqmapsdk.direction({
				mode: 'bicycling',
				from: go,
				to: to,
				success: function(res) {
					var ret = res;
					var coors = ret.result.routes[0],
						pl = [];
					//坐标解压（返回的点串坐标，通过前向差分进行压缩）
					var kr = 1000000;
					for (var i = 2; i < coors.polyline.length; i++) {
						coors.polyline[i] = Number(coors.polyline[i - 2]) + Number(coors.polyline[i]) / kr;
					}
					//将解压后的坐标放入点串数组pl中
					for (var i = 0; i < coors.polyline.length; i += 2) {
						pl.push({
							latitude: coors.polyline[i],
							longitude: coors.polyline[i + 1]
						})
					}
					coors.points = pl;
					resolve(coors);
				},
				fail: function(err) {
					reject(err)
					vm.$u.toast('获取位置信息失败')
				},
				complete: function(res) {
					// console.log(res);
				}
			});
		})
	}
	
	Vue.prototype.$u.qqmap = {
		getLocation,
		search,
		direction
	}
	// 将各个定义的方法，统一放进对象挂载到vm.$u.map(因为vm就是this，也即this.$u.map)下
}
export default {
	install
}
