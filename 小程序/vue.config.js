const path = require('path')
const fs = require('fs')

module.exports = {
  configureWebpack: {
    plugins: [
      {
        apply: (compiler) => {
          compiler.hooks.afterEmit.tap('CopyCustomTabBar', () => {
            // 只在微信小程序编译时执行
            const platform = process.env.UNI_PLATFORM
            if (platform !== 'mp-weixin') return

            const srcDir = path.resolve(__dirname, 'custom-tab-bar')
            const destDir = path.resolve(__dirname, 'unpackage/dist/dev/mp-weixin/custom-tab-bar')

            // 如果目标目录不存在则创建
            if (!fs.existsSync(destDir)) {
              fs.mkdirSync(destDir, { recursive: true })
            }

            // 复制原生 custom-tab-bar 文件
            const files = ['index.wxml', 'index.js', 'index.json', 'index.wxss']
            files.forEach(file => {
              const src = path.join(srcDir, file)
              const dest = path.join(destDir, file)
              if (fs.existsSync(src)) {
                fs.copyFileSync(src, dest)
                console.log(`[CopyCustomTabBar] Copied ${file}`)
              }
            })
          })
        }
      }
    ]
  }
}
