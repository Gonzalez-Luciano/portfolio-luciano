$ErrorActionPreference = 'Stop'

# Vite reads index.html from disk on every development request, so a probe
# written into it proves the Windows bind mount is being watched and served
# through both the Caddy gateway and the direct Vite port.
$pagePath = 'web/index.html'
$original = [IO.File]::ReadAllText($pagePath)
$probe = 'Vite live watcher probe'

if ($original.Contains($probe)) {
  throw 'The HMR probe must not already exist in the source page.'
}

try {
  [IO.File]::WriteAllText(
    $pagePath,
    $original.Replace('<div id="root"></div>', '<div id="root"></div><!-- ' + $probe + ' -->')
  )

  foreach ($attempt in 1..12) {
    $gateway = (Invoke-WebRequest -UseBasicParsing http://localhost:8000/es -TimeoutSec 8).Content
    $direct = docker compose exec -T web node -e "fetch('http://localhost:5173/').then(async response => process.stdout.write(String((await response.text()).includes('$probe'))))"
    if ($gateway.Contains($probe) -and $direct -eq 'true') {
      break
    }
    if ($attempt -eq 12) {
      throw 'The live probe did not appear through both Caddy and direct Vite.'
    }
    Start-Sleep -Seconds 1
  }
} finally {
  [IO.File]::WriteAllText($pagePath, $original)
}

foreach ($attempt in 1..12) {
  $gateway = (Invoke-WebRequest -UseBasicParsing http://localhost:8000/es -TimeoutSec 8).Content
  $direct = docker compose exec -T web node -e "fetch('http://localhost:5173/').then(async response => process.stdout.write(String(!(await response.text()).includes('$probe'))))"
  if (-not $gateway.Contains($probe) -and $direct -eq 'true') {
    Write-Output 'HMR edit and revert propagated through Caddy and direct Vite.'
    exit 0
  }
  if ($attempt -eq 12) {
    throw 'The probe removal did not propagate through both Caddy and direct Vite.'
  }
  Start-Sleep -Seconds 1
}
