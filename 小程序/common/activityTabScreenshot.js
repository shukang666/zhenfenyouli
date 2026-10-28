import settings from "./settings.js";

/** 活动 Tab 最顶部 Banner（纯展示；后台轮播见 slide_id=9 / banner_list） */
export const ACTIVITY_TAB_TOP_BANNER_VER = "2026102818";

export const ACTIVITY_TAB_TOP_BANNER_URL =
  settings.staticAssetBase +
  "planning/activity-tab-top-banner.png?v=" +
  ACTIVITY_TAB_TOP_BANNER_VER;

/** 真分入口条（活动 Tab、公益行动分类页顶部） */export const ACTIVITY_TAB_ENTRY_BANNER_VER = "2026102814";

export const ACTIVITY_TAB_ENTRY_BANNER_URL =
  settings.staticAssetBase +
  "planning/activity-tab-zhenfen-entry.png?v=" +
  ACTIVITY_TAB_ENTRY_BANNER_VER;

/** 垃圾分类工作台大图（garbage-classify 页底部；COS 独立 key，不覆盖 screenshot） */
export const DTSH_WORKBENCH_BANNER_VER = "2026102816";

export const DTSH_WORKBENCH_BANNER_URL =
  settings.staticAssetBase +
  "planning/activity-tab-dtsh-workbench.jpg?v=" +
  DTSH_WORKBENCH_BANNER_VER;