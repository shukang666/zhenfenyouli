/**
 * 活动 Tab · 邻里活动瀑布流：列表分页、发布后刷新
 */
export default {
  data() {
    return {
      member_active_list: [],
      member_active_page: 1,
      member_active_has_more: true,
      member_active_loading: false,
      _memberActiveNeedRefresh: false,
    };
  },
  computed: {
    member_active_col_left() {
      return (this.member_active_list || []).filter((_, i) => i % 2 === 0);
    },
    member_active_col_right() {
      return (this.member_active_list || []).filter((_, i) => i % 2 === 1);
    },
  },
  methods: {
    goPublishMemberActive() {
      if (!this.$u.func.checkLogin()) return;
      this._memberActiveNeedRefresh = true;
      this.$u.route("/pages/activity/member-active-publish");
    },

    fetchMemberActiveList(reset = false) {
      if (reset) {
        this.member_active_page = 1;
        this.member_active_has_more = true;
        if (!this.member_active_list.length) {
          this.member_active_list = [];
        }
      }
      if (!this.member_active_has_more && !reset) return Promise.resolve();
      if (this.member_active_loading) return Promise.resolve();
      this.member_active_loading = true;
      const page = reset ? 1 : this.member_active_page;
      return this.$u.api
        .memberActiveList({ page, page_size: 10 })
        .then((res) => {
          const data = res.data || {};
          const list = data.list || [];
          if (reset) {
            this.member_active_list = list;
          } else {
            this.member_active_list = (this.member_active_list || []).concat(list);
          }
          const lastPage = Number(data.last_page) || 1;
          const cur = Number(data.page) || page;
          this.member_active_has_more = cur < lastPage;
          this.member_active_page = cur + 1;
        })
        .catch(() => {})
        .finally(() => {
          this.member_active_loading = false;
        });
    },

    loadMemberActiveMore() {
      if (this.member_active_loading || !this.member_active_has_more) return;
      this.fetchMemberActiveList(false);
    },

    /** 在 Tab 页 onShow 中调用（登录且已通过社区校验后） */
    refreshMemberActiveAfterPublish() {
      let needRefresh = this._memberActiveNeedRefresh;
      try {
        if (uni.getStorageSync("member_active_tab_refresh")) {
          uni.removeStorageSync("member_active_tab_refresh");
          needRefresh = true;
        }
      } catch (e) {}
      if (needRefresh) {
        this._memberActiveNeedRefresh = false;
        this.fetchMemberActiveList(true);
        if (this.discover_filter === "nearby") {
          this.fetchDiscoverList(true);
        }
      }
    },

    actEngagementCount(item) {
      if (!item) return 0;
      if (Number(item.is_date_pending) === 1) {
        return Number(item.interest_count) || 0;
      }
      return Number(item.active_count) || 0;
    },
  },
};
