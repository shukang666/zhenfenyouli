const path = require('path')
const fs = require('fs')

const SKIP_PLACEHOLDER = {}
const SKIP_JSON = {
  'app.json': true,
  'project.config.json': true,
  'project.private.config.json': true
}

function injectPageComponentPlaceholders(mpRoot) {
  if (!fs.existsSync(mpRoot)) return
  const walk = (dir) => {
    let names
    try {
      names = fs.readdirSync(dir)
    } catch (e) {
      return
    }
    names.forEach((name) => {
      if (name === 'custom-tab-bar' || name === 'node_modules' || name === 'static') return
      const full = path.join(dir, name)
      let stat
      try {
        stat = fs.statSync(full)
      } catch (e) {
        return
      }
      if (stat.isDirectory()) {
        walk(full)
        return
      }
      if (!name.endsWith('.json') || SKIP_JSON[name]) return
      let json
      try {
        json = JSON.parse(fs.readFileSync(full, 'utf8'))
      } catch (e) {
        return
      }
      if (json.component === true) return
      const using = json.usingComponents
      if (!using || typeof using !== 'object') return
      const ph = Object.assign({}, json.componentPlaceholder)
      let changed = false
      Object.keys(using).forEach((key) => {
        if (SKIP_PLACEHOLDER[key]) return
        if (!ph[key]) {
          ph[key] = 'view'
          changed = true
        }
      })
      if (!changed) return
      json.componentPlaceholder = ph
      fs.writeFileSync(full, JSON.stringify(json))
    })
  }
  walk(mpRoot)
}

module.exports = {
  configureWebpack: {
    plugins: [
      {
        apply: (compiler) => {
          compiler.hooks.afterEmit.tap('CopyCustomTabBar', () => {
            // 只在微信小程序编译时执行
            const platform = process.env.UNI_PLATFORM
            if (platform !== 'mp-weixin') return

            const mpRoots = [
              path.resolve(__dirname, 'unpackage/dist/dev/mp-weixin'),
              path.resolve(__dirname, 'unpackage/dist/build/mp-weixin')
            ]
            mpRoots.forEach((mpRoot) => {
              if (fs.existsSync(mpRoot)) {
                injectPageComponentPlaceholders(mpRoot)
              }
            })

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
