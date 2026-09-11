/* ==================================================================
 * 后台「删除二次校验」独立脚本
 * ------------------------------------------------------------------
 * 为什么单独成文件、而不是塞进 admin.js 末尾：
 *   JS 里一个 <script> 只要有未捕获异常，后面所有代码都不会再执行。
 *   放在 1400+ 行的 admin.js 末尾时，只要前面任何一行抛错
 *   （例如 Wind.css is not a function），或者浏览器缓存了旧的
 *   admin.js，整个校验就会【静默失效】——点删除直接跳转 URL 删除。
 *   独立文件 + header.html 引入 + 版本号查询串，可以彻底避免这两件事。
 *
 * 适用范围：所有 `.js-ajax-delete`（后台单条删除的统一入口）
 * 不影响　：批量删除（走 form.js-ajax-form + button.js-ajax-submit，另一条通道）
 * 实现方式：在 document 捕获阶段抢先拦截点击 → 校验通过后重新派发一次
 *           原生点击，交回原有 artDialog 流程真正执行删除请求。
 * 补充说明：勾选「今日不再弹出」只跳过本校验，原有普通确认仍会保留，
 *           不会出现"点一下就静默删除"的情况。
 *
 * ★ 服务端校验（必须与本文件配套，否则前端形同虚设）
 *   本文件只是**前端**拦截——绕过页面直接 POST /admin/xxx/delete.html?id=2
 *   照样能删。真正的关口在 app/middleware/AdminDeleteGuard.php：
 *   它要求所有「删除类」请求带上参数 _dc_token，且值与 session 里的令牌一致。
 *   本文件负责用 $.ajaxPrefilter 把该令牌自动补到删除类 ajax 请求上；
 *   令牌存在 cookie cmf_dc_token 里，由那个中间件在页面响应时下发。
 *   两边的参数名/令牌名必须一致：_dc_token / cmf_dc_token。
 *
 * 修改本文件后，请同步把 header.html 里引入处的 ?v= 版本号 +1，
 * 否则浏览器会继续用缓存的旧文件。
 * ================================================================== */
