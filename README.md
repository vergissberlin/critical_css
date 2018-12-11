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
