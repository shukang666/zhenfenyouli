<template>
	<text :style="{color, 'font-weight': weight}" :class="(lineThrough ? 'u-line-through' : '') + ' price-format'">
		<text v-if="showSubscript" :style="{'font-size': subscriptSize + 'rpx', 'margin-right': '2rpx'}">¥</text>
		<text :style="{'font-size': firstSize + 'rpx', 'margin-right': '1rpx'}">{{priceSlice.first}}</text>
		<text v-if="priceSlice.second" :style="{'font-size': secondSize + 'rpx'}">.{{priceSlice.second}}</text>
	</text>
</template>

<script>
	export default {
		name: 'v-price-format',
		data() {
			return {
				priceSlice: {}
			};
		},
		props: {
			// 小数点前数字大小
			firstSize: {
				type: [String, Number],
				default: 24
			},
			// 小数点后数字大小
			secondSize: {
				type: [String, Number],
				default: 24
			},
			// 颜色
			color: {
				type: String
			},
			// 加粗
			weight: {
				type: [String, Number],
				default: 400
			},
			// 金额
			price: {
				type: [String, Number],
				default: ""
			},
			// 是否显示人民币符号
			showSubscript: {
				type: Boolean,
				default: true
			},
			// 人民币符号大小
			subscriptSize: {
				type: [String, Number],
				default: 24
			},
			// 是否划线价
			lineThrough: {
				type: Boolean,
				default: false
			}
		},
		created() {
			this.priceFormat()
		},
		watch: {
			price(val) {
				this.priceFormat()
			}
		},
		methods: {
			priceFormat() {
				let { price } = this;
				let priceSlice = {}
				if (price !== null && price !== '' && price !== undefined) {
					price = parseFloat(price);
					price = String(price).split('.');
					priceSlice.first = price[0];
					priceSlice.second = price[1];
					this.priceSlice = priceSlice
				}

			}
		}
	};
</script>
<style>
	.price-format {
		font-family: Avenir, SourceHanSansCN, PingFang SC, Arial, Hiragino Sans GB, Microsoft YaHei, sans-serif;
	}
</style>
