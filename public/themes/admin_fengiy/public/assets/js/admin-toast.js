/**
 * 后台轻提示默认体验：顶部胶囊、轻入场。不改业务回调。
 */
(function (w) {
	function assignDefaults(options) {
		var out = {};
		var k;
		if (options && typeof options === 'object') {
			for (k in options) {
				if (Object.prototype.hasOwnProperty.call(options, k)) {
					out[k] = options[k];
				}
			}
		}
		if (out.offset == null) {
			out.offset = '28px';
		}
		// 不能改 skin：layer.msg 默认 skin 是 layui-layer-msg，
		// 一旦覆盖成别的名字，胶囊样式全部套不上，看起来就只挪了位置。
		return out;
	}

	function mergeOptions(options) {
		var out = {};
		var k;
		if (options && typeof options === 'object') {
			for (k in options) {
				if (Object.prototype.hasOwnProperty.call(options, k)) {
					out[k] = options[k];
				}
			}
		}
		return out;
	}

	function patchLayer(layerObj) {
		if (!layerObj || typeof layerObj.msg !== 'function') {
			return;
		}
		if (!layerObj.msg.__cmfPatched) {
			var origMsg = layerObj.msg;
			layerObj.msg = function (content, options, end) {
				if (typeof options === 'function') {
					end = options;
					options = {};
				} else if (typeof options === 'number') {
					options = { time: options };
				} else if (!options) {
					options = {};
				}
				return origMsg.call(this, content, assignDefaults(options), end);
			};
			layerObj.msg.__cmfPatched = true;
		}
		if (typeof layerObj.confirm === 'function' && !layerObj.confirm.__cmfPatched) {
			var origConfirm = layerObj.confirm;
			layerObj.confirm = function (content, options, yes, cancel) {
				if (typeof options === 'function') {
					cancel = yes;
					yes = options;
					options = {};
				}
				options = mergeOptions(options);
				// 默认标题是「信息」，再加关闭叉会和右上角精灵图叠成两个关闭按钮
				if (options.title == null) {
					options.title = false;
				}
				if (options.closeBtn == null) {
					options.closeBtn = 0;
				}
				return origConfirm.call(this, content, options, yes, cancel);
			};
			layerObj.confirm.__cmfPatched = true;
		}
	}

	function tryPatchLayer() {
		if (w.layer) {
			patchLayer(w.layer);
		}
		if (w.layui && w.layui.layer) {
			patchLayer(w.layui.layer);
		}
	}

	function patchNoty() {
		if (!w.noty || w.noty.__cmfPatched) {
			return !!w.noty;
		}
		var orig = w.noty;
		var wrapped = function (opts) {
			opts = opts || {};
			if (opts.modal && !opts.buttons) {
				opts.modal = false;
			}
			return orig(opts);
		};
		wrapped.__cmfPatched = true;
		var k;
		for (k in orig) {
			if (Object.prototype.hasOwnProperty.call(orig, k)) {
				wrapped[k] = orig[k];
			}
		}
		w.noty = wrapped;
		return true;
	}

	tryPatchLayer();
	patchNoty();

	var n = 0;
	var timer = setInterval(function () {
		tryPatchLayer();
		patchNoty();
		n += 1;
		if (n > 80) {
			clearInterval(timer);
		}
	}, 100);
})(window);
