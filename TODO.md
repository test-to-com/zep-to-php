TODO LIST
=========

TD-001: Improved Pretty Printing for PHP Emitter
------------------------------------------------

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
