#### H5/小程序——复制

#### 引入
```
import copy from "@/utils/clipboard/clipboard.js"
```

#### 使用

```
handleCopy(data){
	let that = this;
	copy.getClipboardData(data, (res) => {
		// #ifdef H5 || MP-ALIPAY
		if(res) this.$u.toast('复制成功')
		// #endif
	})
}
```