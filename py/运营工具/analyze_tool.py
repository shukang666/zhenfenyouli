import tkinter as tk
from tkinter import filedialog, messagebox, ttk
import pandas as pd
import matplotlib.pyplot as plt
from matplotlib.backends.backend_tkagg import FigureCanvasTkAgg
import numpy as np
from datetime import datetime

# 设置matplotlib中文字体，避免中文乱码
plt.rcParams['font.sans-serif'] = ['SimHei', 'WenQuanYi Micro Hei']
plt.rcParams['axes.unicode_minus'] = False

class PointInspectionAnalyzer:
    def __init__(self, root):
        self.root = root
        self.root.title('点位巡检数据分析工具')
        self.root.geometry('1200x800')
        self.root.configure(bg='#f0f0f0')
        
        # 初始化变量
        self.file_path = None
        self.df = None
        self.analysis_results = {}
        
        # 创建GUI组件
        self.create_widgets()
        
    def create_widgets(self):
        # 顶部框架：文件选择和控制按钮
        top_frame = tk.Frame(self.root, bg='#f0f0f0')
        top_frame.pack(fill=tk.X, padx=10, pady=10)
        
        # 标题
        title_label = tk.Label(top_frame, text='点位巡检数据分析工具', font=('微软雅黑', 16, 'bold'), bg='#f0f0f0')
        title_label.pack(side=tk.LEFT, padx=10)
        
        # 选择文件按钮
        self.select_btn = tk.Button(top_frame, text='选择Excel文件', command=self.select_file, 
                                   font=('微软雅黑', 12), bg='#4CAF50', fg='white', padx=20, pady=5)
        self.select_btn.pack(side=tk.RIGHT, padx=10)
        
        # 分析按钮
        self.analyze_btn = tk.Button(top_frame, text='开始分析', command=self.start_analysis, 
                                    font=('微软雅黑', 12), bg='#2196F3', fg='white', padx=20, pady=5, state=tk.DISABLED)
        self.analyze_btn.pack(side=tk.RIGHT, padx=10)
        
        # 保存结果按钮
        self.save_btn = tk.Button(top_frame, text='保存分析结果', command=self.save_results, 
                                 font=('微软雅黑', 12), bg='#FF9800', fg='white', padx=20, pady=5, state=tk.DISABLED)
        self.save_btn.pack(side=tk.RIGHT, padx=10)
        
        # 中间框架：结果显示和图表区域
        middle_frame = tk.Frame(self.root, bg='#f0f0f0')
        middle_frame.pack(fill=tk.BOTH, expand=True, padx=10, pady=10)
        
        # 左侧：文本结果显示区
        left_frame = tk.Frame(middle_frame, bg='white', bd=2, relief=tk.SUNKEN)
        left_frame.pack(side=tk.LEFT, fill=tk.Y, padx=5, pady=5)
        
        # 文本框
        self.result_text = tk.Text(left_frame, width=60, height=50, font=('微软雅黑', 10), wrap=tk.WORD)
        self.result_text.pack(side=tk.LEFT, fill=tk.Y, padx=5, pady=5)
        
        # 滚动条
        scrollbar = tk.Scrollbar(left_frame, command=self.result_text.yview)
        scrollbar.pack(side=tk.RIGHT, fill=tk.Y)
        self.result_text.configure(yscrollcommand=scrollbar.set)
        
        # 右侧：图表显示区
        right_frame = tk.Frame(middle_frame, bg='white', bd=2, relief=tk.SUNKEN)
        right_frame.pack(side=tk.RIGHT, fill=tk.BOTH, expand=True, padx=5, pady=5)
        
        # 图表画布
        self.fig = plt.Figure(figsize=(8, 6), dpi=100)
        self.canvas = FigureCanvasTkAgg(self.fig, master=right_frame)
        self.canvas.get_tk_widget().pack(fill=tk.BOTH, expand=True)
        
        # 底部状态栏
        self.status_bar = tk.Label(self.root, text='请选择Excel文件', bg='#f0f0f0', font=('微软雅黑', 10), fg='#666')
        self.status_bar.pack(side=tk.BOTTOM, fill=tk.X, padx=10, pady=5)
        
    def select_file(self):
        """选择Excel文件"""
        file_path = filedialog.askopenfilename(
            filetypes=[('Excel文件', '*.xlsx *.xls'), ('所有文件', '*.*')],
            title='选择点位巡检数据文件'
        )
        
        if file_path:
            self.file_path = file_path
            try:
                # 读取文件
                self.df = pd.read_excel(file_path)
                # 检查数据结构
                required_columns = ['用户ID', '昵称', '社区', '小区', '手机号', '积分', '注册日期']
                missing_columns = [col for col in required_columns if col not in self.df.columns]
                
                if missing_columns:
                    messagebox.showerror('错误', f'文件缺少必要的列：{", ".join(missing_columns)}')
                    self.analyze_btn.config(state=tk.DISABLED)
                    return
                
                # 更新状态栏
                self.status_bar.config(text=f'已加载文件：{file_path} | 数据行数：{len(self.df)}')
                self.analyze_btn.config(state=tk.NORMAL)
                messagebox.showinfo('成功', f'文件加载成功！共{len(self.df)}条数据')
                
            except Exception as e:
                messagebox.showerror('错误', f'加载文件失败：{str(e)}')
                self.analyze_btn.config(state=tk.DISABLED)
    
    def start_analysis(self):
        """开始数据分析"""
        if self.df is None:
            messagebox.showwarning('警告', '请先选择文件')
            return
        
        try:
            # 清空之前的结果
            self.result_text.delete(1.0, tk.END)
            self.fig.clear()
            
            # 执行所有分析
            self.analysis_results = {}
            
            # 1. 基础数据概览
            self.basic_overview()
            
            # 2. 社区分布分析
            self.community_analysis()
            
            # 3. 小区分布分析
            self.residence_analysis()
            
            # 4. 积分分布分析
            self.points_analysis()
            
            # 5. 注册时间分布分析
            self.registration_time_analysis()
            
            # 6. 积分排名分析
            self.points_ranking()
            
            # 显示所有结果
            self.display_results()
            
            # 生成图表
            self.generate_charts()
            
            # 启用保存按钮
            self.save_btn.config(state=tk.NORMAL)
            
            self.status_bar.config(text='分析完成！')
            messagebox.showinfo('完成', '数据分析已完成，结果已显示')
            
        except Exception as e:
            messagebox.showerror('错误', f'分析失败：{str(e)}')
            self.status_bar.config(text='分析失败')
    
    def basic_overview(self):
        """基础数据概览"""
        total_users = len(self.df)
        valid_community = self.df['社区'].notna().sum()
        valid_residence = self.df['小区'].notna().sum()
        total_points = self.df['积分'].sum()
        avg_points = self.df['积分'].mean()
        median_points = self.df['积分'].median()
        max_points = self.df['积分'].max()
        min_points = self.df['积分'].min()
        zero_points = (self.df['积分'] == 0).sum()
        
        # 计算百分比
        community_ratio = (valid_community / total_users * 100) if total_users > 0 else 0
        residence_ratio = (valid_residence / total_users * 100) if total_users > 0 else 0
        zero_points_ratio = (zero_points / total_users * 100) if total_users > 0 else 0
        
        self.analysis_results['basic_overview'] = {
            'total_users': total_users,
            'valid_community': valid_community,
            'community_ratio': community_ratio,
            'valid_residence': valid_residence,
            'residence_ratio': residence_ratio,
            'total_points': total_points,
            'avg_points': avg_points,
            'median_points': median_points,
            'max_points': max_points,
            'min_points': min_points,
            'zero_points': zero_points,
            'zero_points_ratio': zero_points_ratio
        }
    
    def community_analysis(self):
        """社区分布分析"""
        # 统计各社区用户数
        community_count = self.df['社区'].value_counts().reset_index()
        community_count.columns = ['社区', '用户数量']
        total_valid = community_count['用户数量'].sum()
        
        # 计算占比
        community_count['占比(%)'] = (community_count['用户数量'] / total_valid * 100).round(2)
        community_count['累计占比(%)'] = community_count['占比(%)'].cumsum().round(2)
        
        self.analysis_results['community_analysis'] = community_count
    
    def residence_analysis(self):
        """小区分布分析"""
        # 统计各小区用户数
        residence_count = self.df['小区'].value_counts().reset_index()
        residence_count.columns = ['小区', '用户数量']
        total_valid = residence_count['用户数量'].sum()
        
        # 计算占比
        residence_count['占比(%)'] = (residence_count['用户数量'] / total_valid * 100).round(2)
        
        # TOP10小区
        top10_residence = residence_count.head(10)
        
        self.analysis_results['residence_analysis'] = {
            'full': residence_count,
            'top10': top10_residence
        }
    
    def points_analysis(self):
        """积分分布分析"""
        # 积分区间划分
        bins = [-1, 0, 30, 100, 500, 1000, np.inf]
        labels = ['0分', '1-30分', '31-100分', '101-500分', '501-1000分', '1000分以上']
        self.df['积分区间'] = pd.cut(self.df['积分'], bins=bins, labels=labels, right=True)
        
        # 统计各区间用户数
        points_interval = self.df['积分区间'].value_counts().sort_index().reset_index()
        points_interval.columns = ['积分区间', '用户数量']
        total_users = points_interval['用户数量'].sum()
        
        # 计算占比
        points_interval['占比(%)'] = (points_interval['用户数量'] / total_users * 100).round(2)
        
        self.analysis_results['points_analysis'] = points_interval
    
    def registration_time_analysis(self):
        """注册时间分布分析"""
        # 转换注册日期为datetime格式
        self.df['注册日期'] = pd.to_datetime(self.df['注册日期'], errors='coerce')
        
        # 按日期统计注册用户数
        self.df['注册日期_纯日期'] = self.df['注册日期'].dt.date
        daily_registration = self.df['注册日期_纯日期'].value_counts().sort_index().reset_index()
        daily_registration.columns = ['注册日期', '用户数量']
        
        # 按小时统计注册用户数
        self.df['注册小时'] = self.df['注册日期'].dt.hour
        hourly_registration = self.df['注册小时'].value_counts().sort_index().reset_index()
        hourly_registration.columns = ['小时', '用户数量']
        
        self.analysis_results['registration_time'] = {
            'daily': daily_registration,
            'hourly': hourly_registration
        }
    
    def points_ranking(self):
        """积分排名分析"""
        # 按积分降序排序
        points_ranking = self.df.sort_values(by='积分', ascending=False).head(10)[['昵称', '社区', '小区', '积分', '注册日期']]
        points_ranking['排名'] = range(1, len(points_ranking)+1)
        points_ranking = points_ranking[['排名', '昵称', '社区', '小区', '积分', '注册日期']]
        
        self.analysis_results['points_ranking'] = points_ranking
    
    def display_results(self):
        """显示分析结果到文本框"""
        text = self.result_text
        
        # 1. 基础概览
        basic = self.analysis_results['basic_overview']
        text.insert(tk.END, "="*80 + "\n")
        text.insert(tk.END, "📊 一、基础数据概览\n")
        text.insert(tk.END, "="*80 + "\n")
        text.insert(tk.END, f"总用户数：{basic['total_users']} 人\n")
        text.insert(tk.END, f"有社区信息的用户：{basic['valid_community']} 人，占比 {basic['community_ratio']:.2f}%\n")
        text.insert(tk.END, f"有小区信息的用户：{basic['valid_residence']} 人，占比 {basic['residence_ratio']:.2f}%\n")
        text.insert(tk.END, f"总积分：{basic['total_points']} 分\n")
        text.insert(tk.END, f"平均积分：{basic['avg_points']:.2f} 分\n")
        text.insert(tk.END, f"积分中位数：{basic['median_points']} 分\n")
        text.insert(tk.END, f"最高积分：{basic['max_points']} 分\n")
        text.insert(tk.END, f"最低积分：{basic['min_points']} 分\n")
        text.insert(tk.END, f"0分用户数：{basic['zero_points']} 人，占比 {basic['zero_points_ratio']:.2f}%\n\n")
        
        # 2. 社区分布
        community = self.analysis_results['community_analysis']
        text.insert(tk.END, "="*80 + "\n")
        text.insert(tk.END, "🏘️ 二、社区用户分布\n")
        text.insert(tk.END, "="*80 + "\n")
        text.insert(tk.END, community.to_string(index=False) + "\n\n")
        
        # 3. 小区TOP10
        residence = self.analysis_results['residence_analysis']
        text.insert(tk.END, "="*80 + "\n")
        text.insert(tk.END, "🏠 三、小区用户分布TOP10\n")
        text.insert(tk.END, "="*80 + "\n")
        text.insert(tk.END, residence['top10'].to_string(index=False) + "\n\n")
        
        # 4. 积分分布
        points = self.analysis_results['points_analysis']
        text.insert(tk.END, "="*80 + "\n")
        text.insert(tk.END, "💰 四、积分区间分布\n")
        text.insert(tk.END, "="*80 + "\n")
        text.insert(tk.END, points.to_string(index=False) + "\n\n")
        
        # 5. 注册时间分布
        reg_time = self.analysis_results['registration_time']
        text.insert(tk.END, "="*80 + "\n")
        text.insert(tk.END, "📅 五、注册时间分布\n")
        text.insert(tk.END, "="*80 + "\n")
        text.insert(tk.END, "按日期注册统计：\n")
        text.insert(tk.END, reg_time['daily'].to_string(index=False) + "\n\n")
        text.insert(tk.END, "按小时注册统计：\n")
        text.insert(tk.END, reg_time['hourly'].to_string(index=False) + "\n\n")
        
        # 6. 积分TOP10
        ranking = self.analysis_results['points_ranking']
        text.insert(tk.END, "="*80 + "\n")
        text.insert(tk.END, "🏆 六、积分TOP10用户\n")
        text.insert(tk.END, "="*80 + "\n")
        text.insert(tk.END, ranking.to_string(index=False) + "\n\n")
        
        # 文本框只读
        text.configure(state=tk.DISABLED)
    
    def generate_charts(self):
        """生成分析图表"""
        fig = self.fig
        fig.clear()
        
        # 1. 社区分布饼图
        ax1 = fig.add_subplot(2, 2, 1)
        community = self.analysis_results['community_analysis']
        top5_community = community.head(5)
        other_community = pd.DataFrame({
            '社区': ['其他'],
            '用户数量': [community['用户数量'].sum() - top5_community['用户数量'].sum()]
        })
        community_pie = pd.concat([top5_community[['社区', '用户数量']], other_community], ignore_index=True)
        
        ax1.pie(community_pie['用户数量'], labels=community_pie['社区'], autopct='%1.1f%%', 
               startangle=90, colors=['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7', '#DDA0DD'])
        ax1.set_title('社区用户分布占比', fontsize=12, fontweight='bold')
        ax1.axis('equal')
        
        # 2. 积分分布柱状图
        ax2 = fig.add_subplot(2, 2, 2)
        points = self.analysis_results['points_analysis']
        ax2.bar(points['积分区间'], points['用户数量'], color='#45B7D1', alpha=0.7)
        ax2.set_title('积分区间用户数量分布', fontsize=12, fontweight='bold')
        ax2.set_xlabel('积分区间')
        ax2.set_ylabel('用户数量')
        ax2.tick_params(axis='x', rotation=45)
        
        # 3. 每日注册用户趋势图
        ax3 = fig.add_subplot(2, 2, 3)
        daily_reg = self.analysis_results['registration_time']['daily']
        ax3.plot(daily_reg['注册日期'], daily_reg['用户数量'], marker='o', color='#FF6B6B', linewidth=2)
        ax3.set_title('每日注册用户趋势', fontsize=12, fontweight='bold')
        ax3.set_xlabel('注册日期')
        ax3.set_ylabel('用户数量')
        ax3.tick_params(axis='x', rotation=45)
        ax3.grid(True, alpha=0.3)
        
        # 4. 小区TOP10柱状图
        ax4 = fig.add_subplot(2, 2, 4)
        top10_residence = self.analysis_results['residence_analysis']['top10']
        ax4.barh(top10_residence['小区'], top10_residence['用户数量'], color='#96CEB4', alpha=0.7)
        ax4.invert_yaxis()
        ax4.set_title('小区用户数量TOP10', fontsize=12, fontweight='bold')
        ax4.set_xlabel('用户数量')
        ax4.set_ylabel('小区')
        
        # 调整布局
        plt.tight_layout()
        self.canvas.draw()
    
    def save_results(self):
        """保存分析结果到Excel文件"""
        if not self.analysis_results:
            messagebox.showwarning('警告', '没有可保存的分析结果')
            return
        
        save_path = filedialog.asksaveasfilename(
            defaultextension='.xlsx',
            filetypes=[('Excel文件', '*.xlsx'), ('所有文件', '*.*')],
            title='保存分析结果'
        )
        
        if save_path:
            try:
                with pd.ExcelWriter(save_path, engine='openpyxl') as writer:
                    # 基础概览
                    basic = self.analysis_results['basic_overview']
                    basic_df = pd.DataFrame({
                        '指标': ['总用户数', '有社区信息用户数', '社区信息占比', '有小区信息用户数', '小区信息占比', 
                                 '总积分', '平均积分', '积分中位数', '最高积分', '最低积分', '0分用户数', '0分用户占比'],
                        '数值': [
                            basic['total_users'], basic['valid_community'], f"{basic['community_ratio']:.2f}%",
                            basic['valid_residence'], f"{basic['residence_ratio']:.2f}%", basic['total_points'],
                            f"{basic['avg_points']:.2f}", basic['median_points'], basic['max_points'],
                            basic['min_points'], basic['zero_points'], f"{basic['zero_points_ratio']:.2f}%"
                        ]
                    })
                    basic_df.to_excel(writer, sheet_name='基础数据概览', index=False)
                    
                    # 社区分布
                    self.analysis_results['community_analysis'].to_excel(writer, sheet_name='社区用户分布', index=False)
                    
                    # 小区分布
                    self.analysis_results['residence_analysis']['full'].to_excel(writer, sheet_name='小区用户分布', index=False)
                    
                    # 积分分布
                    self.analysis_results['points_analysis'].to_excel(writer, sheet_name='积分区间分布', index=False)
                    
                    # 注册时间分布
                    self.analysis_results['registration_time']['daily'].to_excel(writer, sheet_name='每日注册统计', index=False)
                    self.analysis_results['registration_time']['hourly'].to_excel(writer, sheet_name='每小时注册统计', index=False)
                    
                    # 积分TOP10
                    self.analysis_results['points_ranking'].to_excel(writer, sheet_name='积分TOP10用户', index=False)
                
                messagebox.showinfo('成功', f'分析结果已保存到：{save_path}')
                self.status_bar.config(text=f'结果已保存：{save_path}')
                
            except Exception as e:
                messagebox.showerror('错误', f'保存失败：{str(e)}')

if __name__ == '__main__':
    root = tk.Tk()
    app = PointInspectionAnalyzer(root)
    root.mainloop()