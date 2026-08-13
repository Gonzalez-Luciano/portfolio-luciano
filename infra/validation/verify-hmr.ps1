$ErrorActionPreference = 'Stop'

$pagePath = 'web/src/app/[locale]/page.tsx'
$original = [IO.File]::ReadAllText($pagePath)
$probe = 'Phase 3 live watcher probe'

if ($original.Contains($probe)) {
  throw 'The HMR probe must not already exist in the source page.'
}

try {
  [IO.File]::WriteAllText(
    $pagePath,
    $original.Replace(
      '<h1 className="text-3xl font-semibold tracking-tight">{t(''title'')}</h1>',
      '<h1 className="text-3xl font-semibold tracking-tight">{t(''title'')}</h1><p data-phase3-hmr="probe">' + $probe + '</p>'
    )
  )

  foreach ($attempt in 1..12) {
    $gateway = (Invoke-WebRequest -UseBasicParsing http://localhost:8000/es -TimeoutSec 8).Content
    $direct = docker compose exec -T web node -e "fetch('http://localhost:3000/es').then(async response => process.stdout.write(String((await response.text()).includes('$probe'))))"
    if ($gateway.Contains($probe) -and $direct -eq 'true') {
      break
    }
    if ($attempt -eq 12) {
      throw 'The live probe did not appear through both Caddy and direct Next.'
    }
    Start-Sleep -Seconds 1
  }
} finally {
  [IO.File]::WriteAllText($pagePath, $original)
}

foreach ($attempt in 1..12) {
  $gateway = (Invoke-WebRequest -UseBasicParsing http://localhost:8000/es -TimeoutSec 8).Content
  $direct = docker compose exec -T web node -e "fetch('http://localhost:3000/es').then(async response => process.stdout.write(String(!(await response.text()).includes('$probe'))))"
  if (-not $gateway.Contains($probe) -and $direct -eq 'true') {
    Write-Output 'HMR edit and revert propagated through Caddy and direct Next.'
    exit 0
  }
  if ($attempt -eq 12) {
    throw 'The probe removal did not propagate through both Caddy and direct Next.'
  }
  Start-Sleep -Seconds 1
}
