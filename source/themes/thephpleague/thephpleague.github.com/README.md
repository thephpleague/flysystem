# Project Websites

## Introduction

The following document explains how to quickly generate fully responsive, branded project websites for The PHP League. These website are created using [Sculpin](https://sculpin.io/), and are designed to be hosted on [GitHub Pages](http://pages.github.com/).

## Repository setup

The website source code should be kept in new "orphaned" branches within the existing project repository. This keeps the project files and public website neatly in one spot. The Sculpin source files will be placed in a `gh-pages-sculpin` branch, and the published files in the standard GitHub Pages branch, `gh-pages`.

## Install Sculpin

Before doing anything, start by [installing Sculpin](https://sculpin.io/download/). Consider dropping it in your `bin` folder to simply run it as `sculpin`. Sculpin works very similar to Composer.

## Project setup

To create a new website for your project, following these instructions.

~~~bash
# Go to your project's root folder.
# This guide assumes that you already have GIT
# initialized for this project in this folder.
cd your-project-folder

# Create new branch for the Sculpin source files.
# Note, the gh-pages-sculpin branch won't appear
# in the list of branches until you make your
# first commit.
git checkout --orphan gh-pages-sculpin

# Remove all files from the old working tree.
git rm -rf .

# Run the boilerplate setup script.
# This will simply create some default files and folders
# (required by Sculpin) in the current folder.
curl -sS https://raw.github.com/thephpleague/thephpleague.github.com/project-website-theme/boilerplate.php | php

# Install the League's theme.
# If you do not have Sculpin installed yet, do that now.
# In the future, simply run "sculpin update" to get
# the most current version of the League's theme.
sculpin install

# Set some project details.
# These can be changed at any time, so to start just
# set the title and tagline.
vim app/config/sculpin_site.yml

# Preview the website by starting the Sculpin
# development server.
# It will be available at http://localhost:8000.
# Quit the server with CONTROL-C.
sculpin generate --watch --server
~~~

## How to build

### Development server

Start by enabling the development server. Once started, the website will be available at [http://localhost:8000](http://localhost:8000). Stop the server by pressing `CONTROL-C`.

~~~bash
# Go to your project's root folder.
cd your-project-folder

# Make sure you're on the gh-pages-sculpin branch.
# Note, the gh-pages-sculpin branch won't appear
# in the list of branches until you make your
# first commit.
git checkout gh-pages-sculpin

# Start the Sculpin development server.
sculpin generate --watch --server
~~~

### Project settings

Set your project specific settings in the `app/config/sculpin_site.yml` file. This includes the project `title`, `tagline`, `description`, `google_analytics_tracking_id` and `menu`. Note that all links in the menu must fall under a section. Examples exist to get you started.

### Project pages

To add pages to your website, simply create Markdown files within the `source` directory. Note that the filename will become it's URL. For example, `simple-example.md` becomes `http://your-domain.com/simple-example/`. You can set the page `<title>` at the top of the file. Here is a simple example page:

~~~markdown
---
layout: layout
title: Simple example
---

Simple example
==============

Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
~~~

When showing PHP code examples, but sure to use the `~~~.language-php` Markdown code block syntax.

### Project icons

If you wish to customize your website with a logo icon and Favicon, simply add these files to your `source/img` path:

- `apple-touch-icon-precomposed.png`
- `favicon.ico`
- `logo.png` (must be `50 x 40px`)

### Domain name

To use a custom domain name (ie. `your-project.thephpleague.com`), create a file in the `source` folder called `CNAME` and put your domain in that file. For example:

~~~
your-project.thephpleague.com
~~~

Next, set your domain DNS settings as per the GitHub Pages [documentation](https://help.github.com/articles/setting-up-a-custom-domain-with-pages#setting-up-dns).

Finally, if you haven't done so yet, be sure to update your [package's website address](https://github.com/thephpleague/thephpleague.github.com/blob/master/_data/packages.yml) on the main League website.

### Google Analytics

A Google Analytics account has been created specifically for The PHP League. Ideally, all project websites are setup within this account. That allows all members to view the statistics of each participating project, as well as the main website. To gain access to this account, or to generate a new tracking ID for your project, please contact [Jonathan Reinink](https://twitter.com/reinink).

### Committing changes

When ready, commit your changes to the `gh-pages-sculpin` branch.

~~~markdown
git add -A
git commit .
git push origin gh-pages-sculpin
~~~


## How to publish

To publish your website, you must create a production ready version of your static site. This will be hosted on the `gh-pages` branch. To make this easy to do, we'll actually setup another GIT instance within the `output_prod` folder. This may seem odd at first, but it basically allows you to work on two branches simultaneously.

~~~bash
# Go to your project's root folder.
cd your-project-folder

# Remove the output_prod folder (if it exists).
rm -rf output_prod

# Make sure you have the most current version of
# the League's theme.
sculpin update

# Generate the published website.
sculpin generate --env=prod

# Go to the output_prod folder.
cd output_prod

# Commit changes and push live.
# Be sure to update the repository URL for your project.
# You do not have to change the commit message.
# The gh-pages branch will only ever have one commit.
git init
git remote add origin https://github.com/thephpleague/YOUR-PROJECT.git
git add -A
git commit -m "Publish"
git push --force origin HEAD:gh-pages

# Go back to your project's root folder.
cd ..

# Remove the output_prod folder.
# You'll have less issues if you simply repeat these
# steps in their entirety next time you publish.
rm -rf output_prod
~~~

## Need help?

If you need help, please contact [Jonathan Reinink](https://twitter.com/reinink).