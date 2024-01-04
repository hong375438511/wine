
--
-- 广告图
--
INSERT INTO `__PREFIX__unishop_ads` (`id`, `image`, `product_id`, `background`, `position`, `status`, `weigh`, `createtime`, `updatetime`) VALUES
(1, '/assets/addons/unishop/image/banner1.png', 8, 'rgb(203, 87, 60)', 0, 1, 1, 1561122209, 1586446660),
(2, '/assets/addons/unishop/image/banner2.png', 9, 'rgb(205, 215, 218)', 0, 1, 2, 1561122255, 1586446648),
(3, '/assets/addons/unishop/image/banner3.png', 11, 'rgb(183, 73, 69)', 0, 1, 3, 1561122284, 1586446293);


--
-- 分类
--
INSERT INTO `__PREFIX__unishop_category` (`id`, `pid`, `type`, `name`, `nickname`, `flag`, `image`, `keywords`, `description`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
(43, 0, 'product', '女士', 'WOMEN', 'index', '/assets/addons/unishop/image/category/086a7eb84609aeb6218cbd31b1788384.png', '', '', '', 1582456695, 1586155906, 43, 'normal'),
(44, 0, 'product', '男士', 'MAN', 'index', '/assets/addons/unishop/image/category/e4a7970a7cc1ddef74d07965135a5c15.png', '', '', '', 1582456707, 1586155919, 44, 'normal'),
(45, 0, 'product', '儿童', 'CHILDREN', 'index', '/assets/addons/unishop/image/category/da346e5d636874fcb41702320796a39d.png', '', '', '', 1582456725, 1586155932, 45, 'normal'),
(46, 0, 'product', '鞋和包', 'Shoes and bags', 'index', '/assets/addons/unishop/image/category/58085483723dabedfd66d4f105b30d1d.png', '', '', '', 1582456756, 1586155944, 46, 'normal'),
(47, 46, 'product', '女士', 'Lady', '', '/assets/addons/unishop/image/category/cc1a119874b3ce3fe620b247916a51e0.png', '', '', '', 1583764127, 1586157696, 47, 'normal'),
(48, 46, 'product', '男士', 'Man', '', '/assets/addons/unishop/image/category/77dca8e429ad40c05254eb3fc2f52941.png', '', '', '', 1583766695, 1586157667, 47, 'normal'),
(49, 46, 'product', '儿童', 'Children', '', '/assets/addons/unishop/image/category/4e401d8f429ff5080fbaa23397309920.png', '', '', '', 1583766739, 1586157679, 47, 'normal'),
(50, 45, 'product', '男童', 'Boy', '', '/assets/addons/unishop/image/category/2e843f316110a3362e6af1c3ce8f4885.png', '', '', '', 1584279125, 1586157634, 50, 'normal'),
(51, 45, 'product', '女童', 'Girl', '', '/assets/addons/unishop/image/category/6b1c283a584350338bc8719cd1ac8d38.png', '', '', '', 1584279143, 1586157645, 51, 'normal'),
(52, 44, 'product', '大衣和风衣', '大衣和风衣', '', '/assets/addons/unishop/image/category/00a2fb67c52427ac82c778704cbda2a4.png', '', '', '', 1584279340, 1586157364, 52, 'normal'),
(53, 44, 'product', '夹克外套', '夹克外套', '', '/assets/addons/unishop/image/category/ab2cd32e6ef8f8cf0477eadd8a3387e8.png', '', '', '', 1584279445, 1586157387, 53, 'normal'),
(54, 44, 'product', '休闲西装', '休闲西装', '', '/assets/addons/unishop/image/category/bac157f7c7681c59e3ec4247ff5fdcab.png', '', '', '', 1584279445, 1586157401, 54, 'normal'),
(55, 44, 'product', '西服套装', '西服套装', '', '/assets/addons/unishop/image/category/7f2a4995799cf451188a88c4c13249d0.png', '', '', '', 1584279445, 1586157412, 55, 'normal'),
(56, 44, 'product', '针织衫', '针织衫', '', '/assets/addons/unishop/image/category/a8579c481cc12505aca506f5ae4cb8c4.png', '', '', '', 1584279445, 1586157426, 56, 'normal'),
(57, 44, 'product', '裤装', '裤装', '', '/assets/addons/unishop/image/category/2014d52d5e86098af68ea9b3ceedcb0e.png', '', '', '', 1584279445, 1586157439, 57, 'normal'),
(58, 44, 'product', '牛仔裤', '牛仔裤', '', '/assets/addons/unishop/image/category/60a3f7532e602692d20fc69527603f8e.png', '', '', '', 1584279445, 1586157449, 58, 'normal'),
(59, 44, 'product', '衬衫', '衬衫', '', '/assets/addons/unishop/image/category/2575155fba2557a088ae04b01a8ced98.png', '', '', '', 1584279445, 1586157460, 59, 'normal'),
(60, 44, 'product', '运动服', '运动服', '', '/assets/addons/unishop/image/category/2cbb72d073c1f9f7c886df14c54e3098.png', '', '', '', 1584279445, 1586157470, 60, 'normal'),
(61, 44, 'product', 'T 恤', 'T 恤', '', '/assets/addons/unishop/image/category/df1fcb5de821f3a29278a9a81699acc7.png', '', '', '', 1584279445, 1586157481, 61, 'normal'),
(62, 44, 'product', 'POLO 衫', 'POLO 衫', '', '/assets/addons/unishop/image/category/ddf8e9133e317ba53d1ac611513b4446.png', '', '', '', 1584279445, 1586157498, 62, 'normal'),
(63, 44, 'product', '卫衣', '卫衣', '', '/assets/addons/unishop/image/category/94578624cf8390f7cd0d0b829c1cf2cf.png', '', '', '', 1584279445, 1586157510, 63, 'normal'),
(64, 44, 'product', '配饰', '配饰', '', '/assets/addons/unishop/image/category/15375ba47305c5be708ba212376b1652.png', '', '', '', 1584279445, 1586157524, 64, 'normal'),
(65, 43, 'product', '大衣 I 风衣', '大衣 I 风衣', '', '/assets/addons/unishop/image/category/393f1d44643bd4d9dcb7223449fc09e9.png', '', '', '', 1584279796, 1586156329, 65, 'normal'),
(66, 43, 'product', '夹克外套', '夹克外套', '', '/assets/addons/unishop/image/category/87536ff693464e4965f207fac818badf.png', '', '', '', 1584279796, 1586156722, 66, 'normal'),
(67, 43, 'product', '西装', '西装', '', '/assets/addons/unishop/image/category/0d9ee12f350f08b5fcc753aebcec206a.png', '', '', '', 1584279796, 1586156354, 67, 'normal'),
(68, 43, 'product', '连衣裙', '连衣裙', '', '/assets/addons/unishop/image/category/34b2214914f6485a25e894475cbc54cc.png', '', '', '', 1584279796, 1586156364, 68, 'normal'),
(69, 43, 'product', '衬衫 I 上衣', '衬衫 I 上衣', '', '/assets/addons/unishop/image/category/1eb96892666f6ba6e9093e86b128b951.png', '', '', '', 1584279796, 1586156375, 69, 'normal'),
(70, 43, 'product', 'T 恤', 'T 恤', '', '/assets/addons/unishop/image/category/0307bf61551dcd232bc3e4b03178e8e8.png', '', '', '', 1584279796, 1586156387, 70, 'normal'),
(71, 43, 'product', '毛衣针织', '毛衣针织', '', '/assets/addons/unishop/image/category/c06ad721db96281193e0b5dc303c6299.png', '', '', '', 1584279796, 1586156401, 71, 'normal'),
(72, 43, 'product', '裤装', '裤装', '', '/assets/addons/unishop/image/category/469c757a4060d53b0c1596d5aba040c5.png', '', '', '', 1584279796, 1586156416, 72, 'normal'),
(73, 43, 'product', '牛仔裤', '牛仔裤', '', '/assets/addons/unishop/image/category/1aefd2f2cb2889fd5dafb3ad25b25c35.png', '', '', '', 1584279796, 1586156430, 73, 'normal'),
(74, 43, 'product', '半身裙 | 短裤', '半身裙 | 短裤', '', '/assets/addons/unishop/image/category/e447634d32a825ad14b66e08c5aff700.png', '', '', '', 1584279796, 1586156442, 74, 'normal'),
(75, 43, 'product', '卫衣', '卫衣', '', '/assets/addons/unishop/image/category/70e008c1789d992c38b4071be4ff8b83.png', '', '', '', 1584279796, 1586156455, 75, 'normal');


--
-- 物流快递
--
INSERT INTO `__PREFIX__unishop_delivery` (`id`, `name`, `min`, `type`, `weigh`, `switch`, `createtime`, `updatetime`) VALUES
(29, '购买3件以上包邮', 3, 'quantity', 3, 1, 1545191441, 1577000646),
(31, '广东地区包邮', 1, 'quantity', 1, 1, 1546399832, 1583500523);
INSERT INTO `__PREFIX__unishop_delivery_rule` (`id`, `delivery_id`, `area`, `first`, `first_fee`, `additional`, `additional_fee`, `createtime`, `updatetime`) VALUES
(44, 29, '2,20,38,61,76,84,104,124,150,168,180,197,208,221,232,244,250,264,271,278,290,304,319,337,352,362,372,376,389,398,407,422,430,442,449,462,467,481,492,500,508,515,522,530,537,545,553,558,566,574,581,586,597,607,614,619,627,634,640,646,656,675,692,702,711,720,730,748,759,764,775,782,793,802,821,833,842,853,861,871,880,887,896,906,913,920,927,934,948,960,972,980,986,993,1003,1010,1015,1025,1035,1047,1057,1066,1074,1081,1088,1093,1098,1110,1118,1127,1136,1142,1150,1155,1160,1169,1183,1190,1196,1209,1222,1234,1245,1253,1264,1274,1279,1285,1299,1302,1306,1325,1339,1350,1362,1376,1387,1399,1408,1415,1421,1434,1447,1459,1466,1471,1476,1479,1492,1504,1513,1522,1533,1546,1556,1572,1583,1593,1599,1612,1623,1630,1637,1643,1650,1664,1674,1685,1696,1707,1710,1724,1731,1740,1754,1764,1768,1774,1782,1791,1802,1809,1813,1822,1828,1838,1848,1854,1867,1880,1890,1900,1905,1912,1924,1936,1949,1955,1965,1977,1999,2003,2011,2017,2025,2035,2041,2050,2056,2065,2070,2077,2082,2091,2123,2146,2150,2156,2163,2177,2189,2207,2215,2220,2225,2230,2236,2245,2258,2264,2276,2283,2292,2297,2302,2306,2324,2363,2368,2388,2395,2401,2409,2416,2426,2434,2440,2446,2458,2468,2475,2486,2493,2501,2510,2516,2521,2535,2554,2573,2584,2589,2604,2611,2620,2631,2640,2657,2671,2686,2696,2706,2712,2724,2730,2741,2750,2761,2775,2784,2788,2801,2807,2812,2817,2826,2845,2857,2870,2882,2890,2899,2913,2918,2931,2946,2958,2972,2984,2997,3008,3016,3023,3032,3036,3039,3045,3053,3058,3065,3073,3081,3090,3098,3108,3117,3127,3135,3142,3147,3152,3158,3165,3172,3179,3186,3190,3196,3202,3207,3216,3221,3225,3229,3237,3242,3252,3262,3267,3280,3289,3301,3309,3317,3326,3339,3378,3386,3416,3454,3458,3461,3491,3504,3518,3532,3551,3578,3592,3613,3632,3666,3683,3697,3704,3711,3717,3722,3728,3739,3745,3747', 1, '0.00', 1, '0', 1577000646, 1577000646),
(47, 31, '1965,1977,1988,1999,2003,2011,2017,2025,2035,2041,2050,2056,2065,2070,2077,2082,2091,2123,2146,2150,2156', 1, '0.00', 1, '0', 1583500523, 1583500523);

