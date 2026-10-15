# Andy Core 原生 WordPress 三级导航 V2 — Canonical IA / Route Migration (2026-10-14)

> **永久升级规则（Owner 2026-10-15 确认）：** 以后所有 Andy 自研插件新版本及新增后台需求，必须遵循 [Andy Core Admin Architecture Governance Canonical V1](./ANDY-CORE-ADMIN-ARCHITECTURE-GOVERNANCE-CANONICAL-V1.md) 的 L1/L2/L3 架构与兼容性门禁；第三方插件、WordPress 原生页面保留独立性。此规范在 Draft PR 中，CI 硬门禁与 V2.2 运行时修复尚待实现和验收。

> **状态：结构 Owner PASS；页面迁移表已完成静态代码审计，尚未进行 Dev 实际三级导航 UAT。**
> 设计依据：Owner 2026-10-14 明确要求 Rank Math 模式：**一级和二级均在 WordPress 左侧后台菜单，只有三级功能目录在右侧功能页面内部。** 取消二级重复导航、总览中转和卡片中转。
> 此文档是 **设计和安全迁移契约**，不是已发布清单、功能承诺或 Dev 部署授权。

## 0. 只允许的结构

| 层级 | 位置 | 设计契约 |
|---|---|---|
| L1 | WordPress 后台左侧一级 | 唯一 Andy 入口名 **Andy Core**；保留现有根路由 `admin.php?page=yby-os` 以兼容历史 URL |
| L2 | WordPress 左侧 Andy Core 展开子菜单 | Inquiry OS / Newsletter OS / Email OS / Content OS / Marketing OS / Commerce OS / System & Connectors；点击一次即打开有用的现有功能，不强制先进入总览 |
| L3 | 右侧工作区内部左侧栏（如 Rank Math） | 只显示当前 L2 模块的真实三级页面入口；右侧主区域加载**原有功能页面/控制器**，不复制或重建业务逻辑 |
| 移动端 | WordPress 官方折叠菜单 + 右侧三级 | iPhone 14 宽度做截图验收：层级可发现、无横向溢出、焦点不丢、权限一致 |
| 非 Andy 插件 | WordPress 原有侧栏 | WooCommerce / WordPress 官方菜单 / Rank Math / AffiliateWP 保持各自独立，不纳入 Andy 一级入口 |

**显示名/代码名区别：** L1 用户可见名是 `Andy Core`，不是上个 V1 原型的 `Andy OS`；代码保留 `yby-os`，插件仍为 **Andy Core 与 Andy Commerce 两个可独立升级/关闭的插件**。不要求创造“Andy OS 插件”。

**默认导航约束：** L2 必须直接落地真实工作页面：Inquiry → 询盘列表；Newsletter → 订阅者列表；Email → 邮件总览；Content → Dev 已可用的 Business 招商页或用户上次选的已授权内容页面；Marketing → Commerce 的已有 `marketing-promotions`（仅有 Woo 权限且 Dev 能用时）；Commerce → 已有 Commerce 页面（当前 Overview 可用，可支持上次功能记忆，但不得增加必经总览）；System → 已有模块设置。禁用/缺权限时隐藏 L2 并拒绝直链，不显示虚假空列表。

## 1. 真实 Dev 代码依据

2026-10-14 Dev-only 只读盘点路径：`dev.beyourlover.com/wp-content/plugins/yby-core/` 与 `andy-commerce/`。相关实现：

- Core: `admin/class-yby-project-studio.php`、`admin/class-yby-content-admin.php`、`inc/class-yby-core.php`、`admin/class-yby-andy-os-navigation.php`。
- **Dev 特有最新代码**：`admin/class-yby-three-os-admin.php`、`admin/class-yby-three-os-readonly.php`、`admin/class-yby-mail-v2-p6-dev-preview.php`；原 Draft PR #103 对应 GitHub HEAD 中 `class-yby-three-os-admin.php` 查询不到。实施前必须在 GitHub 确定已审计版本的代码出处/分支/Commit，不得拿旧 GitHub Core 文件覆盖 Dev。
- Others: `admin/class-yby-inquiry-admin.php`、`admin/class-yby-popup-admin.php`、`admin/class-yby-docs-os-admin.php`、`admin/class-yby-business-os-admin.php`、`inc/class-yby-landing-page-cpt.php`、`admin/class-yby-admin.php`、`admin/class-yby-social-login-admin.php`、`inc/class-yby-brand-os.php`、`packages/andy-commerce/admin/class-andy-commerce-admin.php` (**Dev 实际 Commerce 比其旧 GitHub 实现更新，有 M3/M4/M5 功能；以 Dev 只读核验为准**).
- Dev 模块选项：`inquiry_os`, `email_os`, `project_studio`, `social_login`, `connector`, `landing_pages`, `newsletter_os`, `business_os`。**`docs_os` 不在 Dev 已启用列表**，Docs 代码/旧入口要保留，但不能声称当前已启用 Docs 菜单。
- 本文是路由**静态注册证据**；Dev 侧的最终实时浏览器可见性、管理员/员工真实账号权限、JS 点击和第三层实际 UI 均须独立 UAT，不能由代码存在推导已上线可用。

