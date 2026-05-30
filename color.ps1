Add-Type -AssemblyName System.Drawing
$img = [System.Drawing.Bitmap]::FromFile("c:\Data\Work\My Work\the web artist\images\twa-logo.png")
$colors = @{}
for ($x = 0; $x -lt $img.Width; $x += 5) {
    for ($y = 0; $y -lt $img.Height; $y += 5) {
        $c = $img.GetPixel($x, $y)
        if ($c.A -gt 100 -and -not ($c.R -gt 240 -and $c.G -gt 240 -and $c.B -gt 240) -and -not ($c.R -lt 15 -and $c.G -lt 15 -and $c.B -lt 15)) {
            $hex = "#{0:x2}{1:x2}{2:x2}" -f $c.R, $c.G, $c.B
            if ($colors.ContainsKey($hex)) {
                $colors[$hex]++
            } else {
                $colors[$hex] = 1
            }
        }
    }
}
$colors.GetEnumerator() | Sort-Object Value -Descending | Select-Object -First 20 | Format-Table -AutoSize
