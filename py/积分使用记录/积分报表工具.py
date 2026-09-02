# -*- coding: utf-8 -*-
"""
积分报表一键导出工具
====================
功能：按月份从数据库提取用户积分获取、消耗、兑换明细，一个大表全展示，支持预览和导出 Excel

使用方式：双击运行 或 命令行 python 积分报表工具.py
依赖：pymysql, pandas, openpyxl
"""

import tkinter as tk
from tkinter import ttk, filedialog, messagebox
import pymysql
import pandas as pd
from openpyxl import Workbook
from openpyxl.styles import Font, Alignment, PatternFill, Border, Side
from datetime import datetime, timedelta
import os


# ========== 数据库配置 ==========
DB_CONFIG = {
    'host': '124.223.56.252',
    'port': 3306,
    'user': 'xcxkf052_aubye_c',
    'password': 'acacQQndx12ADXMm',
    'database': 'xcxkf052_aubye_c',
    'charset': 'utf8mb4'
}

# ========== 积分类型映射 ==========
# 表头列名 => 对应的数据库 type 值（这些 type 产生的积分计入该列）
POINT_TYPE_MAP = {
    '线上督导获得积分': [201],          # 201=问卷获得
    '督导巡查获得积分': [222],          # 222=点位巡查
    '督导活动获得积分': [225, 221],     # 225=社区活动打卡, 221=报名
    '大件垃圾巡查获得积分': [224],       # 224=商家打卡
    '分拣获得积分': [200],              # 200=拍照打卡
    '活动分享获得积分': [223],          # 223=活动分享
    '管理员调整': [210],                # 210=管理员增减
}
# 消耗型 type
CONSUME_TYPES = [203, 205]  # 203=订单抵扣, 205=积分扣除
# 排除的冲正/删除类 type
EXCLUDE_TYPES = [204, 226, 227]  # 204=订单取消, 226=社区活动打卡删除, 227=垃圾打卡删除

# 最终 Excel 列顺序
FINAL_COLS = [
    '姓名', '手机号', '所属小区',
    '线上督导获得积分', '督导巡查获得积分', '督导活动获得积分',
    '大件垃圾巡查获得积分', '分拣获得积分', '活动分享获得积分',
    '管理员调整',
    '总获得积分', '总使用积分',
    '兑换物品明细', '兑换次数', '兑换消耗积分',
    '当前积分'
]