状态字段：**A** 已有实际页面/处理器，Dev 需运行时复核；**C** 模块/权限/环境条件可用才显示；**L** 旧 URL/旧兼容行为必须保留但不作为新三级默认入口；**D** Draft/只读/演示/Dev-only，不能写成已上线操作；**P** 尚未证实真实页面，不加可点击菜单。

## 2. L2 / L3 全量迁移矩阵（按 Dev 注册源码）

### L2 — Inquiry OS（直接打开询盘列表）

| L3 | 现有 URL（以 `/wp-admin/` 为起点） | 当前/能力 | 状态 |
|---|---|---|---|
| 询盘列表（默认） | `admin.php?page=andy-core-leads&tab=leads` | `andy_core_leads_view`、`inquiry_os` | A |
| 询盘统计 | `admin.php?page=andy-core-leads&tab=stats` | 同上 | A，只读统计 |
| 悬浮询盘 | `admin.php?page=andy-core-leads&tab=floating_inquiry` | 额外 `andy_core_settings_manage` | C |
| 询盘弹窗 | `admin.php?page=andy-core-leads&tab=popup_inquiry` | 额外 `andy_core_settings_manage` | C |
| 询盘短代码 | `admin.php?page=andy-core-leads&tab=shortcode_inquiry` | 额外 `andy_core_settings_manage` | C |
| 询盘设置 | `admin.php?page=andy-core-leads&tab=settings` | 额外 `andy_core_settings_manage` | A/C |
| 询盘通知 | `admin.php?page=andy-core-leads&tab=notification` | 额外 `andy_core_settings_manage` | A/C |
| 旧设置入口 | `admin.php?page=yby-core&tab=inquiry`, `...&tab=inquiry-notification` | 保留原 GET 重定向、POST 接收门禁 | L |

**不允许**把原 `Inquiry OS` 完整业务操作迁到 WordPress 中另一套 CRM；ERP 仍是未来 B2B+B2C 单一询盘客户事实源。ERP 安全接收链路通过前，现有 WordPress Inquiry OS 不停用。

### L2 — Newsletter OS（直接打开订阅者列表）

WordPress 原有菜单 slug **`andy-core-newsletter`**；显示权限独立 **`andy_core_newsletter_view`**，模块 `newsletter_os` 启用时才出现。

| L3 | 现有 URL | 状态/说明 |
|---|---|---|
| 订阅者列表（默认） | `admin.php?page=andy-core-newsletter&tab=subscribers` | A，现有 M1 表只读分页管理，不导入真实订阅者 |
| 双重确认与退订 | `admin.php?page=andy-core-newsletter&tab=consent` | A，原有状态控制器只读摘要 |
| 订阅弹窗 | `admin.php?page=andy-core-newsletter&tab=popup_subscribe` | C，旧处理器仍要求管理员权限及 Inquiry 模块 |
| 订阅短代码 | `admin.php?page=andy-core-newsletter&tab=shortcode_subscribe` | C，同上 |
| 弹窗抽奖 | `admin.php?page=andy-core-newsletter&tab=popup_lottery` | L/D，路由元数据有定义，但 **当前 `newsletter_visible_tabs()` 默认隐藏**，不能假装现成配置页 |
| 营销活动、审批、统计 | `...&tab=campaigns`, `approvals`, `statistics` | L/D，旧书签兼容或说明，非可执行正式群发功能 |

弹窗和参与者归 Newsletter；实际折扣/奖励规则归 Andy Commerce Marketing，避免两个系统分别执行奖励计算。

### L2 — Email OS（直接打开现有邮件总览）

WordPress 原有菜单 slug **`andy-core-email`**；独立能力 **`andy_core_mail_view`**、模块 `email_os`。

