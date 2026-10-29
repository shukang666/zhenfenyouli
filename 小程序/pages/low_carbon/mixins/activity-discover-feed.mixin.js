/**
 * 活动 Tab · 发现页「正在报名」列表（筛选 + 分页）
 */
import { resolveHomeHotThumb } from "@/common/homeHotSpotEntries.js";

export default {
  data() {
    return {
      discover_filter: "recommend",
      discover_list: [],
      discover_page: 1,
      discover_has_more: true,
      discover_loading: false,
    };
  },
  methods: {
    switchDiscoverFilter(key) {
      if (this.discover_filter === key) return;
      this.discover_filter = key;
      this.fetchDiscoverList(true);
    },

    buildDiscoverListParams(page) {
      const pageSize = 10;
      if (this.discover_filter === "nearby") {
        return { type: "member", page, page_size: pageSize };
      }
      const params = { page, page_size: pageSize };
      if (this.discover_filter === "weekly") {
        params.is_month_recommend = 1;
      }
      return { type: "platform", ...params };
    },

    fetchDiscoverList(reset = false) {
      if (reset) {
        this.discover_page = 1;
        this.discover_has_more = true;
        if (!this.discover_list.length) {
          this.discover_list = [];
        }
      }
      if (!this.discover_has_more && !reset) return Promise.resolve();
      if (this.discover_loading) return Promise.resolve();
      this.discover_loading = true;
      const page = reset ? 1 : this.discover_page;
      const spec = this.buildDiscoverListParams(page);

      const done = (list, lastPage, curPage) => {
        const rows = this.normalizeDiscoverRows(list || []);
        if (reset) {
          this.discover_list = rows;
        } else {
          this.discover_list = (this.discover_list || []).concat(rows);
        }
        const last = Number(lastPage) || 1;
        const cur = Number(curPage) || page;
        this.discover_has_more = cur < last;
        this.discover_page = cur + 1;
      };

      if (spec.type === "member") {
        return this.$u.api
          .memberActiveList({ page: spec.page, page_size: spec.page_size })
          .then((res) => {
            const data = res.data || {};
            done(data.list || [], data.last_page, data.page);
          })
          .catch(() => {
            if (reset) this.discover_list = [];
            this.discover_has_more = false;
          })
          .finally(() => {
            this.discover_loading = false;
          });
      }

      const { type, ...apiParams } = spec;
      return this.$u.api
        .get_new_active_list(apiParams)
        .then((res) => {
          const payload = res.data || {};
          let list = payload.data || payload.list || [];
          if (this.discover_filter === "enrolling") {
            list = list.filter((item) => Number(item.is_end) !== 1);
          }
          if (this.discover_filter === "free") {
            list = list.filter(
              (item) => !Number(item.point) && !Number(item.send_point),
            );
          }
          done(list, payload.last_page, payload.current_page);
        })
        .catch(() => {
          if (reset) this.discover_list = [];
          this.discover_has_more = false;
        })
        .finally(() => {
          this.discover_loading = false;
        });
    },

    loadDiscoverMore() {
      if (this.discover_loading || !this.discover_has_more) return;
      this.fetchDiscoverList(false);
    },

    normalizeDiscoverRows(list) {
      return (list || []).map((item) => this.formatDiscoverCard(item));
    },

    formatDiscoverCard(item) {
      const row = { ...item };
      const startTs = Number(row.offline_sign_start) || 0;
      let dateLine = "";
      if (Number(row.is_date_pending) === 1) {
        dateLine = "时间待定";
      } else if (startTs > 0) {
        const w = ["日", "一", "二", "三", "四", "五", "六"][new Date(startTs * 1000).getDay()];
        dateLine =
          this.$u.timeFormat(startTs, "mm-dd") +
          " 周" +
          w +
          " " +
          this.$u.timeFormat(startTs, "hh:MM");
      } else if (row.date) {
        dateLine = this.$u.timeFormat(row.date, "yy-mm-dd");
      } else {
        dateLine = "时间见详情";
      }
      const addr = (row.address || row.place_name || "").trim();
      if (addr) {
        dateLine = dateLine + " · " + addr;
      } else if (row.time_line) {
        dateLine = row.time_line;
      }
      row._date_line = dateLine;
      row._tags = this.buildDiscoverTags(row);
      row._quota_hint = this.buildDiscoverQuotaHint(row);
      row._btn_text = row.web_launch_url ? "了解并申请" : "去报名";
      const rawThumb = (row.thumb || row.cover || row.image || "").trim();
      row._thumb = resolveHomeHotThumb(rawThumb) || rawThumb;
      return row;
    },

    buildDiscoverTags(item) {
      const tags = [];
      if (!Number(item.point) && !Number(item.send_point)) {
        tags.push("免费");
      }
      const tagStr = (item.tags || "").toString();
      if (tagStr) {
        tagStr.split(",").forEach((t) => {
          const s = t.trim();
          if (s && tags.length < 3 && !tags.includes(s)) tags.push(s);
        });
      }
      if (item.theme_label && tags.length < 3) {
        tags.push(item.theme_label);
      }
      return tags.slice(0, 3);
    },

    buildDiscoverQuotaHint(item) {
      if (Number(item.is_month_recommend) === 1) {
        return "本月重点推荐";
      }
      const n = Number(item.active_count) || 0;
      if (n > 0) {
        return n + "人已报名";
      }
      return "欢迎参加";
    },

    openDiscoverActive(item) {
      if (!item || !item.id) return;
      if (this.$u.func && typeof this.$u.func.openActiveDetail === "function") {
        this.$u.func.openActiveDetail(item);
        return;
      }
      this.$u.route("/pages/activity/lsxf/details-new?id=" + item.id);
    },
  },
};
