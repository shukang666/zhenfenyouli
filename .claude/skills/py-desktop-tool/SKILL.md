---
name: "py-desktop-tool"
description: "Python 桌面小工具开发指南（tkinter GUI + pandas 数据处理 + openpyxl 导出 Excel + PyMuPDF PDF处理 + PyInstaller 打包 exe）。当用户要求做数据报表、Excel 批量导出、运营分析工具、PDF 转图像、直连数据库的查询小软件，或需要把 Python 脚本打包成 exe 给同事免环境使用时调用。"
---

# Python 桌面数据工具开发指南

项目内 `py/` 目录下已有多个成熟工具，开发新工具时**先 Read 同目录同类文件对齐风格**，不要引入新框架：

- `py/积分使用记录/积分报表工具.py` — 直连 MySQL，按月查询积分流水，pandas 聚合，openpyxl 导出带合并单元格的 Excel（参考样本）
- `py/运营工具/analyze_tool.py` — 读 Excel 文件分析，matplotlib 图表嵌入 tkinter
- `py/PDF转图像工具/pdf_to_image.py` — PDF 转高清图像，PyMuPDF 渲染，后台线程批量处理，进度条+右键菜单

## 标准技术栈（固定，勿换）

| 用途 | 库 | 说明 |
|------|-----|------|
| GUI | `tkinter` + `ttk` | 标准库，打包体积小，零依赖 |
| 数据库 | `pymysql` | 直连 MySQL，配置见 `.env`（host=124.223.56.252，库名/账号见 `DATABASE_*`） |
| 数据处理 | `pandas` | DataFrame 聚合、merge、groupby |
| Excel 导出 | `openpyxl` | 支持合并单元格、样式、动态行高；**不要用 pandas.to_excel**（样式控制弱） |
| 图表 | `matplotlib` | 中文字体必须设置：`plt.rcParams['font.sans-serif'] = ['SimHei','WenQuanYi Micro Hei']`，`plt.rcParams['axes.unicode_minus'] = False` |
| PDF 处理 | `PyMuPDF (fitz)` | PDF 渲染为高清图像、提取文本/图像、合并拆分；安装：`pip install PyMuPDF` |
| 打包 | `PyInstaller` | 单文件 exe |

## 代码结构模板

### 数据查询类工具（Treeview 预览 + Excel 导出）

```python
# -*- coding: utf-8 -*-
"""
工具名
======
功能：一句话说明
使用方式：双击运行 或 python xxx.py
依赖：pymysql, pandas, openpyxl
"""
import tkinter as tk
from tkinter import ttk, filedialog, messagebox
import pymysql
import pandas as pd
from openpyxl import Workbook
from openpyxl.styles import Font, Alignment, PatternFill, Border, Side
from datetime import datetime

DB_CONFIG = {
    'host': '124.223.56.252', 'port': 3306,
    'user': 'xcxkf052_aubye_c', 'password': 'acacQQndx12ADXMm',
    'database': 'xcxkf052_aubye_c', 'charset': 'utf8mb4'
}

class XxxTool:
    def __init__(self, root):
        self.root = root
        self.root.title('工具名')
        self.root.geometry('1200x800')
        self.df = None  # 查询结果
        self.create_widgets()

    def create_widgets(self):
        # 顶部：筛选条件 + 查询/导出按钮（ttk.Frame pack side=top fill=x）
        # 中部：结果预览 Treeview（横向滚动条必须配 xscrollcommand）
        # 底部：状态栏 Label
        ...

    def query_data(self):
        """查询数据库，返回 DataFrame。空结果必须返回带完整列名的空 DataFrame！"""
        conn = pymysql.connect(**DB_CONFIG)
        try:
            # 时间范围筛选：create_time BETWEEN start_ts AND end_ts
            # 跨表数据用多条 SELECT 预加载到 dict，再在 Python 里关联，
            # 避免大 SQL JOIN 把数据库拖垮
            ...
            if rows:
                df = pd.DataFrame(rows, columns=FINAL_COLS)
            else:
                df = pd.DataFrame(columns=FINAL_COLS)  # 关键：空结果也要有列
            return df
        finally:
            conn.close()

    def export_excel(self):
        """openpyxl 导出。合并单元格、换行、动态行高都在这里控制"""
        wb = Workbook()
        ws = wb.active
        # 表头样式
        # 数据行：多条明细换行显示用 '\n'，alignment(wrap_text=True)
        # 合并单元格：ws.merge_cells(start_row,start_column,end_row,end_column)
        # 动态行高：ws.row_dimensions[r].height = 根据换行数估算
        path = filedialog.asksaveasfilename(defaultextension='.xlsx')
        if path:
            wb.save(path)
            messagebox.showinfo('成功', f'已保存：{path}')
```