| L3 | 现有 URL | 状态/说明 |
|---|---|---|
| 邮件总览（默认） | `admin.php?page=andy-core-email&tab=overview` | A，只读状况 |
| 邮件模板中心 | `admin.php?page=andy-core-email&tab=email_templates` | A/C，编辑权限仍归 `andy_core_settings_manage` |
| 发送通道 | `admin.php?page=andy-core-email&tab=transport` | A，只读状态，**不自动变更 SMTP 凭据/投递** |
| 系统健康 | `admin.php?page=andy-core-email&tab=health` | A，只读 |
| Dev 队列预览 | `admin.php?page=andy-core-email&tab=preview_queue` | D，`YBY_Mail_V2_P6_Dev_Preview::eligible_site()` 时可见 |
| Dev 失败处理预览 | `admin.php?page=andy-core-email&tab=preview_failures` | D，同上，仅 Dev |
| 邮件队列、失败、事件日志 | `...&tab=queue`, `failures`, `logs` | L/D，旧链接只读说明；**无正式启用 Worker/Outbox/自动重试** |
| 旧弹窗/模板入口 | `admin.php?page=yby-core-popups&tab=email_templates` | L，GET 可能重定向到 `andy-core-email`，原 POST 处理器必须保留 |

**权限 P0：** 当前 V1 根菜单 `root_capability()` 不含 `andy_core_newsletter_view` 和 `andy_core_mail_view`；如果用户只有这类角色权限，一级入口可能消失。V2 必须覆盖并做负向权限测试，不能仅测 Administrator。

### L2 — Content OS（直接进入真实内容页）

| L3 / 原有页面 | 原路由 | 状态/权限 |
|---|---|---|
| Business 招商页面（Dev 可用默认候选） | `admin.php?page=andy-business` | A/C，`business_os` + `andy_core_settings_manage` |
| 合作项目展示 | `admin.php?page=andy-business-programs` | A/C，同上 |
| 申请表单与来源 | `admin.php?page=andy-business-applications` | A/C，同上；**表单仍是模拟，不真实提交** |
| ERP 连接状态 | `admin.php?page=andy-business-settings` | A/C，只读且尚未连接 |
| 原 Business 旧链接 | `andy-business-pages`, `andy-business-partners`, `andy-business-catalog`, `andy-business-fulfillment` | L，隐藏但受权限保护的迁移提示 |
| Landing Pages 列表 | `edit.php?post_type=landing_page` | A/C，真实 WordPress CPT，`landing_pages` 模块启用；**目前 `show_in_menu=true` 使其生成自己一级菜单，V2 必须安全迁移/高亮/权限兼容** |
| Landing Pages 新建 | `post-new.php?post_type=landing_page` | A/C，原 WordPress 编辑器与 revisions、缩略图不动 |
| Project Studio | `admin.php?page=yby-project-studio` | A/C，模块 `project_studio`，`manage_options` |
| Projects CPT 列表 | `edit.php?post_type=yby_project` | A/C，保留 WP 原生 CPT 路由 |
| Docs OS 首页 | `admin.php?page=yby-docs-os` | C，模块 `docs_os` Dev **未启用**，不显示伪功能入口 |
| Docs 全部文档 | `admin.php?page=yby-docs-all` | C，`edit_posts` |
| Docs 分类 | `admin.php?page=yby-docs-directory` | C，`manage_categories` |
| Docs 标签 | `edit-tags.php?taxonomy=doc_tag&post_type=docs` | C，原生 WordPress taxonomy 页面 |
| Docs FAQ / Tutorial | `admin.php?page=yby-docs-faq`, `admin.php?page=yby-docs-tutorial` | C，`edit_posts` |
| Docs 设置 | `admin.php?page=yby-docs-settings` | C，`andy_core_settings_manage` |
| Docs 编辑器 | `admin.php?page=yby-docs-editor` | C，隐藏原深链，`edit_posts` |
| 旧 Andy Content 首页 | `admin.php?page=yby-content` | L，保留旧书签、权限校验，不能强制员工先打开 Overview |
| WordPress Page 编辑 | `edit.php?post_type=page` | 外部 WP 原生内容管理；**保留原 WordPress 一级 Page 菜单**，可在 Content OS 提供不复制数据的快捷跳转 |

Docs、Landing Page、Projects 三类的原生 WP editor、post type、taxonomy、媒体选择器、页面高亮/父级菜单必须分别回归；不能统一将其伪装为 `admin.php?page=...` 后丢失编辑器上下文。

### L2 — Marketing OS（直接到现有 Commerce 营销工作页）

| L3 | 原路由 | 状态 |
|---|---|---|
| 营销活动 / 草稿 | `admin.php?page=andy-commerce&tab=marketing-promotions` | A/C，`manage_woocommerce`，真实 Dev Commerce M3 草稿/列表；不启用活动 |
| M3 草稿编辑 | 原 URL 含 `m3_edit` 参数 | C，复用原控制器；只受 Dev 与 Woo 原权限控制 |
| M4/M5 Shadow 实验页 | 原 URL 含 `m45_preview=1` | D，Dev-only 只读实验，不推广为正式生产菜单 |
| Smart Coupon / BOGO / 捆绑 / 活动提醒 | 不能凭 UI 草案创造 URL | P，在确认真实已上线模块路由前**禁止可点击菜单** |

