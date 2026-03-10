<#
.SYNOPSIS
    WordPress-LBD 主题快速打包脚本
.DESCRIPTION
    自动读取 style.css 版本号，排除开发文件，生成纯净的 ZIP 包。
    生成的 ZIP 包将位于主题根目录下。
#>

$ErrorActionPreference = "Stop"
$themeName = "WordPress-LBD"
$scriptPath = $PSScriptRoot
$styleCss = Join-Path $scriptPath "style.css"

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "   WordPress-LBD Build Script" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan

# 1. 获取版本号
if (-not (Test-Path $styleCss)) {
    Write-Error "Error: style.css not found in $scriptPath"
    exit 1
}

$content = Get-Content $styleCss -Raw
if ($content -match "Version:\s*([0-9a-zA-Z\.\-]+)") {
    $version = $matches[1]
    Write-Host "Target Version: $version" -ForegroundColor Green
} else {
    Write-Error "Error: Version not found in style.css"
    exit 1
}

# 2. 定义路径
# 使用系统临时目录避免污染项目目录
$timestamp = Get-Date -Format "yyyyMMdd-HHmm"
$tempBase = Join-Path ([System.IO.Path]::GetTempPath()) "LBD_Build_$(Get-Random)"
$targetDir = Join-Path $tempBase $themeName
# 使用版本号 + 时间戳作为文件名，避免覆盖
$zipName = "$themeName-$version-$timestamp.zip"
$zipPath = Join-Path $scriptPath $zipName

Write-Host "Temp Dir: $targetDir" -ForegroundColor Gray
Write-Host "Output:   $zipPath" -ForegroundColor Gray

# 3. 准备临时目录
if (Test-Path $targetDir) { Remove-Item $targetDir -Recurse -Force }
New-Item -ItemType Directory -Path $targetDir -Force | Out-Null

# 4. 复制文件 (使用 Robocopy 高效复制并排除)
Write-Host "`n[1/3] Copying files..." -ForegroundColor Cyan

# 排除目录列表
$excludeDirs = @(
    ".git", ".github", ".vscode", ".idea", 
    "node_modules", "examples", "tests", "bin", "unit-tests",
    "dist", "build", "languages" # 如果 languages 不需要打包可以排除，通常需要保留
)

# 排除文件列表
$excludeFiles = @(
    ".gitignore", ".gitattributes", ".editorconfig", ".travis.yml",
    "composer.json", "composer.lock", "package.json", "package-lock.json", "yarn.lock",
    "phpcs.xml", "*.map", "*.log", "*.tmp", ".DS_Store", "Desktop.ini", "Thumbs.db",
    "build.ps1", "*.zip", "*.tar.gz" # 排除脚本自身和压缩包
)

# 构建 Robocopy 参数
# /E :: 复制子目录，包括空的
# /XD :: 排除目录
# /XF :: 排除文件
# /NFL /NDL /NJH /NJS :: 减少日志输出
$robocopyArgs = @($scriptPath, $targetDir, "/E", "/XD") + $excludeDirs + @("/XF") + $excludeFiles + @("/NFL", "/NDL", "/NJH", "/NJS")

# 执行 Robocopy
# Robocopy 返回值 < 8 表示成功
$p = Start-Process robocopy -ArgumentList $robocopyArgs -NoNewWindow -PassThru -Wait
if ($p.ExitCode -ge 8) {
    Write-Error "Robocopy failed with exit code $($p.ExitCode)"
    exit 1
}

# 5. 二次清理 (针对特定深层文件的清理)
Write-Host "[2/3] Cleaning specific files..." -ForegroundColor Cyan

# 清理 inc/plugin-update-checker 下的非必要文件
$pucPath = Join-Path $targetDir "inc\plugin-update-checker"
if (Test-Path $pucPath) {
    $pucExcludes = @("README.md", "composer.json", "phpcs.xml", "examples", ".gitignore")
    foreach ($item in $pucExcludes) {
        $p = Join-Path $pucPath $item
        if (Test-Path $p) { 
            Remove-Item $p -Recurse -Force -ErrorAction SilentlyContinue 
        }
    }
}

# 6. 压缩
Write-Host "[3/3] Compressing package..." -ForegroundColor Cyan
# 不需要移除旧文件，因为文件名包含时间戳，总是新的
# if (Test-Path $zipPath) { Remove-Item $zipPath -Force }

# 使用 Compress-Archive
# 注意：压缩 $targetDir 的父目录下的文件夹，以确保 zip 包内包含 WordPress-LBD 根文件夹
# 如果直接压缩 $targetDir/*，解压后会散落在当前目录，不符合 WordPress 主题规范
# WordPress 主题 zip 解压后应该是一个文件夹
Compress-Archive -Path $targetDir -DestinationPath $zipPath

# 7. 清理临时目录
Remove-Item $tempBase -Recurse -Force

Write-Host "`nSuccess! Package created at:" -ForegroundColor Green
Write-Host "  $zipPath" -ForegroundColor White
Write-Host "==========================================" -ForegroundColor Cyan
