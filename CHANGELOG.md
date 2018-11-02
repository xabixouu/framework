# Changelog


## [v0.2.7]
### Tag
`0.2.7`

### Date
November 2nd, 2018

### Changes
* Response json now takes second optional argument to set statusCode

### Additions
* None

### Removals
* None


## [v0.2.6]
### Tag
`0.2.6`

### Date
April 11th, 2018

### Changes
* None

### Additions
* Set Cookie -> handle httpOnly variable

### Removals
* None


## [v0.2.5]
### Tag
`0.2.5`

### Date
April 11th, 2018

### Changes
* None

### Additions
* Response File -> You can now download a file via URL

### Removals
* None


## [v0.2.4]
### Tag
`0.2.4`

### Date
February 19th, 2018

### Changes
* Composer.json -> Force smarty ~3.1 to 3.1.28 (conflict with newer version)

### Additions
* None

### Removals
* None


## [v0.2.3]
### Tag
`0.2.3`

### Date
February 19th, 2018

### Changes
* Composer.json -> smarty-dev to smarty

### Additions
* None

### Removals
* None


## [v0.2.2]
### Tag
`0.2.2`

### Date
February 19th, 2018

### Changes
* Patch MySQLModel -> Rebase to previous version

### Additions
* Patch MySQLModel -> Added Wheres reset

### Removals
* None


## [v0.2.1]
### Tag
`0.2.1`

### Date
February 19th, 2018

### Changes
* Patch MySQLModel -> Builder was not reset properly

### Additions
* None

### Removals
* None


## [v0.2]
### Tag
`0.2`

### Date
December 11th, 2017

### Changes
* None

### Additions
* `php xabi bundle:create` now creates a fully functional bundle with knockout-js

### Removals
* None


## [v0.1.31]
### Tag
`0.1.31`

### Date
November 22nd, 2017

### Changes
* TranslationLoader now automatically finds and loads all available files in `app/langs`
	* Files MUST BE of format `xx.json` where `xx` follows the [RFC ISO 639-1 codes](https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes)

### Additions
* Added `setlLocale(string)` method to BaseController

### Removals
* Cleaned comments on TranslationLoader file


## [v0.1.30]
### Tag
`0.1.30`

### Date
November 7th, 2017

### Changes
* None

### Additions
* Added `whereRaw` method to WhereBuilder for Mysql methods as `CURDATE()`

### Removals
* Cleaned unused variables


## [v0.1.29]
### Tag
`0.1.29`

### Date
October 25th, 2017

### Changes
* None

### Additions
* Added question in `xabi bundle:create Bundle` command for middleware controller

### Removals
* None


## [v0.1.28]
### Tag
`0.1.28`

### Date
October 23rd, 2017

### Changes
* Patch some remaining debug

### Additions
* None

### Removals
* Debug on `safe_encrypt` helper


## [v0.1.27]
### Tag
`0.1.27`

### Date
October 23rd, 2017

### Changes
* Added functions to framework's MySQLModel

### Additions
* Helpers `safe_encrypt` and `safe_decrypt` has been added. Encrypts data without a time variable

### Removals
* None


## [v0.1.26]
### Tag
`0.1.26`

### Date
October 20th, 2017

### Changes
* Added functions to framework's MySQLModel

### Additions
* Model `save` method, updating case: Now returns number of affected rows

### Removals
* None


## [v0.1.25]
### Tag
`0.1.25`

### Date
October 20th, 2017

### Changes
* Added functions to framework's Controller

### Additions
* Method `fetchHTML` in controller. Returns a string from template

### Removals
* None


## [v0.1.24]
### Tag
`0.1.24`

### Date
October 19th, 2017

### Changes
* Fixed PDOExceptions not correctly caught: some methods missing

### Additions
* None

### Removals
* None


## [v0.1.23]
### Tag
`0.1.23`

### Date
October 19th, 2017

### Changes
* Fixed PDOExceptions not correctly caught

### Additions
* None

### Removals
* None


## [v0.1.22]
### Tag
`0.1.22`

### Date
October 17th, 2017

### Changes
* Fixed chained `where()` in WhereBuilder

### Additions
* None

### Removals
* None


## [v0.1.21]
### Tag
`0.1.21`

### Date
October 16th, 2017

### Changes
* Fixed `call_user_func_array` to User's controler's methods

### Additions
* None

### Removals
* None


## [v0.1.20]
### Tag
`0.1.20`

### Date
October 10th, 2017

### Changes
* Fixed builder's bindings on multiple calls to Model methods

### Additions
* None

### Removals
* None


## [v0.1.19]
### Tag
`0.1.19`

### Date
October 10th, 2017

### Changes
* Improvements on framework's usability
* scan command improvement with additional overloads

### Additions
* You can now force a redirection
* Controller's `cookie` is now a `Symfony\Component\HttpFoundation\ParameterBag`

### Removals
* None


## [v0.1.18]
### Tag
`0.1.18`

### Date
October 9th, 2017

### Changes
* Mysql `query` and `delete` now use secured bindings

### Additions
* None

### Removals
* None


## [v0.1.17]
### Tag
`0.1.17`

### Date
October 9th, 2017

### Changes
* Patch on cookies