营销优惠规则执行权仍属 Andy Commerce，Newsletter 弹窗与参与者保持原归属。

### L2 — Commerce OS（直接进入既有 Commerce 页）

| L3 | 原 URL | 状态 |
|---|---|---|
| Commerce 现有工作台 | `admin.php?page=andy-commerce&tab=overview` | A/C，`manage_woocommerce`；用户可直接选择其他三级项，无前置总览跳转 |
| Checkout / Shipping | `admin.php?page=andy-commerce&tab=checkout-shipping` | A/C |
| Order Export | `admin.php?page=andy-commerce&tab=order-export` | A/C |
| Marketing / Promotions | `admin.php?page=andy-commerce&tab=marketing-promotions` | A/C，与 Marketing OS 共享同一个真实源页面（不要复制一套活动数据库） |
| Commerce Settings | `admin.php?page=andy-commerce&tab=settings` | A/C |
| Cart Drawer / Checkout Drawer 配置后台 | 未确认独立后台 URL | P，现有前端功能可能存在，但**不因此生成虚假的设置页** |
| 原独立 Andy Commerce 一级菜单 | `andy-commerce` slug | L，两个插件仍独立升级；兼容旧直链、Woo role、Core OFF 降级路径 |

### L2 — System & Connectors（直接到既有模块设置）

| L3 | 原 URL | 状态/权限 |
|---|---|---|
| 常规 | `admin.php?page=yby-core&tab=general` | A，`andy_core_settings_manage` |
| 模块管理（默认） | `admin.php?page=yby-core&tab=modules` | A，同上；现有真实模块启停 |
| Addons | `admin.php?page=yby-core&tab=addons` | A，同上 |
| Connector / WP API | `admin.php?page=yby-core&tab=wp-api` | C，`connector` 模块；维持原认证与配置权限 |
| 文章目录 | `admin.php?page=yby-core&tab=article-toc` | C，相关模块注册才显示 |
| 系统状态 | `admin.php?page=yby-core&tab=system-status` | A，只读 |
| 插件更新 | `admin.php?page=yby-core&tab=updates` | A，复用现有升级页；不自动下载或安装 |
| Brand OS | `admin.php?page=yby-brand-os` | A/C，`manage_options` |
| Social Login | `admin.php?page=yby-social-login` | A/C，`social_login`、`manage_options` |
| Inquiry / Notification 旧设置 | `admin.php?page=yby-core&tab=inquiry`, `...inquiry-notification` | L，GET 原重定向到 Inquiry OS，POST 保存处理器不改 |

## 3. 权限与数据所有权不变量

- L1 可见性是**当前用户有至少一个现有 L2 可访问能力**，不通过颁发 `manage_options`、`manage_woocommerce` 或其他能力合并权限。
- L2 与 L3 独立鉴权；父级有访问权并不允许操作所有三级页。直接旧 URL 仍由原控制器校验，而非只用 CSS 隐藏。
- `andy_core_newsletter_view`、`andy_core_mail_view`、`andy_core_leads_view`、`andy_core_settings_manage`、`manage_woocommerce`、`manage_options`、`edit_posts`、`manage_categories` 必须分别测试。
- WordPress B2B/B2C 表单来源配置与 ERP 客户询盘操作分离；没有 ERP 安全接收链路，不新建客户表/PI/佣金/订单流。
- Newsletter 无真实群发；Email 不开启 Worker、Outbox、自动重试；Commerce 不修改支付、运费、折扣生效规则；不写真实业务数据。
- Core 关闭时 Commerce 可独立访问自有 `andy-commerce` 入口；Commerce 关闭时 Core 其他模块继续可用。
- 禁用任意模块时，原已有持久数据和 legacy route 合同不被“菜单精简”代码删除。

## 4. V1 失误与 V2 工程门禁

