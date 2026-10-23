# Marketing OS B0–B2 中文桌面后台源码与 UAT 交接
日期：2026-10-23。策略：先视觉 → 再功能 → 最后真实交易。

## 0. 边界
- 当前窗口只负责 Marketing OS；Currency OS 窗口负责唯一 Andy Commerce 版本、Dev 安装包及汇率/币种模块。
- 本分支基于 Currency 集成源码 f218a106771cceadcd56d150c34ec0c37c83c4cf，仅修改 Marketing 页面、测试及本记录；不升级插件版本，不动 Currency OS 或真实 Woo hooks。
- 营销奖励、运费变更、真实订单及支付一律 OFF。后台 Desktop-only，不要求 M14；顾客可见前台仍需要移动端适配。
- 沿用已审批的 #198 中文文案；不重新设计规则、不改变百分比/赠品引擎语义。

## B0：Andy Core 左侧导航唯一事实源
- 核验 admin/class-yby-andy-os-navigation.php 中 YBY_Andy_OS_Navigation::sections() 注册 andy-core-marketing「营销」，destination() 指向 admin.php?page=andy-commerce&tab=marketing-promotions。
- Marketing 右侧仅渲染内容；不新增 add_menu_page、add_submenu_page、左侧自建菜单或重复导航。历史 fallback 内部标签仍遵循 Core Shell 的 suppress_legacy_tabs 约束。
- 复用现有 Currency OS M1/M2/V2、Marketing V2、M7/M8/M9，不重复合并功能。

## B1：中文版右侧后台
- marketing-m9-usd-multirule-lab.php：订单金额输入、两档满减/赠品、独立规则表、草稿保存、币种结果、权限与错误文案中文化。
- marketing-m7-dev-studio.php：活动草稿、场景对比、模拟预览、编辑与归档及 M9 入口统一中文。
- marketing-v2-rule-editor.php：固定减免、百分比草稿、买赠、组合、阶梯、赠品草稿和高级 JSON 表单中文。
- marketing-promotions.php：历史只读设计回退页面中文化。
- 数据内部键、WordPress Nonce、URL、POST 路由、金额与优惠计算未修改；模拟达标不得等同真实生效。

## B2：桌面视觉
- 右侧最大内容宽度 1360px；PingFang SC 字体；深色正文 + 绿色管理动作；四列桌面输入，约 37px 输入高度；白色紧凑卡片和清晰状态/金额表。
- Core 官方 L2/L3 导航完全保持现状；仅优化右侧页面。桌面 Dev 推荐 1440×900 / 1680×900 UAT，后台不做手机端验收。
- Marketing V2 可保存百分比草稿，M9 统一 USD 引擎暂未完全消费百分比优惠，明确推迟到 B3，禁止误称已正式支持活动生效。
- 所有营销活动与特殊运费仍保留原有 OFF / Woo 原生权威，不能通过该 UI 创建真实付款或赠品发放。

## 源码和 UAT 门禁
- marketing-b0b2-desktop-zh-harness.php：导航归属、中文/桌面/安全语义静态断言。
- marketing-m7-draft-security-harness.php：原有 nonce、权限、revision。
- marketing-v2-visual-rule-harness.php：现有草稿模式与高级 JSON。
- marketing-m9-dev-page-render-harness.php：隔离 PHP 视图渲染，五币种/五个 Woo 区域。
- marketing-m9-usd-multirule-harness.php：USD 250 项测试；运行 Commerce 全量 PHP tests 和 native Completed JS。
- 源码测试 PASS 不等于 Dev 实机 UAT。先交 Currency OS 唯一集成主线吸收独立 Marketing 改动，建立版本与回滚证据，经原生 WP Dev 安装及读取源校验后，进行 Owner 一次性桌面视觉 UAT。
- GitHub main、Stable、WWW、真实订单/支付/运费/税费/邮件/Cron 不修改。正式上线仍需单独授权。
