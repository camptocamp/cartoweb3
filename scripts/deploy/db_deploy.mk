##
## Makefile for deployment of cartoweb applications
##  database specific part
##
## Copyright 2005, Sylvain Pasche Camptocamp SA
## $Id$

# Useful targets:

# pre_deploy_db:
#  pre deployment of a whole database
# deploy_db:
#  real database whole deployment, needs to be done after the pre_deploy_db
# undeploy_db:
#  undo a database deployment (not tested recently !)
# deploy_db_table (+ DEPLOY_TABLE environnemnt variable)
#  deploy only one table out of a database

# $(1) ssh host
# $(2) autossh monitoring port
define launch_tunnel
	ps -ef|grep -v grep|grep '[a]utossh.*$(1)'>/dev/null || { echo "Starting autossh"; autossh -f -M $(2) -N $(1); sleep 3; }
endef

DB_TMP ?= $(DB)_tmp

SQL_PATH=~/tmp/
SQL_FILE=$(SQL_PATH)$(DB).sql.gz

launch_tunnel:
	@$(call launch_tunnel,$(TARGET_HOST),20005)

CREATEDB=createdb $(CREATEDB_OPTS)

pre_deploy_db: check_user launch_tunnel sync_misc
	if $(DB_OPTS_TARGET) psql -l|grep -q "\W$(DB_TMP)\W" ; then \
		read -p "Error: database $(DB_TMP) is on the way. Press <ctrl-c> to stop, or enter to remove it"; \
		$(DB_OPTS_TARGET) dropdb $(DB_TMP); \
	fi
	$(DB_OPTS_TARGET) $(CREATEDB) $(DB_TMP)
	$(DB_OPTS) pg_dump $(DB) |gzip --fast > $(SQL_FILE)
	scp $(SQL_FILE) $(TARGET_HOST):$(SQL_PATH)
	ssh $(TARGET_HOST) $(MAKE) -C $(TOPSRCDIR) fill_db_tmp DB=$(DB)

fill_db_tmp:
	gzip -cd $(SQL_FILE) | $(DB_OPTS) psql $(DB_TMP)
	rm $(SQL_FILE)

MAX_RETRY = 2
RETRY_SLEEP = 1
# $(1): the command to launch
# FIXME: raise sleep and count
define do_command_repeat
	declare -i count; count=0;max=$(MAX_RETRY); while true; do \
		$(1); \
		if [ $$? -ne 0 ]; then \
			echo -e "\E[47;31m\E[1mFailure\033[0m during command, retrying ($$count/$$max)..."; \
		else \
			echo "OK"; \
			break; \
		fi; \
		count=$$(($$count + 1)); \
		if [ $$count -gt $$max ]; then \
			break; \
		fi; \
		sleep $(RETRY_SLEEP); \
	done; \
	if [ $$count -eq $$(($$max + 1)) ] ; then \
		echo -e " \E[47;31m\E[1mCommand failed\033[0m exiting "; \
		exit 1; \
	fi
endef

deploy_db: check_user launch_tunnel
	@if ! $(DB_OPTS_TARGET) psql -l|grep -q "\W$(DB_TMP)\W" ; then \
		echo "Temporary database is not there, make sure you did the pre-deploy"; \
		exit 1; \
	fi

	@if ! $(DB_OPTS_TARGET) psql -l|grep -q "\W$(DB)\W" ; then \
		echo "Inconsistency: Target database is not there. Press enter to continue (will be created)"; \
		read; \
	else \
		$(call do_command_repeat,$(DB_OPTS_TARGET) dropdb $(DB)) \
	fi

	$(DB_OPTS_TARGET) psql template1 -c "alter database $(DB_TMP) rename to $(DB)"


undeploy_db: check_user launch_tunnel
	@echo -e "\E[47;31m\E[1mWarning, the undeploy will overwrite the database from this host with the one from "\
	"the target deploy host. Service will be interrupted during the undeploy.\033[0m"
	@echo "Press <ctrl-c> to abort, or enter to continue"
	@read
	if $(DB_OPTS) psql -l|grep -q "\W$(DB)\W" ; then \
		$(DB_OPTS) dropdb $(DB); \
	fi
	$(DB_OPTS) $(CREATEDB) $(DB); \
	$(DB_OPTS_TARGET) pg_dump $(DB) | $(DB_OPTS) psql $(DB)

TABLE_WORDS := $(subst ., ,$(DEPLOY_TABLE))
ifeq ($(words $(TABLE_WORDS)),2)
      SCHEMA := $(word 1,$(TABLE_WORDS))
      SCHEMAPREFIX := $(SCHEMA).
      TABLE := $(word 2,$(TABLE_WORDS))
else
      SCHEMA :=
      SCHEMAPREFIX :=
      TABLE := $(DEPLOY_TABLE)
endif

deploy_db_table: check_user launch_tunnel
	@if test -z "$$DEPLOY_TABLE"; then \
		echo "Error: you need to give a table name in DEPLOY_TABLE variable, example:"; \
		echo "DEPLOY_TABLE=grid_lk100 make deploy_db_table"; \
		exit 1; \
	fi
ifndef NO_CONFIRM
	@echo "Warning, table $$DEPLOY_TABLE will be deployed directly, be sure the data is correct. "\
	"Press <ctrl-c> to abort, or enter to continue"
	@read
endif

	echo $(SCHEMA),$(SCHEMAPREFIX),$(TABLE)
	(echo "delete from $(SCHEMAPREFIX)$(TABLE);"; \
	$(DB_OPTS) pg_dump $(if $(SCHEMA),-n $(SCHEMA)) -t $(TABLE) $(DB) -a ) | $(DB_OPTS_TARGET) psql $(DB)
