/*
 * print_r of javascript variable, object, etc...
 * @param mixed input can be anything (variable, object, element, ...)
 * @param int _max_level set the level of recursivity, default is 5
 * @param bool show_function display function objects
 * @param bool show_number display number objects
 * @param bool showinpopup display the result in a dynamicaly added textarea
 * @return string output
 */
var print_r_recurs_level;
var print_r_max_level;
function print_r(input, _max_level, show_function, show_number, showinpopup) {
  // reset
  print_r_recurs_level = 0;
  // default
  print_r_max_level = 5;

  if (_max_level){
    print_r_max_level = _max_level;
  }
  var inline = true;;
  if (showinpopup) {
    inline = false;
  }
  var show_function = show_function ? show_function : false;
  var show_number = show_number ? show_number : false
  // call main recursive function
  var output = _print_r(input, show_function, show_number, _max_level);

  if (inline) {
    return output;
  } else {
    // chow result in floating textarea
    print_r_open(output);
    return false;
  }

  
}
function _print_r(input, show_function, show_number, _max_level)
{
    print_r_recurs_level++;
    
    var indent = '    ';
    var paren_indent = '';

    switch(typeof(input)) {
        case 'boolean':
            var output = (input ? 'true' : 'false') + "\n";
            break;
        case 'object':
            if ( input===null ) {
                var output = "null\n";
                break;
            }
            var output = ((input.reverse) ? 'Array' : 'Object') + " (\n";
            for(var i in input) {
              // ignore function
              if (i != 'selectionStart' && i != 'selectionEnd' && i != 'domConfig' && i != 'parentNode' && i != 'ownerDocument' && i != 'form' && i != 'offsetParent' && i != 'firstChild' && i != 'lastChild' && i != 'ownerElement'  && i != 'childNodes' && i != 'baseURI' && i != 'localName' && i != 'firstChild' && i != 'style' && i != 'previousSibling' && i != 'nextSibling'){
                // selectionStart, selectionEnd and domConfig cause inner error
                try {
                  input[i];
                } catch (e) {
                  alert(i + ' ' + e);
                }
                // ignore function and number type
                if (input[i]){
                  var doit = true;
                  switch (typeof(input[i])) {
                    case 'function' :
                      if (!show_function) {
                        doit = false;
                      }
                    break;
                    case 'number' :
                      if (!show_number) {
                        doit = false;
                      }
                    break;
                  }
                  if (doit == true) {
                    if (print_r_recurs_level < print_r_max_level){
                      output += indent + "[" + i + "] => " + _print_r(input[i], show_function, show_number);
                      print_r_recurs_level--;
                    } else {
                      output += indent + "[" + i + "] => Object\n";
                    }
                  }
                }
              }
            }
            output += paren_indent + ")\n";
            break;
        case 'number':
        case 'string':
        default:
            var output = "" + input  + "\n";
    }
    return output;
}
function print_r_open(output) {
  var div = document.getElementById('print_r');
    if (typeof(div) != 'undefined') {
        var newDiv = document.createElement("div");
        var newTarea = document.createElement("textarea");
        newTarea.style.width = '800px';
        newTarea.style.height = '600px';
        newTarea.setAttribute("id", "print_r_tarea");
        newTarea.innerHTML = output;
        newDiv.innerHTML = '<a href="javascript:print_r_close();">close</a>';
        newDiv.appendChild(newTarea);
        newDiv.style.position = 'absolute';
        newDiv.style.top = '10px';
        newDiv.style.left = '10px';
        newDiv.style.zIndex = '1000';
        newDiv.setAttribute("id", "print_r");
        document.body.appendChild(newDiv);        
    } else {
        var tarea = document.getElementById('print_r_tarea');
        tarea.innerHTML = output;
        div.style.display = 'block';
    }
}
function print_r_close() {
    var div = document.getElementById('print_r');
    div.style.display = 'none';
}