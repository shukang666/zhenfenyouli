(function (global) {
  function qs() {
    return new URLSearchParams(global.location.search || '');
  }

  function activeIdFromQuery() {
    var p = qs();
    return p.get('active_id') || '';
  }

  function api(ctx, path, body) {
    var base = (ctx.api && ctx.api.base) || ActiveWebSdk.defaultApiBase();
    var openid = (ctx.session && ctx.session.openid) || '';
    return ActiveWebSdk.apiPost(base, openid, path, body || {}).then(function (res) {
      if (!res || res.code !== 1) {
        throw new Error((res && res.msg) || '请求失败');
      }
      return res.data || {};
    });
  }

  function toast(msg) {
    var el = document.getElementById('toast');
    if (!el) {
      alert(msg);
      return;
    }
    el.textContent = msg;
    el.classList.add('show');
    setTimeout(function () {
      el.classList.remove('show');
    }, 2200);
  }

  function goZone() {
    var q = global.location.search || '';
    global.location.href = 'zone.html' + q;
  }

  global.LifeNavigatorApp = {
    qs: qs,
    activeIdFromQuery: activeIdFromQuery,
    api: api,
    toast: toast,
    goZone: goZone,
  };
})(window);
