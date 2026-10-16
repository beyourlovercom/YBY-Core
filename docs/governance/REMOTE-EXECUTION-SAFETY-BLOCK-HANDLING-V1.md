# Remote Execution Safety V1 — 最小单元执行与阻断处理规范

**状态：提案 / Draft PR；未经合并不得声称已成为强制策略。**
**范围：** YBY-Core 关联的 WordPress、GitHub、Desktop Commander（自建 DC / 官方 OAuth MCP）、Google Ads 的获准工作流。其他仓库/设备只有在各自流程显式引用本规则后才适用。
**日期：** 2026-10-16
**依据：** GitHub 是 YBY-Core 唯一源码事实源；生产环境发布需要独立门禁；AI 执行者不能通过拆分、变更工具或编码方式规避明确的安全阻断。

## 一、核心原则

1. **最小任务单元（Atomic Task）**：按真实业务功能拆成可独立验证的小步骤。每步固定目标、主机/账户、权限、动作、影响范围、预期输出、回滚策略；不将多台设备、数据库及线上广告修改打包到同一命令。
2. **先读后写**：只读 GitHub / 状态 / 草稿 / CI / 线上公开渲染与生产写入彼此分离。读取信息不足时不得猜测当前环境或线上成功状态。
3. **默认继续独立的安全工作**：某一步受阻，只停止该步骤及依赖它的后续写入；不受影响的文档、Draft PR、CI、测试及无害设备状态检查可以继续，不必每一步要求 Owner 回答“下一步”。
4. **明确阻断不可规避**：平台或工具明确报告安全拦截时，禁止通过进一步碎片化同一被拒效果、换旧 DC/SSH/浏览器/脚本、编码/代理转发等方式规避。需记录拒绝并走正常授权、解释或人工管理路径。
5. **不重启 READY 设备**：自建 DC 与官方 OAuth MCP 的状态只按实时结果判断；不得为了重试安全阻断而重启在线设备、Chrome 配置或代理。只选择能独立完成该任务的获准工具，不做连接转换的规避动作。
6. **外部副作用须验证**：一次 GitHub CI PASS 只代表代码检查，不等于 Dev/Production UAT；一次 Google Ads 写入成功提示不等于实时投放、审核或真实询盘；公开网页可访问也不代表业务流程已验收。
7. **源码边界**：GitHub `beyourlovercom/YBY-Core` 的 `main` 为已合并代码事实源；Draft PR 是候选，不自动合并或部署。插件、Bricks 页面、历史订单、询盘、支付、邮件及现有广告均独立保护。

## 二、任务元数据

每个步骤至少记录：
- `task_id`：例如 `US-WHOLESALE-S02`
- `intent`、`tool`、`device/account`、`hostname/path`（无秘密）
- `mode`：`READ_ONLY` / `DRAFT_WRITE` / `PRODUCTION_WRITE` / `LIVE_ADS_WRITE`
- `allowed_scope` 和明确的 `forbidden_scope`
- `preconditions`（独立证据）、`expected_evidence`、`rollback`
- `status`：`PENDING`、`PASS`、`FAIL`、`BLOCKED_SECURITY`、`BLOCKED_PERMISSION`、`BLOCKED_TRANSIENT`、`SKIPPED_DEPENDENCY`、`NEEDS_OWNER`

## 三、错误分类与动作规则

| 类型 | 判定依据 | 允许的下一步 | 禁止 |
|---|---|---|---|
| 安全拦截 `BLOCKED_SECURITY` | 平台或工具明确拒绝（例如 OpenAI 安全检查） | 立即停止受阻效果；记录返回文字、任务范围、时间、涉及工具；继续独立的只读/GitHub 工作；正常审核或管理员授权/人工介入 | 换另一 DC 或拆小相同效果反复试探、编码绕过、改变入口以重做已拒请求 |
| 权限不足 `BLOCKED_PERMISSION` | `403`、未授权、角色或能力不足 | 检查当前授权和官方角色设置，申请合法授权，复核后再决定执行 | 提权、偷用其他账号、跳过审批 |
| 参数/业务校验 `FAIL` | 明确 4xx 输入错误、契约差异、签名错误、对象不存在 | 对照官方接口与 GitHub 修正非受阻参数，在相同已获准权限范围内重新独立验证 | 删除门禁或盲目重复写操作 |
| 瞬时故障 `BLOCKED_TRANSIENT` | 网络超时、短暂断线、限流 | 限次重试并检查是否有已完成副作用，幂等后才重试 | 无限重试造成重复页面/费用/订单 |
| 依赖未满足 `SKIPPED_DEPENDENCY` | 未发布落地页、广告未审核、UAT 未 PASS | 跳过依赖该前置条件的动作，继续其它可独立完成工作 | 伪报通过或先开广告 |

