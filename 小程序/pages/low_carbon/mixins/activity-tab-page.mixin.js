/**
 * 活动 Tab 页：生命周期、TabBar、跨页筛选 pending、发布刷新
 */
import { checkCommunity } from "@/common/communityCheck.mixin.js";
import {
  ACTIVITY_PAGE_TAB_PENDING_KEY,
  DISCOVER_FILTER_PENDING_KEY,
} from "@/common/activityTabNav.js";

const DISCOVER_FILTERS = [
  "recommend",
  "weekly",
  "nearby",
  "enrolling",
  "free",
];

export default {
  onLoad() {
    this.fetchDiscoverList(true);
  },
  onShow() {
    this.updateTabBarSelected(1);
    if (checkCommunity(this)) return;
    this.applyDiscoverPendingFromStorage();
    if (!this.isLogin) return;
    this.refreshMemberActiveAfterPublish();
    if (this._memberActiveNeedRefresh || this.discover_filter === "nearby") {
      this.fetchDiscoverList(true);
    }
  },
  onReachBottom() {
    this.loadDiscoverMore();
  },
  methods: {
    applyDiscoverPendingFromStorage() {
      try {
        const pendingFilter = uni.getStorageSync(DISCOVER_FILTER_PENDING_KEY);
        if (pendingFilter) {
          uni.removeStorageSync(DISCOVER_FILTER_PENDING_KEY);
          if (DISCOVER_FILTERS.includes(pendingFilter)) {
            this.discover_filter = pendingFilter;
            this.fetchDiscoverList(true);
          }
          return;
        }
        const pending = uni.getStorageSync(ACTIVITY_PAGE_TAB_PENDING_KEY);
        if (pending === "featured") {
          uni.removeStorageSync(ACTIVITY_PAGE_TAB_PENDING_KEY);
          this.discover_filter = "weekly";
          this.fetchDiscoverList(true);
        } else if (pending === "neighbor") {
          uni.removeStorageSync(ACTIVITY_PAGE_TAB_PENDING_KEY);
          this.discover_filter = "nearby";
          this.fetchDiscoverList(true);
        }
      } catch (e) {}
    },

    /** 其它页跳转仍可能写旧 Tab 名 */
    switchActivityPageTab(tab) {
      const map = { neighbor: "nearby", featured: "weekly" };
      const key = map[tab] || tab;
      if (DISCOVER_FILTERS.includes(key)) {
        this.switchDiscoverFilter(key);
      }
    },

    goMoreAllDiscover() {
      this.$u.route({
        url: "/pages/activity/active-list",
        params: { filter: "all" },
      });
    },

    gotoCateList(cate) {
      if (!cate || !cate.id) return;
      this.$u.route(
        `/pages/activity/cate-list?cate_id=${cate.id}&name=${encodeURIComponent(cate.name || "")}`,
      );
    },

    gotoSearch() {
      this.$u.route("/pages/activity/active-search");
    },

    updateTabBarSelected(index) {
      if (
        typeof this.$mp.page.getTabBar === "function" &&
        this.$mp.page.getTabBar()
      ) {
        this.$mp.page.getTabBar().setSelected(index);
      }
    },
  },
};
