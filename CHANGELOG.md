# 2015-11-27

**MAJOR:** Rewrote the Code Emitter so that it's now possible to control the 
formatting of the PHP Output Code, without having to having to alter the Source
of the emitter. Should Make the PHP OUTPUT less of an eye sore :)

# 2015-11-05

**MAJOR:** Rewrote the way string were being converted to PHP. Initially, since Zephir doesn't
support embedded variables, I converted using single quotes.

This had 2 problems:

1. (Major) Single quotes in PHP ignore special escape sequences like \n,\t,etc.
2. (Minor) You don't have to escape " (double quote). This was generating problems
in the Phalcon Debug Extension, in SQL that was being generated.

I now output ZEPHIR strings as " (double quote) PHP Strings.

**NOTE: This Change, like any change in the PHP code emitter, forces a rebuild in
the PHALCON Debug Extension**

**MINOR:** Implemented more of the zephir builtin functions.

