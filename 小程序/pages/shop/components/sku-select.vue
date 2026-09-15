<template>
	<view>
		<u-popup v-model="show" mode="bottom" closeable border-radius="10" safe-area-inset-bottom>
			<view class="u-m-30">
				<view class="u-flex u-col-bottom">
					<u-image :src="thumb[0] || Info.thumb" width="150" height="150" border-radius="8"
						@click="previewImage"></u-image>
					<view class="u-font-24 u-m-l-30">
						<view class="u-font-bold">
							{{Info.goods_name}}
						</view>
						<view class="u-type-error u-m-t-10">
							<view class="u-font-35 u-font-bold">
								<text v-if="Number(price) || Number(Info.price)">{{price ? price : Info.price}}元</text>
								<text v-if="(Number(price) || Number(Info.price)) && Info.point"> + </text>
								<text v-if="Info.point">{{Info.point}}积分</text>
							</view>
						</view>
						<view class="u-m-t-10">库存：{{stock}}</view>
					</view>
				</view>

				<block v-if="process_attribute.length">
					<u-line color="#F7F7F7" margin="30rpx 0 20rpx"></u-line>
					<view class="u-col-top" v-for="(attr,val) in process_attribute" :key="val">
						<view class="u-font-28 u-font-bold u-m-b-15">{{attr.name}}：</view>
						<view class="u-flex u-flex-wrap u-m-b-20">
							<view class="attr-box" v-for="(item, index) in attr.item" :key="index">
								<block v-if="item.disabled">
									<button plain size="mini" disabled
										:style="{border: '1px #F7F7F7 solid', background: '#F7F7F7'}">{{ item.name }}</button>
									<view class="tip">缺货</view>
								</block>
								<button plain size="mini" v-else
									:style="{border: item.actived ? '1px #FCBD71 solid' : '1px #F7F7F7 solid', background: item.actived ? '#FDF6EC' : '#F7F7F7'}"
									@click="skuClick(val, index)">{{ item.name }}</button>
							</view>
						</view>
					</view>
				</block>

				<!-- 5兑换区 只可购买1件 -->
				<block v-if="Info.position_id != 5">
					<u-line color="#F7F7F7" margin="10rpx 0 30rpx"></u-line>
					<view class="u-flex u-row-between u-col-center">
						<view class="u-font-28 u-font-bold">数量：</view>
						<u-number-box v-model="count" :min="stock ? 1 : 0" :max="stock" disabled-input
							@change="valChange"></u-number-box>
					</view>
				</block>

				<view class="submit-box">
					<block v-if="type == 'addcart'">
						<view class="fill btn cart" @click="submitBtn('addcart')">加入购物车</view>
					</block>
					<block v-else-if="type == 'buy'">
						<view class="fill btn" @click="submitBtn('buy')">立即购买</view>
					</block>
					<block v-else>
						<view class="btn cart" @click="submitBtn('addcart')">加入购物车</view>
						<view class="btn" @click="submitBtn('buy')">立即购买</view>
					</block>
				</view>
			</view>
		</u-popup>
	</view>
</template>

