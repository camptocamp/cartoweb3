{foreach from=$tables item=group}
<script type='text/javascript' src='{r type=js plugin=tables}fenster.js{/r}'></script>
<script type='text/javascript' src='{r type=js plugin=tables}x_drag.js{/r}'></script>
<link rel="stylesheet" type="text/css" href="{r type=css plugin=tables}fenster.css{/r}" />
<script language="javascript">
</script>
<div id='fen1' class='fenster'>
  <div id='fenClBtn1' class='fenClBtn' title='{t}Click to Close{/t}'><img src="{r type=gfx plugin=tables}clBtn.png{/r}" /></div>
  <div id='fenBar1' class='fenBar' title='{t}Drag to Move{/t}'>{$group->groupTitle}</div>
<div id='fenContent' class='fenContent'>
  <center>
  <a name="top"></a>
  <table class="cw3table" width="70%" cellpadding="0" cellspacing="0">
  <caption>{t}Resultats de la requete{/t}</caption>
    <tr>
      <th>{t}Zonage{/t}</th>
      <th>{t}Nombre de resultats{/t}</th>
    </tr>
{foreach from=$group->tables item=table}
  {if $table->numRows > 0}
  <tr bgcolor="{cycle values="#efefef,#dedede"}">
    <td><a href="#{$table->tableId}">{$table->tableTitle}</a></td>
    <td>{$table->numRows}</td>
  </tr>
  {/if}
{/foreach}
  </table>
  </center>
  <br /><br />
{foreach from=$group->tables item=table}
{if $table->numRows > 0}
  <center>
  <a name="{$table->tableId}"></a>
<table cellpadding="0" cellspacing="0" border="0" class="cw3table" width="70%">
  <caption>{$table->tableTitle}</caption>
  <tr>
    {foreach from=$table->columnTitles item=column}
      <th>{$column}</th>               
    {/foreach}
  </tr>
  {foreach from=$table->rows item=row}
    <tr bgcolor="{cycle values="#efefef,#dedede"}">
      {foreach from=$row->cells item=value}
        <td>{$value}</td>
      {/foreach}
    </tr>
  {/foreach}
</table>
  <a href="#top">{t}Retour{/t}</a>
  <br /><br /><br />
{if $exportcsv_active|default:''}
<div class="exportlink"><a href="{$exportcsv_url}project={$project}&amp;exportcsv_groupid={$group->groupId}&amp;exportcsv_tableid={$table->tableId}">{t}Download CSV{/t}</a></div>
{/if}
  </center>
{/if}
{/foreach}
</div>
{*<div  class="fenDetail">
  <iframe id="fenDetail" src="../blank.php" frameborder="0" scrolling="auto" width="500" height="200"></iframe>
  </div>*}
{/foreach}
</div>
