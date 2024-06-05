Add-Type -AssemblyName System.IO.Compression.FileSystem
$sourceDir = "com_usersexport"
$zipFile = "com_usersexport.zip"
$fullZipPath = Join-Path -Path (Get-Location) -ChildPath $zipFile
Write-Host "Source directory: $sourceDir"
Write-Host "Destination ZIP file: $fullZipPath"

if (Test-Path $fullZipPath)
{
    Remove-Item $fullZipPath -Force
    Write-Host "Existing ZIP file removed."
    if (Test-Path $fullZipPath)
    {
        Write-Host "Failed to remove existing ZIP file, please check file permissions or if it is in use."
        exit
    }
}
else
{
    Write-Host "No existing ZIP file to remove."
}

try
{
    $zipArchive = [System.IO.Compression.ZipFile]::Open($fullZipPath, [System.IO.Compression.ZipArchiveMode]::Create)
    Write-Host "New ZIP archive created."

    $files = Get-ChildItem -Path $sourceDir -Recurse | Where-Object { -not $_.PSIsContainer -and $_.FullName -notmatch '\\node_modules\\' }
    $totalFiles = $files.Count
    Write-Host "$totalFiles files found for compression."

    $currentFile = 0
    foreach ($file in $files)
    {
        $currentFile++
        $relativePath = $file.FullName.Substring((Get-Location).Path.Length + 1)
        Write-Host $file.FullName
        $null = [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zipArchive, $file.FullName, $relativePath, [System.IO.Compression.CompressionLevel]::Optimal)
    }

}
catch
{
    Write-Host "An error occurred: $_"
}
finally
{
    if ($null -ne $zipArchive)
    {
        $zipArchive.Dispose()
        Write-Host "ZIP archive closed."
    }
}

Write-Host "Compression complete. '$zipFile' has been created."
