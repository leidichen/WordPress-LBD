# GitHub 更新规则（WordPress 主题）

目的：规范基于 GitHub 的主题自动更新发布流程，确保安装包精简、版本一致、可回溯。

## 更新机制与版本来源
- 使用 Plugin Update Checker（PUC）从 GitHub 获取更新：见 [functions.php](file:///Users/reddy/Local%20Sites/design-project/app/public/wp-content/themes/WordPress-LBD/functions.php#L8-L15) 的初始化。
- WordPress 实际对比版本以主题头部的 `style.css -> Version` 为准：见 [style.css](file:///Users/reddy/Local%20Sites/design-project/app/public/wp-content/themes/WordPress-LBD/style.css#L1-L12)。
- 代码内同时使用 `LBD_VERSION` 常量做资源版本控制：见 [functions.php](file:///Users/reddy/Local%20Sites/design-project/app/public/wp-content/themes/WordPress-LBD/functions.php#L1-L4)。三者需保持一致：
  - `style.css` 中的 Version
  - `functions.php` 中的 `LBD_VERSION`
  - README 中的版本徽标/文本（便于展示与回溯）

## 必须排除/不进入发布包的文件
当前通过 [.gitignore](file:///Users/reddy/Local%20Sites/design-project/app/public/wp-content/themes/WordPress-LBD/.gitignore) 已配置：
- docs/
- .DS_Store、._*
- .vscode/、.idea/
- *.log
- node_modules/、dist/、build/
- *.zip、*.tar.gz、*.tgz

说明：
- `inc/plugin-update-checker/` 内的 `.editorconfig`、`.gitattributes`、`composer.json`、`phpcs.xml` 属于上游库开发文件，保留不影响运行与打包。

## 版本号规范（SemVer）
- 使用 `MAJOR.MINOR.PATCH`（例如：`1.2.3`）。
- 每次版本迭代必须同步更新三处：`style.css`、`LBD_VERSION`、README 版本徽标。

## 发布流程（从源码到 GitHub 包）
1. 更新版本
   - 修改 [style.css](file:///Users/reddy/Local%20Sites/design-project/app/public/wp-content/themes/WordPress-LBD/style.css) 的 `Version: X.Y.Z`
   - 修改 [functions.php](file:///Users/reddy/Local%20Sites/design-project/app/public/wp-content/themes/WordPress-LBD/functions.php#L1-L4) 的 `LBD_VERSION`
   - 修改 [README.md](file:///Users/reddy/Local%20Sites/design-project/app/public/wp-content/themes/WordPress-LBD/README.md) 中的版本徽标与变更记录
2. 确认忽略项与仓库整洁
   - `.gitignore` 已包含上文规则；不应再出现 `docs/`、系统/编辑器临时文件等。
3. 提交与打 Tag
   - `git add -A && git commit -m "release: vX.Y.Z"`
   - `git tag vX.Y.Z -m "vX.Y.Z"`
   - `git push && git push --tags`
4. 可选：创建 GitHub Release
   - 在 GitHub 上创建 Release，填写同样的版本号与变更说明。
   - 如需使用 Release 附件作为更新包，可在代码中启用 `enableReleaseAssets()`（当前保持关闭，使用源码打包）。
5. 验证
   - 在 WordPress 后台检查更新是否出现、安装包是否不包含 `docs/` 等无关内容。
   - 安装后在主题列表中显示的新版本号应与 `style.css` 一致。

## 本次变更摘要（复盘）
- 清理：移除 `docs/`、`.DS_Store`，并新增 `.gitignore`（见提交 `860f24f`）。
- 自动更新来源未变：仍从 GitHub 拉取。
- 当前版本信息：
  - `style.css -> Version`: `1.2.2`
  - `functions.php -> LBD_VERSION`: `1.2.2`
  - `README` 版本徽标：`1.2.1`（需在下一次发布前同步为 `1.2.2` 或更高）

## 发布前检查清单
- 版本一致：`style.css`、`LBD_VERSION`、README 徽标一致。
- 变更记录：README 中补充“本次更新说明/Changelog”。
- 打包内容：下载 GitHub 自动生成的 zip，确认无 `docs/`、系统或编辑器临时文件。
- 更新可见：测试站点的主题更新页能检测到新版本并正常安装/覆盖。

