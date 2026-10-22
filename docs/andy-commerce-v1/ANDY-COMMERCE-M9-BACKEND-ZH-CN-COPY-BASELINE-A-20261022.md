# Andy Commerce 后台中文文案规范 A 阶段（定稿候选）
**版本：2026-10-22 · V1.0 · Desktop-only · 仅适用于 Andy 系列插件后台管理界面**

> 依据：Owner 审核的中文桌面端效果图、现有 M9 Draft PR #195 HEAD `7c343eb7dca0177902098937eebd10507a59813f` 的 `marketing-m9-usd-multirule-lab.php`、`marketing-m7-dev-studio.php`、M9 USD 规则引擎与 Currency OS M1/M2。此文件是**中文文案设计与实现契约**，并非已部署的译文或生产运行证明。

## 0. 生效范围与不可变规则

1. **Andy 系列插件的后台界面 Desktop-only**：不要求 M14/375px 适配、不新增后台移动响应式 UAT；PC 桌面浏览器为唯一目标。此规则不适用于顾客可见的前端货币切换器、商品页、购物车和 Checkout，那些仍须移动端适配。
2. **后台面向操作员的可见文案必须为简体中文**：页面标题、菜单、按钮、表格、帮助文字、输入框、占位符、报错、成功提示、空状态、二次确认、状态标签和无障碍文案。保留技术术语：Andy Commerce、Marketing OS（首次出现可注“营销系统”）、Currency OS（“币种与汇率”）、WooCommerce、USD/EUR/GBP/AUD/CAD、SKU、ID、ISO2、API、Dev、OFF；不输出整段英文操作指令。
3. 中文文本**不改变代码的内部 ID、POST action、GET 参数、nonce、类名、选项键或 URL**；例如 `m9_preview`、`draft_type=spend_save`、`andy_commerce_m7_save_draft` 原样保留。
4. **USD 是折扣后合格商品金额的核算基准**：先减已有优惠，再判满减/赠品资格；在 M9 模拟中最多选中一个最高金额的固定 USD 满减；赠品只是资格预览、不发货；普通商品免邮在模拟满减后再次比较真实 Woo 配送区域的 USD 门槛。最后才使用同一汇率快照显示客户货币。不得用本地货币四舍五入结果反判 USD 资格。
5. **M9 为 Dev-only 模拟**：当前无真实订单、税费、赠品、优惠券、运费修改或支付接线。所有活动为 OFF 草稿，页面不可暗示优惠已生效。
6. 沿用当前 Andy Commerce 菜单层级，**不重复新建 Marketing OS 顶级 WordPress 菜单**，也不混入 Currency OS 汇率设置。A 阶段仅语言与文案，不进行页面结构 B 阶段扩展。
7. 视觉稿中 “减免金额（百分比）” 是不准确的示意：当前 M9/M7 的 spend_save 是 **固定金额减免（USD）**；未实现百分比折扣的输入与计算，禁止在真实界面展示为已支持。

## 1. 统一术语字典

