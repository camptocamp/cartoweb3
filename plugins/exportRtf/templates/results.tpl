[[[counter start=1 skip=1 name=idcount assign=idcount]]]
[[[foreach from=$tables item=group]]]
{\b\fs40\insrsid5910821\charrsid13770154  [[[$group->groupTitle]]] \par }
\par
[[[foreach from=$group->tables item=table]]]
[[[if $table->numRows > 0]]]
\pard\plain
\par
{\b\highlight16\insrsid71092\charrsid13518055 [[[$table->tableTitle]]] \par }
[[[*-------------------header-------------------------- *]]]
{\rtf1
\trowd\trautofit1
\intbl
[[[*----th style here---*]]]
[[[if !$table->noRowId]]]\cellx1[[[/if]]]
[[[counter start=1 skip=1 name=cellcount assign=cellcount]]]
[[[foreach from=$table->columnTitles item=column]]]\cellx[[[counter name=cellcount print=true ]]]
[[[/foreach]]]
{[[[if !$table->noRowId]]][[[t]]]Id[[[/t]]]\cell [[[/if]]][[[foreach from=$table->columnTitles item=column]]][[[$column]]]\cell [[[/foreach]]]}
[[[*closing th*]]]
{\trowd\trautofit1
\intbl
[[[if !$table->noRowId]]]
\cellx1[[[/if]]]
[[[counter start=1 skip=1 name=cellcount2 assign=cellcount2]]]
[[[foreach from=$table->columnTitles item=column]]]
\cellx[[[counter name=cellcount2 print=true ]]]
[[[/foreach]]]
\row }
[[[*-------------------rows-------------------------- *]]]
[[[foreach from=$table->rows item=row]]]
\trowd\trautofit1
\intbl
[[[*----cells style here---*]]]
[[[if !$table->noRowId]]]
\cellx1[[[/if]]]
[[[counter start=1 skip=1 name=cellcount3 assign=cellcount3]]]
[[[foreach from=$row->cells item=value]]]
\cellx[[[counter name=cellcount3 print=true ]]][[[/foreach]]]
{[[[if !$table->noRowId]]][[[$row->rowId]]]\cell[[[/if]]]
[[[foreach from=$row->cells item=value]]] [[[$value]]]\cell[[[/foreach]]]}
{\trowd\trautofit1
\intbl
[[[if !$table->noRowId]]]\cellx1
[[[/if]]]
[[[counter start=1 skip=1 name=cellcount4 assign=cellcount4]]]
[[[foreach from=$row->cells item=value]]]
\cellx[[[counter name=cellcount4 print=true]]] 
[[[/foreach]]]
\row }
[[[/foreach]]]
}
\pard\plain
\par
[[[/if]]]
[[[/foreach]]]

[[[/foreach]]]
