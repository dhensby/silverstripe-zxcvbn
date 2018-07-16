# zxcvbn Password Validator

This is a drop-in replacement for the core `SilverStripe\Security\PasswordValidator` class. This module implements
[`bjeavons/zxcvbn-php`](https://github.com/bjeavons/zxcvbn-php) which provides a more realistic measure of password
strength (or, rather, vulnerability).

## Installation

Install with [`composer`](https://getcomposer.org/):

```
composer require dhensby/silverstripe-zxcvbn
```

## Usage

This module automatically registers its validator against `Injector` meaning any existing instantiations of
`SilverStripe\Security\PasswordValidator` (using the `Injector` factory) will automatically be replaced with this
validator.

Most new SilverStripe applications come with a `PasswordValidator` already registered, if that's the case you'll likely
need to make this change:

In your `_config.php`:

```diff
use SilverStripe\Security\PasswordValidator;
use SilverStripe\Security\Member;

-$validator = new PasswordValidator();
+$validator = PasswordValidator::create();
+$validator->setMinTestScore(3);
$validator->setMinLength(8);
$validator->setHistoricCount(6);
Member::set_password_validator($validator);
```

The existing rules for minimum length and historical password count still exist and will work as expected but note that
a password with a short length will do well to reach a score of 3 and a short password of score 3 is going to be better
than a longer password of score 2.

If you don't have any existing configuration you can set the validator up with the help of `Injector` and no PHP code is
needed (SS 4.2+):

```yml
SilverStripe\Security\PasswordValidator:
  min_test_score: 3
```

Or:

```yml
SilverStripe\Core\Injector\Injector:
  SilverStripe\Security\PasswordValidator:
    properties:
      MinTestScore: 3
```

## Guidance

Passwords will be given a score by the validator; that score can range from 0 - 4, with 0 being the worst and 4 the best.

In reality a score of 3 is going to be acceptable on most sites; enforcing a score of 4 will become very frustrating
for most users.
