<?php

/*
 * This file is used to quickly generate boilerplate project
 * websites for The League of Extraordinary Packages.
 *
 * The following files and folders will be created:
 *
 *     sculpin.json
 *     .gitignore
 *     /app
 *       /config
 *           sculpin_kernel.yml
 *           sculpin_site.yml
 *     /source
 *       /img
 *       index.md
 *       installation.md
 *       simple-example.md
 */

$dir = getcwd();

if (!is_dir($dir . '/app')) {
    mkdir($dir . '/app');
}

if (!is_dir($dir . '/app/config')) {
    mkdir($dir . '/app/config');
}

if (!is_dir($dir . '/source')) {
    mkdir($dir . '/source');
}

if (!is_dir($dir . '/source/img')) {
    mkdir($dir . '/source/img');
}

if (!is_file($dir . '/app/config/sculpin_kernel.yml')) {

    $content = "sculpin:\n"
             . "    exclude:\n"
             . "        - sass/\n"
             . "sculpin_theme:\n"
             . "    theme: thephpleague/thephpleague.github.com";

    file_put_contents($dir . '/app/config/sculpin_kernel.yml', $content);
}

if (!is_file($dir . '/app/config/sculpin_site.yml')) {

    $content = "title: Library Title\n"
             . "tagline: The best PHP library ever\n"
             . "description: I'll appear in the meta description field.\n"
             . "google_analytics_tracking_id:\n"
             . "menu:\n"
             . "    Getting Started:\n"
             . "        Introduction: '/'\n"
             . "        Simple example: '/simple-example/'\n"
             . "        Installation: '/installation/'";

    file_put_contents($dir . '/app/config/sculpin_site.yml', $content);
}

if (!is_file($dir . '/sculpin.json')) {

    $content = "{\n"
             . "    \"require\": {\n"
             . "        \"thephpleague/thephpleague.github.com\": \"dev-project-website-theme\"\n"
             . "    },\n"
             . "    \"repositories\": [\n"
             . "        {\n"
             . "            \"type\": \"vcs\",\n"
             . "            \"url\": \"git@github.com:thephpleague/thephpleague.github.com.git\"\n"
             . "        }\n"
             . "    ]\n"
             . "}";

    file_put_contents($dir . '/sculpin.json', $content);
}

if (!is_file($dir . '/.gitignore')) {

    $content = "/.sculpin/\n"
             . "/output_*/\n"
             . "/source/themes/";

    file_put_contents($dir . '/.gitignore', $content);
}

if (!is_file($dir . '/source/index.md')) {

    $content = "---\n"
             . "layout: layout\n"
             . "---\n"
             . "\n"
             . "Introduction\n"
             . "============\n"
             . "\n"
             . "<ul class=\"quick_links\">\n"
             . "    <li><a class=\"github\" href=\"https://github.com/thephpleague/YOUR-PROJECT\">View Source</a></li>\n"
             . "    <li><a class=\"twitter\" href=\"https://twitter.com/YOUR-USERNAME\">Follow Author</a></li>\n"
             . "</ul>\n"
             . "\n"
             . "## Subtitle\n"
             . "\n"
             . "Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";

    file_put_contents($dir . '/source/index.md', $content);
}

if (!is_file($dir . '/source/simple-example.md')) {

    $content = "---\n"
             . "layout: layout\n"
             . "title: Simple example\n"
             . "---\n"
             . "\n"
             . "Simple example\n"
             . "==============";

    file_put_contents($dir . '/source/simple-example.md', $content);
}

if (!is_file($dir . '/source/installation.md')) {

    $content = "---\n"
             . "layout: layout\n"
             . "title: Installation\n"
             . "---\n"
             . "\n"
             . "Installation\n"
             . "============\n"
             . "\n"
             . "This library is available via Composer:\n"
             . "\n"
             . "~~~.language-javascript\n"
             . "{\n"
             . "    \"require\": {\n"
             . "        \"league/name\": \"dev-master\"\n"
             . "    }\n"
             . "}\n"
             . "~~~";

    file_put_contents($dir . '/source/installation.md', $content);
}
