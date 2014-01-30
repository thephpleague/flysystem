<h1>Project Websites</h1>

<h2>Introduction</h2>

<p>The following document explains how to quickly generate fully responsive, branded project websites for The PHP League. These website are created using <a href="https://sculpin.io/">Sculpin</a>, and are designed to be hosted on <a href="http://pages.github.com/">GitHub Pages</a>.</p>

<h2>Repository setup</h2>

<p>The website source code should be kept in new "orphaned" branches within the existing project repository. This keeps the project files and public website neatly in one spot. The Sculpin source files will be placed in a <code>gh-pages-sculpin</code> branch, and the published files in the standard GitHub Pages branch, <code>gh-pages</code>.</p>

<h2>Install Sculpin</h2>

<p>Before doing anything, start by <a href="https://sculpin.io/download/">installing Sculpin</a>. Consider dropping it in your <code>bin</code> folder to simply run it as <code>sculpin</code>. Sculpin works very similar to Composer.</p>

<h2>Project setup</h2>

<p>To create a new website for your project, following these instructions.</p>

<pre><code class="bash"># Go to your project's root folder.
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
</code></pre>

<h2>How to build</h2>

<h3>Development server</h3>

<p>Start by enabling the development server. Once started, the website will be available at <a href="http://localhost:8000">http://localhost:8000</a>. Stop the server by pressing <code>CONTROL-C</code>.</p>

<pre><code class="bash"># Go to your project's root folder.
cd your-project-folder

# Make sure you're on the gh-pages-sculpin branch.
# Note, the gh-pages-sculpin branch won't appear
# in the list of branches until you make your
# first commit.
git checkout gh-pages-sculpin

# Start the Sculpin development server.
sculpin generate --watch --server
</code></pre>

<h3>Project settings</h3>

<p>Set your project specific settings in the <code>app/config/sculpin_site.yml</code> file. This includes the project <code>title</code>, <code>tagline</code>, <code>description</code>, <code>google_analytics_tracking_id</code> and <code>menu</code>. Note that all links in the menu must fall under a section. Examples exist to get you started.</p>

<h3>Project pages</h3>

<p>To add pages to your website, simply create Markdown files within the <code>source</code> directory. Note that the filename will become it's URL. For example, <code>simple-example.md</code> becomes <code>http://your-domain.com/simple-example/</code>. You can set the page <code>&lt;title&gt;</code> at the top of the file. Here is a simple example page:</p>

<pre><code class="markdown">---
layout: layout
title: Simple example
---

Simple example
==============

Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
</code></pre>

<p>When showing PHP code examples, but sure to use the <code>~~~.language-php</code> Markdown code block syntax.</p>

<h3>Project icons</h3>

<p>If you wish to customize your website with a logo icon and Favicon, simply add these files to your <code>source/img</code> path:</p>

<ul>
<li><code>apple-touch-icon-precomposed.png</code></li>
<li><code>favicon.ico</code></li>
<li><code>logo.png</code> (must be <code>50 x 40px</code>)</li>
</ul>

<h3>Domain name</h3>

<p>To use a custom domain name (ie. <code>your-project.thephpleague.com</code>), create a file in the <code>source</code> folder called <code>CNAME</code> and put your domain in that file. For example:</p>

<pre><code>your-project.thephpleague.com
</code></pre>

<p>Next, set your domain DNS settings as per the GitHub Pages <a href="https://help.github.com/articles/setting-up-a-custom-domain-with-pages#setting-up-dns">documentation</a>.</p>

<p>Finally, if you haven't done so yet, be sure to update your <a href="https://github.com/thephpleague/thephpleague.github.com/blob/master/_data/packages.yml">package's website address</a> on the main League website.</p>

<h3>Google Analytics</h3>

<p>A Google Analytics account has been created specifically for The PHP League. Ideally, all project websites are setup within this account. That allows all members to view the statistics of each participating project, as well as the main website. To gain access to this account, or to generate a new tracking ID for your project, please contact <a href="https://twitter.com/reinink">Jonathan Reinink</a>.</p>

<h3>Committing changes</h3>

<p>When ready, commit your changes to the <code>gh-pages-sculpin</code> branch.</p>

<pre><code class="markdown">git add -A
git commit .
git push origin gh-pages-sculpin
</code></pre>

<h2>How to publish</h2>

<p>To publish your website, you must create a production ready version of your static site. This will be hosted on the <code>gh-pages</code> branch. To make this easy to do, we'll actually setup another GIT instance within the <code>output_prod</code> folder. This may seem odd at first, but it basically allows you to work on two branches simultaneously.</p>

<pre><code class="bash"># Go to your project's root folder.
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
</code></pre>

<h2>Need help?</h2>

<p>If you need help, please contact <a href="https://twitter.com/reinink">Jonathan Reinink</a>.</p>
