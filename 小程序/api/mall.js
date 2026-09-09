const order = {
    // 商品分类
    get_category: (params = {}) => uni.$u.post('/wxapp/goods/get_category', params),
    // 商品列表
    get_goods_list: (params = {}) => uni.$u.post('/wxapp/goods/get_goods_list', params),
    // 商品详情
    get_goods_detail: (params = {}) => uni.$u.post('/wxapp/goods/get_goods_detail', params),
    // 商品评论
    get_goods_comment: (params = {}) => uni.$u.post('/wxapp/goods/get_goods_comment', params),
    // 加入购物车
    add_cart: (params = {}) => uni.$u.post('/wxapp/goods/add_cart', params),
    // 收藏
    col_goods: (params = {}) => uni.$u.post('/wxapp/goods/col_goods', params),
    // 我的收藏
    my_col: (params = {}) => uni.$u.post('/wxapp/goods/my_col', params),
    // 我的收货地址
    get_address_info: (params = {}) => uni.$u.post('/wxapp/user/get_address_info', params),
    // 设置收货地址
    post_address_data: (params = {}) => uni.$u.post('/wxapp/user/post_address_data', params),
    // 我的购物车
    my_cart: (params = {}) => uni.$u.post('/wxapp/goods/my_cart', params),
    // 改变购物车数量
    set_cart: (params = {}) => uni.$u.post('/wxapp/goods/set_cart', params),
    // 下单
    add_order: (params = {}) => uni.$u.post('/wxapp/order/add_order', params),
    // 支付
    pay_order: (params = {}) => uni.$u.post('/wxapp/pay/pay_order', params),
    // 我的订单
    my_order: (params = {}) => uni.$u.post('/wxapp/order/my_order', params),
    // 我的订单详情
    order_detail: (params = {}) => uni.$u.post('/wxapp/order/order_detail', params),
    // 取消订单
    cancel_order: (params = {}) => uni.$u.post('/wxapp/order/cancel_order', params),
    // 商家信息
    get_shop: (params = {}) => uni.$u.post('/wxapp/shop/get_shop', params),
    // 商家订单统计
    get_shop_order_total: (params = {}) => uni.$u.post('/wxapp/shop/get_shop_order_total', params),
    // 商家收益记录
    get_log_list: (params = {}) => uni.$u.post('/wxapp/balance/get_log_list', params),
    // 商家提现
    withdraw: (params = {}) => uni.$u.post('/wxapp/balance/withdraw', params),
    // 商家商品信息
    get_shop_goods_list: (params = {}) => uni.$u.post('/wxapp/goods/get_shop_goods_list', params),
    // 添加商品信息
    add_goods: (params = {}) => uni.$u.post('/wxapp/goods/add_goods', params),
    // 能使用的优惠卷
    get_can_use_coupon: (params = {}) => uni.$u.post('/wxapp/order/get_can_use_coupon', params),
    // 地提点
    goods_position: (params = {}) => uni.$u.post('/wxapp/order/goods_position', params),
    // 确认收货
    confirm_order: (params = {}) => uni.$u.post('/wxapp/order/confirm_order', params),
    // 评价
    add_order_comment: (params = {}) => uni.$u.post('/wxapp/order/add_comment', params),
    // 快递信息
    get_express_info: (params = {}) => uni.$u.post('/wxapp/order/get_express_info', params),
    // 商家订单
    get_shop_order_list: (params = {}) => uni.$u.post('/wxapp/shop/get_shop_order_list', params),
    // 商家发货
    shop_send: (params = {}) => uni.$u.post('/wxapp/shop/shop_send', params),
    // 商家优惠卷列表
    get_shop_coupon_list: (params = {}) => uni.$u.post('/wxapp/shop/get_shop_coupon_list', params),
    // 领取商家优惠卷
    receive_coupon: (params = {}) => uni.$u.post('/wxapp/shop/receive_coupon', params),
    // 我领取的商家优惠卷
    my_shop_coupon: (params = {}) => uni.$u.post('/wxapp/coupon/my_shop_coupon', params),
    // 商品排行榜
    get_goods_ranking_list: (params = {}) => uni.$u.post('/wxapp/goods/get_goods_ranking_list', params),
    
    // 申请开票
    addInvoice: (params = {}) => uni.$u.post('/wxapp/order/add_invoice', params),
    // 开票详情
    getInvoice: (params = {}) => uni.$u.post('/wxapp/order/get_invoice', params),
}
export default order