**安全拦截规则比任务拆分规则优先。** 若一个原始复合请求被拦截，先确认是否存在**真正独立、未被拒绝的无害操作**；只有这些可继续执行。不得把被拒内容拆成可拼回相同效果的步骤逐项运行。

## 四、发布/投放状态机

`READ_ONLY_AUDIT → SOURCE_CONTRACT → DRAFT_PREPARED → CI_PASS → AUTHORIZED_ENVIRONMENT_PREFLIGHT → DRAFT_UAT → OWNER_UAT → APPROVED_RELEASE → POST_RELEASE_UAT → ADS_REVIEW → ENABLED_AND_VERIFIED`

- 状态跳级一律不允许。跨 Dev / Production、站点 / 广告、代码 / 数据库均分别取得适用授权。
- 发布前核对目标唯一身份、allowlist、Git HEAD、来源指纹、签名、回滚；发布后用真实运行时证据核验。
- 人工操作仅在 Owner 或管理员经其正常权限流程独立执行时发生；AI 仅提供必要的合规操作清单并接收可核实的结果，不收取生产凭据或引导绕开平台阻断。
- 出现阻断时只提醒 Owner **一次具体决策/人工操作**，其它无依赖工作持续推进，避免不停要求“pass/下一步”。

## 五、美国 Wholesale 2026-10-16 现状与原子任务

**不可将完成的准备环节重复执行；不要安装旧插件版本、重新建同名 Campaign 或修改现有其它国家。**

| 步骤 | 唯一目标 / 验证证据 | 当前状态 | 操作边界 |
|---|---|---|---|
| US-S00 | GitHub PR #136 Draft / 最新 exact-head / 变更文件 | PASS | 只读 GitHub |
| US-S01 | GitHub CI 五项 PASS | PASS | 只读 GitHub |
| US-S02 | 自建 DC Windows、US Mac mini 设备联网状态 | PASS | ping、list devices，不启动 SSH/WP |
| US-S03 | Production 德国 #824 / TY #310 / 母版 #215 精确签名及唯一性核验 | BLOCKED_SECURITY（曾触发平台阻断；不可改入口绕过） | 只能在合法解除阻断或 Owner 管理员流程获准后继续 |
| US-S04 | 用候选脚本仅生成美国 Wholesale 新 Draft | SKIPPED_DEPENDENCY | 依赖 US-S03，严禁覆盖德国 #824 或旧 US Solution #798 |
| US-S05 | 美国页面 M14 / Desktop、Bricks 4/4 Code、17 CTA、弹窗、归因 UAT | SKIPPED_DEPENDENCY | 不发送真实伪造询盘 |
| US-S06 | 页面单独正式发布、HTTP200 与回滚可用性 | SKIPPED_DEPENDENCY | 仅美国目标；禁止任何插件升级、订单/邮件/CRM 写入 |
| US-S07 | Google Ads 美国 Campaign #24355649904 完整广告、PRESENCE、搜索合作伙伴 OFF、正式 Final URL、审批 | SKIPPED_DEPENDENCY | 预算 ¥100/天；必须引用已验证真实美国正式页面 |
| US-S08 | 仅启用美国 Campaign 并核验状态和花费 | SKIPPED_DEPENDENCY | 仅新美国系列，不触碰德国、坦桑尼亚、肯尼亚等既有系列 |

**已知依据：**
- 美国页面发布候选：Draft PR #136，审核时 latest head `1419ac71a730bf678773ce424f19eda3c2b41970`，5/5 CI PASS，不合并/不发布。
- 德国最终 Owner UAT：Production Wholesale Landing #824，iPhone14 / 桌面样式；美国开发草稿 #821 与旧 Solution #798 不能替代生产发布。
- Google Ads 美国：Campaign #24355649904（按最后成功的写入结果为 PAUSED）、2 个暂停广告组、20 个正向和 18 个否定关键词，拟定 ¥100/天；最近 Windsor.ai 读取出现套餐占位文案，不能拿来当实时状态证据。
- 早前 Production WP 远程访问的 OpenAI 明确安全拦截是事实，准确触发规则未披露。**不得把安全拦截误写为 GitHub / WP 故障，也不得推断从另一接口肯定能成功。**

## 六、汇报与人工升级模板

一行摘要优先：`PASS 已完成 N 项；BLOCKED_SECURITY US-S03；未执行 US-S04–08；Owner 只需通过正常管理流程确认生产页面发布的获准路径。无 Ads 花费变更。`

如 Owner 需手工处理，只给**一个**可行动的请求：由正式站具备授权的管理员使用正常 WordPress 管理界面/获准部署流程核实、执行受限操作，并返回无敏感信息的成功/失败证据。对同一阻断不反复追问、改工具或隐蔽重试。

**这份规则提升可审计性与稳定性，不提供也不允许任何安全门禁绕过方法。**
