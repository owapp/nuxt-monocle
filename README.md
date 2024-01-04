# Spur.us Monocle

[![npm version][npm-version-src]][npm-version-href]
[![npm downloads][npm-downloads-src]][npm-downloads-href]
[![Circle CI][circle-ci-src]][circle-ci-href]
[![Codecov][codecov-src]][codecov-href]
[![Standard JS][standard-js-src]][standard-js-href]

> 🤖 Simple and easy Spur.us Monocle integration with Nuxt.js

[📖 **Release Notes**](./CHANGELOG.md)

## Setup

1. Add `nuxt-monocle` dependency with `yarn` or `npm` into your project
2. Add `nuxt-monocle` to `modules` section of `nuxt.config.js`
3. Configure it:

```js
{
  modules: [
    [
      'nuxt-monocle', {
        /* Monocle options */
      }
    ],
  ]
}
```

using top level options

```js
{
  modules: [
    'nuxt-monocle',
  ],

  monocle: {
    /* Monocle options */
  },
}
```

## Configuration

```js
{
  // ...
  monocle: {
    token: String, // Token for requests
  },
  // ...
}
```

## Runtime config

```js
// nuxt.config.js
export default {
  publicRuntimeConfig: {
    monocle: {
      /* Monocle options */
      token: process.env.MONOCLE_TOKEN // for example
    }
  }
}
```

## Generate Monocle Token

You can generate token [by registering a new account](https://app.spur.us/auth/sign-in).

## Usage

### Monocle

1. Add `<monocle>` component inside your component:

```vue
<template>
  <monocle />
  <button @click="verifyAnonMode">Clic me</button>
</template>
```

2. Call `getBundle` to get Monocle bundle and send it to the server:

```js
async verifyAnonMode() {
  try {
    const bundle = await this.$monocle.getBundle()
    console.log('Monocle bundle:', bundle)

    // send bundle to server alongside your form data

    // at the end you need to refresh monocle
    await this.$monocle.refresh()
  } catch (error) {
    console.log('error:', error)
  }
},
```

3. Call `destroy` function inside `beforeDestroy` hook of the page. (This will remove Monocle scripts, styles and badge from the page)

```js
beforeDestroy() {
  this.$monocle.destroy()
}
```

See:
- [example](https://github.com/owapp/nuxt-monocle/tree/main/example)

### Server Side

When you send `data + bundle` to the server, you should verify the bundle on the server side to make sure it does not requested from a anon network.
You can find out how to verify bundle on the server side by looking at the [server controller](https://github.com/owapp/nuxt-monocle/blob/main/example/api/app/Http/Controllers/MonocleController.php) inside example.

## Development

1. Clone this repository
2. Install dependencies using `yarn install` or `npm install`
3. Start development server using `npm run dev`

## License

[MIT License](./LICENSE)

Copyright (c) owapp <contact@owapp.fr>

<!-- Badges -->
[npm-version-src]: https://img.shields.io/npm/dt/nuxt-monocle.svg?style=flat-square
[npm-version-href]: https://npmjs.com/package/nuxt-monocle
[npm-downloads-src]: https://img.shields.io/npm/v/nuxt-monocle/latest.svg?style=flat-square
[npm-downloads-href]: https://npmjs.com/package/nuxt-monocle
[circle-ci-src]: https://img.shields.io/circleci/project/github/owapp/nuxt-monocle.svg?style=flat-square
[circle-ci-href]: https://circleci.com/gh/owapp/nuxt-monocle
[codecov-src]: https://img.shields.io/codecov/c/github/owapp/nuxt-monocle.svg?style=flat-square
[codecov-href]: https://codecov.io/gh/nuxt-monocle
[standard-js-src]: https://img.shields.io/badge/code_style-standard-brightgreen.svg?style=flat-square
[standard-js-href]: https://standardjs.com