class PointReportTool:
    """积分报表工具主类"""

    def __init__(self, root):
        self.root = root
        self.root.title("积分报表一键导出工具")
        self.root.geometry("1280x720")
        self.root.minsize(1000, 600)

        self.df = None  # 合并后的大表缓存

        self._build_ui()

    def _build_ui(self):
        """构建界面"""
        # 顶部工具栏
        top = ttk.Frame(self.root, padding=10)
        top.pack(fill=tk.X)

        ttk.Label(top, text="选择月份：", font=("Microsoft YaHei", 11)).pack(side=tk.LEFT)

        now = datetime.now()
        self.year_var = tk.StringVar(value=str(now.year))
        self.month_var = tk.StringVar(value=str(now.month))

        # 年份
        yf = ttk.Frame(top)
        yf.pack(side=tk.LEFT, padx=5)
        ttk.Combobox(yf, textvariable=self.year_var,
                     values=list(range(now.year - 3, now.year + 1)),
                     width=6, state='readonly',
                     font=("Microsoft YaHei", 11)).pack(side=tk.LEFT)
        ttk.Label(yf, text="年", font=("Microsoft YaHei", 11)).pack(side=tk.LEFT)

        # 月份
        mf = ttk.Frame(top)
        mf.pack(side=tk.LEFT, padx=5)
        ttk.Combobox(mf, textvariable=self.month_var,
                     values=[str(m) for m in range(1, 13)],
                     width=4, state='readonly',
                     font=("Microsoft YaHei", 11)).pack(side=tk.LEFT)
        ttk.Label(mf, text="月", font=("Microsoft YaHei", 11)).pack(side=tk.LEFT)

        # 按钮
        ttk.Button(top, text="🔍 查询预览", command=self.query_data,
                   width=14).pack(side=tk.LEFT, padx=(15, 5))
        ttk.Button(top, text="📊 导出 Excel", command=self.export_excel,
                   width=14).pack(side=tk.LEFT)

        # 状态
        self.status_var = tk.StringVar(value="请选择月份后点击「查询预览」")
        ttk.Label(top, textvariable=self.status_var,
                  foreground="#666").pack(side=tk.RIGHT)

        # 表格区
        body = ttk.Frame(self.root, padding=(10, 0, 10, 10))
        body.pack(fill=tk.BOTH, expand=True)

        # 默认行高，跟系统一致不撑大
        style = ttk.Style()
        style.configure('Treeview', rowheight=26)

        self.tree = ttk.Treeview(body, show='headings', height=30)
        vsb = ttk.Scrollbar(body, orient="vertical", command=self.tree.yview)
        hsb = ttk.Scrollbar(body, orient="horizontal", command=self.tree.xview)
        self.tree.configure(yscrollcommand=vsb.set, xscrollcommand=hsb.set)

        self.tree.grid(row=0, column=0, sticky='nsew')
        vsb.grid(row=0, column=1, sticky='ns')
        hsb.grid(row=1, column=0, sticky='ew')
        body.grid_rowconfigure(0, weight=1)
        body.grid_columnconfigure(0, weight=1)

    def _get_month_range(self, year, month):
        """月份起止时间戳"""
        start = datetime(year, month, 1)
        if month == 12:
            end = datetime(year + 1, 1, 1) - timedelta(seconds=1)
        else:
            end = datetime(year, month + 1, 1) - timedelta(seconds=1)
        return int(start.timestamp()), int(end.timestamp())

    def query_data(self):
        """查询 + 预览"""
        try:
            year = int(self.year_var.get())
            month = int(self.month_var.get())
        except ValueError:
            messagebox.showerror("错误", "请选择有效的年月")
            return

        self.status_var.set("正在查询数据库...")
        self.root.update()

        try:
            conn = pymysql.connect(**DB_CONFIG)
            start_ts, end_ts = self._get_month_range(year, month)
            month_label = f"{year}年{month}月"

            self.status_var.set(f"[{month_label}] 查询积分流水...")
            self.root.update()
            df_point = self._query_point_summary(conn, start_ts, end_ts)

            self.status_var.set(f"[{month_label}] 查询兑换明细...")
            self.root.update()
            df_exchange_agg = self._query_exchange_agg(conn, start_ts, end_ts)

            conn.close()

            # 合并
            self.status_var.set("正在合并数据...")
            self.root.update()
            self.df = self._merge(df_point, df_exchange_agg)

            # 预览
            self._fill_treeview(self.df)

            user_count = len(self.df)
            exchange_user = (self.df['兑换次数'] > 0).sum()
            self.status_var.set(
                f"✅ {month_label} 查询完成：共 {user_count} 位用户，其中 {exchange_user} 位有兑换记录"
            )

        except Exception as e:
            self.status_var.set("❌ 查询失败")
            messagebox.showerror("数据库错误", str(e))

    def _query_point_summary(self, conn, start_ts, end_ts):
        """查询积分汇总（每个用户每个type一行）"""
        cursor = conn.cursor()
        exclude_str = ','.join(str(t) for t in EXCLUDE_TYPES)

        sql = f"""
            SELECT a.user_id,
                   COALESCE(NULLIF(b.true_name, ''), b.nickname) AS name,
                   b.mobile,
                   c.name AS community_name,
                   a.type,
                   SUM(a.change_amount) AS amount
            FROM cmf_user_account_log a
            LEFT JOIN cmf_member b ON a.user_id = b.id
            LEFT JOIN cmf_member_community c ON b.community_id = c.id
            WHERE a.account_type = 2
              AND a.create_time BETWEEN {start_ts} AND {end_ts}
              AND a.delete_time = 0
              AND a.type NOT IN ({exclude_str})
            GROUP BY a.user_id, a.type
            ORDER BY a.user_id, a.type
        """
        cursor.execute(sql)
        rows = cursor.fetchall()

        # 构建反向映射 type -> 表头列名
        type_to_col = {}
        for col_name, types in POINT_TYPE_MAP.items():
            for t in types:
                type_to_col[t] = col_name

        # 收集所有用户
        user_set = set(r[0] for r in rows)
        if not user_set:
            return pd.DataFrame()

        # 查当前积分
        id_list = ','.join(str(uid) for uid in user_set)
        cursor.execute(f"SELECT id, point FROM cmf_member WHERE id IN ({id_list})")
        point_map = {row[0]: float(row[1]) if row[1] else 0 for row in cursor.fetchall()}

        # 初始化用户基础数据
        user_map = {}
        for r in rows:
            uid = r[0]
            if uid not in user_map:
                user_map[uid] = {
                    'user_id': uid,
                    '姓名': r[1] or '(未命名)',
                    '手机号': r[2] or '',
                    '所属小区': r[3] or '',
                    '当前积分': int(point_map.get(uid, 0)),
                    **{col: 0 for col in POINT_TYPE_MAP.keys()},
                    '总获得积分': 0,
                    '总使用积分': 0,
                }

        # 填充各类型积分
        for r in rows:
            uid = r[0]
            col_name = type_to_col.get(r[4])
            amt = float(r[5]) if r[5] else 0
            if col_name and amt > 0:
                user_map[uid][col_name] += amt
            # 消耗类（负数且非排除项）
            if amt < 0 and r[4] not in EXCLUDE_TYPES:
                user_map[uid]['总使用积分'] += abs(amt)

        # 计算总获得 + 全部 int 化
        earn_cols = list(POINT_TYPE_MAP.keys())
        for uid in user_map:
            user_map[uid]['总获得积分'] = sum(user_map[uid][c] for c in earn_cols)
            for c in earn_cols + ['总获得积分', '总使用积分']:
                user_map[uid][c] = int(user_map[uid][c])

        return pd.DataFrame(list(user_map.values()))

    def _query_exchange_agg(self, conn, start_ts, end_ts):
        """查询兑换明细并按用户聚合"""
        cursor = conn.cursor()

        sql = f"""
            SELECT a.user_id,
                   FROM_UNIXTIME(a.pay_time, '%m-%d') AS date_short,
                   d.goods_name,
                   COALESCE(NULLIF(d.sku_name, ''), '默认规格') AS sku_name,
                   d.count,
                   a.point_pay
            FROM cmf_shop_order a
            LEFT JOIN cmf_shop_order_detail d ON a.order_num = d.order_num
            WHERE a.point_pay > 0
              AND a.delete_time = 0
              AND a.pay_time BETWEEN {start_ts} AND {end_ts}
            ORDER BY a.pay_time
        """
        cursor.execute(sql)
        rows = cursor.fetchall()

        if not rows:
            return pd.DataFrame(columns=['user_id', '兑换物品明细', '兑换次数', '兑换消耗积分'])

        # 按用户聚合
        exchange_map = {}
        for r in rows:
            uid = r[0]
            date_short = r[1] or ''
            goods = r[2] or ''
            sku = r[3] or '默认规格'
            count = int(r[4]) if r[4] else 1
            point_pay = int(float(r[5])) if r[5] else 0

            # 显示文本：日期 + 商品名(规格)x数量
            label = goods[:25]
            if sku and sku != '默认规格':
                label += f"({sku})"
            if count > 1:
                label += f"×{count}"

            if uid not in exchange_map:
                exchange_map[uid] = {'items': [], '消耗合计': 0}
            exchange_map[uid]['items'].append(f"{date_short} {label}")
            exchange_map[uid]['消耗合计'] += point_pay

        result = []
        for uid, data in exchange_map.items():
            result.append({
                'user_id': uid,
                # 用 \n 分隔多条，方便 Excel wrap_text 和 Treeview 多行显示
                '兑换物品明细': '\n'.join(data['items']),
                # 每条独立的兑换记录列表，Excel 导出时展开用
                '_exchange_items': data['items'],
                '兑换次数': len(data['items']),
                '兑换消耗积分': data['消耗合计'],
            })
        return pd.DataFrame(result)

    def _merge(self, df_point, df_exchange):
        """合并积分汇总 + 兑换聚合"""
        if df_point.empty:
            return pd.DataFrame(columns=['序号'] + FINAL_COLS + ['_exchange_items'])

        merged = df_point.merge(df_exchange, on='user_id', how='left')
        merged['兑换物品明细'] = merged['兑换物品明细'].fillna('—')
        merged['兑换次数'] = merged['兑换次数'].fillna(0).astype(int)
        merged['兑换消耗积分'] = merged['兑换消耗积分'].fillna(0).astype(int)
        # 没兑换的用户 _exchange_items 是空列表
        merged['_exchange_items'] = merged['_exchange_items'].apply(
            lambda x: x if isinstance(x, list) else []
        )

        # 按指定列顺序（保留 _exchange_items 在最后，不显示但 Excel 导出用）
        merged = merged[FINAL_COLS + ['_exchange_items']]

        # 先按总获得积分降序，再按兑换次数降序
        merged = merged.sort_values(
            ['总获得积分', '兑换次数'], ascending=[False, False]
        ).reset_index(drop=True)

        # 加序号列
        merged.insert(0, '序号', range(1, len(merged) + 1))

        return merged

    def _fill_treeview(self, df):
        """填充表格预览：有兑换的用户展开多行，每行一条兑换记录"""
        for item in self.tree.get_children():
            self.tree.delete(item)

        if df is None or df.empty:
            self.tree['columns'] = ['提示']
            self.tree.heading('提示', text='提示')
            self.tree.column('提示', width=300)
            self.tree.insert('', tk.END, values=['暂无数据'])
            return

        # 显示列（过滤掉内部 _exchange_items）
        display_cols = [c for c in df.columns if not c.startswith('_')]
        self.tree['columns'] = display_cols

        # 列宽
        fixed_widths = {
            '序号': 60, '姓名': 120, '手机号': 130, '所属小区': 160,
            '兑换物品明细': 380,
            '线上督导获得积分': 130, '督导巡查获得积分': 130, '督导活动获得积分': 130,
            '大件垃圾巡查获得积分': 140, '分拣获得积分': 110, '活动分享获得积分': 120,
            '管理员调整': 110, '总获得积分': 110, '总使用积分': 110,
            '兑换次数': 90, '兑换消耗积分': 110, '当前积分': 110,
        }
        for col in display_cols:
            self.tree.heading(col, text=col)
            w = fixed_widths.get(col, 100)
            anchor = 'w' if col == '兑换物品明细' else 'center'
            self.tree.column(col, width=w, minwidth=60, anchor=anchor, stretch=False)

        # 展开前 300 个用户（展开后最多约 500 行）
        cnt = 0
        for _, ur in df.head(300).iterrows():
            exch_items = ur.get('_exchange_items', []) or []
            exch_count = len(exch_items)

            if exch_count == 0:
                # 没兑换 → 正常一行
                row_vals = [ur[c] for c in display_cols]
                self.tree.insert('', tk.END, values=row_vals)
                cnt += 1
            else:
                # 有兑换 → 展开 exch_count 行
                base_vals = []
                for c in display_cols:
                    if c == '兑换物品明细':
                        base_vals.append(None)  # 占位
                    elif c == '兑换次数':
                        base_vals.append(exch_count)
                    elif c == '兑换消耗积分':
                        base_vals.append(ur[c])
                    else:
                        base_vals.append(ur[c])

                for i, item_text in enumerate(exch_items):
                    row_vals = list(base_vals)
                    # 兑换物品明细列 = 这一条
                    ei = display_cols.index('兑换物品明细')
                    row_vals[ei] = item_text
                    # 兑换消耗积分：只有第一行显示合计，后续空着
                    ci = display_cols.index('兑换消耗积分')
                    if i > 0:
                        row_vals[ci] = ''
                    ni = display_cols.index('兑换次数')
                    if i > 0:
                        row_vals[ni] = ''
                    self.tree.insert('', tk.END, values=row_vals)
                    cnt += 1
                    if cnt >= 600:
                        break
            if cnt >= 600:
                break

    def export_excel(self):
        """导出格式化 Excel"""
        if self.df is None or self.df.empty:
            messagebox.showwarning("提示", "请先查询数据再导出")
            return

        year = self.year_var.get()
        month = self.month_var.get()
        default_name = f"积分报表_{year}年{month}月.xlsx"

        file_path = filedialog.asksaveasfilename(
            defaultextension=".xlsx",
            initialfile=default_name,
            filetypes=[("Excel 文件", "*.xlsx")],
            initialdir=os.path.expanduser("~")
        )
        if not file_path:
            return

        self.status_var.set("正在生成 Excel 文件...")
        self.root.update()

        try:
            wb = Workbook()
            ws = wb.active
            ws.title = f"{year}年{month}月积分报表"

            self._write_merged_sheet(ws, self.df, year, month)

            wb.save(file_path)
            self.status_var.set(f"✅ 导出成功：{os.path.basename(file_path)}")
            messagebox.showinfo(
                "导出成功",
                f"文件已保存到：\n{file_path}\n\n"
                f"共 {len(self.df)} 位用户"
            )
            os.startfile(os.path.dirname(file_path))

        except Exception as e:
            self.status_var.set("❌ 导出失败")
            messagebox.showerror("导出错误", str(e))

    def _write_merged_sheet(self, ws, df, year, month):
        """写入 Excel：兑换明细展开多行 + 左侧列合并单元格"""
        from openpyxl.styles import Font, Alignment, PatternFill, Border, Side

        # --- 列分组：哪些列需要合并，哪些不合并 ---
        # 左边合并列：每个用户一行内的值相同，跨兑换行合并
        MERGE_COLS = [
            '序号', '姓名', '手机号', '所属小区',
            '线上督导获得积分', '督导巡查获得积分', '督导活动获得积分',
            '大件垃圾巡查获得积分', '分拣获得积分', '活动分享获得积分',
            '管理员调整', '总获得积分', '总使用积分', '当前积分',
        ]
        # 兑换相关列：不合并，每行各自显示（有兑换的每行一条，没兑换的只一行）
        EXCH_COLS = ['兑换物品明细', '兑换次数', '兑换消耗积分']
        ALL_DISPLAY_COLS = MERGE_COLS + EXCH_COLS
        ncols = len(ALL_DISPLAY_COLS)

        # 样式
        title_fill = PatternFill('solid', fgColor='1F4E78')
        title_font = Font(name='微软雅黑', size=16, bold=True, color='FFFFFF')
        hd_fill = PatternFill('solid', fgColor='4472C4')
        hd_font = Font(name='微软雅黑', size=11, bold=True, color='FFFFFF')
        dfont = Font(name='微软雅黑', size=10)
        dfont_bold_red = Font(name='微软雅黑', size=10, color='843C0C', bold=True)
        center = Alignment(horizontal='center', vertical='center', wrap_text=True)
        left_center = Alignment(horizontal='left', vertical='center', wrap_text=True)
        thin = Border(left=Side('thin','CCCCCC'), right=Side('thin','CCCCCC'),
                      top=Side('thin','CCCCCC'), bottom=Side('thin','CCCCCC'))

        # ===== 标题行 =====
        ws.merge_cells(start_row=1, start_column=1, end_row=1, end_column=ncols)
        tc = ws.cell(1, 1, f'{year}年{month}月 用户积分与兑换汇总报表')
        tc.font, tc.fill, tc.alignment = title_font, title_fill, center
        ws.row_dimensions[1].height = 40

        # ===== 表头行 =====
        for i, col in enumerate(ALL_DISPLAY_COLS, 1):
            c = ws.cell(2, i, col); c.fill, c.font, c.alignment, c.border = hd_fill, hd_font, center, thin
        ws.row_dimensions[2].height = 30

        # ===== 数据行（展开 + 合并） =====
        cur_row = 3  # 当前写入的 Excel 行号
        col_idx_map = {col: i + 1 for i, col in enumerate(ALL_DISPLAY_COLS)}  # col_name -> excel col

        for _, user_row in df.iterrows():
            # 获取该用户的兑换条目列表
            exch_items = user_row.get('_exchange_items', []) or []
            exch_count = int(user_row.get('兑换次数', 0))
            total_rows = max(len(exch_items), 1)  # 没兑换的也占 1 行

            r_start = cur_row
            r_end = cur_row + total_rows - 1  # 这个用户在 Excel 里占多少行

            # --- 1) 写合并列（左边那些）：只写第一行，之后合并 ---
            for col in MERGE_COLS:
                val = user_row.get(col, '')
                col_no = col_idx_map[col]

                if r_end > r_start:
                    # 多行合并
                    ws.merge_cells(start_row=r_start, start_column=col_no,
                                   end_row=r_end, end_column=col_no)

                cell = ws.cell(row=r_start, column=col_no, value=val)
                cell.font, cell.alignment, cell.border = dfont, center, thin

                # 给合并范围内的所有单元格补边框（merge_cells 后合并区只有一条边框）
                for r in range(r_start, r_end + 1):
                    if r != r_start:
                        c2 = ws.cell(row=r, column=col_no)
                        c2.border = thin
                    else:
                        c2 = ws.cell(row=r, column=col_no)
                        c2.border = thin

            # --- 2) 写兑换相关列（不合并） ---
            exch_col_no = col_idx_map['兑换物品明细']
            count_col_no = col_idx_map['兑换次数']
            consume_col_no = col_idx_map['兑换消耗积分']

            if exch_items:
                # 每条兑换写一行
                for i, item_text in enumerate(exch_items):
                    r = r_start + i
                    c = ws.cell(row=r, column=exch_col_no, value=item_text)
                    c.font, c.alignment, c.border = dfont, left_center, thin

                    c2 = ws.cell(row=r, column=count_col_no, value=1)
                    c2.font, c2.alignment, c2.border = dfont, center, thin

                    c3 = ws.cell(row=r, column=consume_col_no, value='')
                    c3.font, c3.alignment, c3.border = dfont, center, thin

                # 兑换次数 / 消耗积分 合并在第一行（如果多行）
                if r_end > r_start:
                    ws.merge_cells(start_row=r_start, start_column=count_col_no,
                                   end_row=r_end, end_column=count_col_no)
                    ws.merge_cells(start_row=r_start, start_column=consume_col_no,
                                   end_row=r_end, end_column=consume_col_no)
                c_cnt = ws.cell(row=r_start, column=count_col_no, value=exch_count)
                c_cnt.font, c_cnt.alignment, c_cnt.border = dfont, center, thin

                total_consume = int(user_row.get('兑换消耗积分', 0))
                c_con = ws.cell(row=r_start, column=consume_col_no, value=total_consume)
                c_con.font, c_con.alignment, c_con.border = dfont_bold_red, center, thin
                if total_consume > 0:
                    c_con.fill = PatternFill('solid', fgColor='FCE4D6')

            else:
                # 没兑换的，只一行，写 "—"
                c = ws.cell(row=r_start, column=exch_col_no, value='—')
                c.font, c.alignment, c.border = dfont, center, thin
                c2 = ws.cell(row=r_start, column=count_col_no, value=0)
                c2.font, c2.alignment, c2.border = dfont, center, thin
                c3 = ws.cell(row=r_start, column=consume_col_no, value=0)
                c3.font, c3.alignment, c3.border = dfont, center, thin

            # --- 3) 行高 ---
            # 基础行高 20，每条兑换再加 18（大致一行文字的高度）
            base_h = 22
            line_h = 18
            ws.row_dimensions[r_start].height = base_h + line_h * max(total_rows, 1)

            cur_row = r_end + 1

        # ===== 列宽 =====
        fixed = {'序号': 6, '姓名': 14, '手机号': 14, '所属小区': 16,
                 '兑换物品明细': 45}
        for i, col in enumerate(ALL_DISPLAY_COLS, 1):
            letter = ws.cell(2, i).column_letter
            ws.column_dimensions[letter].width = fixed.get(col, 14)

        # ===== 冻结前两行 =====
        ws.freeze_panes = 'A3'


def main():
    root = tk.Tk()
    try:
        ttk.Style().theme_use('vista')
    except tk.TclError:
        try:
            ttk.Style().theme_use('clam')
        except tk.TclError:
            pass

    PointReportTool(root)
    root.mainloop()


if __name__ == '__main__':
    main()
