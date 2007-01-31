#!/home/sypasche/bin/python 

#
# This script makes a checkout of cartoweb cvs, and launches the unit tests.
# If no update of cvs was done, it does not run the tests.
#
# In case of a test failure, an email is sent to a specified address.

# Configuration: change to match your environment
DISABLING_FILE = "/tmp/auto_test_disabled"
CVSROOT=':pserver:username@example.com:/var/lib/cvs/projects/cw3'
EMAIL = "your.email@example.com"
PHP_PATH = "php"
BASE_URL="'http://example.com/auto_test/'"
SMTP_SERVER = "example.com"

# You shouldn't need to change these
CVS_DIR='cvs'
LOG_FILE='log.txt'

import commands, sys, os, os.path, datetime, smtplib, email.Utils, random
import os.path as path

class TestsException(Exception):
    pass

def prepare():
    print "prepare"
    if not path.isdir(CVS_DIR):
        os.makedirs(CVS_DIR)
    print "Enter your cvs password when prompted ..."
    ret = commands.getoutput("cd %s; cvs -d %s login" % (CVS_DIR, CVSROOT))
    ret = commands.getoutput("cd %s; cvs -d %s co cartoweb3" % (CVS_DIR, CVSROOT))
    print ret

def is_uptodate():
    out = commands.getoutput('cd %s/cartoweb3;cvs update -dP' % CVS_DIR)
    for l in out.split("\n"):
        #if l.startswith("U") or l.startswith("P"):
        if not l.startswith("cvs update"):
            print "FAILED line:", l
            return False
        #print "LINE", l
    #print "Not updated"
    return True

def run(cmd):
    exitvalue = os.system(cmd)
    if exitvalue != 0:
        print >>log, "Failed to execute %s, exitting" % cmd
        raise TestsException('Failed to execute command: %s' % cmd)
        
def fetch_cartoweb():
    cmds = """[ -d cartoweb3 ] && rm -rf cartoweb3 || :
    cp -r cvs/cartoweb3 .
    (cd cartoweb3; %s cw3setup.php --install --base-url %s --debug) || true
    (cd cartoweb3; %s cw3setup.php --fetch-demo) || true""" % (PHP_PATH, BASE_URL, PHP_PATH)

    for cmd in cmds.split("\n"):
        run(cmd)

def rd(s, sep="."):
        i = random.randint(0, len(s))
        return s[0:i] + sep + s[i:]

def send_mail(kind, output):

    print >>log, "Test failure, sending mail to %s" % EMAIL
    server = smtplib.SMTP(SMTP_SERVER)
    FROM_ADDRESS="noreply@camptocamp.com"
    subject = rd("auto_test_report", " ")
    body = ("This is an error report from the automatic cartoweb testing. \n" + \
              " The error type is: '%s' \n\n" + \
              " The error message is: \n\n%s") % (kind, output)
    msg = ("Date: %s\r\nFrom: %s\r\nTo: %s\r\nSubject: %s\r\n\r\n%s"
                  % (email.Utils.formatdate(), FROM_ADDRESS, EMAIL, subject, body))
    server.sendmail(FROM_ADDRESS, EMAIL, msg)

def run_tests():

    print >>log, "Running tests"
    (status, output) = commands.getstatusoutput("cd cartoweb3/tests/; %s phpunit.php AllTests" % PHP_PATH)

    # truncate to 50k maximum
    MAX_SIZE = 25 * 1024
    output = output[:MAX_SIZE]
    if len(output) == MAX_SIZE:
        output += " <TRUNCATED> "

    print >>log, "Test output", output
    print >>log, "Test status", status

    # for debugging:
    #status = 1

    if status != 0 or "Failure" in output:
        send_mail('Unit test failure', output)

def main():

    print >>log, "\n" + "=" * 80
    print >>log, "Script launched at ", datetime.datetime.now().__str__()
    log.flush()
    
    if "-prepare" in sys.argv:
        prepare()
        sys.exit()

    if os.path.exists(DISABLING_FILE):
        print >>log, "Disabling file (%s) is there, skipping tests" % DISABLING_FILE
        sys.exit()
    
    SKIP_UPDATE_CHECK=False
    if "-skip-update-check" in sys.argv or SKIP_UPDATE_CHECK:
        uptodate = False
    else:
        uptodate = is_uptodate()

    print "Uptodate: ", uptodate

    if uptodate:
        print >>log, "CVS up to date, skipping tests"
        sys.exit()

    fetch_cartoweb()
    run_tests()
    print >>log, "End of tests\n" + "=" * 80


if __name__ == '__main__':

    os.chdir(path.abspath(path.dirname(__file__)))

    if "-debug" in sys.argv:
        log = sys.stderr
    else:
        log = open(LOG_FILE, 'a')

    try:
        main()
    except TestsException, e:
        print "TestsException: ", e
        send_mail('Auto test setup failure', e)