| 当前英文或旧版表述 | 后台统一中文 | 使用规则 |
| --- | --- | --- |
| Marketing OS / Marketing Studio | 营销 OS / 营销工作台 | 导航可用“营销”，页面标题使用“营销 OS” |
| USD Multi-Rule Studio | 美元多规则工作台 | 页面主标题 |
| Currency OS | 币种与汇率（Currency OS） | 跨模块第一次出现使用，中后续可称“币种与汇率” |
| Currency OS handoff | 币种换算结果 | 不使用“交接/衔接”作为操作标题 |
| USD-first | 以 USD 为核算基准 | 不应误写为“先把本地币种换回 USD 才计算” |
| Qualifying Gross | 符合条件的商品原价（USD） | 活动折扣发生前、但已排除无资格商品 |
| Previous Discounts | 已有折扣（USD） | 该金额先扣减 |
| Eligible subtotal / qualification basis | 活动资格金额（USD） | 符合条件商品的已有折扣后小计 |
| Discount threshold | 满减门槛（USD） | 每条规则独立管理 |
| Discount amount | 固定减免金额（USD） | **不是百分比** |
| Gift threshold | 赠品门槛（USD） | 仅金额资格，不等于有库存或已发货 |
| Customer Currency | 客户币种 | 当前五币种 |
| Delivery Zone | 配送区域 | Woo 原生已选中配送区 |
| Recalculate independent rules | 重新计算规则 | 只模拟，不保存 |
| Qualified (shadow) | 模拟达标 | **不是“已生效”** |
| Below threshold | 未达到门槛 | 金额不足 |
| Amount qualified (shadow) | 模拟达到免邮金额 | 不代表真实 Woo 运费已免除 |
| Not qualified / unverified | 未达标或待验证 | 不应伪称“已免邮” |
| OFF / Draft | 未启用 / 草稿 | 独立于规则金额达标状态 |
| OFF · never award | 未启用 · 不发放 | 赠品或优惠未触发 |
| No Woo rate changed | 未修改 Woo 运费 | 数据真实性提示 |
| Native free-shipping availability | Woo 原生免邮方式待核验 | 香港特殊处理 |
| Shadow / Preview | 模拟 / 预览 | 全页面统一 |
| Synthetic test rates | 模拟汇率（非实时） | 不写“实时汇率” |
| Save Inactive Draft | 保存为未启用草稿 | 必须强调未启用 |
| Gift product ID | 赠品商品 ID | 当前为数字 ID、非 SKU 字符串 |
| Eligible Country Codes (ISO2) | 适用国家代码（ISO2） | 如 US,CA,GB |
| Priority | 优先级 | 同等金额优惠的次级选择条件 |

## 2. 侧栏、导航和页面头部（定稿文案）

| 位置 | 中文文案 | 说明 |
| --- | --- | --- |
| Andy Commerce 菜单 | Andy Commerce | 品牌标识不翻译 |
| 二级导航 | 总览 / 订单 / 物流 / 营销 / 设置 | 复用现有后台菜单，不另建一级 |
| 当前模块标题 | 营销 OS · 美元多规则工作台 | 对应 M9 |
| 页面副标题 | 所有营销活动均按折扣后的合格商品 USD 金额判定；客户币种仅用于显示换算结果。 | 不使用“按实时汇率”误导 M9 |
| 安全状态提示 | 开发环境 · 模拟预览 · 所有活动未启用 | 常驻于页面醒目位置 |
| 操作帮助 | 查看使用说明 | 仅真实文档目标存在时才渲染链接 |
| 返回上一页面 | 返回营销工作台 | 导向现有 `tab=marketing-promotions` |
| 营销首页入口 | 打开美元多规则工作台 | 原 `Open USD Multi-Rule Preview` |
| 货币页面入口 | 查看营销多规则模拟 | Currency OS 单 $99 演示旁的链接 |

## 3. 卡片一：“订单场景模拟”全部字段

| 旧源码名称 | 最终中文标签 | 控件/示例值 | 说明或帮助文案 |
| --- | --- | --- | --- |
| USD-first order scenario | 订单场景模拟 | 卡片标题 | 输入示例购物车金额，查看多条活动资格；不会改变真实订单。 |
| Qualifying gross (USD) | 符合条件的商品原价（USD） | 数字，例 180 | 只输入可参与活动的商品金额；不含税费、运费、排除商品。 |
| Previous discounts (USD) | 已有折扣（USD） | 数字，例 15 | 当前已生效的商品折扣合计；优先从原价扣除。 |
| Customer currency | 客户币种 | USD / EUR / GBP / AUD / CAD | 仅影响模拟展示金额，不影响 USD 门槛判断。 |
| Delivery zone | 配送区域 | 现有 Woo 区域 0/1/3/4/5 | 使用模拟的配送区域；真实运费以 Woo 已选方式为准。 |
| Independent promotion rules (USD) | 模拟活动参数（USD） | 分组标题 | 各活动可有独立门槛；这里是一次性的测试参数，不是全局规则设置。 |
| Gift threshold | 赠品门槛（USD） | 数字，例 99 | 达标只显示赠品资格，不自动赠送。 |
| Discount threshold | 第一档满减门槛（USD） | 数字，例 169 | 判断 $169 减 $20 等规则。 |
| Discount amount | 第一档固定减免（USD） | 数字，例 20 | 固定金额，不是百分比。 |
| Second discount threshold | 第二档满减门槛（USD） | 数字，例 129 | 与第一档分别验证；默认不叠加。 |
| Second discount amount | 第二档固定减免（USD） | 数字，例 10 | 选中单个最高优惠模拟。 |
| Recalculate independent rules (simulation) | 重新计算规则 | 主按钮 | 只刷新结果，不写入草稿或订单。 |