<script>
	export default {
		name: 'skuSelect',
		data() {
			return {
				show: false,
				Info: {}, //商品详情
				type: '', //addcart加入购物车 buy立即购买
				// sku字段分隔符
				separator: ';',
				process_attribute: [],
				process_sku: [],
				// 当前选中 sku 的库存及价格区间
				stock: 0,
				thumb: [],
				price: '', //会员价格
				user_price: '', //当前用户可享受的价格
				count: 1 //已选数量
			};
		},
		methods: {
			//获取规格数据 isInit 是否初始化SKU数据process_attribute
			init(type, data, specInfo) {
				this.Info = this.$u.deepClone(data);
				this.type = type;
				let sku = this.Info.sku;
				if (!sku.length) {
					this.stock = data.stock;
					this.show = true;
					return
				}
				if (!this.process_attribute.length) {
					sku.attr_list.map(attr => {
						let temp = {
							name: attr.name
						}
						temp.item = attr.item.map(item => {
							return {
								name: item,
								actived: false,
								disabled: false
							}
						})
						this.process_attribute.push(temp)
					})
					//处理默认选中数据
					if (specInfo.sku) {
						let arry = specInfo.sku.split(';');
						arry.forEach(item => {
							this.process_attribute.forEach(obj => {
								obj.item.forEach(v => {
									if (item == v.name) v.actived = true;
								})
							})
						})
					}

					// 对 sku 数据进行加工，并存入 process_sku 中
					sku.sku_list.map(v => {
						var combArr = this.arrayCombine(v.sku.split(this.separator))
						for (var j = 0; j < combArr.length; j++) {
							var key = combArr[j].join(this.separator)
							if (this.process_sku[key]) {
								// 库存累加，价格添加进数组
								this.process_sku[key].stock += v.stock
								this.process_sku[key].images.push(v.image)
								this.process_sku[key].prices.push(v.price)
								this.process_sku[key].user_prices.push(v.user_price)
							} else {
								this.process_sku[key] = {
									stock: v.stock,
									images: [v.image],
									prices: [v.price],
									user_prices: [v.user_price]
								}
							}
						}
					})
					this.getStockPrice()
				}

				// 更新数据视图
				this.process_sku = Object.assign({}, this.process_sku);
				this.skuCheck()
			},
			arrayCombine(targetArr) {
				var resultArr = []
				for (var n = 0; n <= targetArr.length; n++) {
					var flagArrs = this.getFlagArrs(targetArr.length, n)
					while (flagArrs.length) {
						var flagArr = flagArrs.shift()
						var combArr = Array(targetArr.length)
						for (var i = 0; i < targetArr.length; i++) {
							if (flagArr[i]) {
								combArr[i] = targetArr[i]
							}
						}
						resultArr.push(combArr)
					}
				}
				return resultArr
			},
			getFlagArrs(m, n) {
				var flagArrs = [],
					flagArr = [],
					isEnd = false
				for (var i = 0; i < m; i++) {
					flagArr[i] = i < n ? 1 : 0
				}
				flagArrs.push(flagArr.concat())
				// 当n不等于0并且m大于n的时候进入
				if (n && m > n) {
					while (!isEnd) {
						var leftCnt = 0
						for (var i = 0; i < m - 1; i++) {
							if (flagArr[i] == 1 && flagArr[i + 1] == 0) {
								for (var j = 0; j < i; j++) {
									flagArr[j] = j < leftCnt ? 1 : 0
								}
								flagArr[i] = 0
								flagArr[i + 1] = 1
								var aTmp = flagArr.concat()
								flagArrs.push(aTmp)
								if (aTmp.slice(-n).join('').indexOf('0') == -1) {
									isEnd = true
								}
								break
							}
							flagArr[i] == 1 && leftCnt++
						}
					}
				}
				return flagArrs
			},
			skuClick(key1, key2) {
				if (!this.process_attribute[key1].item[key2].disabled) {
					this.process_attribute[key1].item.map((item, index) => {
						item.actived = index == key2 ? !item.actived : false
					})
					this.skuCheck()
					this.getStockPrice()
				}
			},
			skuCheck() {
				let sku = []
				this.process_attribute.map(attr => {
					let name = ''
					attr.item.map(item => {
						if (item.actived) {
							name = item.name
						}
					})
					sku.push(name)
				})
				this.stock = this.process_sku[sku.join(this.separator)].stock;
				if (!this.stock) this.count = 0;
				let arr = this.process_sku[sku.join(this.separator)].images;
				this.thumb = arr.filter(v => {
					return v && v.trim()
				});
				this.price = Math.min.apply(Math, this.process_sku[sku.join(this.separator)].prices)
				this.user_price = Math.min.apply(Math, this.process_sku[sku.join(this.separator)].user_prices)
				//处理会员价格列表显示
				this.$emit('update-specInfo', sku.join(this.separator));

				this.show = true;
			},
			//预览图片
			previewImage() {
				let img = this.thumb[0] || this.Info.thumb;
				uni.previewImage({
					urls: [img]
				});
			},
			getStockPrice() {
				this.process_attribute.map(attr => {
					attr.item.map(item => {
						item.disabled = false
					})
				})
				let count = 0
				let i = 0
				this.process_attribute.map((attr, index) => {
					let flag = false
					attr.item.map(item => {
						if (item.actived) {
							flag = true
						}
					})
					if (!flag) {
						count += 1
						i = index
					}
				})
				// 当只有一组规格没选时
				if (count == 1) {
					this.process_attribute[i].item.map(item => {
						let sku = []
						let text = item.name
						this.process_attribute.map((attr, index) => {
							if (index != i) {
								attr.item.map(item2 => {
									if (item2.actived) {
										sku.push(item2.name)
									}
								})
							} else {
								sku.push(text)
							}
						})
						if (this.process_sku[sku.join(this.separator)].stock == 0) {
							item.disabled = true
						}
					})
				}
				// 当所有规格都有选时
				if (count == 0) {
					this.process_attribute.map((attr, index) => {
						let i = index
						this.process_attribute[index].item.map(item => {
							if (!item.actived) {
								let sku = []
								let text = item.name
								this.process_attribute.map((list, index) => {
									if (index != i) {
										list.item.map(item2 => {
											if (item2.actived) {
												sku.push(item2.name)
											}
										})
									} else {
										sku.push(text)
									}
								})
								if (this.process_sku[sku.join(this.separator)].stock == 0) {
									item.disabled = true
								}
							}
						})
					})
				}
			},
			//数量选择
			valChange(e) {
				this.count = e.value;
			},
			//提交选择
			submitBtn(type) {
				let sku = []
				this.isSelectSKU = this.process_attribute.every(attr => {
					let filter = attr.item.filter(v => v.actived)
					if (filter.length != 0) {
						sku.push(filter[0].name)
					}
					return filter.length != 0
				})
				if (this.isSelectSKU) {
					setTimeout(() => {
						if (!this.count) return this.$u.toast('请选择购买数量');
						var itemSku = sku.join(this.separator)
						if (this.Info.sku_list) {
							this.Info.sku_list.forEach(item => {
								if (JSON.stringify(item.sku) == JSON.stringify(itemSku)) {
									this.skuItem = item
								}
							})
							this.$emit('change', {
								type: type,
								shop_info: this.Info.shop_info,
								is_take: this.Info.is_take,
								is_express: this.Info.is_express,
								count: this.count,
								sku: sku.join(this.separator),
								skuItem: this.skuItem,
								goods_id: this.Info.id,
								thumb: this.Info.thumb,
								price: this.skuItem.price,
								goods_name: this.Info.goods_name,
								point: this.Info.point
							})
						} else {
							this.$emit('change', {
								type: type,
								shop_info: this.Info.shop_info,
								is_take: this.Info.is_take,
								is_express: this.Info.is_express,
								count: this.count,
								goods_id: this.Info.id,
								thumb: this.Info.thumb,
								price: this.Info.price,
								goods_name: this.Info.goods_name,
								point: this.Info.point
							})
						}
					}, 100)

					// this.$u.toast(`当前SKU为：${sku.join(this.separator)}`)
				} else {
					this.$u.toast('请选择完整商品属性')
				}
			}
		}
	}
