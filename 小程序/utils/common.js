// 全局公共方法
// 此处第二个参数vm，也就是页面使用的this
const install = (Vue, vm) => {
	// 检查登录状态
	const checkLogin = (e = {}) => {
		if (!vm.isLogin) {
			uni.navigateTo({
				url: '/pages/public/login'
			})
			return false
		}
		return true
	}

	// 校验是否是H5页面
	const checkH5 = (url) => {
		const testHttp = vm.$u.test.url(url)
		if (testHttp) {
			return '/pages/h5/h5?url=' + encodeURIComponent(url)
		} else {
			return url
		}
	}
	// URL参数转对象
	const paramsToObj = (url) => {
		// 如果是完整URL，包含?号，先通过?号分解
		if (url.indexOf('?') != -1) {
			let arr = url.split('?')[1]
		}
		let arr = url.split('&') //先通过？分解得到？后面的所需字符串，再将其通过&分解开存放在数组里
		let obj = {}
		for (let i of arr) {
			obj[i.split('=')[0]] = i.split('=')[1] //对数组每项用=分解开，=前为对象属性名，=后为属性值
		}
		return obj
	}

	// 刷新当前页面
	const refreshPage = () => {
		const pages = getCurrentPages()
		const currentPage = pages[pages.length - 1]
		const path = '/' + currentPage.route + vm.$u.queryParams(currentPage.options)
		if (vm.$u.test.contains(currentPage.route, 'tabbar')) {
			uni.reLaunch({
				url: path,
				fail: (err) => {
					console.log(err)
				}
			})
		} else {
			uni.redirectTo({
				url: path,
				fail: (err) => {
					console.log(err)
				}
			})
		}
	}
	
	/**
	* 返回上一页面并执行某个操作
	* pageLen: 返回上几页
	* 使用：this.$u.func.prePage(2).data = {id:1, name: '名称'}
	* 使用：this.$u.func.prePage(2).getList(); //getList为方法名称
	*/
	const prePage = (pageLen = 2)=>{
		let pages = getCurrentPages();
		let prePage = pages[pages.length - pageLen];
		// // #ifdef H5
		// return prePage;
		// // #endif
		return prePage.$vm;
	}
	
	/**
	* 跳转到tabBar页面并刷新
	* url: tabBar的地址
	* data: 参数['页码字段', '数据列表字段']
	* data: ['page', 'List']
	*/
	//
	function reloadPage(url,data) {
		uni.switchTab({
			url: url,
			success: (res) => {
				let page = getCurrentPages().pop();
				if (page == undefined || page == null) return;
				page.$vm[data[0]] = 1; //页码置为1
				page.$vm[data[1]] = []; //数据列表置为空
				page.onLoad();
			}
		})
	}
	
	/**
	* 跳转到指定位置
	* target: 目标位置的节点：类名
	* Box: 最外层盒子的节点：类名
	*/
	function goTo (target,Box){
		uni.createSelectorQuery().select('.'+target).boundingClientRect(data=>{
			  uni.createSelectorQuery().select('.'+Box).boundingClientRect(res=>{
					uni.pageScrollTo({
						  duration: 100,//过渡时间
						  scrollTop: data.top - res.top  ,//到达距离顶部的top值
					})
			  }).exec()
		}).exec();
	}

	/**
	* Toast 提示消息
	*/
	const showToast = (data = {}) => {
		if (typeof data == 'string') {
			uni.showToast({
				title: data,
				icon: 'none'
			})
		} else {
			uni.showToast({
				title: data.title,
				icon: data.icon || 'none',
				image: data.image || '',
				mask: data.mask || false,
				position: data.position || 'center',
				duration: data.duration || 1500,
				success: () => {
					setTimeout(() => {
						data.success && data.success()
						if (data.back) return uni.navigateBack()
						if (data.url) return vm.$u.route({
							type: data.type || 'navigateTo',
							url: data.url,
							params: data.params || ''
						})
					}, data.duration || 1500)
				}
			})
		}
	}

	/**
	* 小程序微信支付
	* payInfo: 支付所属的信息
	* success: 成功的回调
	*/
	const wxappPay = ({
		payInfo,
		success,
		fail,
		complete
	}) => {
		uni.requestPayment({
			provider: 'wxpay',
			timeStamp: payInfo.timeStamp + '',
			nonceStr: payInfo.nonceStr,
			package: payInfo.package,
			paySign: payInfo.paySign,
			signType: payInfo.signType,
			success: res => {
				success && success(res)
			},
			fail: err => {
				fail && fail(err)
			},
			complete: res => {
				complete && complete(res)
			}
		})
	}
	
	/**
	* 获取日期信息
	* day: -1昨天 0今天 1 明天
	* month: 0上/1下个月的今天
	* year: 多少年0前/1后的今天
	*/
	function getDay (day = 0, month = 0, year = 0) {
		var today = new Date()
		var targetday = today.getTime() + 1000 * 60 * 60 * 24 * day
		// var weekArr = ['周日', '周一', '周二', '周三', '周四', '周五', '周六']
		today.setTime(targetday)
		var tYear = today.getFullYear() + year
		var tMonth = today.getMonth() + 1
		if(tMonth + Number(month) == 0){
			tYear = tYear - 1
			tMonth = 12
		}else{
			tMonth = tMonth + Number(month) < 10 ? '0'+ (tMonth + Number(month)) : tMonth + Number(month)
		}
		var tDate = today.getDate() < 10 ? '0'+today.getDate(): today.getDate()
		// var wk = today.getDay();
		return tYear + '-' + tMonth + '-' + tDate
	}
	
	//获取当前日期、时间(2021-09-26 14:44:33)
	function curentTime(){
		var now = new Date();
		var year = now.getFullYear(); //年
		var month = now.getMonth() + 1; //月
		var day = now.getDate(); //日
		
		var hh = now.getHours(); //时
		var mm = now.getMinutes(); //分
		var ss = now.getSeconds(); //秒
		var clock = year + "-";
		if(month < 10) clock += "0";
		clock += month + "-";
		if(day < 10) clock += "0";
		clock += day + " ";
		if(hh < 10)	clock += "0";
		clock += hh + ":";
		if (mm < 10) clock += '0';
		clock += mm + ":";
		if (ss < 10) clock += '0';
		clock += ss;
		return(clock); 
	}
	
	//把日期时间转换为时间戳
	function transDate(time){
	    var date = new Date();
	    date.setFullYear(time.substring(0, 4));
	    date.setMonth(time.substring(5, 7) - 1);
	    date.setDate(time.substring(8, 10));
	    date.setHours(time.substring(11, 13));
	    date.setMinutes(time.substring(14, 16));
	    date.setSeconds(time.substring(17, 19));
	    return Date.parse(date) / 1000;
	}
	
	/**
	 * 把秒转换时间
	 * @param {number} value
	 * @returns {string}
	 */
	function timeChange (value) {
		var secondTime = parseInt(value); // 秒
		var minuteTime = 0; // 分
		var hourTime = 0; // 小时
		if (secondTime > 60) {
			minuteTime = parseInt(secondTime / 60);
			//获取秒数，秒数取余，得到整数秒数
			secondTime = parseInt(secondTime % 60);
			//如果分钟大于60，将分钟转换成小时
			if (minuteTime > 60) {
				//获取小时，获取分钟除以60，得到整数小时
				hourTime = parseInt(minuteTime / 60);
				//获取小时后取剩余的的分，获取分钟除以60取剩余的分
				minuteTime = parseInt(minuteTime % 60);
			}
		}
		if (parseInt(secondTime) < 10) {
			var result = "0" + parseInt(secondTime);
		} else {
			var result = parseInt(secondTime);
		}

		if (minuteTime > 0) {
			result = "" + parseInt(minuteTime) + ":" + result;
		}
		if (hourTime > 0) {
			result = "" + parseInt(hourTime) + ":" + result;
		}
		return result;
	}
	
	/**
	 * 转换时间为几分钟前
	 * @param {number} time
	 * @param {string} option
	 * @returns {string}
	 */
	function formatTime(time, option) {
	    if (('' + time).length === 10) {
	        time = parseInt(time) * 1000
	    } else {
	        time = +time
	    }
	    const d = new Date(time)
	    const now = Date.now()
	
	    const diff = (now - d) / 1000
	
	    if (diff < 30) {
	        return '刚刚'
	    } else if (diff < 3600) {
	        // less 1 hour
	        return Math.ceil(diff / 60) + '分钟前'
	    } else if (diff < 3600 * 24) {
	        return Math.ceil(diff / 3600) + '小时前'
	    } else if (diff < 3600 * 24 * 2) {
	        return '1天前'
	    }
	    if (option) {
	        return parseTime(time, option)
	    } else {
	        return (
	            d.getMonth() + 1 + '月' +
	            d.getDate() + '日' +
	            d.getHours() + '时' +
	            d.getMinutes() + '分'
	        )
	    }
	}
	
	//文本域换行
	function formatTextArea(val) {
		if (!val) return ''
		let newString = val.replace(/\n/g, '_@').replace(/\r/g, '_#');
		newString = newString.replace(/_#_@/g, '<br/>'); // IE7-8
		newString = newString.replace(/_@/g, '<br/>'); // IE9、FF、chrome
		newString = newString.replace(/\s/g, '&nbsp;'); // 空格处理
		return newString;
	}
	
	/**
	* 截取符号前/后内容
	* obj:字符串
	* sign:符号
	* state:前0后1
	*/
	function getCaption(obj,sign,state) {
		if(!obj) return ''
		var index = obj.lastIndexOf(sign);
		if(state == 0){
			obj = obj.substring(0, index);
		}else {
			obj = obj.substring(index + 1, obj.length);
		}
		return obj;
	}
	
	/**
	* 截取图片路径 https://www.fengiy.com/upload/abc.png
	* url:图片路径
	* 截取结果：/upload/abc.png
	*/
	function cutImgUrl(url) {
		if(!url) return ''
		let path = url.split("//");
		let index = path[1].indexOf('/');
		return path[1].substring(index + 1);
	}
	// function cutImgUrl(url) {
	// 	let path = url.split("//");
	// 	let start = path[1].indexOf('/', path[1].indexOf('/') + 1);
	// 	return path[1].substring(start + 1);
	// }
	
	/**
	* 数组排序 正序 [1,5,2,7,6] => [1,2,5,6,7]
	* array:数组
	*/
	function sortArr(array){
		if(!array.length) return []
		return array.sort(sortNumber);
	}
	
	/**
	* 数组对象排序 [{id:1, name: '数组1'},{id:2, name: '数组2'}]
	* array:数组
	* key:以数组中的某个值为准
	*/
	function sortByKey(array, key){
	    return array.sort(function(a,b){
	        var x = a[key];
	        var y = b[key];
	        return((x<y)?-1:((x>y)?1:0));
	    })
	}
	
	/**
	 * 数组以某个值为准去重
	 * array:数组
	 * key:以数组中的某个值为准
	 */
	 function uniqueByKey(arr, key) {
		 const res = new Map();
		 return arr.filter((a) => !res.has(a[key]) && res.set(a[key], 1));
	 }
	
	/**
	* 字符串隐藏为*号
	* str:字符串
	* frontLen:前面需要保留几位
	* endLen:后面需要保留几位
	*/
	function hideStr(str,frontLen,endLen) { 
		var len = str.length-frontLen-endLen;
		var xing = '';
		for (var i=0; i<len; i++) {
			xing += '*';
		}
		return str.substring(0,frontLen)+xing+str.substring(str.length-endLen);
	}
	
	/**
	* 银行卡号每4位加空格
	* val:银行卡号——字条串
	*/
	function BankCardSpaces(val) {
		let cardNumber = val.toString();
		return cardNumber.replace(/(\d{4})(?=\d)/g, '$1 ');
	}
	
	/**
	* 金额格式化 100,10.00
	* money:金额
	* val:保留几位小数，默认2位
	*/
	function formatMoney(money, val=2) {
		return parseFloat(money).toFixed(val).toString().split('').reverse().join('').replace(/(\d{3})/g, '$1,').replace(/\,$/, '').split('').reverse().join('');
	}
	
	/*
	 * 处理富文本里的图片宽度自适应
	 * 1.去掉img标签里的style、width、height属性
	 * 2.img标签添加style属性：max-width:100%;height:auto
	 * 3.修改所有style里的width属性为max-width:100%
	 * 4.去掉<br/>标签
	 * @param html
	 * @returns {void|string|*}
	 */
	function formatRichText(html) {
		if(!html) return ''
		// 处理富文本默认图片，视频大小
		let newContent = html.replace(/<img[^>]*>/gi, function(match, capture) {
			match = match.replace(/style="[^"]+"/gi, '').replace(/style='[^']+'/gi, '');
			match = match.replace(/width="[^"]+"/gi, '').replace(/width='[^']+'/gi, '');
			match = match.replace(/height="[^"]+"/gi, '').replace(/height='[^']+'/gi, '');
			return match;
		}).replace(/<video[^>]*>/gi, function(match, capture) {
			match = match.replace(/style="[^"]+"/gi, '').replace(/style='[^']+'/gi, '');
			match = match.replace(/width="[^"]+"/gi, '').replace(/width='[^']+'/gi, '');
			match = match.replace(/height="[^"]+"/gi, '').replace(/height='[^']+'/gi, '');
			return match;
		});
		newContent = newContent.replace(/style="[^"]+"/gi, function(match, capture) {
			match = match.replace(/width:[^;]+;/gi, 'max-width:100%;').replace(/width:[^;]+;/gi, 'max-width:100%;');
			return match;
		});
		newContent = newContent.replace(/\<img/gi, '<img style="max-width:100%;height:auto;display:inline-block;"').replace(/\<video/gi,
			'<video style="width:100%;display:inline-block;margin:10rpx auto;"');
		//去除table的宽度
		newContent = newContent.replace(/<table[^>]*>/gi, function(match, capture) {
			return match.replace(/width=\"(.*)\"/gi, '');
		});
		return newContent;
	}
	
	/** 是否图片格式
	 * @param {Object} value
	 */
	function image(value) {
	    const IMAGE_REGEXP = /\.(jpeg|jpg|gif|png|svg|webp|jfif|bmp|dpg)/i
	    return IMAGE_REGEXP.test(value)
	}
	
	/**
	 * 是否视频格式
	 * @param {Object} value
	 */
	function video(value) {
	    const VIDEO_REGEXP = /\.(mp4|mpg|mpeg|dat|asf|avi|rm|rmvb|mov|wmv|flv|mkv)/i
	    return VIDEO_REGEXP.test(value)
	}
	
	/**
	* uView虚拟navbar下，使用吸顶u-sticky计算偏移量
	* <u-sticky :offsetTop="$u.func.getStatusBarHeight()"></u-sticky>
	*/
	const getStatusBarHeight = () => {
		let statusBarHeight = 0
		statusBarHeight = vm.$u.sys().statusBarHeight + (vm.$u.sys().osName == 'ios' ? 44 : 48)
		return statusBarHeight * 2
	}
	
	/**
	 * 加法精度计算
	 * @author wjb
	 * 
	 * @param {Object} number 数字
	 */
	function bcadd(a, b){
	    var c, d, e;
	    try {
	        c = a.toString().split(".")[1].length
	    } catch(f) {
	        c = 0
	    }
	    try {
	        d = b.toString().split(".")[1].length
	    } catch(f) {
	        d = 0
	    }
	    return e = Math.pow(10, Math.max(c, d)),(this.bcmul(a, e) + this.bcmul(b, e)) / e
	}
	
	/**
	 * 减法精度计算
	 * @author wjb
	 * 
	 * @param {Object} number 数字
	 */
	function bcsub(a, b){
	    var c, d, e;
	    try {
	        c = a.toString().split(".")[1].length
	    } catch(f) {
	        c = 0
	    }
	    try {
	        d = b.toString().split(".")[1].length
	    } catch(f) {
	        d = 0
	    }
	    return e = Math.pow(10, Math.max(c, d)),(this.bcmul(a, e) - this.bcmul(b, e)) / e
	}
	
	/**
	 * 乘法精度计算
	 * @author wjb
	 * 
	 * @param {Object} number 数字
	 */
	function bcmul(a, b){
	    var c = 0,
	    d = a.toString(),
	    e = b.toString();
	    try {
	        c += d.split(".")[1].length
	    } catch(f) {}
	    try {
	        c += e.split(".")[1].length
	    } catch(f) {}
	    return Number(d.replace(".", "")) * Number(e.replace(".", "")) / Math.pow(10, c)
	}
	
	/**
	 * 除法精度计算
	 * @author wjb
	 * 
	 * @param {Object} number 数字
	 */
	function bcdiv(a, b){
	    var c, d, e = 0,
	    f = 0;
	    try {
	        e = a.toString().split(".")[1].length
	    } catch(g) {}
	    try {
	        f = b.toString().split(".")[1].length
	    } catch(g) {}
	    return c = Number(a.toString().replace(".", "")),d = Number(b.toString().replace(".", "")),this.bcmul(c / d, Math.pow(10, f - e))
	}
	
	/**
	* 处理业绩显示
	* num:业绩
	* 10000 则显示为1万
	*/
	function unitConvert(num) {
	    var moneyUnits = ["", "万", "亿"]
	    var dividend = 10000;
	    var curentNum = num;
	    //转换数字
	    var curentUnit = moneyUnits[0];
	    //转换单位
	    for (var i = 0; i <4; i++) {
	        curentUnit = moneyUnits[i]
	        if(strNumSize(curentNum)<5){
	            break;
	        }
	        curentNum = curentNum / dividend
	    }
	    return curentNum + curentUnit;
	}
	function strNumSize(tempNum){
	    var stringNum = tempNum.toString()
	    var index = stringNum.indexOf(".")
	    var newNum = stringNum;
	    if(index!=-1){
	        newNum = stringNum.substring(0,index)
	    }
	    return newNum.length
	}
	
	Vue.prototype.$u.func = {
		checkLogin,
		checkH5,
		paramsToObj,
		refreshPage,
		prePage,
		reloadPage,
		goTo,
		showToast,
		wxappPay,
		getDay,
		curentTime,
		transDate,
		timeChange,
		formatTime,
		formatTextArea,
		getCaption,
		cutImgUrl,
		sortArr,
		sortByKey,
		uniqueByKey,
		hideStr,
		BankCardSpaces,
		formatMoney,
		formatRichText,
		image,
		video,
		getStatusBarHeight,
		bcadd,
		bcsub,
		bcmul,
		bcdiv,
		unitConvert
	}
	// 将各个定义的方法，统一放进对象挂载到vm.$u.func(因为vm就是this，也即this.$u.func)下
}
export default {
	install
}