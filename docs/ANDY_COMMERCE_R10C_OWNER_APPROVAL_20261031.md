# Andy Commerce R10-C Owner 审批单 — 2026-10-31

**结论：准备提交 Owner Gate A（仅 Dev 候选版）；不是 www 交易启用批准。**

## 1. 变更范围与实际状态

- 仓库 beyourlovercom/YBY-Core，主整合 PR #213 仍为 Draft，未合并 main。
- www 仍是 Andy Commerce 1.0.14 OBSERVE，R10-C 从未部署到 www 或 Dev。
- 源代码插件版本号目前仍为 1.0.14；计划下一轮 Dev 候选版号 1.1.0-rc1，但尚未构建、发布或安装。
- WordPress 插件入口的观察模式 return 在 R10-C 的 require 和 boot 之前：观察模式优先，R10-C 在 OBSERVE 下不会注册交易 Hook。
- R10-C 两个独立模块默认 OFF：andy_commerce_r10c_shipping_enabled_v1 / andy_commerce_r10c_coupon_enabled_v1，必须分别明确设置为 yes 才有机会运行。
- www 双重授权要求：站点必须是 www.beyourlover.com，WordPress 运行类型 production，服务器显式设定 ANDY_COMMERCE_R10C_OWNER_RELEASE_AUTH = OWNER_APPROVED_R10C_RELEASE，并且模块选项启用。当前没有给任何正式站授权。
- 精确依赖检查：WooCommerce 11.2.1、YayCurrency Pro 3.3.5、WebToffee Smart Coupons Pro 3.2.0、StoreApps Smart Coupons 9.51.0，均必须启用；商店基础币种 USD、两位小数。任何不匹配均 Fail Closed。
- 后台入口：优先 Andy Core 左侧二级菜单 R10-C 模块开关；管理员权限 + Nonce + 旧状态检查 + 显式输入 ENABLE_R10C_SHIPPING 或 ENABLE_R10C_COUPON。独立关闭无须符合启用前提，最近20次切换写审计。观察模式下后台入口不加载。

## 2. 实施逻辑

### Issue #275 — EUR 免邮浮点边界

仅处理 Woo 原生默认区域 0 的 free_shipping instance 1，要求金额门槛类型为 min_amount/either、ignore_discounts=no、方式启用、标价不含税。仅当顾客购物车折扣后金额与转换后的免邮门槛四舍五入后属于完全相同的整数分值，且原生 FALSE 由 0 至 1e-9 的负向浮点差造成时，允许修复为 TRUE。其他区域、运费方法、特殊 shipping class、赠品、含税标价及无法确认的商品都保持原生判断；后续插件仍有否决权。

### Issue #276 — 同时只允许一张普通折扣券

仅限制正金额的 percent、fixed_cart、fixed_product 普通折扣券。先应用的普通券保留，第二张普通券拒绝。原生有效免邮券、零金额券及 Store Credit/BOGO 等非标准券不占用普通折扣券名额；不执行订单、支付、库存或赠品写入。

**Owner 仍须确认：自动应用的普通优惠券是否也占用这个名额。当前候选为“先到的普通券优先”，因此先自动应用的折扣可能阻止顾客随后输入手动券。若 Owner 希望“手动券优先、自动券让路”，需单独完善并复测。**

## 3. 隔离 UAT — 真实插件联合运行

环境：全新 Windows Local WordPress 7.1、PHP 8.2、MySQL 8.4，准确版本 Woo 11.2.1 / YayCurrency Pro 3.3.5 / WebToffee Pro 3.2.0 / StoreApps 9.51.0 全部 active。仅使用新建空白测试数据库和合成商品/优惠券。四个外币是 YayCurrency 真正的币种切换机制，但汇率为测试专用固定值 USD1 / EUR0.92 / GBP0.80 / AUD1.52 / CAD1.35，不代表 www 的真实汇率。

