#!/bin/bash
#
# ownCloud
#
# @author Artur Neumann
# @copyright 2017 Artur Neumann info@individual-it.net
#
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Look for command line options for:
# -c or --config - specify a behat.yml to use
# --feature - specify a single feature to run
# --tags - specify tags for scenarios to run (or not)
BEHAT_TAGS_OPTION_FOUND=false

while [[ $# -gt 1 ]]
do
	key="$1"
	case $key in
		-c|--config)
			BEHAT_YML="$2"
			shift
			;;
		--feature)
			BEHAT_FEATURE="$2"
			shift
			;;
		--tags)
			BEHAT_TAGS="$2"
			BEHAT_TAGS_OPTION_FOUND=true
			shift
			;;
		*)
			# ignore unknown options
			;;
	esac
	shift
done

# An odd parameter by itself at the end is a feature to run
if [ -n "$1" ]
then
	BEHAT_FEATURE="$1"
fi

if [ -z "$BEHAT_YML" ]
then
	BEHAT_YML="tests/ui/config/behat.yml"
fi

if [ "$SRV_HOST_PORT" == "80" ] || [ -z "$SRV_HOST_PORT" ]
then
	BASE_URL="http://$SRV_HOST_NAME/$SRV_HOST_URL"
else
	BASE_URL="http://$SRV_HOST_NAME:$SRV_HOST_PORT/$SRV_HOST_URL"
fi

if [ "$BROWSER" == "firefox" ]
then
	EXTRA_CAPABILITIES='"seleniumVersion":"2.53.1",'
fi

EXTRA_CAPABILITIES=$EXTRA_CAPABILITIES'"maxDuration":"3600"'

echo "Running tests on '$BROWSER' ($BROWSER_VERSION) on $PLATFORM"
export BEHAT_PARAMS='{"extensions" : {"Behat\\MinkExtension" : {"browser_name": "'$BROWSER'", "base_url" : "'$BASE_URL'","selenium2":{"capabilities": {"browser": "'$BROWSER'", "version": "'$BROWSER_VERSION'", "platform": "'$PLATFORM'", "name": "'$TRAVIS_REPO_SLUG' - '$TRAVIS_JOB_NUMBER'", "extra_capabilities": {'$EXTRA_CAPABILITIES'}}, "wd_host":"http://'$SAUCE_USERNAME:$SAUCE_ACCESS_KEY'@localhost:4445/wd/hub"}}}}' 

if [ "$BROWSER" == "internet explorer" ]
then
	if [ "$BEHAT_TAGS_OPTION_FOUND" = true ]
	then
		if [ -z "$BEHAT_TAGS" ]
		then
			lib/composer/bin/behat -c $BEHAT_YML --tags '~@skipOnIE' $BEHAT_FEATURE -v
		else
			lib/composer/bin/behat -c $BEHAT_YML --tags "$BEHAT_TAGS&&~@skipOnIE" $BEHAT_FEATURE -v
		fi
	else
		lib/composer/bin/behat -c $BEHAT_YML --tags '~@skip&&~@skipOnIE' $BEHAT_FEATURE -v
	fi
else
	if [ "$BEHAT_TAGS_OPTION_FOUND" = true ]
	then
		if [ -z "$BEHAT_TAGS" ]
		then
			lib/composer/bin/behat -c $BEHAT_YML $BEHAT_FEATURE -v
		else
			lib/composer/bin/behat -c $BEHAT_YML --tags "$BEHAT_TAGS" $BEHAT_FEATURE -v
		fi
	else
		lib/composer/bin/behat -c $BEHAT_YML --tags '~@skip' $BEHAT_FEATURE -v
	fi
fi

if [ $? -eq 0 ]
then
	PASSED=true
else
	PASSED=false
fi

if [ ! -z "$SAUCE_USERNAME" ] && [ ! -z "$SAUCE_ACCESS_KEY" ] && [ -e /tmp/saucelabs_sessionid ]
then
	SAUCELABS_SESSIONID=`cat /tmp/saucelabs_sessionid`
	curl -X PUT -s -d "{\"passed\": $PASSED}" -u $SAUCE_USERNAME:$SAUCE_ACCESS_KEY https://saucelabs.com/rest/v1/$SAUCE_USERNAME/jobs/$SAUCELABS_SESSIONID

	printf "\n${RED}SAUCELABS RESULTS:${BLUE} https://saucelabs.com/jobs/$SAUCELABS_SESSIONID\n${NC}"
fi

if [ "$PASSED" = true ]
then
	exit 0
else
	exit 1
fi
