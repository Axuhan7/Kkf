-- ============================================
-- 品牌信息更新SQL
-- 品牌名：云宫
-- 技术支持微信：dj1301300
-- 技术支持QQ：1992700063
-- 网址：www.yanshi.fun
-- ============================================

-- 更新 options 表中的 tips 字段（访客端显示的版权提示）
UPDATE `wolive_option` SET `value` = 'By.云宫    技术支持微信：dj1301300  QQ：1992700063  网址：www.yanshi.fun  坚决打击网络诈骗行为、严禁使用本系统进行违法运营。' WHERE `title` = 'tips';

-- 如果上面的语句没有匹配到记录，可以尝试用 LIKE 查找
-- SELECT * FROM `wolive_option` WHERE `value` LIKE '%八零%' OR `value` LIKE '%1500203929%';