### 批量处理类工具（后台线程 + 进度条 + 文件列表）

```python
# -*- coding: utf-8 -*-
"""
批量处理工具
============
功能：一句话说明
使用方式：双击运行 或 python xxx.py
依赖：PyMuPDF 等
"""
import tkinter as tk
from tkinter import ttk, filedialog, messagebox
import threading
import os

class BatchTool:
    def __init__(self, root):
        self.root = root
        self.root.title('工具名')
        self.root.geometry('900x650')
        self.file_list = []       # 待处理文件列表（存在 Treeview 中）
        self.is_processing = False
        self.create_widgets()

    def create_widgets(self):
        # 顶部：参数设置区（DPI、格式、缩放等）
        # 中部：文件列表 Treeview (文件名, 信息, 文件路径)
        # 底部：进度条 + 状态栏 + 开始/停止按钮
        ...

    def start_processing(self):
        """启动后台线程处理"""
        if self.is_processing:
            return
        self.is_processing = True
        thread = threading.Thread(target=self._worker, daemon=True)
        thread.start()

    def stop_processing(self):
        """设置停止标志，由工作线程自行检查并退出"""
        self.is_processing = False

    def _worker(self):
        """后台工作线程：处理大量文件/耗时任务"""
        items = self.tree.get_children()
        total = len(items)
        for idx, item in enumerate(items):
            if not self.is_processing:
                break  # 检查停止标志，自行退出
            self._set_status(f'[{idx+1}/{total}] 处理中...')
            self._set_progress((idx / total) * 100)
            # ... 实际处理逻辑
        # 完成回调（用 root.after 回到主线程更新 UI）
        self.root.after(0, self._on_complete)

    def _set_status(self, text):
        """线程安全的状态更新（工作线程 → 主线程）"""
        self.root.after(0, lambda: self.status_var.set(text))

    def _set_progress(self, value):
        """线程安全的进度更新（工作线程 → 主线程）"""
        self.root.after(0, lambda: self.progress_var.set(value))

    def _on_complete(self):
        """完成回调（在主线程执行）"""
        self.is_processing = False
        self.convert_btn.config(state=tk.NORMAL)
        self.stop_btn.config(state=tk.DISABLED)
        self.progress_var.set(100)
        messagebox.showinfo('完成', '处理完成！')
```

### 右键菜单（文件列表常用）

```python
# 在 create_widgets 中添加右键菜单
self.context_menu = tk.Menu(self.root, tearoff=0)
self.context_menu.add_command(label="删除选中", command=self.remove_selected)
self.context_menu.add_command(label="清空全部", command=self.clear_files)
self.tree.bind("<Button-3>", self._show_context_menu)

def _show_context_menu(self, event):
    """显示右键菜单"""
    try:
        self.context_menu.tk_popup(event.x_root, event.y_root)
    finally:
        self.context_menu.grab_release()
```

## 必须遵守的坑（都踩过）

### 数据库/数据处理

1. **空 DataFrame 必须带列名**：无数据的查询分支返回 `pd.DataFrame(columns=FINAL_COLS)`，否则后续 merge/取列会报 `KeyError: '_xxx'` 或 `'int' object is not iterable`。7 月无数据崩溃就是这个原因。

2. **历史数据可能不走新字段**：不要假设业务字段一直有值。例如积分兑换 7 月及以前不写 `cmf_shop_order.point_pay`，而是记在 `cmf_user_account_log.type=203`。跨月份报表要查日志表兜底，并用"用户ID + 时间差 < 120 秒"关联订单明细。

