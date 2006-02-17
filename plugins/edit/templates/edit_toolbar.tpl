
<div id='menu1' class='menu'>
{if $edit_allowed|default:''}
<table>
  <tr>
    <td><a href='#' onclick="mainmap.getDisplay('map').setTool('draw.poly');mainmap.action='edit';"><img src="edit/gfx/edit_poly.gif" alt="edit_poly" title="edit_poly"></a></td>
  </tr>
  <tr>
    <td><a href='#' onclick="mainmap.getDisplay('map').setTool('move');mainmap.action='edit';"><img src="edit/gfx/edit_move.gif" alt="edit_move" title="edit_move"></a><br /></td>
  </tr>
  <tr>
    <td><a href='#' onclick="mainmap.getDisplay('map').setTool('delete.vertex');mainmap.action='edit';"><img src="edit/gfx/edit_del_vertex.gif" alt="edit_del_vertex" title="edit_del_vertex"></a></td>
  </tr>
  <tr>
    </td>
    <td><a href='#' onclick="mainmap.getDisplay('map').setTool('add.vertex');mainmap.action='edit';"><img src="edit/gfx/edit_add_vertex.gif" alt="edit_add_vertex" title="edit_add_vertex"></a></td>
  </tr>
  <tr>
    <td><a href='#' onclick="mainmap.getDisplay('map').setTool('delete.feature');mainmap.action='edit';"><img src="edit/gfx/edit_del_feature.gif" alt="edit_del_feature" title="edit_del_feature"></a>
    </td>
  </tr>
</table>
{/if}
</div>