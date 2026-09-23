$f = 'html/admin-dashboard.php'
$c = Get-Content $f -Raw
$c = $c -replace '(?s)<<<<<<< Updated upstream.*?=======', ''
$c = $c -replace '>>>>>>> Stashed changes\r?\n?', ''
$c = $c.Replace('$crit = $idx + 1;', '$crit = $idx;')
$functions = @"
        function comparaTrabalhos(`$a, `$b, `$criteriosDesempate) {
          if (`$a['nota_final'] > `$b['nota_final']) return -1;
          if (`$a['nota_final'] < `$b['nota_final']) return 1;
          foreach (`$criteriosDesempate as `$crit) {
            `$notaA = `$a['criterios'][`$crit] ?? 0;
            `$notaB = `$b['criterios'][`$crit] ?? 0;
            if (`$notaA > `$notaB) return -1;
            if (`$notaA < `$notaB) return 1;
          }
          if (`$a['total_trabalhos'] > `$b['total_trabalhos']) return -1;
          if (`$a['total_trabalhos'] < `$b['total_trabalhos']) return 1;
          if (`$a['IDEB'] > `$b['IDEB']) return -1;
          if (`$a['IDEB'] < `$b['IDEB']) return 1;
          if (`$a['focalizada'] && !`$b['focalizada']) return -1;
          if (!`$a['focalizada'] && `$b['focalizada']) return 1;
          if (`$a['ide'] && !`$b['ide']) return -1;
          if (!`$a['ide'] && `$b['ide']) return 1;
          return 0;
        }

        function criterioDesempateUsado(`$a, `$b, `$criteriosDesempate) {
          foreach (`$criteriosDesempate as `$index => `$crit) {
            `$notaA = `$a['criterios'][`$crit] ?? 0;
            `$notaB = `$b['criterios'][`$crit] ?? 0;
            if (`$notaA != `$notaB) { return ['indice' => `$index + 1, 'criterio' => "Critério #" . (`$index + 1)]; }
          }
          if (`$a['focalizada'] !== `$b['focalizada']) { return ['indice' => 'Focalizada', 'criterio' => 'Escola focalizada']; }
          if (`$a['ide'] !== `$b['ide']) { return ['indice' => 'IDE', 'criterio' => 'Escola com IDE']; }
          return null;
        }

"@
$c = $c.Replace("        usort(`$dados, function (`$a, `$b) use (`$criteriosDesempate) {", $functions + "        usort(`$dados, function (`$a, `$b) use (`$criteriosDesempate) {")
[System.IO.File]::WriteAllText($f, $c, (New-Object System.Text.UTF8Encoding($false)))
