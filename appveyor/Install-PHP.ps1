Param(
  [string]$Version,
  [switch]$Highest,
  [switch]$Lowest,
  [switch]$ThreadSafe,
  [string]$Arch = "x86",
  [string]$InstallPath = "C:\tools\php",
  [array]$Extensions
)
Add-Type -assembly "System.IO.Compression.FileSystem"

$Arch = $Arch.ToUpper()
if ($Arch -notin @("X86", "X64")) {
    throw "The arch value must be x86 or x64. Got: $Arch"
}
if ($Highest -and $Lowest) {
    throw "You cannot specify both the highest and lowest version"
}
if (!$Version -and ($Highest -or $Lowest)) {
    throw "If you don't specify a version you cannot specify high or low. The most current is assumed."
}

if ($Version) {
    $Version = New-Object -TypeName System.Version($Version)
    if (($Highest -or $Lowest) -and $Version.Build -ne $null) {
       throw "To Select the highest or lowest version you must not specify an exact version."
    }
    if (!($Highest -or $Lowest) -and [int]$Version.Build -eq $null) {
       throw "If you don't select the highest or lowest version, you must specify an exact version."
    }
}

$VC = @{
    "VC6_X86" = "https://download.microsoft.com/download/d/3/4/d342efa6-3266-4157-a2ec-5174867be706/vcredist_x86.exe"
    "VC9_X86" = "https://download.microsoft.com/download/1/1/1/1116b75a-9ec3-481a-a3c8-1777b5381140/vcredist_x86.exe"
    "VC11_X64" = "https://download.microsoft.com/download/1/6/B/16B06F60-3B20-4FF2-B699-5E9B7962F9AE/VSU_4/vcredist_x64.exe"
    "VC11_X86" = "https://download.microsoft.com/download/1/6/B/16B06F60-3B20-4FF2-B699-5E9B7962F9AE/VSU_4/vcredist_x86.exe"
    "VC14_X86" = "https://download.microsoft.com/download/9/3/F/93FCF1E7-E6A4-478B-96E7-D4B285925B00/vc_redist.x86.exe"
    "VC14_X64" = "https://download.microsoft.com/download/9/3/F/93FCF1E7-E6A4-478B-96E7-D4B285925B00/vc_redist.x64.exe"
    "VC15_X86" = "https://download.microsoft.com/download/6/A/A/6AA4EDFF-645B-48C5-81CC-ED5963AEAD48/vc_redist.x86.exe"
    "VC15_X64" = "https://download.microsoft.com/download/6/A/A/6AA4EDFF-645B-48C5-81CC-ED5963AEAD48/vc_redist.x64.exe"
}

Write-Output "Checking for downloadable PHP versions..."
$AllVersions = @()
foreach ($url in @("http://windows.php.net/downloads/releases/", "http://windows.php.net/downloads/releases/archives/")) {
    $Page = Invoke-WebRequest -URI $url
    $Page.Links | Where-Object { $_.innerText -match "^php-(\d{1,}\.\d{1,}\.\d{1,})-(nts-)?.*(VC\d\d?)-(x\d\d).zip" } | ForEach-Object {
        $php = @{}
        $php['version'] = New-Object -TypeName System.Version($Matches[1])
        $php['vc'] = ($Matches[3] + '_' + $Matches[4]).ToUpper()
        $php['arch'] = $Matches[4].ToUpper()
        $php['url'] = [Uri]::new([Uri]$url, $_.href).AbsoluteUri
        $php['ts'] = ![bool]$Matches[2]

        $AllVersions += $php
    }
}

$Filtered = $AllVersions | Where-Object { [string]$_.ts -eq $ThreadSafe -and $_.arch -eq $Arch }
if ($Version -and $Highest) {
    $ToInstall = $Filtered | Where-Object { [string]$_.version -match [string]$Version } | Sort-Object -Descending { $_.version } | Select-Object -First 1
} elseif ($Version -and $Lowest) {
    $ToInstall = $Filtered | Where-Object { [string]$_.version -match [string]$Version } | Sort-Object { $_.version } | Select-Object -First 1
} elseif ($Version) {
    $ToInstall = $Filtered | Where-Object { [string]$_.version -eq [string]$Version } | Select-Object -First 1
} else {
    $ToInstall = $Filtered | Sort-Object -Descending { $_.version } | Select-Object -First 1
}

if (!$ToInstall) {
    throw "Unable to find an installable version of $Arch PHP $Version. Check that the version specified is correct."
}

$PhpFileName = [Uri]::new([Uri]$ToInstall.url).Segments[-1]
$DownloadFile = ($InstallPath + '\' + $PhpFileName)

$VcFileName = [Uri]::new([Uri]$VC[$ToInstall.vc]).Segments[-1]
$VcDownloadFile = ($InstallPath + '\' + $VcFileName)

New-Item -ItemType Directory -Force -Path $InstallPath | Out-Null

Write-Output ("Downloading PHP " + $ToInstall.version + " $Arch...")
try {
    Invoke-WebRequest $ToInstall.url -OutFile $DownloadFile -ErrorAction Stop
} catch {
    throw ("Unable to download PHP from: " + $ToInstall.url)
}

Write-Output ("Downloading " + $ToInstall.vc + " redistributable...")
try {
    Invoke-WebRequest $VC[$ToInstall.vc] -OutFile $VcDownloadFile -ErrorAction Stop
} catch {
    throw ("Unable to download " + $ToInstall.vc + "  from: " + $VC[$ToInstall.vc])
}

Write-Output ("Installing " + $ToInstall.vc + " redistributable...")
& $VcDownloadFile /q /norestart
if(-not $?) {
    throw ("Unable to install " + $ToInstall.vc)
}
Remove-Item $VcDownloadFile -Force -ErrorAction SilentlyContinue | Out-Null

Write-Output ("Extracting PHP " + $ToInstall.version + " $Arch to: " + $InstallPath)
try {
    [IO.Compression.ZipFile]::ExtractToDirectory($DownloadFile, $InstallPath)
} catch {
    throw "Unable to extract PHP from ZIP"
}
Remove-Item $DownloadFile -Force -ErrorAction SilentlyContinue | Out-Null

Rename-Item "$InstallPath\php.ini-development" -NewName "php.ini" -ErrorAction Stop
$PhpIni = "$InstallPath\php.ini"

'date.timezone="UTC"' | Out-File $PhpIni -Append -Encoding utf8
'extension_dir=ext' | Out-File $PhpIni -Append -Encoding utf8
foreach ($extension in $Extensions) {
    "extension=php_$extension.dll" | Out-File $PhpIni -Append -Encoding utf8
}

try {
    $Reg = "Registry::HKLM\System\CurrentControlSet\Control\Session Manager\Environment"
    $OldPath = (Get-ItemProperty -Path $Reg -Name PATH).Path
    if (($OldPath -split ';') -notcontains $InstallPath){
        Set-ItemProperty -Path $Reg -Name PATH –Value ($OldPath + ’;’ + $InstallPath)
    }
} catch {
    Write-Warning "Unable to add PHP to path. You may have to add it manually: $InstallPath"
}