配送区域的中文选项（保留原 Woo zone ID）：`5=美国本土及部分亚洲地区（USD $69）`；`4=阿拉斯加／夏威夷（USD $129）`；`3=高运费地区（USD $169）`；`0=其他地区（USD $69 或有效原生免邮券）`；`1=香港（以 Woo 原生免邮方式为准）`。以上金额是现有项目配置摘要，不允许被当前模拟页面写入 Woo。

## 4. 结果卡片

| 原文案 | 定稿中文 | 动态示例 | 必须遵守 |
| --- | --- | --- | --- |
| After previous discounts · USD | 已有折扣后（USD） | $165.00 | $180 − $15 |
| After selected campaign · USD | 模拟满减后（USD） | $155.00 | 选取 $10 固定优惠 |
| Customer merchandise subtotal · EUR | 客户商品小计（EUR） | €137.95 | 使用**模拟** 0.89 比率、未含税费运费 |
| Native shipping qualification | 普通商品免邮资格（模拟） | 模拟达标 / 模拟未达标 / 待验证 | 金额达标不等于 Woo 已应用免邮 |
| Selected shadow discount | 本次模拟减免（USD） | −$10.00 | 单项最高可用优惠、不是实际抵扣 |
| Currency OS handoff | 币种换算结果 | $165 → −$10 → $155 → €137.95 | 单向从 USD 换算，不反向判定 |

**固定结果说明**：示例为 `$180 − $15 = $165`，满足 `满 $99 赠品` 资格，未达到 `满 $169 减 $20`，满足 `满 $129 减 $10`，模拟后商品基数 `$155`，使用模拟 EUR 汇率 `0.89` 展示 `€137.95`。文案不得把赠品显示为“已发放”。

## 5. 卡片二：“独立营销规则”表格

| 原字段 | 中文表头/状态 |
| --- | --- |
| Independent Marketing OS rules | 独立营销规则 |
| Rule | 规则名称 |
| Rule ID | 规则 ID |
| USD threshold | 活动门槛（USD） |
| Pre-campaign USD basis | 本次活动判定基数（USD） |
| Preview result | 模拟结果 |
| Fulfillment | 生效状态 |
| Gift at USD threshold | 满额赠品 |
| Discount at USD threshold | 第一档固定满减 |
| Second independent discount | 第二档固定满减 |
| Native Woo zone N · ordinary free shipping | Woo 配送区 N · 普通商品免邮 |
| Qualified (shadow) | 模拟达标 |
| Below threshold | 未达到门槛 |
| Amount qualified (shadow) | 模拟达到免邮金额 |
| Not qualified / unverified | 未达标或待验证 |
| OFF · never award | 未启用 · 不发放 |
| No Woo rate changed | 未修改 Woo 运费 |

卡片说明：
> 各规则独立核对已有折扣后的 USD 商品金额。当前最多模拟采用一项固定金额满减；满减之后重新核对普通商品的免邮金额。所有赠品、优惠券、运费变更均未执行。

