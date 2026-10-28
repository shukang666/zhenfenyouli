import settings from "./settings.js";

/** 真分入口条（活动 Tab、公益行动分类页顶部） */
export const ACTIVITY_TAB_ENTRY_BANNER_VER = "2026102814";

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