# -*- coding: utf-8 -*-
"""
Excel 转高清图像工具（含嵌入图片）
==================================
功能：将 Excel 文件（含嵌入的图片）转换为一张完整的超高清图像，
      表格内容 + 内部图片全部渲染，保证清晰完整。

使用方式：双击运行 或 python excel_to_image.py
依赖：Pillow, openpyxl
安装：pip install Pillow openpyxl

说明：
  - 支持 WPS/Excel 嵌入图片（DISPIMG 函数）
  - 自动从 xlsx 压缩包提取 media 图片
  - 自动建立 图片ID → 单元格 的对应关系
  - 渲染为一张 PNG（无损），内容全、图片清
"""

import tkinter as tk
from tkinter import ttk, filedialog, messagebox
import os
import io
import zipfile
import threading
import re
from xml.etree import ElementTree as ET
from openpyxl import load_workbook
from openpyxl.utils import get_column_letter
from PIL import Image, ImageDraw, ImageFont


# ===================== 渲染常量 =====================
DPI = 200  # 渲染 DPI
SCALE = DPI / 72  # 缩放比例

# 字体大小
FONT_SIZE_TITLE = int(14 * SCALE)
FONT_SIZE_HEADER = int(11 * SCALE)
FONT_SIZE_CELL = int(10 * SCALE)

# 表格布局
ROW_HEIGHT_TEXT = int(40 * SCALE)  # 纯文本行基础高度
CELL_PADDING = int(8 * SCALE)
GRID_LINE_WIDTH = max(1, int(SCALE))

# 列宽（像素）- 给图片列更宽
COL_WIDTHS_RATIO = [8, 16, 8, 18, 10, 15, 10, 15, 12]  # 9列的相对宽度

# 图片单元格最大宽度/高度
IMG_MAX_W = int(200 * SCALE)
IMG_MAX_H = int(150 * SCALE)

# 颜色
COLOR_BG = (255, 255, 255)
COLOR_GRID = (180, 180, 180)
COLOR_HEADER_BG = (68, 114, 196)
COLOR_HEADER_FG = (255, 255, 255)
COLOR_CELL_BG = (255, 255, 255)
COLOR_CELL_FG = (50, 50, 50)
COLOR_ALT_BG = (245, 248, 255)


