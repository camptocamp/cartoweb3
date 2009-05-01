<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style>
{literal}
body {
  font-family: helvetica;
  margin: 40px 30px 40px 30px;
}
table.query {
  border: 1px solid black;
  /*width: 100%;*/
  border-collapse: collapse;
}
table.query td, table.query th {
  text-align: center;
  border: 1px solid #aaa;
}

table.query th {
  background-color: #aaa;
  color: white;
}
table.map {
  width: 90%;
  border:0;
}
table.map td {
  /*border: 1px solid black;*/
}
table.map td.title {
  font-size: 2em;
}
{/literal}
</style>
</head>
<body>
<table class="map">
  <tr><td colspan="3"><img src="{$mainmapUrl}" width="{$mainmapWidth}" height="{$mainmapHeight}" style="border: 1px solid black;" /></tr>
  <tr><td colspan="3">&nbsp;</td></tr>
  <tr>
    <td class="title">{$title}</td>
    <td style="text-align:right"><img src="{$keymapUrl}" width="{$keymapWidth}" height="{$keymapHeight}" />
    <td style="text-align:right"><img src="{$scalebarUrl}" width="{$scalebarWidth}" height="{$scalebarHeight}" /></td>
  </tr>
</table>
{if $queryResult}
<h2 style="page-break-before: always">{t}Query{/t}</h2>
{foreach from=$queryResult item=table}
  {if $table->numRows > 0}
    <h3>{t}{$table->tableTitle}{/t}</h3>
    <table class="query">
      <tr>
      {foreach from=$table->columnTitles item=colTitle}
        <th>{t}{$colTitle}{/t}</th>
      {/foreach}
      </tr>
      {foreach from=$table->rows item=row}
      <tr>
        {foreach from=$row->cells item=cell}
          <td>{$cell}</td>
        {/foreach}
      </tr>
      {/foreach}
    </table>
  {/if}
{/foreach}
{/if}
<script type="text/php">
$title = "{$title|utf8_encode}";
{literal}
if (isset($pdf)) {

    $font = Font_Metrics::get_font("helvetica", "bold");
    $w = $pdf->get_width();
    $h = $pdf->get_height();
    $size = 10;
    $color = array(0,0,0);
    $height = Font_Metrics::get_font_height($font, $size);
    
    // header
    $width = Font_Metrics::get_text_width($title, $font, $size);
    $pdf->page_text(($w - $width) / 2, 18, $title, $font, $size, $color);

    // footer
    $width = Font_Metrics::get_text_width("Page 1 / 2", $font, $size); //FIXME : i18n
    $pdf->page_text($w - $width - 18, $h - $height - 18, "Page {PAGE_NUM} / {PAGE_COUNT}", $font, $size, $color);
}
{/literal}
</script>
</body></html>
