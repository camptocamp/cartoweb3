/*
 * manage_servers_js.phtml have hugely been modified by CartoWeb dev team.
 * The only kept javascipt function is processCommand. It had been simplified.
 *
////////////////////////////////////////////////////////////////////////////////
// MapBrowser application                                                     //
//                                                                            //
// @project     MapLab                                                        //
// @purpose     This is the dbase database management utility page.           //
// @author      William A. Bronsema, C.E.T. (bronsema@dmsolutions.ca)         //
// @copyright                                                                 //
// <b>Copyright (c) 2002, DM Solutions Group Inc.</b>                         //
// Permission is hereby granted, free of charge, to any person obtaining a    //
// copy of this software and associated documentation files(the "Software"),  //
// to deal in the Software without restriction, including without limitation  //
// the rights to use, copy, modify, merge, publish, distribute, sublicense,   //
// and/or sell copies of the Software, and to permit persons to whom the      //
// Software is furnished to do so, subject to the following conditions:       //
//                                                                            //
// The above copyright notice and this permission notice shall be included    //
// in all copies or substantial portions of the Software.                     //
//                                                                            //
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR //
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,   //
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.  IN NO EVENT SHALL   //
// THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER //
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING    //
// FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER        //
// DEALINGS IN THE SOFTWARE.                                                  //
////////////////////////////////////////////////////////////////////////////////

 *
 * Submits form 
 */
function doSubmit() {
  xShow(xGetElementById('loadbarDiv'));
  myform.submit();
}

/**
 * Processes individual command
 */
function processCommand(command) {
    if (command == 'ADD' || command == 'UPDATE') {
        // URL must be set
        if (xGetElementById('url').value == '') {
            alert('Please enter a value for the URL field');
            return;
        }
    }
    if (command != 'ADD' && xGetElementById('selectedServer').value == "") {
        alert ('Please select a server');
        return;
    }
    xGetElementById('command').value = command;
    doSubmit();

    return;
}
