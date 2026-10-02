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

  global.ActiveWebSdk = {
    bootstrap: bootstrap,
    apiPost: apiPost,
    getQuery: qs,
    defaultApiBase: defaultApiBase,
    guestSession: guestSession,
  };
})(typeof window !== 'undefined' ? window : this);
