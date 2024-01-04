'use strict'

const { resolve } = require('path')

module.exports = function (moduleOptions) {
    const options = {
        ...moduleOptions,
        ...this.options.monocle
    }

    this.addPlugin({
        fileName: 'monocle.js',
        options,
        ssr: false,
        src: resolve(__dirname, 'plugin.js')
    })

    this.addTemplate({
        fileName: 'monocle.vue',
        src: resolve(__dirname, 'monocle.vue')
    })
}

module.exports.meta = require('../package.json')
