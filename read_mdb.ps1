$db = "C:\ServBay\www\control-mantenimiento-equipos\Base1.mdb"
$conn = New-Object -ComObject ADODB.Connection
try {
    $conn.Open("Provider=Microsoft.ACE.OLEDB.12.0;Data Source=$db")
} catch {
    try {
        $conn.Open("Provider=Microsoft.Jet.OLEDB.4.0;Data Source=$db")
    } catch {
        Write-Host "Failed to connect to the database. Need ACE or Jet."
        exit
    }
}

$schema = $conn.OpenSchema(20) # adSchemaTables
while (-not $schema.EOF) {
    if ($schema.Fields.Item("TABLE_TYPE").Value -eq "TABLE") {
        $tableName = $schema.Fields.Item("TABLE_NAME").Value
        Write-Host "Table: $tableName"
        
        # Get columns for this table
        $rs = New-Object -ComObject ADODB.Recordset
        $rs.Open("SELECT TOP 1 * FROM [$tableName]", $conn, 3, 3)
        $fields = @()
        for ($i = 0; $i -lt $rs.Fields.Count; $i++) {
            $fields += $rs.Fields.Item($i).Name
        }
        Write-Host "  Columns: $($fields -join ', ')"
        $rs.Close()
    }
    $schema.MoveNext()
}

$conn.Close()
