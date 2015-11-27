TODO LIST
=========

TD-001: Improved Pretty Printing for PHP Emitter
------------------------------------------------

***DONE***

The basis for Pretty Printing the PHP code is hard coded into the EMITTER.

Extract that out to a seperate configuration file (maybe JSON) so that it can be customized according to the user's needs.

TD-002: Improve Handling of Comments
------------------------------------

Output Comments (including inline)

TD-003: Add Option to Add/Remove PHP Document Comments
------------------------------------------------------

Add Possibility an option that can:

* Add PHP Document Comments to Class, Class Methods/Constants/Members, Functions
  * Take into account that private methods/members might not actually need/want PHP Document Comments
  * Add/Correct 
* Remove PHP Document Comments

TD-004: Add Possbility of Supressing Multiple New-Lines/Spaces
--------------------------------------------------------------

If we have multiple, sequential, new-lines or spaces, we should have the
possibility of not emitting them

example:

```
  protected $_intermediate ;\n
  \n
  \n
  static protected $_irPhqlCache ;\n
```

should produce:

```
  protected $_intermediate ;\n
  \n
  static protected $_irPhqlCache ;
```

**NOTE:** that the new line on the statement end, does not count for multiple
new line elimination

like wise:

```
  protected $_intermediate\b\b,\b\b$_something;
```

should produce:

```
  protected $_intermediate\b,\b$_something;
```



TD-005: Sort Sections Based on Visibility Combinations
------------------------------------------------------

Example:

```
class Query ... {
...
  protected $_intermediate ;
  static protected $_irPhqlCache ;
  protected $_manager ;
...
}
```

Should Probably produce something like:

```
class Query ... {
...
  protected $_intermediate ;
  protected $_manager ;
  static protected $_irPhqlCache ;
...
}
```

**PROBLEM:** When Sorting Sections the 1st comment might create a problem

Example:

```
class Query ... {
...
  // Members
  static protected $_irPhqlCache ;
  protected $_intermediate ;
  protected $_manager ;
...
}
```

Might produce something like:

```
class Query ... {
...
  protected $_intermediate ;
  protected $_manager ;
  // Members
  static protected $_irPhqlCache ;
...
}
```

Rather than:

```
class Query ... {
...
  // Members
  protected $_intermediate ;
  protected $_manager ;
  static protected $_irPhqlCache ;
...
}
```