class ExcelToImageTool:
    """Excel 转高清图像工具主类"""

    FORMAT_OPTIONS = {
        'PNG (无损)': 'png',
        'JPEG (有损但小)': 'jpg',
        'TIFF (印刷级)': 'tiff',
        'BMP (无压缩)': 'bmp',
    }

    def __init__(self, root):
        self.root = root
        self.root.title("Excel 转高清图像工具")
        self.root.geometry("850x550")
        self.root.minsize(700, 450)

        self.filepath = ""
        self.output_dir = ""
        self.is_converting = False

        self._build_ui()

    def _build_ui(self):
        """构建界面"""
        # 标题
        top = ttk.Frame(self.root, padding=12)
        top.pack(fill=tk.X)
        ttk.Label(top, text="📊 Excel 转高清图像", font=("Microsoft YaHei", 14, "bold")).pack(side=tk.LEFT)

        # 文件选择
        file_frame = ttk.LabelFrame(self.root, text="文件选择", padding=12)
        file_frame.pack(fill=tk.X, padx=12, pady=(0, 8))

        file_row = ttk.Frame(file_frame)
        file_row.pack(fill=tk.X)
        ttk.Label(file_row, text="Excel 文件：", font=("Microsoft YaHei", 10)).pack(side=tk.LEFT)
        self.file_var = tk.StringVar(value="未选择")
        ttk.Label(file_row, textvariable=self.file_var, foreground="#333").pack(side=tk.LEFT, padx=5)
        ttk.Button(file_row, text="📂 浏览...", command=self.select_file, width=10).pack(side=tk.LEFT, padx=5)

        out_row = ttk.Frame(file_frame)
        out_row.pack(fill=tk.X, pady=(8, 0))
        ttk.Label(out_row, text="输出目录：", font=("Microsoft YaHei", 10)).pack(side=tk.LEFT)
        self.out_var = tk.StringVar(value="与源文件同目录")
        ttk.Label(out_row, textvariable=self.out_var, foreground="#666").pack(side=tk.LEFT, padx=5)
        ttk.Button(out_row, text="📁 浏览...", command=self.select_output_dir, width=10).pack(side=tk.LEFT, padx=5)

        # 参数
        opts = ttk.LabelFrame(self.root, text="渲染参数", padding=12)
        opts.pack(fill=tk.X, padx=12, pady=(0, 8))

        fmt_row = ttk.Frame(opts)
        fmt_row.pack(fill=tk.X, pady=3)
        ttk.Label(fmt_row, text="输出格式：", font=("Microsoft YaHei", 10)).pack(side=tk.LEFT)
        self.fmt_var = tk.StringVar(value='PNG (无损)')
        ttk.Combobox(fmt_row, textvariable=self.fmt_var,
                     values=list(self.FORMAT_OPTIONS.keys()),
                     width=18, state='readonly',
                     font=("Microsoft YaHei", 10)).pack(side=tk.LEFT, padx=5)

        dpi_row = ttk.Frame(opts)
        dpi_row.pack(fill=tk.X, pady=3)
        ttk.Label(dpi_row, text="渲染DPI：", font=("Microsoft YaHei", 10)).pack(side=tk.LEFT)
        self.dpi_var = tk.StringVar(value='200')
        ttk.Combobox(dpi_row, textvariable=self.dpi_var,
                     values=['150', '200', '300', '400'],
                     width=8, state='readonly',
                     font=("Microsoft YaHei", 10)).pack(side=tk.LEFT, padx=5)
        ttk.Label(dpi_row, text="💡 越高越清晰，图越大。200足够印刷，300+极高清",
                  foreground="#888").pack(side=tk.LEFT, padx=10)

        preview_row = ttk.Frame(opts)
        preview_row.pack(fill=tk.X, pady=3)
        self.preview_var = tk.StringVar(value="请选择 Excel 文件")
        ttk.Label(preview_row, textvariable=self.preview_var,
                  foreground="#555", font=("Microsoft YaHei", 9)).pack(side=tk.LEFT)

        # 进度和控制
        bottom = ttk.Frame(self.root, padding=(12, 8))
        bottom.pack(fill=tk.X, side=tk.BOTTOM)

        self.progress_var = tk.DoubleVar(value=0)
        self.progress_bar = ttk.Progressbar(bottom, variable=self.progress_var, maximum=100)
        self.progress_bar.pack(fill=tk.X, pady=(0, 5))

        ctrl = ttk.Frame(bottom)
        ctrl.pack(fill=tk.X)
        self.status_var = tk.StringVar(value="就绪，请选择 Excel 文件")
        ttk.Label(ctrl, textvariable=self.status_var, foreground="#555").pack(side=tk.LEFT)

        self.convert_btn = ttk.Button(ctrl, text="🚀 开始转换", command=self.start_conversion, width=16)
        self.convert_btn.pack(side=tk.RIGHT, padx=5)
        self.stop_btn = ttk.Button(ctrl, text="⏹ 停止", command=self.stop_conversion,
                                   width=10, state=tk.DISABLED)
        self.stop_btn.pack(side=tk.RIGHT, padx=5)

    def select_file(self):
        """选择 Excel 文件"""
        path = filedialog.askopenfilename(
            filetypes=[('Excel 文件', '*.xlsx *.xls'), ('所有文件', '*.*')],
            title='选择 Excel 文件'
        )
        if path:
            self.filepath = path
            self.file_var.set(os.path.basename(path))
            self._update_preview()

    def select_output_dir(self):
        """选择输出目录"""
        folder = filedialog.askdirectory(title='选择输出目录')
        if folder:
            self.output_dir = folder
            self.out_var.set(f"输出到：{folder}")

    def _update_preview(self):
        """更新文件预览"""
        if not self.filepath:
            return
        try:
            wb = load_workbook(self.filepath, read_only=True, data_only=True)
            ws = wb.active
            rows = ws.max_row
            cols = ws.max_column
            img_count = len(ws._images) if hasattr(ws, '_images') else 0

            # 尝试从 xlsx 中提取图片总计
            total_imgs = 0
            if self.filepath.lower().endswith('.xlsx'):
                with zipfile.ZipFile(self.filepath, 'r') as z:
                    total_imgs = len([n for n in z.namelist() if n.startswith('xl/media/')])

            self.preview_var.set(f"共 {len(wb.sheetnames)} 个Sheet | "
                                 f"主表 {rows} 行 × {cols} 列 | "
                                 f"嵌入图片 {total_imgs} 张")
            wb.close()
        except Exception as e:
            self.preview_var.set(f"读取失败：{e}")

    def stop_conversion(self):
        """停止转换"""
        self.is_converting = False

    def start_conversion(self):
        """开始转换"""
        if self.is_converting:
            return
        if not self.filepath:
            messagebox.showwarning("提示", "请先选择 Excel 文件")
            return

        self.is_converting = True
        self.convert_btn.config(state=tk.DISABLED)
        self.stop_btn.config(state=tk.NORMAL)
        self.progress_var.set(0)

        dpi = int(self.dpi_var.get())
        fmt = self.FORMAT_OPTIONS[self.fmt_var.get()]

        thread = threading.Thread(
            target=self._convert_worker,
            args=(dpi, fmt),
            daemon=True
        )
        thread.start()

    def _set_status(self, text):
        if hasattr(self, 'cli_mode') and self.cli_mode:
            print(f"  {text}")
        elif hasattr(self, 'root') and self.root:
            self.root.after(0, lambda: self.status_var.set(text))

    def _set_progress(self, value):
        if hasattr(self, 'cli_mode') and self.cli_mode:
            bar_len = 30
            filled = int(bar_len * value / 100)
            bar = '█' * filled + '░' * (bar_len - filled)
            print(f"\r  [{bar}] {value:.0f}%", end='', flush=True)
            if value >= 100:
                print()  # 换行
        elif hasattr(self, 'root') and self.root:
            self.root.after(0, lambda: self.progress_var.set(value))

    def _convert_worker(self, dpi, fmt):
        """后台转换工作线程"""
        try:
            self._render_excel_to_image(dpi, fmt)
        except Exception as e:
            if hasattr(self, 'cli_mode') and self.cli_mode:
                print(f"\n  ❌ 错误：{e}")
            elif hasattr(self, 'root') and self.root:
                self.root.after(0, lambda: messagebox.showerror("错误", f"转换失败：\n{e}"))
        finally:
            self.is_converting = False
            if hasattr(self, 'cli_mode') and self.cli_mode:
                pass  # CLI模式不需要恢复按钮
            elif hasattr(self, 'root') and self.root:
                self.root.after(0, self._on_complete)

    def _on_complete(self):
        """完成回调"""
        self.convert_btn.config(state=tk.NORMAL)
        self.stop_btn.config(state=tk.DISABLED)
        self.progress_var.set(100)

    def _render_excel_to_image(self, dpi, fmt):
        """核心：渲染 Excel 到图像"""
        self._set_status("⏳ 读取 Excel 数据...")

        wb = load_workbook(self.filepath, read_only=True, data_only=True)
        ws = wb.active

        # 读取所有数据
        headers = []
        rows_data = []
        for row_idx, row in enumerate(ws.iter_rows(min_row=1, max_row=ws.max_row, values_only=True), 1):
            if row_idx == 1:
                headers = [str(c) if c is not None else '' for c in row]
            else:
                rows_data.append([str(c)[:100] if c is not None else '' for c in row])
        wb.close()

        if not rows_data:
            raise ValueError("Excel 没有数据行")

        self._set_status(f"⏳ 提取嵌入图片 ({len(rows_data)} 行)...")

        # 提取图片和映射关系
        image_map = self._extract_images()
        # image_map: {cell_ref: [img_path1, img_path2, ...]}

        self._set_status(f"⏳ 渲染图像 (DPI={dpi})...")

        # === Pillow 渲染 ===
        scale = dpi / 72
        font_size = max(12, int(11 * scale))

        # 尝试加载中文字体
        font = self._load_font(font_size)
        font_header = self._load_font(font_size + 2, bold=True)
        font_small = self._load_font(max(10, font_size - 2))

        # 计算列宽（用文字宽度估算）
        col_widths = self._calc_col_widths(headers, rows_data, font, scale)

        # 计算行高（考虑图片列中的图像数量）
        row_heights, total_images = self._calc_row_heights(
            headers, rows_data, image_map, col_widths, font, scale
        )

        # 图片列的列索引
        img_col_indices = self._detect_image_columns(headers)

        # 画布尺寸
        canvas_w = sum(col_widths) + 2 * CELL_PADDING
        canvas_h = int(60 * scale) + sum(row_heights) + 2 * CELL_PADDING  # 标题高度 + 行高之和

        self._set_status(f"⏳ 绘制画布 ({canvas_w}×{canvas_h} px)...")

        img = Image.new('RGB', (canvas_w, canvas_h), COLOR_BG)
        draw = ImageDraw.Draw(img)

        # 绘制标题
        title = f"Excel: {os.path.basename(self.filepath)}  |  {len(rows_data)} 行  |  {total_images} 张图片"
        title_y = CELL_PADDING
        draw.text((CELL_PADDING, title_y), title, fill=(100, 100, 100), font=font_small)

        # 绘制表头
        header_y = title_y + int(30 * scale)
        x = CELL_PADDING
        header_h = int(36 * scale)
        for col_idx, h in enumerate(headers):
            cell_x = x
            cell_w = col_widths[col_idx]
            # Header background
            draw.rectangle([cell_x, header_y, cell_x + cell_w, header_y + header_h],
                           fill=COLOR_HEADER_BG)
            # Header text
            text_x = cell_x + CELL_PADDING
            text_y = header_y + (header_h - font_size) // 2
            draw.text((text_x, text_y), h, fill=COLOR_HEADER_FG, font=font_header)
            x += cell_w

        # 绘制网格线 - 表头底部
        grid_y_start = header_y + header_h

        # 绘制每行数据
        y = grid_y_start
        img_counter = 0

        for row_idx, row in enumerate(rows_data):
            if not self.is_converting:
                self._set_status("⏹ 已停止")
                return

            if row_idx % 20 == 0:
                self._set_progress((row_idx / len(rows_data)) * 80)
                self._set_status(f"⏳ 渲染第 {row_idx + 1}/{len(rows_data)} 行...")

            row_h = row_heights[row_idx]

            # 绘制单元格背景
            x = CELL_PADDING
            for col_idx, cell_val in enumerate(row):
                cell_x = x
                cell_w = col_widths[col_idx]
                bg = COLOR_ALT_BG if row_idx % 2 == 1 else COLOR_CELL_BG
                draw.rectangle([cell_x, y, cell_x + cell_w, y + row_h], fill=bg)
                x += cell_w

            # 绘制单元格内容
            x = CELL_PADDING
            for col_idx in range(len(row)):
                cell_x = x
                cell_w = col_widths[col_idx]
                cell_val = row[col_idx] if col_idx < len(row) else ''

                if col_idx in img_col_indices:
                    cell_ref = self._get_cell_ref(row_idx + 2, col_idx + 1)
                    img_keys = [k for k in image_map if k.upper() == cell_ref.upper()]
                    if img_keys:
                        imgs = image_map[img_keys[0]]
                        self._draw_images_in_cell(img, imgs, cell_x, y, cell_w, row_h)
                        img_counter += len(imgs)
                else:
                    text_x = cell_x + CELL_PADDING
                    text_y = y + CELL_PADDING
                    display_text = self._truncate_text(str(cell_val), cell_w - 2 * CELL_PADDING, font)
                    draw.text((text_x, text_y), display_text, fill=COLOR_CELL_FG, font=font)

                x += cell_w

            # 绘制行底部网格线
            draw.line([(CELL_PADDING, y + row_h), (canvas_w - CELL_PADDING, y + row_h)],
                      fill=COLOR_GRID, width=GRID_LINE_WIDTH)

            y += row_h

        # 绘制垂直网格线
        x = CELL_PADDING
        for w in col_widths:
            x += w
            draw.line([(x, header_y), (x, y)], fill=COLOR_GRID, width=GRID_LINE_WIDTH)

        # 外边框
        draw.rectangle([CELL_PADDING, header_y, canvas_w - CELL_PADDING, y],
                       outline=COLOR_GRID, width=GRID_LINE_WIDTH * 2)

        self._set_progress(90)
        self._set_status("⏹ 保存图像...")

        # 输出文件路径
        if self.output_dir:
            out_dir = self.output_dir
        else:
            out_dir = os.path.dirname(self.filepath)
        base_name = os.path.splitext(os.path.basename(self.filepath))[0]
        out_path = os.path.join(out_dir, f"{base_name}_完整图像.{fmt}")

        # 保存
        if fmt == 'jpg':
            img.save(out_path, quality=95, optimize=True)
        elif fmt == 'png':
            img.save(out_path, optimize=True)
        elif fmt == 'tiff':
            img.save(out_path, compression='tiff_deflate')
        else:
            img.save(out_path)

        final_size = os.path.getsize(out_path)
        size_mb = final_size / (1024 * 1024)

        msg = (f"✅ 转换完成！\n\n"
               f"📁 输出：{out_path}\n"
               f"📐 尺寸：{canvas_w} × {canvas_h} 像素\n"
               f"📦 文件大小：{size_mb:.1f} MB\n"
               f"🖼 包含图片：{img_counter} 张")

        self._set_progress(100)
        self._set_status(f"✅ 完成！{size_mb:.1f} MB")

        self.root.after(0, lambda: self._show_done(msg, out_path))

    def _extract_images(self):
        """从 xlsx 中提取图片，并建立 图片ID → 单元格引用 的映射"""
        # 返回 {cell_ref: [img_data1, img_data2, ...]}
        image_map = {}

        if not self.filepath.lower().endswith('.xlsx'):
            return image_map

        # 1. 读取所有单元格中的 DISPIMG ID
        # cell_image_ids: {cell_ref: [ID1, ID2, ...]}
        cell_image_ids = self._get_cell_image_ids()

        if not cell_image_ids:
            return image_map

        # 2. 从 xlsx 中提取图片 ID → 图片数据 的映射
        id_to_data = self._get_image_id_data_map()

        # 3. 合并
        for cell_ref, ids in cell_image_ids.items():
            for img_id in ids:
                if img_id in id_to_data:
                    key = cell_ref.upper()
                    if key not in image_map:
                        image_map[key] = []
                    image_map[key].append(id_to_data[img_id])

        return image_map

    def _get_cell_image_ids(self):
        """获取每个单元格中的图片 ID 列表"""
        # {cell_ref: [ID1, ID2, ...]}
        result = {}

        wb = load_workbook(self.filepath, read_only=True, data_only=True)
        ws = wb.active

        for row_idx, row in enumerate(ws.iter_rows(min_row=1, max_row=ws.max_row), 1):
            for col_idx, cell in enumerate(row, 1):
                val = cell.value
                if val and isinstance(val, str) and 'DISPIMG' in val:
                    ids = re.findall(r'DISPIMG\("([^"]+)"\)', val)
                    if ids:
                        cell_ref = f"{get_column_letter(col_idx)}{row_idx}"
                        result[cell_ref] = ids

        wb.close()
        return result

    def _get_image_id_data_map(self):
        """获取图片ID → 图片二进制数据 的映射"""
        result = {}

        with zipfile.ZipFile(self.filepath, 'r') as z:
            # 1. 读取 cellimages.xml
            try:
                cell_imgs_xml = z.read('xl/cellimages.xml').decode('utf-8')
            except KeyError:
                return result

            # 2. 读取 rels 获取 rId → 图片路径
            rels_map = {}
            try:
                rels_xml = z.read('xl/_rels/cellimages.xml.rels').decode('utf-8')
                rels_root = ET.fromstring(rels_xml)
                for rel in rels_root:
                    rId = rel.get('Id')
                    target = rel.get('Target')
                    if target:
                        if target.startswith('../'):
                            # 相对路径转换
                            target = target.replace('../', 'xl/')
                        elif not target.startswith('xl/'):
                            target = 'xl/' + target
                        rels_map[rId] = target
            except Exception:
                pass

            # 3. 解析 cellimages.xml 获取 图片ID → rId
            # 去掉命名空间简化解析
            clean_xml = re.sub(r'xmlns[^"]*"[^"]*"', '', cell_imgs_xml)
            clean_xml = re.sub(r'<([a-z]+):', '<', clean_xml)
            clean_xml = re.sub(r'</([a-z]+):', '</', clean_xml)

            try:
                root = ET.fromstring(clean_xml)
            except ET.ParseError:
                # 进一步清理
                clean_xml = re.sub(r'<[^>]*xmlns[^>]*>', '', cell_imgs_xml)
                try:
                    root = ET.fromstring(clean_xml)
                except ET.ParseError:
                    return result

            for cell_img in root:
                for pic in cell_img.iter():
                    tag = pic.tag.split('}')[-1] if '}' in pic.tag else pic.tag
                    if tag == 'pic':
                        img_id = None
                        embed_id = None
                        for elem in pic.iter():
                            etag = elem.tag.split('}')[-1] if '}' in elem.tag else elem.tag
                            if etag == 'cNvPr':
                                img_id = elem.get('name', '')
                            elif etag == 'blip':
                                # 尝试各种 rId 属性名
                                for attr_name in elem.attrib:
                                    if 'embed' in attr_name:
                                        embed_id = elem.attrib[attr_name]
                                        break
                                if not embed_id:
                                    embed_id = elem.get('embed', '')
                                if not embed_id:
                                    embed_id = elem.get('{http://schemas.openxmlformats.org/officeDocument/2006/relationships}embed', '')

                        if img_id and embed_id and embed_id in rels_map:
                            media_path = rels_map[embed_id]
                            try:
                                result[img_id] = z.read(media_path)
                            except KeyError:
                                pass

        return result

    def _calc_col_widths(self, headers, rows_data, font, scale):
        """计算每列宽度"""
        # 基础宽度 = 表头文字宽度
        total_width = 800 * scale  # 目标总宽度
        widths = []

        for col_idx, h in enumerate(headers):
            # 初始宽度基于表头
            w = len(h) * font.size * 0.7 + 4 * CELL_PADDING
            widths.append(int(w))

        # 归一化到目标宽度
        total = sum(widths)
        if total > 0:
            widths = [max(int(60 * scale), int(w / total * total_width)) for w in widths]

        # 图片列额外加宽
        img_cols = self._detect_image_columns(headers)
        for col_idx in img_cols:
            if col_idx < len(widths):
                widths[col_idx] = max(widths[col_idx], int(180 * scale))

        return widths

    def _detect_image_columns(self, headers):
        """检测哪些列是图片列"""
        img_cols = []
        keywords = ['照片', '图片', '图像', 'photo', 'pic', 'image', 'img', '上传', '附件']
        for col_idx, h in enumerate(headers):
            h_lower = h.lower()
            for kw in keywords:
                if kw in h_lower:
                    img_cols.append(col_idx)
                    break
        return img_cols

    def _calc_row_heights(self, headers, rows_data, image_map, col_widths, font, scale):
        """计算每行高度"""
        img_col_indices = self._detect_image_columns(headers)
        row_heights = []
        total_images = 0

        # 自动检测 DISPIMG 列
        if not img_col_indices:
            discols = set()
            for row in rows_data:
                for col_idx, cell_val in enumerate(row):
                    if 'DISPIMG' in str(cell_val):
                        discols.add(col_idx)
            img_col_indices = list(discols)

        for row_idx, row in enumerate(rows_data):
            row_h = ROW_HEIGHT_TEXT

            for col_idx in img_col_indices:
                if col_idx < len(row):
                    if isinstance(row[col_idx], str):
                        img_count = row[col_idx].count('DISPIMG')
                    else:
                        img_count = 0
                    if img_count > 0:
                        # 每张图片高度比例
                        img_h_each = int(50 * scale)
                        needed_h = max(img_count * img_h_each, int(50 * scale))
                        row_h = max(row_h, needed_h)
                        total_images += img_count

            row_heights.append(row_h)

        return row_heights, total_images

    def _get_cell_ref(self, row, col):
        """获取单元格引用，如 A1, B2"""
        return f"{get_column_letter(col)}{row}"

    def _draw_images_in_cell(self, target_img, img_list, cell_x, cell_y, cell_w, cell_h):
        """在单元格内绘制图片（平铺或纵向排列）"""
        if not img_list:
            return

        padding = CELL_PADDING
        avail_w = cell_w - 2 * padding
        avail_h = cell_h - 2 * padding

        if avail_w <= 0 or avail_h <= 0:
            return

        # 单张图片时居中放大，多张时纵向排列
        if len(img_list) == 1:
            try:
                pil_img = Image.open(io.BytesIO(img_list[0]))
                pil_img = pil_img.convert('RGB')
                ratio = min(avail_w / pil_img.width, avail_h / pil_img.height, 3.0)
                new_w = int(pil_img.width * ratio)
                new_h = int(pil_img.height * ratio)
                pil_img = pil_img.resize((new_w, new_h), Image.LANCZOS)
                paste_x = cell_x + (cell_w - new_w) // 2
                paste_y = cell_y + (cell_h - new_h) // 2
                target_img.paste(pil_img, (paste_x, paste_y))
                pil_img.close()
            except Exception:
                pass
        else:
            # 多张：纵向排列
            max_img_h = min(avail_h // len(img_list), avail_h)
            for i, img_data in enumerate(img_list):
                try:
                    pil_img = Image.open(io.BytesIO(img_data))
                    pil_img = pil_img.convert('RGB')
                    ratio = min(avail_w / pil_img.width, max_img_h / pil_img.height, 3.0)
                    new_w = int(pil_img.width * ratio)
                    new_h = int(pil_img.height * ratio)
                    pil_img = pil_img.resize((new_w, new_h), Image.LANCZOS)
                    paste_x = cell_x + (cell_w - new_w) // 2
                    paste_y = cell_y + padding + i * max_img_h + (max_img_h - new_h) // 2
                    target_img.paste(pil_img, (paste_x, paste_y))
                    pil_img.close()
                except Exception:
                    pass

    def _truncate_text(self, text, max_width, font):
        """根据宽度截断文本"""
        if not text:
            return ''
        # 简单截断
        char_w = font.size * 0.6
        max_chars = int(max_width / char_w) if char_w > 0 else 50
        if len(text) > max_chars:
            return text[:max_chars - 2] + '..'
        return text

    def _load_font(self, size, bold=False):
        """加载字体（尽量用中文字体）"""
        font_paths = [
            'C:/Windows/Fonts/msyh.ttc',       # 微软雅黑
            'C:/Windows/Fonts/msyhbd.ttc',     # 微软雅黑粗体
            'C:/Windows/Fonts/simhei.ttf',     # 黑体
            'C:/Windows/Fonts/simsun.ttc',     # 宋体
            'C:/Windows/Fonts/msyh.ttc',       # 微软雅黑
        ]
        if bold:
            font_paths = [
                'C:/Windows/Fonts/msyhbd.ttc',
                'C:/Windows/Fonts/simhei.ttf',
            ] + font_paths

        for path in font_paths:
            try:
                return ImageFont.truetype(path, size)
            except (IOError, OSError):
                continue
        return ImageFont.load_default()

    def _show_done(self, msg, out_path):
        """显示完成对话框"""
        if messagebox.askyesno("完成", msg + "\n\n是否打开所在目录？"):
            os.startfile(os.path.dirname(out_path))


def run_gui():
    """运行 GUI 模式"""
    root = tk.Tk()
    try:
        ttk.Style().theme_use('vista')
    except tk.TclError:
        try:
            ttk.Style().theme_use('clam')
        except tk.TclError:
            pass

    # 如果命令行指定了文件，自动填入
    import sys
    app = ExcelToImageTool(root)
    if len(sys.argv) > 1 and os.path.isfile(sys.argv[1]):
        app.filepath = sys.argv[1]
        app.file_var.set(os.path.basename(sys.argv[1]))
        app._update_preview()
    root.mainloop()


def run_cli(filepath, output_dir=None, dpi=200, fmt='png'):
    """命令行模式：直接转换"""
    tool = ExcelToImageTool.__new__(ExcelToImageTool)
    tool.filepath = filepath
    tool.output_dir = output_dir or ""
    tool.is_converting = True  # 标记为转换中（防止被停止检查拦截）
    tool.cli_mode = True  # 标记为CLI模式
    tool.root = None

    print(f"\n📊 Excel 转图像: {filepath}")
    print(f"   DPI={dpi}, 格式={fmt}")
    print(f"   输出目录: {output_dir or os.path.dirname(filepath)}")
    print()
    try:
        tool._render_excel_to_image(dpi, fmt)
        print("\n✅ 转换完成！")
    except Exception as e:
        print(f"\n❌ 转换失败：{e}")
        import traceback
        traceback.print_exc()
    finally:
        tool.is_converting = False


if __name__ == '__main__':
    import sys
    if len(sys.argv) > 1 and sys.argv[1] != '--gui':
        # CLI 模式
        file = sys.argv[1]
        out_dir = None
        dpi = 200
        fmt = 'png'
        for i, arg in enumerate(sys.argv[2:], 2):
            if arg == '--out' and i + 1 < len(sys.argv):
                out_dir = sys.argv[i + 1]
            elif arg == '--dpi' and i + 1 < len(sys.argv):
                dpi = int(sys.argv[i + 1])
            elif arg == '--fmt' and i + 1 < len(sys.argv):
                fmt = sys.argv[i + 1]
        run_cli(file, out_dir, dpi, fmt)
    else:
        run_gui()
