# BF2026 R14 — 单管理员 / 单浏览器 Canary 发布手册

版本：`0.2.1-dev-rc2`（2026-11-10）

状态：**代码待 Dev 验收；WWW 只能由 Owner 手动安装，默认全站 OFF**

## 为什么不用直接把全站 Master 打开？
正式站当前正常结账是最高优先级。Andy Campaign Lite 的 `master=OFF` 时会自动清理活动购物车行，所以仅添加礼品按钮白名单仍会导致管理员刷新丢赠品。R14 在当前请求内，仅对**已登录管理员、指定用户 ID、绑定浏览器签名 Cookie、30 分钟未过期**的会话解除该项清理并运行 BF2026 Hooks；其他人继续 Master OFF，不能看到该活动按钮或自动领取赠品。

## 管理员开启与立即停止
1. 通过现有 WordPress 后台的 **WooCommerce → BF2026 测试**。仅 `manage_options` 管理员可以访问。
2. 选择 **开启当前浏览器30分钟测试**。生成安全随机 token，其 SHA-256 存在 WP Option `byl_bf2026_canary_v1`（非自动加载），原始 token 只在当前浏览器的 HttpOnly/Secure/SameSite=Lax Cookie `byl_bf26_canary`，不写入日志。
3. 仅当前登录管理员、同一个浏览器 Cookie、且 30 分钟未超时可以运行赠品逻辑。另一个浏览器、另一位管理员、游客、错误/过期 Cookie、Master 异常或旧营销引擎启用均不具有 Canary 权限。
4. 结束后点击 **立即停止**。关闭选项并注销 Cookie。过期默认 OFF。刷新购物车时 Andy Campaign Lite 会清理旧的活动礼品行。
5. 如需要第二次 Canary，重新点击开启，会作废前次浏览器的随机 token。

## 生产操作门禁
- 版本装上不等于正式启用。默认不存在 Canary Option、`BYL_BF2026_ALLOW_GLOBAL_GIFTS` 常量，`BYL_BF2026_Gifts::ready()` 对普通访客返回 false。即使全站 Master 被打开，赠品功能仍无法向普通访客生效。
- R13 原代码的 US-only 全局商品 `sale_price` 改写仍默认阻断；Canary 不改变原有商品价格。
- 真正要送货，仍须按仓库**已经确认的两款礼品**接入精确 Woo Product ID / SKU / warehouse / destination / carrier 表和完整 Gift 奖励配置。**不需要重新请求仓库审批**，但不能凭未经映射的 SKU 试发。
- WWW 最后仍需测真实 WordPress 登录浏览器和 Store API 的身份持续性、店铺购物车、刷新、购物车优惠、Classic/Blocks / WooPayments 和 ERP 的模拟订单信息。只允许 0 支付的模拟订单场景，严禁真实付款和发货。
- WooCommerce Elementor/主题页面不覆盖。路径冻结：`/holiday/black-friday/`、`/deals/clearance/`、`/holiday/gift/`。
- R14 文件中的 `BYL_BF2026_DEV_CLI_TEST` 仅针对 **dev.beyourlover.com 的 WP-CLI** 与显式测试常量，WWW 无法使用此入口。
- 全站上线必须另行明确批准，并在**不同的发布版本**专门开启 `BYL_BF2026_ALLOW_GLOBAL_GIFTS`。当前候选版不主动定义此常量。

## 本轮测试
- Canary 安全权限 fixture：**19/19 PASS**（游客、非管理员、其他管理员、错 Cookie、过期、停用、旧引擎、Master ON、缺礼品、失败撤销 Hook、单次启动和禁止双重 Hook、缓存头等）。
- 整体 Black Friday PHP fixtures：**230/230 PASS**（原有 211 加 Canary 19）。这些是独立测试，不是 WWW 购物车 Canary PASS。
- GitHub CI、Dev 安装与浏览器 UAT 必须以本分支**最终 SHA**独立读回。

## 回滚
优先在 **WooCommerce → BF2026 测试**点击「立即停止」，确认任何非测试访客仍正常使用普通购物车；如必须卸载/恢复，先检查是否有旧 R10 价格账本未恢复，再在 Owner 手动插件管理中回退到既有版本。不要直接删除历史价格审计数据。
