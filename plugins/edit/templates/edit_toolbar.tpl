<div id="menu1" class="menu">
{if $edit_allowed}
<table>
  <tr>
    <td><a href="#" onclick="mainmap.getDisplay('map').setTool('draw.poly');mainmap.action='edit';"><img src="{r plugin=edit type=gfx}edit_poly.gif" alt="edit_poly" title="{t}edit_poly{/t}" /></a></td>
  </tr>
  <tr>
    <td><a href="#" onclick="mainmap.getDisplay('map').setTool('move');mainmap.action='edit';"><img src="{r plugin=edit type=gfx}edit_move.gif{/r}" alt="edit_move" title="{t}edit_move{/t}" /></a><br /></td>
  </tr>
  <tr>
    <td><a href="#" onclick="mainmap.getDisplay('map').setTool('delete.vertex');mainmap.action='edit';"><img src="{r plugin=edit type=gfx}edit_del_vertex.gif{/r}" alt="edit_del_vertex" title="{t}edit_del_vertex{/t}" /></a></td>
  </tr>
  <tr>
    </td>
    <td><a href="#" onclick="mainmap.getDisplay('map').setTool('add.vertex');mainmap.action='edit';"><img src="{r plugin=edit type=gfx}edit_add_vertex.gif{/r}" alt="edit_add_vertex" title="{t}edit_add_vertex{/t}" /></a></td>
  </tr>
  <tr>
    <td><a href="#" onclick="mainmap.getDisplay('map').setTool('delete.feature');mainmap.action='edit';"><img src="{r plugin=edit type=gfx}edit_del_feature.gif{/r}" alt="edit_del_feature" title="{t}edit_del_feature{/t}" /></a>
    </td>
  </tr>
</table>
{/if}
</div>
