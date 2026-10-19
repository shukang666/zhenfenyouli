/**
 * Web 式活动 H5 辅助：解析 URL 参数并 bootstrap 用户/活动上下文
 * 依赖：与小程序 API 同域（如 https://admin.circular-economy.cn）
 */
(function (global) {
  function qs() {
    return new URLSearchParams(global.location.search || '');
  }

  function defaultApiBase() {
    var o = global.location.origin;
    return o ? o + '/api' : 'https://admin.circular-economy.cn/api';
  }

  function fetchJson(url, options) {
    return fetch(url, options || {}).then(function (r) {
      return r.json();
    });
  }

  /**
   * @param {object} [opts]
   * @param {string} [opts.apiBase]
   * @returns {Promise<object>} 后端 data 字段
   */
  function bootstrap(opts) {
    opts = opts || {};
    var params = qs();
    var webCtx = params.get('web_ctx') || '';
    var activeId = params.get('active_id') || '';
    if (!webCtx) {
      var path = (global.location.pathname || '') + (global.location.href || '');
      var autoGuest = /qxl-photo-share/i.test(path)
        || /flea-market/i.test(path)
        || (params.get('share') && (params.get('photo_id') || params.get('report_id')));
      if (opts.allowGuest || autoGuest) {
        var baseGuest = (opts.apiBase || defaultApiBase()).replace(/\/$/, '');
        return Promise.resolve({
          guest: true,
          active: { id: activeId },
          api: { base: baseGuest, auth_header: 'openid' },
          session: { openid: '' },
        });
      }
      return Promise.reject(new Error('缺少 web_ctx，请从小程序内打开本活动'));
    }
    var base = (opts.apiBase || defaultApiBase()).replace(/\/$/, '');
    var url = base + '/wxapp/public/active_web_bootstrap?web_ctx=' + encodeURIComponent(webCtx);
    if (activeId) {
      url += '&active_id=' + encodeURIComponent(activeId);
    }
    return fetchJson(url).then(function (res) {
      if (!res || res.code !== 1) {
        throw new Error((res && res.msg) || 'bootstrap 失败');
      }
      return res.data;
    });
  }

  /**
   * 与小程序一致的 JSON POST（Header openid）
   */
  function apiPost(apiBase, openid, path, body) {
    var base = (apiBase || defaultApiBase()).replace(/\/$/, '');
    return fetchJson(base + path, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json;charset=UTF-8',
        openid: openid || '',
      },
      body: JSON.stringify(body || {}),
    });
  }

  function guestSession(activeId, apiBase) {
    var baseGuest = (apiBase || defaultApiBase()).replace(/\/$/, '');
    return {
      guest: true,
      active: { id: activeId || '' },
      api: { base: baseGuest, auth_header: 'openid' },
      session: { openid: '' },
    };
  }

  var JWEIXIN_SRC = 'https://res.wx.qq.com/open/js/jweixin-1.6.0.js';
  var miniProgramReady = null;

  function loadScriptOnce(src) {
    return new Promise(function (resolve, reject) {
      if (!global.document) {
        reject(new Error('非浏览器环境'));
        return;
      }
      var existing = global.document.querySelector('script[data-active-web-sdk="' + src + '"]');
      if (existing) {
        if (existing.getAttribute('data-loaded') === '1') {
          resolve();
          return;
        }
        existing.addEventListener('load', function () { resolve(); });
        existing.addEventListener('error', function () { reject(new Error('JSSDK 加载失败')); });
        return;
      }
      var s = global.document.createElement('script');
      s.src = src;
      s.setAttribute('data-active-web-sdk', src);
      s.onload = function () {
        s.setAttribute('data-loaded', '1');
        resolve();
      };
      s.onerror = function () {
        reject(new Error('JSSDK 加载失败'));
      };
      global.document.head.appendChild(s);
    });
  }

  /** web-view 内需 JSSDK 才有 wx.miniProgram */
  function ensureMiniProgramApi() {
    if (miniProgramReady) {
      return miniProgramReady;
    }
    if (global.wx && global.wx.miniProgram) {
      miniProgramReady = Promise.resolve(global.wx.miniProgram);
      return miniProgramReady;
    }
    miniProgramReady = loadScriptOnce(JWEIXIN_SRC).then(function () {
      if (global.wx && global.wx.miniProgram) {
        return global.wx.miniProgram;
      }
      throw new Error('当前环境无法跳转小程序页面，请确认从微信小程序内打开本活动');
    });
    return miniProgramReady;
  }

  var TAB_BAR_PATHS = [
    '/pages/index/index',
    '/pages/low_carbon/low_carbon',
    '/pages/mall/mall',
    '/pages/health/health',
    '/pages/sjhd/sjhd',
  ];

  function isTabBarPath(path) {
    var base = String(path || '').split('?')[0];
    return TAB_BAR_PATHS.indexOf(base) >= 0;
  }

  /**
   * H5 内跳转到小程序页面（web-view 专用）
   * @param {string} path 如 /pages/activity/scene-clock-flow?mode=theme
   * @returns {Promise<void>}
   */
  function navigateMp(path) {
    path = String(path || '').trim();
    if (!path) {
      return Promise.reject(new Error('无效跳转路径'));
    }
    if (path.charAt(0) !== '/') {
      path = '/' + path;
    }
    return ensureMiniProgramApi().then(function (mp) {
      return new Promise(function (resolve, reject) {
        var tabUrl = path.split('?')[0];
        var onFail = function (err) {
          var msg = (err && err.errMsg) ? err.errMsg : '跳转失败';
          reject(new Error(msg));
        };
        if (isTabBarPath(path)) {
          mp.switchTab({ url: tabUrl, success: resolve, fail: onFail });
          return;
        }
        mp.navigateTo({
          url: path,
          success: resolve,
          fail: function () {
            mp.redirectTo({ url: path, success: resolve, fail: onFail });
          },
        });
      });
    });
  }

  global.ActiveWebSdk = {
    bootstrap: bootstrap,
    apiPost: apiPost,
    getQuery: qs,
    defaultApiBase: defaultApiBase,
    guestSession: guestSession,
    navigateMp: navigateMp,
    ensureMiniProgramApi: ensureMiniProgramApi,
  };
})(typeof window !== 'undefined' ? window : this);
