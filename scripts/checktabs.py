#!/usr/bin/env python

#
# Script to print files which contain tabs.
#

# Paths containing a following string will be ignored
ignore_paths = ['include/log4php', 'include/smarty', 'templates_c/', 'include/pear']
# Only files with the following extensions will be processed
check_extensions = ['php', 'ini', 'py', 'wsdl', 'dist', 'tpl', 'txt']


import sys, os, os.path

failed = False

def check_tabs(filename):
    global failed
    content = open(filename).read()
    if '\t' in content:
        failed = True
        print "File %s contains tabs" % filename

def is_ignored(filename):
    for ignore_path in ignore_paths:
        if ignore_path in filename:
            return True

if __name__ == "__main__":

    root = '../..'
    rootpath = os.path.join(os.path.abspath(__file__), root)
    rootpath = os.path.normpath(rootpath)

    for root, dirs, files in os.walk(rootpath):
        for name in files:
            filename = os.path.join(root, name)
            #print "File", filename

            if is_ignored(filename):
                continue

            extension = os.path.splitext(filename)[1]
            extension  = extension[1:]
            if extension in check_extensions:
                check_tabs(filename)

    sys.exit(failed and 1 or 0)
