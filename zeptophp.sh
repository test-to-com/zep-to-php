#!/bin/bash

# --------------------------------------------------------------------------
# ZEP to PHP Translator                                                    
# --------------------------------------------------------------------------
# Copyright (c) 2015 pf at sourcenotes.org                                 
# --------------------------------------------------------------------------
# This source file is subject the MIT license, that is bundled with        
# this package in the file LICENSE, and is available through the           
# world-wide-web at the following url: https://opensource.org/licenses/MIT                                      
# --------------------------------------------------------------------------

# Is the Environment Variable Set?
if [ -z "$ZEPTOPHP" ]; then # NO: Try Default Value
  ZEPTOPHP="%ZEPTOPHP%"
  
  # Is ZEPTOPHP Set to Default Value?
  if [[ "$ZEPTOPHP" == "%""ZEPTOPHP""%" ]]; then # YES: Try to Find main.php
    # Get the Scripts Working Directory
    CWD="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
    ZEPTOPHP="$CWD"

    # Is ZEPTOPHP Set to Correct Working Directory?
    if [ ! -f "${ZEPTOPHP}/main.php" ]; then # NO: Try Parent
      ZEPTOPHP=`dirname $ZEPTOPHP`
    fi

    # Is ZEPTOPHP a Valid Working PAth?
    if [ -f "${ZEPTOPHP}/main.php" ]; then # YES
      export ZEPTOPHP=$ZEPTOPHP
    else # NO
      echo "Environment variable ZEPTOPHP is not set"
      exit 1
    fi
  fi
fi

php "${ZEPTOPHP}/main.php" $*