</script>

<style lang="scss" scoped>
	.consumer-coupon {
		display: flex;
		align-items: center;
		background-color: #ffe9ba;
		border-radius: 6rpx;
		margin-right: 5rpx;
		overflow: hidden;

		.price-name {
			background-color: #101010;
			padding: 4rpx 12rpx;
			font-size: 22rpx;
			color: #ffd4b7;
			position: relative;
			overflow: hidden;

			&.coupons {
				font-size: 26rpx;
				background-color: #f05725;
			}

			&::after {
				content: '';
				display: block;
				width: 20rpx;
				height: 20rpx;
				position: absolute;
				right: -15rpx;
				background-color: #ffe9ba;
				border-radius: 50%;
				top: 50%;
				transform: translateY(-50%);
				box-sizing: border-box;
			}
		}
	}

	.attr-box {
		position: relative;
		margin: 0 20rpx 18rpx 0;

		.tip {
			position: absolute;
			top: -10rpx;
			right: -10rpx;
			background: #FF7E15;
			font-size: 20rpx;
			color: #FFFFFF;
			padding: 0 8rpx 2rpx;
			border-radius: 4px 0 4px 0;
			transform: scale(0.8);
			z-index: 99;
		}
	}

	.submit-box {
		width: 100%;
		display: flex;
		font-size: 28rpx;
		align-items: center;
		margin-top: 50rpx;
		background: #18AF38;
		border-radius: 25px;

		.btn {
			width: 50%;
			text-align: center;
			padding: 20rpx 0;
			color: #ffffff;
			position: relative;

			&.fill {
				width: 100%;

				&.cart {
					border-radius: 25px;
				}
			}

			&.cart {
				background: #FFAC30;
				border-radius: 25px 0 0 25px;
			}
		}
	}
</style>