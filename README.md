# phpafsfree
0. License and Disclaimer

    Please note that unless otherwise stated, all code in phpafsfree is
    Copyright (C) 2006-2009 Stephen Joyce and is released under the terms of
    the GPL.  See the file GNU_GPL.txt for full information.

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License along
    with this program; if not, write to the Free Software Foundation, Inc.,
    51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.


    NOTABLE EXCEPTIONS:
    1. partinfo.pl is Copyright (C) 1998, 1999, 2003, 2004 Board of
    Trustees, Leland Stanford Jr. University and redistributed under the
    terms of the Perl Artistic License.
    2. LightBox 2 is distributed under the Creative Commons Attribution 2.5
    License - http://creativecommons.org/licenses/by/2.5/ and is free for
    use in both personal and commercial projects. This project is not
    endorsed by the LightBox author(s).

1. Purpose and Description

    A PHP/GD AFS fileserver disk utilization grapher inspired by the
    original tcl/tk afsfree script.

2. How To Use

    Unpack the distribution in a directory somewhere on your webserver.
    For example, ~user/public_html/phpafsfree

    Make the script aware of your servers. This is easiest to accomplish
    with symlinks, however if you plan to customize one or more servers,
    you can copy the script instead.
    > cd ~user/public_html/phpafsfree
    > cd servers
    > ln -s ../image.php afsfileserver1.php
      where afsfileserver1 is the name of one of your AFS fileservers.
    Repeat as desired for as many of your fileservers as wanted.

    Optionally, create phpafsfree/notes/afsfileserver1.txt with a short
    plaintext note to be displayed when an admin clicks on the server's
    detail page.

    Verify the location of perl on your system and if it is not
    /usr/bin/perl, modify partinfo.pl accordingly.

    Launch your favorite web browser and visit the page you just created:
    http://yourdomain.dom/~user/phpafsfree. You should see a table of the
    server(s) you configured. Clicking on the thumbnail image will display
    the server details in a new browser window.

3. Fixing problems

    If you run into any problems, double-check everything. Ensure that
    your web server supports PHP with GD extensions. If not, you'll need
    to recompile PHP.

    Verify the location of perl in partinfo.php and the location of the
    vos binary in image.php and partinfo.php. Correct these if they are
    wrong.

    If you are generating graphics but things don't look quite right
    (likely if any of your server /vicepX partitions are over 1TB)
    edit image.php and tweak the settings. The width of the image
    should adjust automatically depending on the number of partitions
    and your settings for $barwidth, $separation, and $margin. Setting
    $dropshadowpx to 0 will disable it. Lastly, you may want to adjust
    $reservepercent as the default of 10% is a bit generous. Similarly,
    you may want to adjust the parameters within details.php used to call
    partinfo.pl. It has separate documentation.
