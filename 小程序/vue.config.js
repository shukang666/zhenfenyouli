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
            const destDirs = [
              path.resolve(__dirname, 'unpackage/dist/dev/mp-weixin/custom-tab-bar'),
              path.resolve(__dirname, 'unpackage/dist/build/mp-weixin/custom-tab-bar')
            ]
            const files = ['index.wxml', 'index.js', 'index.json', 'index.wxss']
            destDirs.forEach(destDir => {
              if (!fs.existsSync(path.dirname(destDir))) return
              if (!fs.existsSync(destDir)) {
                fs.mkdirSync(destDir, { recursive: true })
              }
              files.forEach(file => {
                const src = path.join(srcDir, file)
                const dest = path.join(destDir, file)
                if (fs.existsSync(src)) {
                  fs.copyFileSync(src, dest)
                }
              })
            })
          })
        }
      }
    ]
  }
}
