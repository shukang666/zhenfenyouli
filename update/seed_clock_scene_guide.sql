-- 场景分类引导初始运营文案（18 条，与小程序 sceneClockHub 场景矩阵一致）
-- 可重复执行：依赖 uk_hub_sub_cate，存在则更新文案
-- 字段：如何处理 handle_guide | 建议投放时间 dispose_time | 问卷问题 survey_question | 推荐技巧 preset_tips（JSON 数组）

INSERT INTO `cmf_clock_scene_guide`
(`hub_key`,`sub_key`,`cate_label`,`handle_guide`,`dispose_time`,`survey_question`,`preset_tips`,`learn_path`,`status`,`list_order`,`update_time`)
VALUES
('home','after_meal','可回收','清空残留、压扁纸盒；干净塑料与金属可投可回收。油污重的餐盒归其他/不可回收。','建议餐后 1 小时内整理，避免厨余在室温下久放。','您餐后一般怎么处理剩余食物与包装？','["剩菜先沥水再投厨余","外卖盒冲洗后投可回收","一次性筷子归其他垃圾","汤料袋挤净再分类"]','/pages/dtsh/knowledge',1,10,UNIX_TIMESTAMP()),
('home','after_meal','厨余','剩饭菜、果皮、茶叶渣等投厨余；大骨头、贝壳类按当地要求可能归其他。','每日收餐前清理一次，夏季尽量当日投放。','厨余您会先做哪些处理？','["使用沥水篮减少汤汁","骨头与软质厨余分开放","过期食品去包装再投","小份烹饪减少浪费"]','/pages/dtsh/knowledge',1,11,UNIX_TIMESTAMP()),

('home','room_clean','可回收','旧书报、干净纸箱、塑料瓶罐等保持干燥整洁后投放。','扫除结束后集中投放，避免潮湿污染。','扫除时可回收物您怎么收集？','["纸箱拆平捆扎","塑料瓶压扁省空间","旧衣物单独装袋","玻璃制品包裹防割伤"]','/pages/dtsh/knowledge',1,20,UNIX_TIMESTAMP()),
('home','room_clean','不可回收','污染纸张、尘土、破碎陶瓷、一次性口罩等投其他/不可回收。','与可回收分袋，当日或次日投放。','哪些扫除垃圾最难分类？','["脏抹布归其他","烟灰不要投厨余","大件联系预约收运","电池单独投有害"]','/pages/dtsh/knowledge',1,21,UNIX_TIMESTAMP()),
('home','room_clean','有害','过期药品、废电池、废灯管等保持原包装或密封后投有害桶。','发现即收集，勿长期存放家中。','有害垃圾您一般存放在哪？','["药品保留原盒","电池统一小盒收集","灯管防破碎包装","定期送社区回收点"]','/pages/dtsh/knowledge',1,22,UNIX_TIMESTAMP()),

('home','express','可回收','纸箱拆开压平，塑料袋、气泡膜洗净后可投可回收（以本地要求为准）。','拆包后当天整理，避免纸箱占通道。','快递包装您通常怎么处理？','["纸箱压平回收","胶带撕下再投纸类","填充物分类收集","复用快递袋再投递"]','/pages/dtsh/knowledge',1,30,UNIX_TIMESTAMP()),
('home','express','不可回收','严重污染的包装、复合材质快递袋等多归其他/不可回收。','与干净纸箱分开放置。','哪些快递材料您会投其他垃圾？','["污损纸箱归其他","防伪贴随袋丢弃","泡沫块按本地要求","减少过度包装购物"]','/pages/dtsh/knowledge',1,31,UNIX_TIMESTAMP()),

