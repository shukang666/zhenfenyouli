# -*- coding: utf-8 -*-
"""
PDF 转高清图像工具
=================
功能：将 PDF 文件转换为高清无损图像，支持批量转换、DPI 调节、多种输出格式

使用方式：双击运行 或 命令行 python pdf_to_image.py
依赖：PyMuPDF (fitz)
安装：pip install PyMuPDF
"""

import tkinter as tk
from tkinter import ttk, filedialog, messagebox
import fitz  # PyMuPDF
import os
import threading
from datetime import datetime


class PDFToImageTool:
    """PDF 转高清图像工具主类"""

    # DPI 选项
    DPI_OPTIONS = {
        '150 DPI (较快)': 150,
        '200 DPI (标准)': 200,
        '300 DPI (高清)': 300,
        '400 DPI (超高清)': 400,
        '600 DPI (极高清)': 600,
    }

    # 输出格式选项
    FORMAT_OPTIONS = {
        'PNG (无损)': 'png',
        'JPEG (压缩)': 'jpg',
        'BMP (无压缩)': 'bmp',
        'TIFF (印刷级)': 'tiff',
    }

    def __init__(self, root):
        self.root = root
        self.root.title("PDF 转高清图像工具")
        self.root.geometry("900x650")
        self.root.minsize(750, 550)

        self.file_list = []  # 选中的 PDF 文件列表
        self.output_dir = ""  # 输出目录
        self.is_converting = False

        self._build_ui()

    def _build_ui(self):
        """构建界面"""
        # ========== 顶部工具栏 ==========
        top = ttk.Frame(self.root, padding=12)
        top.pack(fill=tk.X)

        ttk.Label(top, text="📄 PDF 转高清图像", font=("Microsoft YaHei", 14, "bold")).pack(side=tk.LEFT)

        # ========== 参数设置区 ==========
        settings = ttk.LabelFrame(self.root, text="转换参数", padding=12)
        settings.pack(fill=tk.X, padx=12, pady=(0, 8))

        # DPI 设置
        dpi_frame = ttk.Frame(settings)
        dpi_frame.pack(fill=tk.X, pady=5)
        ttk.Label(dpi_frame, text="图像清晰度 (DPI)：", font=("Microsoft YaHei", 10)).pack(side=tk.LEFT)

        self.dpi_var = tk.StringVar(value='300 DPI (高清)')
        dpi_combo = ttk.Combobox(dpi_frame, textvariable=self.dpi_var,
                                 values=list(self.DPI_OPTIONS.keys()),
                                 width=18, state='readonly',
                                 font=("Microsoft YaHei", 10))
        dpi_combo.pack(side=tk.LEFT, padx=5)
        ttk.Label(dpi_frame, text="💡 越高越清晰，文件也越大", foreground="#888").pack(side=tk.LEFT, padx=10)

        # 输出格式
        fmt_frame = ttk.Frame(settings)
        fmt_frame.pack(fill=tk.X, pady=5)
        ttk.Label(fmt_frame, text="输出图像格式：", font=("Microsoft YaHei", 10)).pack(side=tk.LEFT)

        self.fmt_var = tk.StringVar(value='PNG (无损)')
        fmt_combo = ttk.Combobox(fmt_frame, textvariable=self.fmt_var,
                                 values=list(self.FORMAT_OPTIONS.keys()),
                                 width=18, state='readonly',
                                 font=("Microsoft YaHei", 10))
        fmt_combo.pack(side=tk.LEFT, padx=5)
        ttk.Label(fmt_frame, text="💡 PNG 无损推荐，BMP/TIFF 文件最大", foreground="#888").pack(side=tk.LEFT, padx=10)

        # 缩放比例（可选，进一步放大）
        zoom_frame = ttk.Frame(settings)
        zoom_frame.pack(fill=tk.X, pady=5)
        ttk.Label(zoom_frame, text="额外缩放倍数：", font=("Microsoft YaHei", 10)).pack(side=tk.LEFT)

        self.zoom_var = tk.StringVar(value='1.0')
        zoom_combo = ttk.Combobox(zoom_frame, textvariable=self.zoom_var,
                                  values=['1.0', '1.5', '2.0', '3.0', '4.0'],
                                  width=8, state='readonly',
                                  font=("Microsoft YaHei", 10))
        zoom_combo.pack(side=tk.LEFT, padx=5)
        ttk.Label(zoom_frame, text="💡 在 DPI 基础上进一步放大", foreground="#888").pack(side=tk.LEFT, padx=10)

        # ========== 文件操作区 ==========
        file_ops = ttk.Frame(self.root, padding=(12, 0))
        file_ops.pack(fill=tk.X)

        ttk.Button(file_ops, text="➕ 添加 PDF 文件", command=self.add_files,
                   width=16).pack(side=tk.LEFT, padx=(0, 5))
        ttk.Button(file_ops, text="📁 添加文件夹", command=self.add_folder,
                   width=14).pack(side=tk.LEFT, padx=5)
        ttk.Button(file_ops, text="🗑️ 清空列表", command=self.clear_files,
                   width=12).pack(side=tk.LEFT, padx=5)

        ttk.Button(file_ops, text="📂 选择输出目录", command=self.select_output_dir,
                   width=14).pack(side=tk.RIGHT, padx=5)

        self.output_dir_var = tk.StringVar(value="未设置（默认保存到 PDF 同目录）")
        ttk.Label(file_ops, textvariable=self.output_dir_var,
                  foreground="#666").pack(side=tk.RIGHT, padx=10)

        # ========== 文件列表区 ==========
        list_frame = ttk.LabelFrame(self.root, text="待转换文件列表", padding=8)
        list_frame.pack(fill=tk.BOTH, expand=True, padx=12, pady=8)

        # 表格
        columns = ('文件名', '页数', '文件路径')
        self.tree = ttk.Treeview(list_frame, columns=columns, show='headings', height=10)

        self.tree.heading('文件名', text='文件名')
        self.tree.heading('页数', text='页数')
        self.tree.heading('文件路径', text='文件路径')

        self.tree.column('文件名', width=180, anchor='w')
        self.tree.column('页数', width=60, anchor='center')
        self.tree.column('文件路径', width=500, anchor='w')

        vsb = ttk.Scrollbar(list_frame, orient="vertical", command=self.tree.yview)
        hsb = ttk.Scrollbar(list_frame, orient="horizontal", command=self.tree.xview)
        self.tree.configure(yscrollcommand=vsb.set, xscrollcommand=hsb.set)

        self.tree.grid(row=0, column=0, sticky='nsew')
        vsb.grid(row=0, column=1, sticky='ns')
        hsb.grid(row=1, column=0, sticky='ew')
        list_frame.grid_rowconfigure(0, weight=1)
        list_frame.grid_columnconfigure(0, weight=1)

        # 右键菜单
        self.context_menu = tk.Menu(self.root, tearoff=0)
        self.context_menu.add_command(label="删除选中", command=self.remove_selected)
        self.context_menu.add_command(label="清空全部", command=self.clear_files)
        self.tree.bind("<Button-3>", self._show_context_menu)

        # ========== 进度和控制区 ==========
        bottom = ttk.Frame(self.root, padding=(12, 8))
        bottom.pack(fill=tk.X, side=tk.BOTTOM)

        # 进度条
        self.progress_var = tk.DoubleVar(value=0)
        self.progress_bar = ttk.Progressbar(bottom, variable=self.progress_var,
                                            maximum=100, length=400)
        self.progress_bar.pack(fill=tk.X, pady=(0, 5))

        # 状态 + 按钮
        ctrl = ttk.Frame(bottom)
        ctrl.pack(fill=tk.X)

        self.status_var = tk.StringVar(value="就绪，请添加 PDF 文件")
        ttk.Label(ctrl, textvariable=self.status_var,
                  foreground="#555").pack(side=tk.LEFT)

        self.convert_btn = ttk.Button(ctrl, text="🚀 开始转换", command=self.start_conversion,
                                      width=16)
        self.convert_btn.pack(side=tk.RIGHT, padx=5)

        self.stop_btn = ttk.Button(ctrl, text="⏹ 停止", command=self.stop_conversion,
                                   width=10, state=tk.DISABLED)
        self.stop_btn.pack(side=tk.RIGHT, padx=5)

    def _show_context_menu(self, event):
        """显示右键菜单"""
        try:
            self.context_menu.tk_popup(event.x_root, event.y_root)
        finally:
            self.context_menu.grab_release()

    def add_files(self):
        """添加 PDF 文件"""
        files = filedialog.askopenfilenames(
            filetypes=[('PDF 文件', '*.pdf'), ('所有文件', '*.*')],
            title='选择 PDF 文件'
        )
        for f in files:
            self._add_file(f)
        self._update_status()

    def add_folder(self):
        """添加整个文件夹的 PDF"""
        folder = filedialog.askdirectory(title='选择包含 PDF 的文件夹')
        if not folder:
            return
        count = 0
        for root, dirs, files in os.walk(folder):
            for fname in files:
                if fname.lower().endswith('.pdf'):
                    self._add_file(os.path.join(root, fname))
                    count += 1
        if count == 0:
            messagebox.showinfo("提示", "所选文件夹中没有找到 PDF 文件")
        self._update_status()

    def _add_file(self, filepath):
        """添加单个文件到列表"""
        if filepath in [self.tree.item(item)['values'][2] for item in self.tree.get_children()]:
            return  # 已存在
        try:
            doc = fitz.open(filepath)
            page_count = len(doc)
            doc.close()
            fname = os.path.basename(filepath)
            self.tree.insert('', tk.END, values=(fname, page_count, filepath))
        except Exception as e:
            messagebox.showwarning("警告", f"无法读取文件：\n{filepath}\n\n{e}")

    def remove_selected(self):
        """删除选中的文件"""
        selected = self.tree.selection()
        for item in selected:
            self.tree.delete(item)
        self._update_status()

    def clear_files(self):
        """清空文件列表"""
        for item in self.tree.get_children():
            self.tree.delete(item)
        self._update_status()

    def select_output_dir(self):
        """选择输出目录"""
        folder = filedialog.askdirectory(title='选择输出目录')
        if folder:
            self.output_dir = folder
            self.output_dir_var.set(f"输出到：{folder}")

    def _update_status(self):
        """更新状态显示"""
        count = len(self.tree.get_children())
        if count == 0:
            self.status_var.set("就绪，请添加 PDF 文件")
        else:
            total_pages = sum(int(self.tree.item(item)['values'][1]) for item in self.tree.get_children())
            self.status_var.set(f"共 {count} 个文件，合计 {total_pages} 页")

    def start_conversion(self):
        """开始转换"""
        if self.is_converting:
            return

        items = self.tree.get_children()
        if not items:
            messagebox.showwarning("提示", "请先添加 PDF 文件")
            return

        # 获取参数
        dpi = self.DPI_OPTIONS[self.dpi_var.get()]
        fmt = self.FORMAT_OPTIONS[self.fmt_var.get()]
        try:
            zoom = float(self.zoom_var.get())
        except ValueError:
            zoom = 1.0

        self.is_converting = True
        self.convert_btn.config(state=tk.DISABLED)
        self.stop_btn.config(state=tk.NORMAL)
        self.progress_var.set(0)

        # 在后台线程执行转换
        thread = threading.Thread(
            target=self._convert_worker,
            args=(items, dpi, fmt, zoom),
            daemon=True
        )
        thread.start()

    def stop_conversion(self):
        """停止转换"""
        self.is_converting = False
        self.status_var.set("正在停止...")

    def _convert_worker(self, items, dpi, fmt, zoom):
        """后台转换工作线程"""
        total = len(items)
        success = 0
        failed = 0
        skipped = 0

        for idx, item in enumerate(items):
            if not self.is_converting:
                self._set_status(f"⏹ 已停止，完成 {idx}/{total} 个文件")
                break

            values = self.tree.item(item)['values']
            filepath = values[2]  # 文件路径
            fname = values[0]     # 文件名

            self._set_status(f"[{idx+1}/{total}] 正在转换: {fname}")
            self._set_progress((idx / total) * 100)

            try:
                result = self._convert_single_file(filepath, dpi, fmt, zoom)
                if result:
                    success += 1
                else:
                    skipped += 1
            except Exception as e:
                failed += 1
                print(f"转换失败: {filepath} - {e}")

        # 转换完成
        self.is_converting = False
        self.root.after(0, self._conversion_done, success, failed, skipped, total)

    def _convert_single_file(self, filepath, dpi, fmt, zoom):
        """转换单个 PDF 文件"""
        doc = fitz.open(filepath)
        try:
            # 确定输出目录
            if self.output_dir:
                out_dir = self.output_dir
            else:
                out_dir = os.path.dirname(filepath)

            base_name = os.path.splitext(os.path.basename(filepath))[0]
            total_pages = len(doc)

            for page_num in range(total_pages):
                page = doc[page_num]

                # 计算矩阵：DPI + 额外缩放
                # 72 是 PDF 的默认 DPI
                actual_dpi = dpi * zoom
                mat = fitz.Matrix(actual_dpi / 72, actual_dpi / 72)

                # 渲染为像素图（使用高质量设置）
                pix = page.get_pixmap(
                    matrix=mat,
                    alpha=(fmt == 'png')  # PNG 支持透明
                )

                # 构建输出文件名
                if total_pages == 1:
                    out_name = f"{base_name}.{fmt}"
                else:
                    out_name = f"{base_name}_第{page_num + 1}页.{fmt}"

                out_path = os.path.join(out_dir, out_name)

                # 保存图像
                if fmt == 'jpg':
                    pix.save(out_path, jpg_quality=95)
                elif fmt == 'tiff':
                    pix.save(out_path, deflate=True)  # 使用压缩但无损
                else:
                    pix.save(out_path)

                # 释放内存
                pix = None

            return True
        finally:
            doc.close()

    def _set_status(self, text):
        """线程安全的状态更新"""
        self.root.after(0, lambda: self.status_var.set(text))

    def _set_progress(self, value):
        """线程安全的进度更新"""
        self.root.after(0, lambda: self.progress_var.set(value))

    def _conversion_done(self, success, failed, skipped, total):
        """转换完成回调"""
        self.convert_btn.config(state=tk.NORMAL)
        self.stop_btn.config(state=tk.DISABLED)
        self.progress_var.set(100)

        msg = f"✅ 转换完成！成功 {success}，失败 {failed}，跳过 {skipped}（共 {total} 个文件）"
        self.status_var.set(msg)
        messagebox.showinfo("完成", msg)

        # 如果设置了输出目录，询问是否打开
        if self.output_dir and os.path.exists(self.output_dir):
            if messagebox.askyesno("打开目录", "是否打开输出目录？"):
                os.startfile(self.output_dir)


def main():
    root = tk.Tk()
    try:
        ttk.Style().theme_use('vista')
    except tk.TclError:
        try:
            ttk.Style().theme_use('clam')
        except tk.TclError:
            pass

    PDFToImageTool(root)
    root.mainloop()


if __name__ == '__main__':
    main()
