#!/bin/bash

soft='RgpdTools'
version="$(grep PLUGIN_RGPDTOOLS_VERSION setup.php |cut -f 4 -d\'|grep -v ^$)"
email=contact@probesys.com
copyright='PROBESYS'
potfile='locales/rgpdtools.pot'

# All strings to create pot
xgettext *.php */*.php -copyright-holder="$copyright" --package-name="$soft" --package-version="$version" --msgid-bugs-address="$email" -o "$potfile" -L PHP --from-code=UTF-8 --force-po  -i --keyword=_n:1,2 --keyword=__:1,2c --keyword=_e