(function () {
    'use strict';

    if (window.CmfDeleteConfirm) {
        return;
    }

    // 依赖 jQuery；没加载就直接退出，不要抛错影响页面其它脚本
    if (!window.jQuery) {
        if (window.console && console.warn) {
            console.warn('[删除校验] 未检测到 jQuery，脚本未启用');
        }
        return;
    }

    try {
        var TOKEN_COOKIE = 'cmf_dc_token'; // 服务端下发的删除校验令牌（见 app/middleware/AdminDeleteGuard.php）
        var TOKEN_PARAM  = '_dc_token';    // 删除请求必须携带的参数名，必须与服务端一致
        var MUTE_KEY  = 'cmf_delete_confirm_mute'; // 存放免打扰日期，形如 2026-9-11
        var NEED_TEXT = '确认';
        var bypass    = false;  // 校验通过后重新派发点击时置 true，避免被再次拦截
        var $mask     = null;   // 弹窗根节点
        var $input    = null;
        var $ok       = null;
        var onPass    = null;   // 校验通过后的回调

        /* ---------------- 工具 ---------------- */

        function todayKey() {
            var d = new Date();
            return d.getFullYear() + '-' + (d.getMonth() + 1) + '-' + d.getDate();
        }

        function isMutedToday() {
            try {
                return localStorage.getItem(MUTE_KEY) === todayKey();
            } catch (e) {
                return false;
            }
        }

        function muteToday() {
            try {
                localStorage.setItem(MUTE_KEY, todayKey());
            } catch (e) {
            }
        }

        function warn(msg, err) {
            if (window.console && console.warn) {
                console.warn('[删除校验] ' + msg, err || '');
            }
        }

        /* ---------------- 服务端校验令牌 ---------------- */

        /**
         * 读服务端下发的令牌，两条来源依次尝试：
         *   ① window.CMF_DC_TOKEN —— 服务端直接内联在页面里的（不依赖 cookie，最可靠）
         *   ② cookie cmf_dc_token —— 服务端下发的会话 cookie
         * 都读不到就返回空串（此时删除会被服务端拦下，控制台会打警告）
         *
         * 为什么要有 ①：cookie 可能因 path/域、浏览器隐私设置、本地残留等原因读不到，
         * 只靠 cookie 的话这类问题会表现成「点删除永远报未通过安全校验」，很难自查。
         */
        function readToken() {
            try {
                if (window.CMF_DC_TOKEN) {
                    return String(window.CMF_DC_TOKEN);
                }
            } catch (e) {
            }

            try {
                var m = document.cookie.match(new RegExp('(?:^|;\\s*)' + TOKEN_COOKIE + '=([^;]*)'));
                return m ? decodeURIComponent(m[1]) : '';
            } catch (e) {
                return '';
            }
        }

        // 是不是删除类请求地址。
        // 判断口径比服务端 AdminDeleteGuard::isDeleteAction 更宽松（多带参数不影响），
        // 这样不会出现「服务端要令牌、前端没带」的情况。
        function isDeleteUrl(url) {
            var path = String(url || '').split('#')[0].split('?')[0];
            return /(^|\/|_|-)del/i.test(path);
        }

        /**
         * 给所有删除类 ajax 请求自动补上服务端要求的令牌参数。
         *
         * 为什么用 $.ajaxPrefilter 而不是去改 admin.js：
         * 后台删除有两个来源——
         *   ① admin.js 的 .js-ajax-delete（$.ajax，type:post，不带任何 data）
         *   ② 批量删除（form.js-ajax-form + button.js-ajax-submit）与页面自定义删除按钮
         * 不管哪条通道最后都经过 $.ajax，所以在这里统一补参数最稳，也不用碰 admin.js。
         */
        if ($.ajaxPrefilter) {
            $.ajaxPrefilter(function (options) {
                if (!options || !options.url || !isDeleteUrl(options.url)) {
                    return;
                }

                var token = readToken();

                if (!token) {
                    // 走到这里说明服务端既没内联 window.CMF_DC_TOKEN、也没下发 cookie，
                    // 排查顺序：① 页面响应是否被缓存成旧 HTML ② 响应是不是非 HTML（接口/下载）
                    warn('页面未取到删除校验令牌（window.CMF_DC_TOKEN / ' + TOKEN_COOKIE + '），服务端会拒绝本次删除');
                    return;
                }

                var data = options.data;

                try {
                    if (window.FormData && data instanceof window.FormData) {
                        data.append(TOKEN_PARAM, token);
                    } else if (typeof data === 'string') {
                        options.data = data + (data ? '&' : '') + TOKEN_PARAM + '=' + encodeURIComponent(token);
                    } else if (data && typeof data === 'object') {
                        data[TOKEN_PARAM] = token;
                    } else {
                        options.data = TOKEN_PARAM + '=' + encodeURIComponent(token);
                    }
                } catch (e) {
                    warn('追加删除校验令牌失败', e);
                }
            });
        }

        /* ---------------- 样式 ---------------- */

        function injectStyle() {
            if (document.getElementById('cmf-dc-style')) {
                return;
            }

            var css = [
                '.cmf-dc-mask{position:fixed;left:0;top:0;right:0;bottom:0;background:rgba(0,0,0,.45);',
                'z-index:2147483000;display:none;align-items:center;justify-content:center;padding:16px;',
                'box-sizing:border-box;}',
                '.cmf-dc-mask.cmf-dc-show{display:flex;}',
                '.cmf-dc-box{width:100%;max-width:400px;background:#fff;border-radius:8px;overflow:hidden;',
                'box-shadow:0 8px 32px rgba(0,0,0,.22);font-size:14px;line-height:1.5;color:#333;text-align:left;}',
                '.cmf-dc-head{display:flex;align-items:center;padding:14px 18px;font-size:15px;',
                'font-weight:600;border-bottom:1px solid #f0f0f0;}',
                '.cmf-dc-warn{display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;',
                'margin-right:8px;border-radius:50%;background:#ff4d4f;color:#fff;font-size:12px;',
                'font-style:normal;font-weight:700;}',
                '.cmf-dc-body{padding:16px 18px 0;}',
                '.cmf-dc-tip{color:#666;margin-bottom:14px;}',
                '.cmf-dc-tip b,.cmf-dc-label b{color:#ff4d4f;}',
                '.cmf-dc-label{margin-bottom:8px;}',
                '.cmf-dc-input{width:100%;box-sizing:border-box;height:38px;padding:0 12px;border:1px solid #d9d9d9;',
                'border-radius:4px;outline:none;font-size:14px;color:#333;transition:border-color .2s,box-shadow .2s;}',
                '.cmf-dc-input:focus{border-color:#2D8CF0;box-shadow:0 0 0 2px rgba(45,140,240,.12);}',
                '.cmf-dc-input.cmf-dc-err{border-color:#ff4d4f;box-shadow:0 0 0 2px rgba(255,77,79,.12);}',
                '.cmf-dc-mute{display:flex;align-items:flex-start;margin:12px 0 16px;color:#888;',
                'font-size:12px;line-height:1.6;cursor:pointer;font-weight:400;}',
                '.cmf-dc-mute input{margin:2px 6px 0 0;flex:none;}',
                '.cmf-dc-mute em{font-style:normal;color:#bbb;}',
                '.cmf-dc-foot{display:flex;justify-content:flex-end;padding:12px 18px 16px;border-top:1px solid #f0f0f0;}',
                '.cmf-dc-btn{min-width:88px;height:34px;padding:0 12px;border:1px solid #d9d9d9;border-radius:4px;',
                'background:#fff;color:#666;font-size:14px;cursor:pointer;outline:none;}',
                '.cmf-dc-btn+.cmf-dc-btn{margin-left:10px;}',
                '.cmf-dc-cancel:hover{border-color:#2D8CF0;color:#2D8CF0;}',
                '.cmf-dc-ok{border-color:#ff4d4f;background:#ff4d4f;color:#fff;}',
                '.cmf-dc-ok:hover{background:#ff7875;border-color:#ff7875;}',
                '.cmf-dc-ok[disabled]{border-color:#ffd6d6;background:#ffd6d6;color:#fff;cursor:not-allowed;}',
                '@media (max-width:479px){.cmf-dc-btn{flex:1;}.cmf-dc-btn+.cmf-dc-btn{margin-left:8px;}}'
            ].join('');

            $('<style id="cmf-dc-style" type="text/css"></style>').text(css).appendTo('head');
        }

        /* ---------------- 弹窗 ---------------- */

        function close() {
            if ($mask) {
                $mask.removeClass('cmf-dc-show');
            }
            onPass = null;
        }

        function build() {
            if ($mask) {
                return $mask;
            }

            injectStyle();

            $mask = $(
                '<div class="cmf-dc-mask">' +
                    '<div class="cmf-dc-box">' +
                        '<div class="cmf-dc-head"><i class="cmf-dc-warn">!</i>删除确认</div>' +
                        '<div class="cmf-dc-body">' +
                            '<div class="cmf-dc-tip">此操作将<b>永久删除</b>该条数据，删除后无法恢复。</div>' +
                            '<div class="cmf-dc-label">请输入「<b>确认</b>」两个字以继续</div>' +
                            '<input type="text" class="cmf-dc-input" placeholder="请输入：确认"' +
                            ' autocomplete="off" maxlength="10">' +
                            '<label class="cmf-dc-mute">' +
                                '<input type="checkbox">' +
                                '<span>今日不再弹出此校验</span>' +
                            '</label>' +
                        '</div>' +
                        '<div class="cmf-dc-foot">' +
                            '<button type="button" class="cmf-dc-btn cmf-dc-cancel">取消</button>' +
                            '<button type="button" class="cmf-dc-btn cmf-dc-ok" disabled>确认删除</button>' +
                        '</div>' +
                    '</div>' +
                '</div>'
            ).appendTo('body');

            $input = $mask.find('.cmf-dc-input');
            $ok    = $mask.find('.cmf-dc-ok');

            // 输入内容实时校验，只有完全等于「确认」才允许提交
            $input.on('input keyup change', function () {
                var val     = $.trim($input.val());
                var matched = (val === NEED_TEXT);
                $ok.prop('disabled', !matched);
                $input.toggleClass('cmf-dc-err', val !== '' && !matched);
            });

            // 回车即提交
            $input.on('keydown', function (e) {
                if (e.keyCode === 13) {
                    e.preventDefault();
                    if (!$ok.prop('disabled')) {
                        $ok.trigger('click');
                    }
                }
            });

            // 取消 / 点击遮罩关闭
            $mask.find('.cmf-dc-cancel').on('click', close);
            $mask.on('click', function (e) {
                if (e.target === $mask[0]) {
                    close();
                }
            });

            // ESC 关闭
            $(document).on('keydown.cmfDeleteConfirm', function (e) {
                if (e.keyCode === 27 && $mask.hasClass('cmf-dc-show')) {
                    close();
                }
            });

            // 确认删除
            $ok.on('click', function () {
                if ($ok.prop('disabled')) {
                    return;
                }
                if ($mask.find('.cmf-dc-mute input').prop('checked')) {
                    muteToday();
                }

                var fn = onPass;
                onPass = null;
                close();

                if (typeof fn === 'function') {
                    fn();
                }
            });

            return $mask;
        }

        function open(callback) {
            build();
            onPass = callback;

            $input.val('');
            $mask.find('.cmf-dc-mute input').prop('checked', false);
            $ok.prop('disabled', true);
            $mask.addClass('cmf-dc-show');

            setTimeout(function () {
                try {
                    $input.focus();
                } catch (e) {
                }
            }, 60);
        }

        /* ---------------- 交回原有流程 ---------------- */

        /**
         * 重新派发一次点击，交给 admin.js 里原有的 artDialog 流程执行删除。
         *
         * 用 dispatchEvent 而不是 jQuery 的 trigger：trigger 会同时走合成冒泡
         * 与原生事件，导致原有弹窗被触发两次。
         * 同时 dispatchEvent 的返回值能告诉我们原流程是否真的接管了本次点击
         * （返回 false = 有处理器调用了 preventDefault）。
         */
        function replayClick($el) {
            var el = $el[0];

            if (!el || typeof el.click !== 'function') {
                return;
            }

            if (window.MouseEvent) {
                var ev = null;
                try {
                    ev = new MouseEvent('click', {bubbles: true, cancelable: true, view: window});
                } catch (e) {
                    ev = null;
                }
                if (ev) {
                    var notCancelled = el.dispatchEvent(ev);
                    if (notCancelled) {
                        warn('原有删除流程未接管本次点击（admin.js 可能执行中断，或 wind.js 未加载），已回退为链接默认跳转。');
                    }
                    return;
                }
            }

            el.click();
        }

        /* ---------------- 拦截单条删除 ---------------- */

        // 用捕获阶段监听，抢在 admin.js 绑定在 body 上的委托之前拿到事件
        document.addEventListener('click', function (e) {
            if (bypass) {
                return;
            }

            var $el;
            try {
                $el = $(e.target).closest('.js-ajax-delete');
            } catch (err) {
                return;
            }

            if (!$el.length) {
                return;
            }

            // 已勾选「今日不再弹出」：直接放行，交给原有普通确认流程
            if (isMutedToday()) {
                return;
            }

            // 所有提前 return 都放在 preventDefault 之前，
            // 保证任何异常场景都不会把用户的点击吞掉
            e.preventDefault();
            e.stopPropagation();
            if (e.stopImmediatePropagation) {
                e.stopImmediatePropagation();
            }

            try {
                open(function () {
                    bypass = true;
                    try {
                        replayClick($el);
                    } finally {
                        bypass = false;
                    }
                });
            } catch (err) {
                // 弹窗都建不起来时，别让用户点了没反应，直接放行原始点击
                warn('弹窗创建失败，已放行原始点击', err);
                bypass = true;
                try {
                    replayClick($el);
                } finally {
                    bypass = false;
                }
            }
        }, true);

        // 暴露接口，供页面自定义的删除按钮主动接入
        window.CmfDeleteConfirm = {
            version: '2026-09-11c',
            require: function (callback) {
                if (isMutedToday()) {
                    callback();
                    return;
                }
                open(callback);
            },
            isMutedToday: isMutedToday
        };
    } catch (err) {
        warn('初始化失败', err);
    }
})();
