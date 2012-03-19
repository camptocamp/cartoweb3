/* Copyright 2005 Camptocamp SA. 
   Licensed under the GPL (www.gnu.org/copyleft/gpl.html) */


Logger = {    
    /* Avaiable log levels:
     * 0 = none
     * 1 = headers
     * 2 = errors
     * 3 = warns
     * 4 = traces
     * 5 = notes
     * 6 = confirms
     */
    level: 5,
    
    send: function(msg) {
        if (this.level > 0 && typeof(jsTrace) != 'undefined' ) {
            jsTrace.send(msg);
        }
    },
    
    header: function(msg) {
        if (this.level >= 1) {
            this.send('<br /><font size="medium"><strong>' + msg + '</strong></font>');
        }
    },
    error: function(msg) {
        if (this.level >= 2) {
            this.send('<font color="red">' + 'Error: ' + msg + '</font>');
        }
    },    
    warn: function(msg) {
        if (this.level >= 3) {
            this.send('<font color="orange">' + 'Warning: ' + msg + '</font>');
        }
    },
    trace: function(msg) {
        if (this.level >= 4) {
            this.send('<font color="lightgray">' + msg + '</font>');
        }
    },
    note: function(msg) {
        if (this.level >= 5) {
            this.send('<font color="gray">' + msg + '</font>');
        }
    },
    confirm: function(msg) {
        if (this.level >= 6) {
            this.send('<font color="lightgreen">' + 'OK: ' + msg + '</font>');
        }
    }
}
