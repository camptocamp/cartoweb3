<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <title>{t}Print{/t}</title>
</head>

<body>

<table>
<tr>
<td><img src="{$exporthtml_keymap}" /></td>
<td rowspan="2"><img src="{$exporthtml_mainmap}" /></td>
</tr>
<tr>
<td><table>
{foreach from=$exporthtml_legends item=legend}
<tr><td>{$legend.label}</td></tr>
{/foreach}
</table></td>
</tr>
</table>

</body>
</html>
