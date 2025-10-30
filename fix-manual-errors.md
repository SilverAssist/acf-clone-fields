# Manual PHPCS Fixes Required

## 1. Yoda Conditions (WordPress.PHP.YodaConditions.NotYoda)
Convert conditions from `$var === 'value'` to `'value' === $var`

Example:
```php
// ❌ Wrong
if ( $status === 'active' ) {

// ✅ Correct  
if ( 'active' === $status ) {
```

## 2. Missing Translators Comments (WordPress.WP.I18n.MissingTranslatorsComment)
Add translator comments before strings with placeholders:

Example:
```php
// ❌ Wrong
__( 'Hello %s', 'domain' );

// ✅ Correct
/* translators: %s: user name */
__( 'Hello %s', 'domain' );
```

## 3. Block Comments (Squiz.Commenting.BlockComment.*)
Fix block comment formatting:

Example:
```php
// ❌ Wrong
/* comment on same line */

// ✅ Correct
/*
 * Comment on new line.
 */
```

## 4. Short Ternary (Universal.Operators.DisallowShortTernary.Found)
Replace `?:` with full ternary:

Example:
```php
// ❌ Wrong
$value = $input ?: 'default';

// ✅ Correct
$value = $input ? $input : 'default';
// or better:
$value = ! empty( $input ) ? $input : 'default';
```

## 5. Function Comments (Squiz.Commenting.FunctionComment.ThrowsNoFullStop)
Add period to @throws comments:

Example:
```php
// ❌ Wrong
@throws Exception When error occurs

// ✅ Correct
@throws Exception When error occurs.
```

## 6. Constants Prefix (WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound)
Prefix all constants with plugin prefix:

Example:
```php
// ❌ Wrong
define( 'SOME_CONSTANT', 'value' );

// ✅ Correct
define( 'SILVER_ASSIST_ACF_CLONE_SOME_CONSTANT', 'value' );
```