3. **一条订单多商品要展开**：LEFT JOIN 后不能只取第一条，会覆盖。正确做法：分开预加载订单表和明细表，按订单号关联后逐商品展开，积分按商品价格比例分摊（价格全 0 时均分为兜底）。

4. **时间戳范围查询**：`create_time BETWEEN start_ts AND end_ts`，其中 `end_ts` 要包含当天全天（`strtotime(end_date) + 86399`）。

5. **PowerShell 跑 PHP/MySQL 脚本时**：中文输出在终端显示乱码是**控制台 GBK 编码问题，不代表数据库里是乱码**。验证中文是否正确用 PHP 脚本内 `===` 比对预期字符串输出 YES/NO，别相信终端显示的字形。

6. **mysql 命令行在本机 PATH 里没有**：phpStudy 的 mysql.exe 在 `d:\phpstudy_pro\Extensions\MySQL5.7.26\bin\mysql.exe`，但 PowerShell 内联 `-e "SQL"` 引号转义极易出错。**优先写临时 PHP 脚本用 PDO 执行 SQL**（用完删除），不要硬刚命令行转义。

### UI/交互

7. **Treeview 横向滚动**：列多时必须同时挂 `xscrollcommand`，否则宽表看不全。参考积分报表工具的预览区实现。

8. **单元格内换行显示**：Excel 里同一字段多条记录用 `\n` 拼接，单元格设置 `Alignment(wrap_text=True, vertical='center')`，行高按换行数 `行高 = 18 × 行数` 动态设置，合并单元格也要同步行高。

9. **后台线程更新 UI 必须用 `root.after`**：tkinter 非线程安全，工作线程中任何 UI 操作（设置变量、更新进度条）都必须通过 `self.root.after(0, ...)` 委托回主线程，否则会随机崩溃或界面无响应。

10. **长时间任务必须用后台线程**：数据库大查询、PDF 渲染、批量转换等耗时操作必须放到 `threading.Thread(daemon=True)` 中执行，否则主线程阻塞会导致界面卡死、无法取消。

11. **停止按钮用标志位而非强制终止**：设置 `self.is_processing = False`，工作线程每轮循环检查标志自行退出，不要尝试强制 kill 线程（会导致资源泄漏）。

### PDF 处理（PyMuPDF）

12. **DPI 计算公式**：PyMuPDF 使用 `fitz.Matrix(dpi/72, dpi/72)` 控制渲染精度，其中 72 是 PDF 的默认 DPI。等效缩放 = DPI / 72。

13. **可叠加额外缩放**：需要更高清时，Matrix 参数直接乘法叠加，如 `Matrix(dpi*zoom/72, dpi*zoom/72)`。注意极高 DPI（600+4x）可能产生数 GB 内存/文件，需提前告知用户。

14. **PDF 文档对象用完必须 close()**：`fitz.open()` 返回的文档对象用完必须 `.close()`，否则文件锁死不释放。建议用 `try: ... finally: doc.close()` 或上下文管理器。

15. **Pixmap 用完手动释放**：`page.get_pixmap()` 返回的 Pixmap 在大批量处理时建议手动 `pix = None` 释放内存，避免内存持续增长。

16. **多页 PDF 输出命名**：单页用 `文件名.格式`，多页用 `文件名_第N页.格式`，避免多页文件互相覆盖。

17. **批量添加文件时检查重复**：用户可能多次添加同一文件，添加前需校验路径是否已存在列表中。

## 打包 exe

```bash
# 安装（首次）
pip install pyinstaller

# 单文件、无控制台黑窗
pyinstaller -F -w 积分报表工具.py -n 积分报表工具
```

- `-F` 打成单个 exe（约 40-50MB，正常）
- `-w` 隐藏控制台窗口（GUI 程序必加，否则双击弹黑框）
- 产物在 `dist/` 目录
- 改完代码后需重新打包，把 dist 里的 exe 发给同事即可，**对方无需装 Python**
- 打包失败时检查：文件路径不要含特殊字符、杀毒软件拦截、matplotlib 数据文件需 `--add-data`（本项目暂未用到可忽略）

## 文件放置约定

- 新工具放 `py/<工具分类>/<工具名>.py`
- 打包产物 `dist/` 和 `build/` 随工具目录放