('home','daily','可回收','纸张、塑料、金属、织物等保持清洁干燥。','随产随分，建议每晚整理一次。','日常整理时如何养成回收习惯？','["门口设分类小桶","贴标签提醒家人","购物少买难回收品","定期清空避免堆积"]','/pages/dtsh/knowledge',1,40,UNIX_TIMESTAMP()),
('home','daily','不可回收','受污染、难分离的混合垃圾投其他/不可回收。','与厨余、可回收分袋投放。','您怎么避免混投？','["不确定时查分类指南","湿垃圾不投可回收","大件不混投桶内","问物业或督导员"]','/pages/dtsh/knowledge',1,41,UNIX_TIMESTAMP()),
('home','daily','厨余','剩菜剩饭、瓜皮果核、茶叶渣等；注意沥水。','建议每日固定时段投放。','日常厨余如何减少异味？','["加盖投放","滤干汤汁","及时清理桶壁","用小桶先收集再倒"]','/pages/dtsh/knowledge',1,42,UNIX_TIMESTAMP()),
('home','daily','有害','电池、灯管、药品等单独密封，勿与其他垃圾混合。','凑满一小袋再集中投放。','有害垃圾您多久清理一次？','["固定抽屉存放","儿童够不着","不随意丢弃","参与社区回收日"]','/pages/dtsh/knowledge',1,43,UNIX_TIMESTAMP()),

('qinzi','','可回收','和孩子一起识别纸张、塑料、金属等，保持干燥干净。','亲子活动后即时整理，强化记忆。','您会用什么方式教孩子认可回收物？','["贴图分类游戏","购物时讲包装","手工利用旧纸盒","打卡记录小成就"]','/pages/dtsh/knowledge',1,50,UNIX_TIMESTAMP()),
('qinzi','','有害','教孩子识别电池、药品等，强调不能乱丢、不能入口。','发现即放入有害收集盒。','如何向孩子解释有害垃圾？','["用“危险小卫士”比喻","家庭有害专用盒","亲子一起送回收点","读绘本学分类"]','/pages/dtsh/knowledge',1,51,UNIX_TIMESTAMP()),

('office','zhenfenjiao','可回收','办公纸张、快递箱、饮料瓶等投入真分角可回收格口。','下班前或午休后集中投放。','办公可回收您怎么提醒同事？','["工位旁放小回收筐","打印双面减纸","会议水瓶装桶","标签注明真分角位置"]','/pages/dtsh/knowledge',1,60,UNIX_TIMESTAMP()),
('office','zhenfenjiao','不可回收','污染纸张、防尘口罩、复合材质等投其他格口。','与可回收分袋，勿压在最底层。','办公其他垃圾常见有哪些？','["擦手纸归其他","碳粉盒按说明处理","外卖盒去残汤","大件联系物业"]','/pages/dtsh/knowledge',1,61,UNIX_TIMESTAMP()),
('office','zhenfenjiao','有害','废电池、废灯管、部分办公耗材投有害格口。','集中后由物业或专人收运。','办公有害垃圾如何管理？','["前台设回收盒","换电池统一收集","灯管防碎包装","不混入其他桶"]','/pages/dtsh/knowledge',1,62,UNIX_TIMESTAMP()),
('office','zhenfenjiao','厨余','茶叶渣、剩果、便餐剩余等投厨余格口，注意沥水。','午餐后及时投放，避免异味。','办公厨余您怎么处理？','["茶叶沥干再投","外卖剩餐分装","真分角桶盖常闭","自带餐减少包装"]','/pages/dtsh/knowledge',1,63,UNIX_TIMESTAMP()),

('street','','厨余','剩餐、果皮、菜叶等投厨余桶；油水较大先沥水。','营业结束或高峰后 1 小时内清理。','商铺厨余您如何减少违规投放？','["桶边备沥水筛","油脂不直倒下水道","与可回收分区域存放","记录每日投放量"]','/pages/dtsh/knowledge',1,70,UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE
  `handle_guide`    = VALUES(`handle_guide`),
  `dispose_time`    = VALUES(`dispose_time`),
  `survey_question` = VALUES(`survey_question`),
  `preset_tips`     = VALUES(`preset_tips`),
  `learn_path`      = VALUES(`learn_path`),
  `status`          = VALUES(`status`),
  `list_order`      = VALUES(`list_order`),
  `update_time`     = VALUES(`update_time`);