### Additions
* None

### Removals
* None


## [v0.1.16]
### Tag
`0.1.16`

### Date
October 9th, 2017

### Changes
* None

### Additions
* You can now destroy a cookie

### Removals
* None


## [v0.1.15.1]
### Tag
`0.1.15.1`

### Date
October 9th, 2017

### Changes
* Patch wrong controller name

### Additions
* None

### Removals
* None


## [v0.1.15]
### Tag
`0.1.15`

### Date
October 9th, 2017

### Changes
* None

### Additions
* Added `CUR_METHOD` and `CUR_CONTROLLER` in route resolver

### Removals
* None


## [v0.1.14]
### Tag
`0.1.14`

### Date
October 9th, 2017

### Changes
* None

### Additions
* Added `assign` method to dynamically assign variables to Smarty

### Removals
* None


## [v0.1.13]
### Tag
`0.1.13`

### Date
October 9th, 2017

### Changes
* None

### Additions
* Added missing MySQL Query builder files

### Removals
* None


## [v0.1.12]
### Tag
`0.1.12`

### Date
October 9th, 2017

### Changes
* Patch redirect

### Additions
* Added MySQL Query builder

### Removals
* None


## [v0.1.11]
### Tag
`0.1.11`

### Date
October 5th, 2017

### Changes
* None

### Additions
* Added cookie handler

### Removals
* None


## [v0.1.10]
### Tag
`0.1.10`

### Date
October 5th, 2017

### Changes
* Safe redirect method

### Additions
* None

### Removals
* None


## [v0.1.9]
### Tag
`0.1.9`

### Date
October 3rd, 2017

### Changes
* Modify model stud example

### Additions
* None

### Removals
* None


## [v0.1.8.2]
### Tag
`0.1.8.2`

### Date
October 3rd, 2017

### Changes
* Modify controller stud example

### Additions
* None

### Removals
* None


## [v0.1.8.1]
### Tag
`0.1.8.1`

### Date
October 3rd, 2017

### Changes
* Handle not found controllers

### Additions
* None

### Removals
* None


## [v0.1.8]
### Tag
`0.1.8`

### Date
October 3rd, 2017

### Changes
* Patch first run APP_KEY generation

### Additions
* Added `getMessage` with autoremove function

### Removals
* None


## [v0.1.7]
### Tag
`0.1.7`

### Date
October 3rd, 2017

### Changes
* None

### Additions
* Added `setMessage` and `getMessage`

### Removals
* None


## [v0.1.6]
### Tag
`0.1.6`

### Date
October 3rd, 2017

### Changes
* None

### Additions
* Added password encryptor

### Removals
* None


## [v0.1.5.1]
### Tag
`0.1.5.1`

### Date
October 3rd, 2017

### Changes
* None

### Additions
* None

### Removals
* Removed `dd` debug code.


## [v0.1.5]
### Tag
`0.1.5`

### Date
October 3rd, 2017

### Changes
* None

### Additions
* Added `dd` helper

### Removals
* None


## [v0.1.4]
### Tag
`0.1.4`

### Date
October 3rd, 2017

### Changes
* None

### Additions
* Added sessions handler

### Removals
* None


## [v0.1.3.2]
### Tag
`0.1.3.2`

### Date
October 2nd, 2017

### Changes
* None

### Additions
* Added `css_path` helper
* Added `js_path` helper
* Added `images_path` helper

### Removals
* None


## [v0.1.3.1]
### Tag
`0.1.3.1`

### Date
October 2nd, 2017

### Changes
* None

### Additions
* Added `layout_path` helper

### Removals
* None


## [v0.1.3]
### Tag
`0.1.3`

### Date
October 2nd, 2017

### Changes
* Patched `composer dump-autoload` after bundle creation

### Additions
* None

### Removals
* None


## [v0.1.2.4]
### Tag
`0.1.2.4`

### Date
October 2nd, 2017

### Changes
* None

### Additions
* You can now create a bundle via console
* `composer dump-autoload` after bundle creation

### Removals
* None


## [v0.1.2.3]
### Tag
`0.1.2.3`

### Date
October 2nd, 2017

### Changes
* None

### Additions
* Console handler

### Removals
* None


## [v0.1.2.2]
### Tag
`0.1.2.2`

### Date
October 2nd, 2017

### Changes
* None

### Additions
* Patch first run of empty app: app_key auto generation
* Reload configuration files

### Removals
* None

### NOTE
* Doubled tag. Epic fail.


## [v0.1.2.1]
### Tag
`0.1.2.1`

### Date
October 2nd, 2017

### Changes
* None

### Additions
* Patch first run of empty app: app_key auto generation
* Reload configuration files

### Removals
* None


## [v0.1.2]
### Tag
`0.1.2`

### Date
October 2nd, 2017

### Changes
* None

### Additions
* Encryptor not working properly: Added debug on framework

### Removals
* None


## [v0.1.1]
### Tag
`0.1.1`

### Date
October 2nd, 2017

### Changes
* Official first working version

### Additions
* All framework's files

### Removals
* None


## [v0.1.0]
### Tag
`0.1.0`

### Date
September 28th, 2017

### Changes
* Updated composer.json

### Additions
* None

### Removals
* None
