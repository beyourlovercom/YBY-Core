$ErrorActionPreference = 'Stop'
$Repo = 'D:\ai\_repos\YBY-Core'
$Live = 'D:\ai\devybybottle\app\public\wp-content\plugins\yby-core'
$Dirs = @('admin','assets','inc','languages','modules','public','templates')
$Files = @('yby-core.php','uninstall.php','readme.txt','README.md','CHANGELOG.md','VERSION.md')

if (!(Test-Path "$Repo\yby-core.php")) { throw 'YBY-Core repository not found.' }
if (!(Test-Path $Live)) { New-Item -ItemType Directory -Force -Path $Live | Out-Null }

foreach ($dir in $Dirs) {
    $src = Join-Path $Repo $dir
    $dst = Join-Path $Live $dir
    if (Test-Path $src) {
        robocopy $src $dst /MIR /R:1 /W:1 /NFL /NDL /NJH /NJS /NP | Out-Null
        if ($LASTEXITCODE -ge 8) { throw "robocopy failed for $dir with code $LASTEXITCODE" }
    }
}

foreach ($file in $Files) {
    $src = Join-Path $Repo $file
    if (Test-Path $src) { Copy-Item $src (Join-Path $Live $file) -Force }
}

Write-Output 'YBY_CORE_LOCAL_SYNC=PASS'