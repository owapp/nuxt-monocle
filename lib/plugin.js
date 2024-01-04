import { EventEmitter } from 'events'
import Vue from 'vue'

const MONOCLE_SCRIPT_URL = 'https://mcl.spur.us/d/mcl.js'

class Monocle {
    constructor({ token }) {
        if (!token) {
            throw new Error('Monocle error: No token provided')
        }

        this._scipt = null
        this._monocle = null

        this._eventBus = null
        this._ready = false

        this.token = token
    }

    async getBundle() {
        if (process.server) {
            return Promise.resolve();
        }

        return new Promise(async (resolve, reject) => {
            if ('MCL' in window) {
                try {
                    // Rafraîchir les données avant de récupérer le bundle
                    await this.refresh();

                    const response = this._monocle.getBundle();
                    if (response) {
                        this._eventBus.emit('monocle-success', response);
                        resolve(response);
                    } else {
                        const errorMessage = 'Failed to execute';
                        this._eventBus.emit('monocle-error', errorMessage);
                        reject(errorMessage);
                    }
                } catch (error) {
                    // Gérer les erreurs potentielles lors du rafraîchissement
                    this._eventBus.emit('monocle-error', error);
                    reject(error);
                }
            } else {
                reject('MCL not found in window');
            }
        });
    }

    init() {
        if (this._ready) {
            return this._ready;
        }

        this._eventBus = new EventEmitter()
        this._scipt = document.createElement('script')

        const script = this._scipt

        script.id = "_mcl"
        script.async = true
        script.defer = true

        const params = []
        params.push('tk=' + this.token)
        script.src = MONOCLE_SCRIPT_URL + '?' + params.join('&')

        window.monocleSuccessCallback = (token) => this._eventBus.emit('monocle-success', token)
        window.monocleErrorCallback = () => this._eventBus.emit('monocle-error', 'Failed to execute')
        window.monocleOnloadCallback = () => this._eventBus.emit('monocle-onload')

        this._ready = new Promise((resolve, reject) => {
            script.addEventListener('load', () => {
                this._monocle = window.MCL
                resolve()
            })

            script.addEventListener('error', () => {
                document.head.removeChild(script)
                reject('Monocle error: Failed to load script')
                this._ready = null;
            })

            document.head.appendChild(script)
        })

        return this._ready
    }

    on(event, callback) {
        this._eventBus.on(event, callback)
    }

    async refresh() {
        if (this._monocle) {
            try {
                await this._monocle.refresh()
            } catch (error) {
                throw new Error('Monocle error: Failed to refresh')
            }
        }
    }

    destroy() {
        if (process.server) {
            return;
        }

        if (this._ready) {
            this._ready = false

            this._eventBus.removeAllListeners()
            this._eventBus = null

            const { head } = document

            const scripts = [...document.head.querySelectorAll('script')]
                .filter(script => script.src.includes('mcl.spur.us'))

            if (scripts.length) {
                scripts.forEach(script => head.removeChild(script))
            }

            this._scipt = null;
        }
    }
}

export default function (_, inject) {
    Vue.component('Monocle', () => import('./monocle.vue'))
    inject('monocle', new Monocle(<%= JSON.stringify(options) %>))
}
