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
    link_or_copy_glob(htdocs_directory , join(htdocs_directory,
                                              '../*plugins/*/htdocs'))
    link_or_copy_glob(htdocs_directory , join(htdocs_directory,
                                              '../*projects/*/htdocs'))
        
    # TODO: if no symlinks, show alias
    link_or_copy('../www-data/images', join(htdocs_directory, 'images'))
    

def setup_icons(directory):

    # creates www-data icons for each mapid
    icon_dirs = glob.glob(join(directory, '../../server_conf/*/icons'))
    icon_dirs.extend(glob.glob(join(directory, 'server_conf/*/icons')))

    for icon_dir in icon_dirs:
        dirs = icon_dir.split(os.sep)
        map_id = dirs[-2]
        www_data_dir = join(directory, 'www-data/icons', map_id)

        if not os.path.isdir(www_data_dir):
            os.makedirs(www_data_dir)

        give_httpd_write_access(www_data_dir)

    # links all static images
    # TODO: copy mode

    icon_paths = glob.glob(join(directory, '../../server_conf/*/icons/*'))
    icon_paths.extend(glob.glob(join(directory, 'server_conf/*/icons/*')))
    
    for icon_path in icon_paths:
        if not path_ok(icon_path):
            continue

        last_dirs = icon_path.split(os.sep)
        map_id = last_dirs[-3]
        img_name = last_dirs[-1]
        www_data_icon = join(directory, 'www-data/icons', map_id, img_name)

        link_or_copy(icon_path, www_data_icon)

    # make links from htdocs
    # TODO: copy mode
    link_or_copy('../../www-data/icons', join(directory, 'htdocs/gfx/servicons'))
    link_or_copy('servicons', join(directory, 'htdocs/gfx/icons'))
    

def path_ok(path):
    lastpath = os.path.split(path)[-1]
    return not lastpath in ['CVS']


def get_projects(rootpath):
    dirs = os.listdir(join(rootpath, 'projects'))
    return [d for d in dirs if path_ok(d)]
    
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

    for d in projects_path:
        setup_icons(d)
     
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
