import Vue from 'vue'

export interface MonocleOptions {
  /**
   * Public API Token to get Monocle script
   */
  token: string
}

export interface MonocleInstance {
  /**
   * Options
   */
  options: MonocleOptions

  /**
   * Destroy Monocle
   */
  destroy(): void

  /**
   * Returns a verify bundle
   */
  getBundle(): Promise<string>

  /**
   * Initialize Monocle
   */
  init(): Promise<any>

  /**
   * Listen to Monocle events
   */
  on(event: string, callback: Function): void

  /**
   * Refresh Monocle
   */
  refresh(): void
}

declare module 'vue/types/vue' {
  interface Vue {
    $monocle: MonocleInstance
  }
}
