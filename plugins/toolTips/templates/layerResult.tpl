<div class="layerResult">
  <div>
    {$layerLabel}
  </div>
  <table>
    <tr>
{foreach key=attributeName item=attributeValue from=$layerResults}
    <th>{t}{$attributeName|capitalize}{/t}</th>
{/foreach}
    </tr>
    <tr>
{foreach key=attributeName item=attributeValue from=$layerResults}
    <td>{t}{$attributeValue}{/t}</td>
{/foreach}
    </tr>
  </table>
</div>