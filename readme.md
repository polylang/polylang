# ![Polylang](.github/assets/polylang-logo.svg) [POLYLANG](https://wordpress.org/plugins/polylang/)

Welcome to the Polylang repository on GitHub. Here you can browse the source, discuss open issues and keep track of the development.

If you are not a developer, we recommend to [download Polylang](https://wordpress.org/plugins/polylang/) from WordPress directory.

## [Pre-requisites](#pre-requisites)

Before starting, make sure that you have the following software installed and working on your computer:

1. A local [WordPress](https://wordpress.org/support/article/how-to-install-wordpress/) (6.2 or later) instance
2. [Git](https://git-scm.com/book/en/v2/Getting-Started-Installing-Git) to clone the Polylang repository (or your fork of the Polylang repository).
3. [Node.js](https://nodejs.org/en/download/) which provides [NPM](https://docs.npmjs.com/). They are both required by [Webpack](https://webpack.js.org/guides/getting-started/) that Polylang uses to build and minify CSS and javascript files. We recommend to install Node.js LTS version.
4. [Composer](https://getcomposer.org/doc/00-intro.md) because Polylang uses its autoloader to work and it is required to install development tools such as PHP CodeSniffer that ensures your code follows coding standards.

## [How to set up Polylang](#how-to-setup-polylang)

The simplest way is to clone locally this repository and build it directly in your local WordPress instance by following the steps below:

1. Go to your local WordPress instance wp-content/plugins/ folder:<br/>
`cd your/local/wordpress/path/wp-content/plugins`
2. Clone there the polylang repository (or your fork) from GitHub:<br/>
`git clone https://github.com/polylang/polylang.git`
3. Go to your local Polylang clone folder from there: `cd polylang`
4. Run the composer command: `composer build`
5. Activate Polylang as if you had installed it from WordPress.org:<br/>
See <https://wordpress.org/plugins/polylang/#installation>

**Note**: we recommend for Windows users to use `Git Bash` provided with [Git for Windows](https://git-scm.com/download/win) instead of the command or powershell terminal.
