/*
 * EventManager.js
 * by Keith Gaughan
 *
 * This allows event handlers to be registered unobtrusively, and cleans
 * them up on unload to prevent memory leaks.
 *
 * Copyright (c) Keith Gaughan, 2005.
 *
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Common Public License v1.0
 * (CPL) which accompanies this distribution, and is available at
 * http://www.opensource.org/licenses/cpl.php
 *
 * This software is covered by a modified version of the Common Public License
 * (CPL), where Keith Gaughan is the Agreement Steward, and the licensing
 * agreement is covered by the laws of the Republic of Ireland.
 */

// For implementations that don't include the push() methods for arrays.
if (!Array.prototype.push)
{
    Array.prototype.push = function(elem)
    {
        this[this.length] = elem;
    }
}

var EventManager =
{
    _registry: null,

    Initialise: function()
    {
        if (this._registry == null)
        {
            this._registry = [];

            // Register the cleanup handler on page unload.
            EventManager.Add(window, "unload", this.CleanUp);
        }
    },

    /**
     * Registers an event and handler with the manager.
     *
     * @param  obj         Object handler will be attached to.
     * @param  type        Name of event handler responds to.
     * @param  fn          Handler function.
     * @param  useCapture  Use event capture. False by default.
     *                     If you don't understand this, ignore it.
     *
     * @return True if handler registered, else false.
     */
    Add: function(obj, type, fn, useCapture)
    {
        this.Initialise();

        // If a string was passed in, it's an id.
        if (typeof obj == "string")
            obj = document.getElementById(obj);
        if (obj == null || fn == null)
            return false;

        // Mozilla/W3C listeners?
        if (obj.addEventListener)
        {
            obj.addEventListener(type, fn, useCapture);
            this._registry.push({obj: obj, type: type, fn: fn, useCapture: useCapture});
            return true;
        }

        // IE-style listeners?
        if (obj.attachEvent && obj.attachEvent("on" + type, fn))
        {
            this._registry.push({obj: obj, type: type, fn: fn, useCapture: false});
            return true;
        }

        return false;
    },

    /**
     * Cleans up all the registered event handlers.
     */
    CleanUp: function()
    {
        for (var i = 0; i < EventManager._registry.length; i++)
        {
            with (EventManager._registry[i])
            {
                // Mozilla/W3C listeners?
                if (obj.removeEventListener)
                    obj.removeEventListener(type, fn, useCapture);
                // IE-style listeners?
                else if (obj.detachEvent)
                    obj.detachEvent("on" + type, fn);
            }
        }

        // Kill off the registry itself to get rid of the last remaining
        // references.
        EventManager._registry = null;
    }
};