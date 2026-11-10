# BF2026 R15 — Gift 分类、地区门槛与仓库随机赠品契约

日期：2026-11-10。状态：**WWW 商品与 Gift 页面更新完成；新交易规则仅在独立 Draft 源码，尚未安装 WWW。**

## 最新 Owner 规则（优先于上传原文件中的“每单免费礼品”文案）
- 活动期：2026-11-10 至 2026-11-30（America/Los_Angeles）。
- **非美国收货地址：** 折后 USD 商品金额 **不足 $99 不送**；从 **$99** 起随机送 1 件；从 **$199** 起随机送 2 件。
- **美国收货地址：** 正金额商品订单保留 1 件随机礼品；$199+ 共 2 件礼品。$99+ 时可在 `/holiday/gift/` 的指定列表中**选择最多 1 件付费礼品**（+$10/+15/+20），替换一件随机礼品名额，不额外增加总名额。
- **仓库自主配货：** 插件不指定各仓具体免费礼品 SKU、也不假装掌握仓库库存。Woo 订单保存 `_byl_bf2026_random_gift_qty`、`_byl_bf2026_total_gift_slots`、`_byl_bf2026_paid_gift_product_id`；ERP 以件数为准分配实际随机商品。美国明确选择的付费礼品保持真实 Woo 产品行、SKU 与固定升级收费。
- 计算标准：折后**来源 USD 商品金额**，不计税、运费、赠品和升级费。原 Woo 促销商品的折扣未授权自动重算。优惠码、FX 继续遵循先前 fail-closed 规则。
- **来源冲突：** 上传的三页《新-黑五活动设计及开发需求》FAQ 仍说全球“每单免费礼品”，而 Owner 新指令规定国际订单先满 $99。这份业务规则优先。正式主落地页 FAQ/横幅的旧文案需另做精准文案同步，不得默认为新规则已在主 Banner 上显示。

## 已经执行的 WWW Woo 分类映射
实际 WordPress + Woo Store API 读取、写入、独立回读证据（Issue #253 的恢复快照）：`https://github.com/beyourlovercom/YBY-Core/issues/253#issuecomment-6769903043`。

| 付费升级 | 商品（Woo 产品 ID / SKU） |
| --- | --- |
| $10 | Heart 9141/TW158; Blackfire 32685/DW01A; Cloud Spirit 9106/VA560A; Cloud Ipo 9103/VA562A; Magic Cane Green 9123/VT500A-G |
| $15 | Camera 9095/VX007H; Earpod 9096/BX033B; Joy Cup **9117/BX288A（原版，不是 Diamond 9118）**; Squeeze Me 17625/CA594B; Perfume 25358/VX588A |
| $20 | Angela 14188/VX603A; Eria 9109/S106B; Wavey 17767/BA564A; Ariel 34737/VX607A; TARA App 9137/TX218Y |

- 所有 15 款加入原有 Woo `gift` 分类 ID 2726、`gift-upgrade` ID 2728、对应 3 个子类别 `gift-upgrade-10` ID 2729 / `gift-upgrade-15` ID 2730 / `gift-upgrade-20` ID 2731，分类 count = **15/15/5/5/5**。原有价格/库存/SKU/旧分类不变。
- `gift-free` ID 2727 当前 **0** 件：随机礼品由仓库决定，故不会把这些付费升级商品错误地登记成“免费库存”。
- Cloud Spirit、Cloud Ipo、Wavey Woo **缺货**：仍在分类，但美国付费选礼时后端校验 STOCK，无法选择。
- WWW Public Page #42280 `/holiday/gift/` 现有页面已按三档使用 Woo 原生 `[products category=...]` 动态调用。匿名 HTTP200，12 个有货商品卡可见，3 个缺货商品由相应档位缺货文案列出。页面原有 Private Preview 误导文案已清理；主黑五页面结构未修改。

## R15 新插件设计
- 原 R12/R13 随机赠品使用“实际 Woo 产品 SKU + 仓库实物确认”，不适合 Owner 最新“仓库自己发随机礼品”要求。新 `BYL_BF2026_R15_Entitlements` **记录随机礼品件数，不往购物车自动塞猜测 SKU**。
- 美国选择的 15 个已核验 SKU 使用原生 Woo `WC_Cart::add_to_cart` 带标记、`before_calculate_totals` 精准重价，固定 $10/15/20，不能叠加优惠码或多次选；切换地址、优惠码或缺货时剔除不符合条件的付费行。
- 只在管理员 30 分钟 Canary 或未来独立批准的全站发布开关内运行。当前 WWW 全站 Master OFF/Canary OFF。
- Classic Cart/Review 的“随机赠品 × N FREE”展示、Checkout 订单 metadata、Woo 后台中文管理员提示。Block/Store API 消费者页面展示与 ERP 元数据消费链仍需要完整 UAT/适配，不能把“订单里有数量 Meta”宣称为仓库已经发货。
- 仍需要对 WWW 固有 USA-only 折扣与现有产品零售价在国际用户面前的可见性做发布前检查；本支线不改商品真实售价。

## UAT 与发布门禁
- 源码测试目前：R15 38/38 + 原 R14 230/230 = **268/268** PHP mock fixtures PASS，不等于真实商店交易通过。
- 后续：在 Dev 隔离的 WP-CLI / Woo native 环境验证 $98.99/$99/$198.99/$199 跨国家、实际 paid SKU 和购物车、下单前 HPOS Order Meta/ERP 同步（不可真实付款）；对管理员 Canary 做 Woo Store API/Classic Checkout 浏览器验证；人工安装新版 ZIP 到 WWW。正常访客保持 OFF。
- 主落地页旧 FAQ 规则文案必须避免误导；不直接覆盖设计师现有视觉结构。

## Dev 原生 WooCommerce R15 完整验证（补充）
- Dev `dev.beyourlover.com` 已安装 `black-friday-2026 v0.2.2-dev-rc3` Active；旧版有独立可回滚备份。ZIP 19 条 POSIX 路径、SHA256 双端一致，尚未上线 WWW。
- 真正的 Dev WooCommerce 原生 Cart/Customer/Product（使用站点实际 SKU 而非纯 PHP mock）测试 **16/16 PASS**：真实 Heart TW158 +$10、Camera VX007H +$15，US 217 总名额 2、替换后仅一件付费与一件随机；CA 69/138/217 对应 0/1/2；美国切换加拿大自动撤销付费项目；订单 Woo Object 内存 Meta 为随机件数与付费商品 ID。
- 只有此 Dev UAT 进程临时创建 gift/gift-upgrade/10/15 Woo 产品分类并赋予两件测试商品；`finally` 清空 Woo Cart、恢复原产品类别并删除所有新建测试类别。独立 Dev WP-CLI 二次只读查询：所有临时类别不存在；产品原商品属性及类别恢复。**未创建、保存或支付任何订单，也未同步 ERP**。
- 美国 Mac mini 独立访客真实 Store API（Dev 安装后）加购商品 `#9096`：HTTP 成功、USD 69 金额回读正确、Campaign master OFF 时不发送礼品、清空购物车，全部 PASS。
- 不应将此 16/16 结果称为“Chrome 已登录 Canary UAT PASS”，该测试仍须在 Owner 手动安装 WWW 最新插件后进行。
