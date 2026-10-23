# Marketing OS M10 — Andy Core 三级菜单 Dev-only 交接
日期 2026-10-23；Owner 中文交互原型 PASS，推进真实 Dev 代码。

## 0. 唯一事实源与界限
本分支基于 Core L3 注册表/Commerce Currency 联合集成 Draft PR #203，叠加 Marketing 中文 PR #200 已通过内容。遵守 Dev-only / Draft / inactive：不更改 main、Stable、WWW、真实付款订单、优惠券运费或插件版本号。Currency OS 归另一个窗口负责。本分支不做单独的正式版安装包。

## 1. Andy Core 唯一导航
WordPress L2 为 Andy Core → 营销。五个 L3 是活动列表（marketing-promotions）、新建活动（marketing-create）、活动模板（marketing-templates）、规则模拟（marketing-simulate）、执行记录（marketing-logs）。
列表使用 Core 原生的 promotions tab，后四条菜单由 Andy Commerce 的 controlled L3 filter 自注册，不能越过 Controller allowlist、manage_woocommerce、Dev-only 权限。Core 只一次性通用区分 Marketing 前缀与交易分组。后续 Commerce 新增菜单不再硬编码到 Core 菜单表。
Core PHP Shell 已显示原生三级菜单时，原生 L3 JS 不会再加第二组；无 Shell 时采用标准 WordPress wrap。Desktop-only 中文管理端；顾客可见的前端功能仍移动优先。

## 2. 已实现的可操作行为
活动列表从现有 M7 inactive 草稿读取（最多 50 条），支持编辑、复制新草稿、归档。使用原 M7 nonce、版本号 revision 和管理员权限，无新的真实优惠存储或激活路径。
活动模板支持六种外观：满额赠品、固定满减、百分比折扣、BOGO 买赠、组合优惠、阶梯优惠；内部复用 M7 五种类型。新建编辑复用 V2 typed-rule fields；M7 保存只写 Dev inactive draft。右侧活动预览按表单值显示，绝不执行真实活动。
赠品新草稿要求正 USD threshold_minor 与数字 product_id，历史缺少门槛的旧草稿不自动迁移也不会在 M9 误判达标。
M9 USD 引擎识别固定金额与百分比 basis points，严格二选一，在已有折扣后的 USD 商品金额上计算最优单项减免，然后转换客户币种。五币种、五个 Woo 模拟区域的资格逻辑仍不改原生运费。
M9 已加入显式模拟国家输入；不在允许国家列表的草稿不参与预览；真实国家仍需结账地址独立验证。
规则模拟实际内嵌 M9，GET 重新计算不跳出三级模拟菜单。执行记录页面明确无真实订单级活动日志，不显示假数据。

## 3. 当前不支持的内容
有效期、客户分群、预算上限、商品排除、真正多活动叠加尚未与 M7 数据契约接线，当前不提供假保存。BOGO/Bundle/Tiered 的实物商品 SKU 和数量资格需后续 Woo 可信只读快照核验；不能仅凭购物车金额断言其达标。实时库存、税费、特殊运费、付款和实际赠品完全没有接入；所有活动保持 OFF。

## 4. 测试与下一门禁
测试：packages/andy-commerce/tests/marketing-m10-page-render-harness.php、marketing-m10-rules-v1-harness.php、tests/andy-core-marketing-m10-l3-harness.php、原 M7/M9 与完整 Core/Commerce harness、JS/PHP lint。所有源测试 PASS 后需 exact-head GitHub PR CI，再准备唯一 Core+Commerce Dev 候选。
真实 Dev 安装、Core+Commerce 文件一致性读回、桌面 1440/1680 浏览器点击 UAT、角色负面 UAT、草稿保存编辑复制归档、回滚验证均需完成，未执行前不可报 Owner 视觉 PASS。没有任何 WWW 部署或 Stable 发布授权。
