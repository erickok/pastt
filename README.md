pastt
=====

PASTT: PHP Android String Translation Tool

The string resources used by the Android system for localization uses a custom XML-based format. PASTT allows translators without technical knowledge to edit and add translations in the Android resource XML format using a PHP-based webapp.

The current version is simple, but effective. It doesn't override translation files, but rather makes copies so disruptive users don't irreversibly override the work of nice users. It dynamically reads the directory structure so no database or (complex) set-up is needed.

PASTT is developed and released under the GNU GPL v3 copyleft license. It was originally hosted on [Google Code](https://code.google.com/p/android-php-translator/) and moved to GitHub in August 2013.

Originally build for use with Transdroid, for which you can see the tool active at http://www.transdroid.org/translate/

Installation
============

To install: unzip the latest pastt-{version}.zip to your PHP5-enabled web server. Copy your Android string resource directories 'values', 'values-nl', 'values-de', etc. to the res folder. Make sure all the 'values-{langcode}' directories (but not 'values') are readable and writeable by the web server. The res folder itself needs to be made writeable as well to add new languages. The 'values/strings.xml' file is required since it is assumed that it contains the (usually English) base version on which other translations are based.

Finally rename the 'includes/settings.example.php' file to 'includes/settings.php'. Optionally you can set an e-mail address there where you are notified on translation updates. I suggest setting $requireemail as simple initial spam protection.

Two simple shell/php scripts are available that allow FTP/SSH uploading of the current base translation (values/string.xml') and getting the latest on-line translations back into your local working copy. Check out https://gist.github.com/erickok/6339183

Developed By
============

Designed and developed by [Eric Kok](eric@2312.nl) of [2312 development](http://2312.nl). Contributions by various others (see commit log).

License
=======
    
    Copyright 2010-2013 Eric Kok et al.
    
    PASTT is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.
    
    PASTT is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    
    You should have received a copy of the GNU General Public License
    along with Transdroid.  If not, see <http://www.gnu.org/licenses/>.
    
