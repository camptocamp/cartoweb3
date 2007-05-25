<div class="layerResult">
  <div class="layerLabel {$layerId}">
    {$layerLabel}
  </div>

  <div class="attribute">
    <div class="value" style="font-weight: bold">{t}Broadcasting: Station{/t} {$stationName}</div>
    <div class="value">{t}Powercode{/t}: {$stationPower}</div>
  </div>

  <table class="horizontal">
  <tr>
    <th>{t}RadioService{/t}</th>
    <th>{t}RadioProgram{/t}</th>
    <th>{t}RadioFreqChan{/t}</th>
  </tr>

{section name=channel loop=$channels}
  <tr>
    <td class="value">{$channels[channel].service}</td>
    <td>{$channels[channel].program}</td>
    <td>{$channels[channel].freqchan}</td>
  </tr>
{sectionelse}
  <tr>
    <td colspan="3"><i>{t}No channel for this station{/t}</i></td>
  </tr>
{/section}
  </table>
</div>