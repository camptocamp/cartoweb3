<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Failure</title>
<style type="text/css">
{literal}
html {
  background: #ddd;
  font-style: italic;
}
.hint {
  position: absolute;
  top: 0em;
  right: 2em;
}
.back {
  position: absolute;
  top:2em;
  right: 2em;
}
{/literal}
</style>
</head>
<body>

<h1>Failure</h1>

<pre>
class:   {$exception_class}
message:   {$failure_message}
</pre>

<p class="back">
<a href="{$selfUrl}?reset_session">Back to initial map</a>.
</p>

<p class="hint">
Hint: you should customize this template in your project.
</p>

</body>
</html>