| 测试 | 验收 |
| --- | --- |
| Shipping 与 Coupon 全部 OFF | 两个运行门禁均 False |
| 只开启 Shipping | EUR 69 美元等值订单免邮；两普通券仍可叠加 |
| 只开启 Coupon | 第二张普通券拒绝；EUR 边界仍保持未修复的原生 FALSE |
| 两个都 ON：5币种×8组原生 Woo 购物车 | 40/40，免邮资格和 USD 基线一致 |
| 两个都 ON：本地 Store API 实际 HTTP | 11/11，五币种满足金额免邮、五币种第二券返回 HTTP400、有效免邮券可用 |
| EUR 反向测试 | 5/5：低于门槛、超过门槛、后续其他插件拒绝、优惠券反向顺序、免邮券 |
| 特殊类/含税/默认国家运费测试 | 8/8：不扩大特殊免邮、四国默认区域通过 |
| 生产与观察门禁模拟 | 10/10 |
| Observer 优先顺序及管理员操作源码约束 | 12/12 |
| 测试环境订单与支付 | 均为 0 |

无付费插件代码或真实订单/客户/生产数据提交 GitHub。以上仅说明准确版本的**隔离环境**下候选行为 PASS，并不等于正式站线上营销规则已穷尽。

## 4. 尚未通过、不得视为生产就绪的门禁

- Dev 尚未安装两套准确版本的 Smart Coupons，尚无完整 Dev 浏览器 checkout UAT。
- 真实生产活动的 StoreApps auto-apply、WebToffee BOGO、Store Credit、赠品、商品例外、多个运费区域及税收边界需要独立核对。
- 尚未批准自动券 vs 手动券的先后权规则。
- 尤其重要：退出 www 全局 OBSERVE 可能同时启动以前的其他 Andy Commerce 交易 Hook，并非只启动 R10-C 两个模块。绝不能借本次审核直接退出 OBSERVE。
- 源插件目前仍为 1.0.14；Dev 正式候选 ZIP/rollback ZIP 的版本和 SHA256 必须在下一关口制作后审计。

## 5. 分阶段审批、发布与回滚

**Gate A — 现在建议批准（仅 Dev，不含 www）**

批准构建 Andy Commerce 1.1.0-rc1 Dev 候选包，先核实准确插件依赖，补齐两个 Smart Coupons 授权测试副本，仅对 dev.beyourlover.com 受控安装。两个新模块默认 OFF，不修改 /denglu 登录地址，不发送订单、邮件、支付或退款。安装前保留 Dev 的已知可用安装包并准备回滚，发布后记录真实版本、开关状态、ZIP 校验值及浏览器验收。www 完全不动。

建议授权文字：

**pass R10-C-D：允许 1.1.0-rc1 Dev 验收，Shipping OFF、Coupon OFF，www 不变。**

**Gate B — 必须在 Dev 的真实插件/营销/浏览器回归合格后单独批准**

允许 www 仅安装新版本并继续保持 OBSERVE，先确认回滚文件和状态。此权限不包含任何交易模块启用。

**Gate C — 必须按模块单独批准**

Shipping ON、Coupon ON 分别单独 Owner PASS，且整个 Commerce 退出 OBSERVE 前还需完成旧交易 Hook 的影响审计。

回滚：若将来已经启用，管理员可在 Andy Core 的 R10-C 菜单按模块立即关闭并查看审计；紧急情况使用既有 Andy Commerce 全局“进入观察模式”按钮阻断全部交易 Hook；后台不可访问时采用经独立批准的 WordPress 插件回滚包和站点备份，不尝试未授权生产 SSH、重启服务或直接写数据库。

## 6. 当前审批结论

**Owner PASS 尚未发出。尚未合并 main、安装 Dev/WWW、退出观察模式、启用交易模块或执行真实订单/支付测试。推荐仅审批 Gate A。**

Issue #275/#276 继续保持 OPEN，待生产架构、真实促销配置兼容及各自独立发布验收通过后才能关闭。