**重要的“双状态”契约**：`模拟达标` 只描述**资格**，`未启用 · 不发放` 描述**真实执行状态**，两列必须同时保留，不能合并为绿色“已生效”。

**香港与特殊商品**：香港原生免邮未验证时显示 `Woo 原生免邮待验证`；SAKURA/BONDAGE KIT `USD $30/包`、PILLOW `USD $30/件` 不是普通免邮奖励，绝不输出“已减免特殊运费”。

## 6. 卡片三：“新建未启用活动草稿”

| 原字段 | 中文定稿 | 占位/帮助 | 交互要求 |
| --- | --- | --- | --- |
| Create independent inactive campaign draft | 新建未启用活动草稿 | 此操作只保存草稿，不会自动发放赠品、优惠或免邮。 | 标题 |
| Campaign name | 活动名称 | 例如：夏季赠礼活动 | 必填；最长 120 字符（依现有 M7） |
| Reward type | 奖励类型 | 选择一项 | 下拉 |
| Spend & Save · fixed USD discount | 满额立减（固定 USD 金额） | 如满 $169 减 $20 | 实际支持 |
| Gift · product ID | 满额赠品 | 如满 $99 赠送商品 | 实际支持，必须输入 ID |
| Discount (Percentage) | **禁止使用：百分比折扣尚未支持** | 当前旧视觉稿存在错误演示 | 不可渲染 |
| USD threshold | 活动门槛（USD） | 例如：99 | 必填正数 |
| USD discount | 固定减免金额（USD） | 例如：20 | 仅选“满额立减”时显示/必填 |
| Gift product ID | 赠品商品 ID | 输入 Woo 商品数字 ID | 仅选“满额赠品”时显示/必填；不可预填假 ID 123 |
| Eligible country codes (ISO2) | 适用国家代码（ISO2） | 例如：US,CA,GB | 使用大写英文国家代码，半角逗号分隔 |
| Priority | 优先级 | 例如：100 | 只作平局择优/现有规则用途 |
| Save inactive draft | 保存为未启用草稿 | 保存后保持 OFF | 主按钮 |
| Missing valid threshold/reward | 请填写有效的 USD 门槛及奖励参数。 | 校验提示 | 阻止无效草稿 |
| Draft saved successfully | 草稿已保存，当前未启用。 | 成功提示（仅真实后端保存成功时显示） | **不可在本地模拟点击后虚报保存成功** |
| Native M7 write permission unavailable | 当前无权保存草稿；本页仅支持查看和模拟。 | 权限提示 | 无管理员权限/非 Dev 时禁用 |

**M7 写入安全规则不可被中文化改动**：复用 `andy_commerce_m7_save_draft`、Nonce、`manage_woocommerce`、Dev-only 与 revision 校验；不新增 WordPress POST 端点，所有新草稿 `active=false`。

## 7. 卡片四：“币种换算结果”

**标题**：币种换算结果

**说明**：按 USD 计算营销资格与拟定优惠，再使用同一汇率快照显示客户币种金额。

| 流程顺序 | 中文文案 | 示例 |
| --- | --- | --- |
| 1 | 已有折扣后的合格商品金额（USD） | $165.00 |
| 2 | 本次模拟减免（USD） | −$10.00 |
| 3 | 模拟减免后的合格商品金额（USD） | $155.00 |
| 4 | 客户商品小计（EUR） | €137.95 |

**常驻底部提示**：
> 本页面使用模拟汇率，仅供开发环境核对活动规则；未接入实际付款。特殊运费商品（SAKURA、BONDAGE KIT：USD $30/包；PILLOW：USD $30/件）不会因普通商品促销资格被自动免除。

## 8. 必须有的错误、空状态和确认反馈

