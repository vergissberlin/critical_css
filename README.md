[![Build Status](https://travis-ci.org/Nemo64/critical_css.svg?branch=master)](https://travis-ci.org/Nemo64/critical_css)
[![Latest Stable Version](https://poser.pugx.org/nemo64/critical_css/v/stable)](https://packagist.org/packages/nemo64/critical_css)
[![Total Downloads](https://poser.pugx.org/nemo64/critical_css/downloads)](https://packagist.org/packages/nemo64/critical_css)
[![Monthly Downloads](https://poser.pugx.org/nemo64/critical_css/d/monthly)](https://packagist.org/packages/nemo64/critical_css)
[![License](https://poser.pugx.org/nemo64/critical_css/license)](https://packagist.org/packages/nemo64/critical_css)

# critical css typo3 extension

This extension automatically detects and inlines critical css definitions.

## how it works

The implementation of this extension does not use a browser to check a specific viewport size.
Instead, it uses a marker that you can place anywhere on your site.
The example TypoScript places it after the second tt_content element.

This extension will then use sophisticated regular expressions to create a statistic
of what elements/attributes/classes/ids are used up until the marker.
Then all css files are parsed using [sabberworm/php-css-parser](https://github.com/sabberworm/PHP-CSS-Parser)
and all css selectors are matched against and reduced by the statistic created earlier. 

This entire process is quiet fast even against huge frameworks like bootstrap and results in just 2-3 kb of inline css.

## things you should know

- If the "below the fold" marker isn't present the extension will do nothing
- The inlined css is just a good guess of what you'll need. There is no browser selector matching at work. It guesses quiet good though...
- All animations/transitions as well as hover/focus effects are removed from the inline css since they aren't really important for the (hopefully at most) 1 second you'll have to wait until you get the real css 
- external stylesheets are also downloaded and inlined (TODO create a good way to create exceptions)
- google fonts is currently a hardcoded exception from inlining since they deliver different fonts via user agent

## when should i consider using this extension (or alternatives, i'm not judging)

- This should be one of the last optimizations you do
- Make sure there is nothing but css blocking the first paint or else this optimization is useless
- Make sure that your site isn't delivered via php since that will probably be the biggest performance improvement you can do. I recommend [lochmueller/staticfilecache](https://github.com/lochmueller/staticfilecache) since it has no platform dependencies and is very transparent. But other strategies are fine too. 

## contributing

### running the tests

The easiest way to run the test suite is by having docker installed and using the following commands:

- `composer db:start` which starts a docker image for a mysql database. This is required for functional tests.
- `composer test` runs unit and functional test suites. You can run them separately by using `test:unit` or `test:functional`. You can also pass filters using `composer test:unit -- --filter External`.
- `composer db:stop` will stop and remove the database again.

You can of course run them without docker if you have a database locally but then you'll have to compose your tests commands manually ;)
