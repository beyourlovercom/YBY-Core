# Andy Core v1.5.1 Admin Architecture Audit

- Parent: Andy Core, slug yby-os, callback YBY_Project_Studio::render_project_studio_page, existing capability manage_options.
- Inquiry: slug andy-core-leads, callback YBY_Inquiry_Admin::render_page, capability andy_core_leads_view; only new submenu and moved first.
- Project Studio: slug yby-os, callback YBY_Project_Studio::render_project_studio_page, capability manage_options.
- Projects: edit.php?post_type=yby_project, WordPress post-list callback, capability manage_options.
- Brand: existing YBY_Brand_OS::add_admin_menu registration, slug and callback preserved.
- Social Login: existing YBY_Social_Login_Admin::add_admin_menu registration, slug and callback preserved.
- Settings: existing YBY_Helpers::admin_page_slug() slug, callback YBY_Admin::render_settings_page, capability andy_core_settings_manage.
- Menu order: Inquiry, Project Studio, Projects, Brand, Social Login, Settings.
- Assets: Inquiry CSS/JS loads only on yby-os_page_andy-core-leads; existing admin assets remain scoped to prior hooks.
- Highlighting and direct URLs: parent yby-os and the existing Settings tab allowlist preserve direct routes and highlighting.
