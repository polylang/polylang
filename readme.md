## Introduction

Since 3.0 release Polylang can't be used anymore out of the box from the zip file directly downloaded from the [GitHub repository](https://github.com/polylang/polylang).

So now, before being able to use Polylang, you need to build the Polylang project as it is described below.

## How to build Polylang

- clone polylang from GitHub: `git clone https://github.com/polylang/polylang.git`
- go to your local Polylang clone folder: `cd /your/local/path/polylang`
- run the `npm ci && npm run build` command

## Requirements

As the Polylang build process use [Webpack](https://webpack.js.org/) under the hood, you need to install [Node.js](https://nodejs.org/en/) to be able to use the npm package manager commands.

- Node.js LTS: 14.16.0 for now