| 场景 | 中文定稿 |
| --- | --- |
| 非 Dev / 无权限 | 仅限获得授权的开发环境管理员使用此页面。 |
| 输入负金额 | 金额不能为负数。 |
| 已有折扣超过商品金额 | 已有折扣不能超过符合条件的商品原价。 |
| 门槛为空或非数字 | 请输入有效的 USD 活动门槛。 |
| 固定减免小于等于 0 | 固定减免金额必须大于 0。 |
| 赠品商品 ID 缺失 | 请选择有效的赠品商品 ID。 |
| 不支持的币种 | 不支持该币种，请选择 USD、EUR、GBP、AUD 或 CAD。 |
| 无法确定配送区域 | 无法确定配送区域，暂不显示免邮资格。 |
| 原生 Woo 免邮方式未验证 | WooCommerce 免邮方式尚未核实，不能确认已免邮。 |
| 数据/规则校验失败 | 活动模拟失败：规则数据无效，未执行任何优惠。 |
| 没有活动规则 | 暂无活动规则，可创建未启用草稿进行测试。 |
| 草稿列表为空 | 暂无活动草稿。 |
| 草稿保存被拒绝 | 草稿未保存，请检查操作权限、页面有效性与输入内容。 |
| 模拟计算成功 | 已更新模拟结果；未更改真实购物车、订单或运费。 |
| 活动资格不足 | 未达到门槛。 |
| 活动仅达标但未启用 | 模拟达标 · 活动未启用。 |
| 汇率过期/缺失 | 汇率不可用或已过期，不能生成可信换算结果。 |

## 9. 实施与验收门禁

**A 阶段交付是中文文案契约，不等于代码翻译已经部署**。实施需覆盖主 M9 视图、M7 草稿列表/预览、Currency OS 与 Marketing OS 的跨模块文案，具体分支和测试不得覆盖其他正在进行的主线。

- Desktop-only 建议以 **1440×900** 和 **1680×900** 实际后台作为视觉检查基准；验证标题、输入框、卡片、完整表格及按钮的可读性，无需 M14 后台适配。
- 抽查 HTML 可见字符串、`aria-label`、按钮标题、服务器端报错及 JS 校验提示；插件后台不得出现整段英文操作提示。
- 禁止翻译内部 form/action/option 名称和常量；保证 M7 草稿 nonce/security regression、M9 多规则计算、Currency OS 汇率纯计算和其他 Commerce regression 完整 PASS。
- 确保固定 USD 满减/赠品身份，**不要**误把百分比优惠、真实赠品发放、真实多币种 Checkout 说成已实现。
- 保持 Dev 之外的 WordPress 管理页、GitHub main、Stable 和 WWW 不变；上线/合并必须另行授权。仅当实际 Dev 页面已改动并读回验证之后，才可以声称“中文版 Dev UAT PASS”。
- **本文件单独存档，不触发 Dev 部署，也不要求 Owner 重新测试原有 250 条 USD M9 业务断言。**

## 10. 开发映射（不改变运行时逻辑）

- `packages/andy-commerce/admin/views/marketing-m9-usd-multirule-lab.php`：页面标题、表单、规则表、草稿按钮、模拟/异常说明。
- `packages/andy-commerce/admin/views/marketing-m7-dev-studio.php`：现有 Marketing Studio 和草稿列表/编辑入口中文化。
- `packages/andy-commerce/admin/views/currency-os-v1-dev-lab.php`：旧单条 $99 场景必须继续标注“示例”，新增中文多规则入口。
- `packages/andy-commerce/admin/class-andy-commerce-admin.php`：属于 Andy Commerce 的导航文字可中文化，保持 WordPress 标准菜单结构。
- `packages/andy-commerce/includes/class-andy-commerce-m9-usd-rule-engine.php`：**内部结果/字段键不翻译**；在 PHP 视图层根据状态输出中文。

附：已核对的实际 M9 代码支持 `spend_save` **固定减免 USD** 和 `free_gift` 赠品 ID；不支持“百分比满减”这一图稿假选项。旧图中的“实时汇率”应改为“模拟汇率”，直至真实币种支付模块获得独立 UAT。
