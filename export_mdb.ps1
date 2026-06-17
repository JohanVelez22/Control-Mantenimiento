$db = "C:\ServBay\www\control-mantenimiento-equipos\Base1.mdb"
$conn = New-Object -ComObject ADODB.Connection
$conn.Open("Provider=Microsoft.ACE.OLEDB.12.0;Data Source=$db")

$exportDir = "C:\ServBay\www\control-mantenimiento-equipos\storage\app\migration"
if (-not (Test-Path $exportDir)) {
    New-Item -ItemType Directory -Force -Path $exportDir | Out-Null
}

$tables = @("Clientes", "Proveedores", "Stock")

foreach ($tableName in $tables) {
    Write-Host "Exporting $tableName..."
    
    $rs = New-Object -ComObject ADODB.Recordset
    $rs.Open("SELECT * FROM [$tableName]", $conn, 3, 3)
    
    $fields = @()
    for ($i = 0; $i -lt $rs.Fields.Count; $i++) {
        $fields += $rs.Fields.Item($i).Name
    }
    
    $data = @()
    while (-not $rs.EOF) {
        $row = New-Object PSObject
        foreach ($field in $fields) {
            $val = $rs.Fields.Item($field).Value
            # Handle DBNull
            if ([string]::IsNullOrEmpty($val) -or $val -is [System.DBNull]) {
                $val = ""
            }
            $row | Add-Member -MemberType NoteProperty -Name $field -Value $val
        }
        $data += $row
        $rs.MoveNext()
    }
    $rs.Close()
    
    $csvPath = Join-Path $exportDir "$tableName.csv"
    $data | Export-Csv -Path $csvPath -NoTypeInformation -Encoding UTF8
    Write-Host "Created $csvPath"
}

$conn.Close()
Write-Host "Export complete!"
