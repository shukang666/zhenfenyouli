/** 活动 Tab · 发现页五大主题分类（与 cmf_active_community_cate.id 对应） */
export const ACTIVITY_MAIN_CATEGORIES = [
  {
    id: 1,
    name: "健康运动",
    subtitle: "Healthy Life",
    pillText: "动出活力",
    icon: "play-circle-fill",
    iconColor: "#1877F2",
    iconHalo: "rgba(24, 119, 242, 0.35)",
    bgColor: "linear-gradient(160deg, #E7F0FE 0%, #1877F2 100%)",
    surfaceBg:
      "linear-gradient(145deg, #ffffff 0%, #eef4ff 42%, #c8dcff 100%)",
    pillBg: "rgba(24, 119, 242, 0.14)",
    pillColor: "#1565D8",
  },
  {
    id: 2,
    name: "学习成长",
    subtitle: "Growing Up",
    pillText: "充电提升",
    icon: "star-fill",
    iconColor: "#1F9D55",
    iconHalo: "rgba(31, 157, 85, 0.32)",
    bgColor: "linear-gradient(160deg, #D4FC79 0%, #3CB878 100%)",
    surfaceBg:
      "linear-gradient(145deg, #ffffff 0%, #eefbf3 50%, #b8ebd0 100%)",
    pillBg: "rgba(31, 157, 85, 0.16)",
    pillColor: "#16884a",
  },
  {
    id: 3,
    name: "兴趣体验",
    subtitle: "Hobby Time",
    pillText: "好玩体验",
    icon: "grid-fill",
    iconColor: "#E86A1A",
    iconHalo: "rgba(232, 106, 26, 0.34)",
    bgColor: "linear-gradient(160deg, #FFD194 0%, #FF8A3D 100%)",
    surfaceBg:
      "linear-gradient(145deg, #fffaf5 0%, #fff0e0 55%, #ffd4b0 100%)",
    pillBg: "rgba(232, 106, 26, 0.14)",
    pillColor: "#c45a12",
  },
  {
    id: 4,
    name: "交流互动",
    subtitle: "Neighbors",
    pillText: "邻里互动",
    icon: "chat-fill",
    iconColor: "#E84D6A",
    iconHalo: "rgba(232, 77, 106, 0.32)",
    bgColor: "linear-gradient(160deg, #FF9A9E 0%, #F06B8A 100%)",
    surfaceBg:
      "linear-gradient(145deg, #fff8f9 0%, #ffecef 55%, #ffc8d4 100%)",
    pillBg: "rgba(232, 77, 106, 0.14)",
    pillColor: "#c93d58",
  },
  {
    id: 5,
    name: "公益行动",
    subtitle: "Volunteer",
    pillText: "志愿参与",
    icon: "gift-fill",
    iconColor: "#7B4FD6",
    iconHalo: "rgba(123, 79, 214, 0.34)",
    bgColor: "linear-gradient(160deg, #D8B4FE 0%, #8B5CF6 100%)",
    surfaceBg:
      "linear-gradient(145deg, #faf7ff 0%, #f1eaff 55%, #dbc8ff 100%)",
    pillBg: "rgba(123, 79, 214, 0.14)",
    pillColor: "#6b3fc4",
  },
];

const DISCOVER_CATE_EMOJI = {
  1: "🏃",
  2: "📚",
  3: "✨",
  4: "💬",
  5: "♻️",
};

const DISCOVER_CATE_SHORT = {
  1: "运动户外",
  2: "学习成长",
  3: "兴趣体验",
  4: "交流互动",
  5: "公益环保",
};

export function mapDiscoverCategories(mainCategories) {
  return (mainCategories || []).map((c) => ({
    ...c,
    emoji: DISCOVER_CATE_EMOJI[c.id] || "🎯",
    shortName: DISCOVER_CATE_SHORT[c.id] || c.name,
  }));
}
