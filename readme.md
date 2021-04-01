![Polylang](assets/polylang-logo.png)

Welcome to the Polylang repository on GitHub. Here you can browse the source, look at open
issues and keep track of development.

If you are not a developer, please use the [Polylang plugin page](https://wordpress.org/plugins/polylang/) on [WordPress.org](https://wordpress.org).

## [Pre-requisites](#pre-requisites)

Before starting, make sure you have the following software installed and working on your machine:

1. A local [WordPress](https://wordpress.org/support/article/how-to-install-wordpress/) (5.1 or later) instance
2. [Git](https://git-scm.com/book/en/v2/Getting-Started-Installing-Git) to clone the Polylang repository (or your fork of the Polylang repository).
3. [NVM](https://github.com/nvm-sh/nvm) or [chocolatey](https://chocolatey.org/install) (on Windows OS) to install [Node.js](https://nodejs.org/en/download/) and [NPM](https://docs.npmjs.com/). They are required by [Webpack](https://webpack.js.org/guides/getting-started/) that Polylang uses to build and minifies CSS and javascript files.
4. [Composer](https://getcomposer.org/doc/00-intro.md) because Polylang uses its autoloader to work and it is required to install development tools such as PHP CodeSniffer that ensures your code follows code standards.

## [How to set up Polylang](#how-to-setup-polylang)

The most simple way is to clone locally this repository directly in your local WordPress instance `wp-content/plugins/` folder.

1. Go to your local WordPress instance wp-content/plugins/ folder:<br/>
`cd your/local/wordpress/path/wp-content/plugins`
2. Clone there the polylang repository (or your fork) from GitHub:<br/>
`git clone https://github.com/polylang/polylang.git`
3. Go to your local Polylang clone folder from there: `cd polylang`
4. Run the bash script: `./bin/build.sh`
5. Activate Polylang as if you had installed it from WordPress.org:<br/>
See <https://wordpress.org/plugins/polylang/#installation>

**Note**: we recommend for Windows user to use `Git Bash` provided with [Git for Windows](https://git-scm.com/download/win) instead of the command or powershell terminal.
