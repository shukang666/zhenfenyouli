/**
 * 七贤岭学考模块 — 前端展示元数据（与后台文章 category 字段一致）
 * bento: 枢纽页网格占位（hub 页 CSS 类名后缀）
 */
export const QXL_MODULE_UI = {
	人文: {
		emoji: '📜',
		tagline: '读故事、懂七贤',
		bento: 'hero-left',
		gradient: 'linear-gradient(145deg, #0d5c48 0%, #1fa971 100%)',
		accent: '#0f6e56',
	},
	人口: {
		emoji: '👥',
		tagline: '结构概况一目了然',
		bento: 'compact',
		gradient: 'linear-gradient(145deg, #1a5f7a 0%, #3d9ec7 100%)',
		accent: '#1a5f7a',
	},
	地理: {
		emoji: '🗺️',
		tagline: '区位山水与生态',
		bento: 'compact',
		gradient: 'linear-gradient(145deg, #2d6a4f 0%, #52b788 100%)',
		accent: '#2d6a4f',
	},
	景点: {
		emoji: '🏞️',
		tagline: '地标景观走读',
		bento: 'wide',
		gradient: 'linear-gradient(145deg, #0f6e56 0%, #74c69d 100%)',
		accent: '#1fa971',
	},
	七贤由来: {
		emoji: '✨',
		tagline: '地名渊源与传说',
		bento: 'tall-right',
		gradient: 'linear-gradient(145deg, #5c4d7d 0%, #9d8df1 100%)',
		accent: '#5c4d7d',
	},
	小区信息: {
		emoji: '🏘️',
		tagline: '辖区小区与配套',
		bento: 'standard',
		gradient: 'linear-gradient(145deg, #355070 0%, #6d8dad 100%)',
		accent: '#355070',
	},
}

export function getModuleUi(category) {
	return QXL_MODULE_UI[category] || {
		emoji: '📖',
		tagline: '边读边测',
		bento: 'standard',
		gradient: 'linear-gradient(145deg, #0f6e56 0%, #1fa971 100%)',
		accent: '#0f6e56',
	}
}
