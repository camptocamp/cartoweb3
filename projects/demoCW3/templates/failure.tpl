<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <link rel="stylesheet" type="text/css" href="{r type=css}cartoweb.css{/r}" title="stylesheet" />
  <link rel="stylesheet" type="text/css" href="{r type=css}failure.css{/r}" title="stylesheet" />
  <title>Failure</title>
</head>
<body>
  <div id="logo"><img src="{r type=gfx/layout}logoc2c.gif{/r}" alt="camptocamp" style="border:0"/><br /><br /><br /></div>
  <table id="user_failure" align="center">
    <tr><td id="title" colspan="2">
      Failure
    </td></tr>
    <tr><td  colspan="2">
      <pre style="border:0">Class : {$exception_class}<br />Message : {$failure_message}</pre>
    </td></tr>
  </table>
  <div style="text-align:right"><br /><br /><a href="{$selfUrl}?reset_session">Back to initial map</a></div>
</body>
</html>
