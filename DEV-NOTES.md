IMPLEMENTATION NOTES
====================

NOTE-001: Problems Implementing 'fetch'
---------------------------------------

###Normal Scenarios:

'fetch' as an expression:

(phalcon\db\dialect.zep: 598)

```
if fetch left, expression["left"] {
  return this->getSqlExpression(left, escapeChar, bindCounts) . " " . expression["op"];
}
```

or **negated**:

(phalcon\db\dialect.zep: 528)

```
if !fetch value, expression["value"] {
  throw new Exception("Invalid SQL expression");
}
```

'fetch' as a statement:

(phalcon\db\dialect.zep: 370)

```
/**
 * The index "1" is the schema name
 */
fetch schemaName, table[1];
```

###Problem 1:

(phalcon\db\dialect.zep: 625)

```
if fetch arguments, expression["arguments"] && typeof arguments == "array" {
```

###Problem 2:


###Problem 3:

(phalcon\db\dialect.zep: 657)

```
if (fetch values, expression[0] || fetch values, expression["value"]) && typeof values == "array" {
```

**NOTICE:** that the variable *values* is re-used between the 2 *fetch* expressions.