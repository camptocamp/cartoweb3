#!/usr/bin/python

#
# This scripts manages the setup of the initial files for cartoweb.
#  It either makes symbolic links or copy files depending on the
#  platform support.
#
# TODO: tell the user what aliases/proxypath she has to use when
#  no symlinks allowed

import sys, os, os.path, glob, shutil
from stat import *
from os.path import join

dummy = False
use_sudo = False

if sys.version < 0x20304f5:
    print "Too old python version, please use at least 2.3.4"
    sys.exit(-1)

def give_httpd_write_access(directory):
    #print "giving write access to ", directory

    if use_sudo:
        print "sudo not yet implemented, please fix. Exitting"
        sys.exit(-1)

    os.chmod(directory, S_IRUSR | S_IWUSR | S_IXUSR | S_IRWXG | S_IRGRP \
             | S_IWGRP | S_IXGRP | S_IRWXO | S_IROTH | S_IWOTH | S_IXOTH)
    

def link_or_copy(source, dest):
    # TODO: copy on non link supporting oses

    #print "linking: source:%s target: %s" % (source, dest)
    if not dummy:
        if os.path.islink(dest):
            os.unlink(dest)
        elif os.path.isfile(dest):
            print "error, target %s exists and is a file" % dest
            sys.exit(-1)
            
        os.symlink(source, dest)


def link_or_copy_glob(base_dir, source_dir):

    for path in glob.glob(source_dir):
        if not path_ok(path):
            continue

        last_path = path.split(os.sep)[-2]
        #print "last path", last_path
        link_or_copy(path, join(base_dir, last_path))


def setup_htdocs(htdocs_directory):
    if not os.path.isdir(htdocs_directory):
        print "project has not htdocs directory for: ", htdocs_directory
        return
    link_or_copy_glob(htdocs_directory , join(htdocs_directory,
                                              '../*plugins/*/htdocs'))
    link_or_copy_glob(htdocs_directory , join(htdocs_directory,
                                              '../*projects/*/htdocs'))
        
    # TODO: if no symlinks, show alias
    link_or_copy('../www-data/images', join(htdocs_directory, 'images'))
    link_or_copy('../www-data/icons', join(htdocs_directory, 'icons'))
    link_or_copy('../www-data/pdf', join(htdocs_directory, 'pdf'))
    

def path_ok(path):
    lastpath = os.path.split(path)[-1]
    return not lastpath in ['CVS']


def get_projects(rootpath):
    dirs = os.listdir(join(rootpath, 'projects'))
    return [d for d in dirs if path_ok(d)]

def setup_icons(rootpath, directory, project=None):

    if project == None:
        project = directory.split(os.sep)[-1]

    icon_paths = glob.glob(join(directory, 'server_conf/*'))
    icon_paths = [p for p in icon_paths if os.path.isdir(p) and path_ok(p)]
    #print directory
    for p in icon_paths:
        #print p
        mapid = p.split(os.sep)[-1]
        dest_static = join(rootpath, 'htdocs/gfx/icons/',project)
        if not os.path.isdir(dest_static):
            os.makedirs(dest_static)
        source_static = join(p, "icons")
        link_or_copy(source_static, join(rootpath, join(dest_static, mapid)))

    #print icon_paths
    
    
def setup_files():

    rootpath = os.path.join(os.path.abspath(__file__), '../..')
    rootpath = os.path.normpath(rootpath)

    projects_path = [join(rootpath, 'projects', p) \
                     for p in get_projects(rootpath)]

    # the main htdocs has to be processed last so if we copy files,
    #  all projects htdocs will be copied
    projects_path.append(rootpath)

    for d in projects_path:
        setup_htdocs(join(d, 'htdocs'))

    setup_icons(rootpath, projects_path[-1], "default")
    for d in projects_path[:-1]:
        setup_icons(rootpath, d)

    link_or_copy('../po', join(rootpath, 'htdocs/po'))
    
    sys.exit(0)

def usage():
    print """options:
  setup_files          makes symbolic links (or copy) from htdocs to plugins and projects
  
    """
    sys.exit(-1)

if __name__ == "__main__":


    if "setup_files" in sys.argv:
        setup_files()
    else:
        usage()
