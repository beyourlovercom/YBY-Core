$ErrorActionPreference = 'Stop'

$Repo = 'D:\ai\_repos\YBY-Core'
$Live = 'D:\ai\devybybottle\app\public\wp-content\plugins\yby-core'
$SyncScript = Join-Path $Repo 'scripts\windows\sync-yby-core-local.ps1'
$Dirs = @('admin','assets','inc','languages','modules','public','templates')
$Files = @('yby-core.php','uninstall.php','readme.txt','README.md','CHANGELOG.md','VERSION.md')
$Url = 'https://localdev.ybybottle.com'

function Get-RelativePath([string]$Base, [string]$Path) {
    return $Path.Substring($Base.Length).TrimStart('\')
}

try {
    if (!(Test-Path -LiteralPath $Repo -PathType Container)) { throw "Repository not found: $Repo" }
    if (!(Test-Path -LiteralPath $SyncScript -PathType Leaf)) { throw "Sync script not found: $SyncScript" }

    & $SyncScript
    if ($LASTEXITCODE -and $LASTEXITCODE -ge 8) { throw "Local sync failed with exit code $LASTEXITCODE" }

    $caCertificate = 'C:\Users\Administrator\AppData\Roaming\Local\run\router\nginx\certs\localdev.ybybottle.com.crt'
    if (!(Test-Path -LiteralPath $caCertificate -PathType Leaf)) { throw "LocalWP CA certificate not found: $caCertificate" }
    try {
        $httpStatus = (& python.exe -c "import ssl,sys,urllib.request; context=ssl.create_default_context(cafile=sys.argv[2]); opener=urllib.request.build_opener(urllib.request.ProxyHandler({}),urllib.request.HTTPSHandler(context=context)); response=opener.open(sys.argv[1]); print(response.status)" $Url $caCertificate | Out-String).Trim()
        $pythonExitCode = $LASTEXITCODE
    } catch {
        throw "Local site request failed for $($Url): $($_.Exception.Message)"
    }
    if ($pythonExitCode -ne 0) { throw "Local site request failed for $($Url): python.exe exit code $pythonExitCode." }
    if ($httpStatus -ne '200') { throw "Local site returned HTTP $httpStatus, expected HTTP 200." }

    $livePluginFile = Join-Path $Live 'yby-core.php'
    if (!(Test-Path -LiteralPath $livePluginFile -PathType Leaf)) { throw "Synced runtime plugin file not found: $livePluginFile" }

    $repoHeader = Get-Content -LiteralPath (Join-Path $Repo 'yby-core.php') -Raw
    $liveHeader = Get-Content -LiteralPath $livePluginFile -Raw
    $repoVersionMatch = [regex]::Match($repoHeader, '(?im)^\s*\*\s*Version:\s*([^\r\n]+)')
    $liveVersionMatch = [regex]::Match($liveHeader, '(?im)^\s*\*\s*Version:\s*([^\r\n]+)')
    if (!$repoVersionMatch.Success -or !$liveVersionMatch.Success) { throw 'Could not read the plugin version header.' }
    $repoVersion = $repoVersionMatch.Groups[1].Value.Trim()
    $liveVersion = $liveVersionMatch.Groups[1].Value.Trim()
    if ($repoVersion -ne $liveVersion) { throw "Plugin version mismatch: repository $repoVersion, runtime $liveVersion." }

    $expected = @{}
    foreach ($dir in $Dirs) {
        $sourceDir = Join-Path $Repo $dir
        if (Test-Path -LiteralPath $sourceDir -PathType Container) {
            Get-ChildItem -LiteralPath $sourceDir -Recurse -File | ForEach-Object {
                $relative = Get-RelativePath $Repo $_.FullName
                $expected[$relative] = $_.FullName
            }
        }
    }
    foreach ($file in $Files) {
        $sourceFile = Join-Path $Repo $file
        if (Test-Path -LiteralPath $sourceFile -PathType Leaf) { $expected[$file] = $sourceFile }
    }

    foreach ($relative in $expected.Keys) {
        $runtimeFile = Join-Path $Live $relative
        if (!(Test-Path -LiteralPath $runtimeFile -PathType Leaf)) { throw "Synced runtime file missing: $relative" }
        $sourceHash = (Get-FileHash -LiteralPath $expected[$relative] -Algorithm SHA256).Hash
        $runtimeHash = (Get-FileHash -LiteralPath $runtimeFile -Algorithm SHA256).Hash
        if ($sourceHash -ne $runtimeHash) { throw "Synced runtime file differs from repository: $relative" }
    }

    foreach ($dir in $Dirs) {
        $runtimeDir = Join-Path $Live $dir
        if (Test-Path -LiteralPath $runtimeDir -PathType Container) {
            Get-ChildItem -LiteralPath $runtimeDir -Recurse -File | ForEach-Object {
                $relative = Get-RelativePath $Live $_.FullName
                if (!$expected.ContainsKey($relative)) { throw "Unexpected synced runtime file: $relative" }
            }
        }
    }

    Write-Output "YBY_CORE_LOCAL_UAT=PASS HTTP_STATUS=$httpStatus PLUGIN_VERSION=$repoVersion SYNCED_FILES=$($expected.Count)"
    exit 0
} catch {
    Write-Error "YBY_CORE_LOCAL_UAT=FAIL $($_.Exception.Message)"
    exit 1
}
