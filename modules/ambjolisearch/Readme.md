#Documentation
Complete documentation can be found in /docs, or on in your prestashop customer account.

#Description
The JoliSearch module considerably improves the standard instant research provided by Prestashop. It of- fers a better visual experience for your clients : images and brands of your products are directly displayed in the results list. Moreover, the module handles typing errors and spelling mistakes for optimal search results.

#Common trouble-shouting
Problem : The module is oddly-placed on the webpage
Solution : You should change the position of the module in the back-office, through « Modules > Positions ». In the hook « Top of pages », make sure the module JoliSearch is at the same level as « Quick Search block » or higher. The module can also be hooked on the left or the right side of your theme.

Problem : Approximative search doesn’t seem to work / an error message appeared during installation
Solution : This may be due to access restrictions in your database configuration. Make sure the database user prestashop uses has the privileges « CREATE ROUTINE » and « EXECUTE ».
These may be activated under the tab privileges in phpmyadmin or with these instructions for mysql command line :
      GRANT CREATE ROUTINE, EXECUTE ON your_prestashop_database TO
      your_user@your_database_server_name
(see mysql documentation: http://dev.mysql.com/doc/refman/5.1/en/grant.html )
Afterward, you should reset the module for everything to work properly.

Problem : Instant search displays HTML code
Solution : Tick « yes » at « Enable Html Output Debug » in the configuration panel of the module to correct this problem.


# Release notes
- --
## 1.2.0
- Special characters are now allowed in search bar
- SEO compatibility has been improved
- Multiple term searches now work all the time
- Product prices may now be shown during instant searches
- « show more results » added at the bottom of instant search results

### 1.2.1
- Correction of performance issue. Since version 1.2, searches experienced performance issues, especially when a great number of results was returned. This update resolves the issue and substantially improves the search procedure.
### 1.2.3
- Standardisation of css and tpl files. Default css and tpl files are now identical to those from blocksearch. This ensures easier integration and better theme compliancy.
### 1.2.4
- Instant searches will now display products even if they don’t have a            category
### 1.2.5
- No release note
### 1.2.6
- No release note
### 1.2.7
- support show price settings for both products and groups
- support display price without tax or with tax settins for groups
- doesn't display prices anymore in catalog mode
- fix hover style problem of the results list when another jquery ui theme is         installed by another module
- Huge performance increase when displaying results
- Enhance compatibility of JoliLink.php across prestashop versions from 1.5->1.5.5.0 to v1.5.5.0->1.6.x.x
- Add a javascript option via data attributes to set position of the results list         in regards of the input search field
### 1.2.8
- Restore translations on the «search» button
- --
## 1.3.0
- Module will not trigger php notices in debug mode anymore

### 1.3.1
- Module is now compatible with SSL mode

- --
## 2.0.0
- Major performance boost
- Product attributes can now be displayed during instant searches
- Categories can now be displayed on the search results page
- New terms are available to translate for languages others than english and French
### 2.0.1
- Instant searches do not return notices in debug mode anymore
- Container divs are now identified by an id («product», «manufacturer» or «category») for customizable css declarations

### 2.0.2
- Category descriptions were always displayed in 1.6, this is now fixed
### 2.0.3
- Compatibility with prestamodule4 had an issue, this is nos fixed
- Prices are now displayed correctly in multishop contexts
- Category displays have been improved
- Instant search performances have been notably improved
- --
## 2.1.0
- When the module can’t be installed correctly due to database rights, a compatibility mode is activated. This mode is now faster and has more options like price display.
- Config images didn’t appear, this is not corrected.
- Added «common troubleshooting» to the readme file.
- Add a javascript compatibility mode to prevent issues with other modules.

### 2.1.1
- The «More results» button now links to jolisearch instead of standard search in compatibility mode.
- Added documentation to the module folder.
- Copyrights have been updated in licences.
### 2.1.2
- In some cases, no results page had an issue finishing its routine. This is now fixed.
### 2.1.3
- Levenshtein requests now take customer groups into account during their searches, in order to prevent synonym multiplication under certain circumstances.
### 2.1.4
- Synonym multiplication is now impossible.
- Synonyms can now be reset through the module configuration panel. The amount of synonyms stored is also indicated.
### 2.1.5
- Auto upgrade is now possible towards 2.1.5 for all previous versions
### 2.1.6
- Auto upgrade now drops and recreates the full table, creating the unique index in any circumstances

### 2.1.7
- With the sql cache system activated, results count would always return the same result, no matter the search query. This is now fixed.
- Jolisearch now returns an error message if there is no word longer than the minimal word length in the search query.
### 2.1.8
- On some systems, levenshtein function would not install properly. This is now fixed
- Synonyms are now inserted through a «REPLACE INTO» instead of «INSERT INTO», to avoid unique index collisions.
### 2.1.9
- Prestashop Exceptions are now taken into account but don’t throw
- --
## 2.2.0
- The module now has an easier to follow searching process, and avoids redundant function calls.
- Installation errors are now more explicit and explain which steps to follow to correct the issue.
- Categories are now filtered according to the shop context

### 2.2.1
- Accents that were stripped one the results page will now be displayed correctly.
- Added Spanish translation for the module
### 2.2.2
- Added a specific jolisearch hook to facilitate integration in custom themes
- Various small bug fixes
### 2.2.3
- AdvancedSearch4 compatibility
### 2.2.4
- Add «Search within word» option
### 2.2.5
- fix return of Ids for advancedSearch4
- fix SQL slave usage
- add option to use default blocksearch css
- add option to disable autocomplete
### 2.2.6
- improve SSL compatibility on search results page
- add option to search in all languages at once
- fix issue with "show all" button on search results page
### 2.2.7
- improve query speed by reusing previous results
- fix issue on sorted searches
### 2.2.8
- instant searches start based on prestashop min word length

### 2.3.0
- fix full SSL issue for some configurations with a single shop
- fix SSL issue on search results page for Prestashop 1.5
- ensure compatibility with prestamodule advancedsearch >= 4.11
- Refactor code for maintanability
- 1.7 compatibility

### 2.3.1
- fix SSL issue on ajax requests in some SSL configurations
- quickfix issue with 1.6 compliancy

### 2.3.2
- Restore AJS_COMPAT in jolisearch configuration

## 2.4.0
- Add documentation block in configuration

### 2.4.1
- fix compatibility issue with PHP < 5.4

### 2.4.2
- fix pagination for prestashop 1.7+

## 3.0.0
- Make ajax queries return empty array instead of null for 1.7 compatibility
- Fix notices during ajax queries
- Official release for Prestashop 1.7

### 3.0.1
- Fix compatibility for PHP < 5.4
- Fix layout selection issue
- Fix 404 error with prestashop 1.7.1
- Fix no results found in prestashop 1.7.0.x

### 3.0.2
- Fix issue with amazzing filter module when "more results" link is used in drop down list of search results
- Fix missing accented characters issue

### 3.0.3
- Fix category settings issue
- Fix notice when no categories are found

### 3.0.4
- Fix url of no-image when using SSL
- Improve Levenshtein searches

### 3.0.5
- Make instant search items right clickable
- Ensure compatibility for ja, tw and zh languages

### 3.0.6
- Update readmes for version 3
- Fix javascript inclusion path

### 3.0.7
- Fix no results issue
- Fix sort order issue

### 3.0.8
- Fix category descriptions not displaying even when activated
- Fix undefined variable notice in BO

### 3.0.9
- Fix sorting on quantity in results page

### 3.0.10
- Prevent 404 error on js in back-office

### 3.0.11
- Fix "no results found message" on results page when different languages are enabled
- Fix missing images on products list
- Fix bad numbers of results display when image covers are not set on products
- Fix no product found when default visitor group is not 1 as id
- Fix category display in dropdown list
- Improve compatibility with themes using custom search bar (based on Warehouse theme)
- Improve compatibility with themes using default search bar
- Improve compatibility with others Ambris modules
- Fix html code displayal in search bar when up or down keys are used with "no results found" message in dropdown list