1. **不要重复右侧二级导航：** WP 左侧的 L2 完全承担模块选择；右侧只渲染 L3 与现有业务页面。
2. **不要依靠强制总览：** 点击 L2 直接落到默认真实工作页，不增加“工作台 → 卡片 → 原模块”。
3. **不要另造“空白业务页面”：** 第三级直接复用原控制器/模板。若原页面已有顶部 Tabs，V2 的右侧 L3 应替换/适配导航而不同时显示两个并列 Tab 栏。
4. **不能把所有旧子菜单藏完就算迁移：** 每个真实页面需要可发现的新三级入口；对禁用模块才条件隐藏。
5. **必须解决 M0 源码漂移：** Dev 的 Three_OS / Commerce M3/M4 文件与 GitHub PR #103 历史基线不同。先在 GitHub 对齐真实版本、保留原 Dev 文件哈希和回滚；禁止用旧插件 ZIP/旧 GitHub 文件全量覆盖。
6. **不要动外部插件：** WooCommerce、Rank Math、AffiliateWP、WP 默认 Pages/Posts 菜单保持。
7. **文件范围、CI、备份、路由兼容、Desktop+M14 截图 UAT 均是单独门禁。** GitHub 合并 main 和 WWW 部署均需要独立 Owner 批准。

## 5. Owner UAT Checklist

| ID | 要求 |
|---|---|
| IA-01 | 左侧唯一 Andy 一级菜单显示 **Andy Core**；原 Andy Content / Andy Commerce 不再分别占位，其他原生 WordPress 菜单保持 |
| IA-02 | Andy Core 展开后 L2 依权限依次显示 Inquiry OS、Newsletter OS、Email OS、Content OS、Marketing OS、Commerce OS、System & Connectors |
| IA-03 | 点击任意 L2 一次直达真实页面，右侧没有另一个“二级模块栏”，没有必经总览卡片 |
| IA-04 | L3 标签完整、清晰、当前项高亮；L3 直接打开原模板/原处理器；无双份顶栏 Tabs |
| IA-05 | 旧 URL / 冷启动直链、旧 GET Redirect 和 POST Handler 权限兼容，无 403/404、登录环或重复跳转 |
| IA-06 | Inquiry 设置与通知；Newsletter 订阅者与 consent；Email 模板、transport、health；Business 四个页面；Commerce 五个 tab 按实际能力可用 |
| IA-07 | Docs OS 开关关闭时不显示假功能；启用后分类/Tags/FAQ/Tutorial/Editor 以及 Landing Page CPT/Project CPT 的 WordPress 原生编辑器正常 |
| IA-08 | Newsletter-only、Email-only、Inquiry-only、Content editor、Woo manager、Admin 不越权；root 包含两个独立 newsletter/mail 能力 |
| IA-09 | WP 前端 /business/、/cart/、/checkout/（空车 302 为正常）、/blog/ 和原来的 Newsletter 页面无回归 |
| IA-10 | 桌面浏览器和 iPhone 14/M390 真实截图：L1/L2/L3 在可用宽度显示正常、没有重叠/水平溢出 |
| IA-11 | 失败可恢复 Dev 原菜单与 PHP 文件；GitHub main/WWW 未经批准不变 |

## 6. 下一步（不得越序）

**M0** 基于 Dev 当前源码/运行态列出全部页面 slug 与权限证据，并确定任何无来源页面都不作为可用 L3；解决 Dev 的 Three_OS 和 Commerce 版本漂移。

**M1** 使用本文件建立桌面/手机三级菜单视觉和交互图；此聊天 Owner 已对 L1/L2 位置及 L3 规则 **PASS**，但实际业务完整迁移表尚未单独视觉 UAT。

**M2** 在独立 GitHub Draft PR 实现 native L1/L2 + right-side L3，保留路由和原权限；针对 WordPress 真正的菜单回调和动态页面验证，不做 CSS-only 隐藏。

**M3** CI + 后台权限矩阵 + Legacy URL + editor/CPT/taxonomy 回归；可按当前 Dev 文件内容生成精确差量 Dev Release Candidate。

**M4** Dev-only 受控部署（可使用已获 Owner 授权的 Hostinger File Manager 流程），备份 SHA、原子修改、失败回滚，不动 WWW。

**M5** Owner 真实后台 Desktop / iPhone 14 UAT；仅在 Owner 明确 PASS 后更新 Closure。GitHub main merge、WWW 发布必须另获明确授权。

## 7. 追踪

- Owner V2.1 L1/L2/L3 结构已 **PASS**：2026-10-14。
- V1 Hostinger RC [Draft PR #104](https://github.com/beyourlovercom/YBY-Core/pull/104) 源 [Draft PR #103](https://github.com/beyourlovercom/YBY-Core/pull/103)：部署于 Dev 但 Owner UI UAT FAIL；不合并 main。
- 主任务 [YBY-Core Issue #102](https://github.com/beyourlovercom/YBY-Core/issues/102)。
- 此文档作为 V2 完整信息架构和旧 URL 迁移的可评审初稿；标为 **A** 的仍需要实机 Owner UAT 证明。
