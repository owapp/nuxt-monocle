<template>
  <client-only>
    <div
      v-show="false"
      data-callback="monocleSuccessCallback"
      data-error-callback="monocleErrorCallback"
      data-onload-callback="monocleOnloadCallback"
      class="s-monocle"
    ></div>
  </client-only>
</template>

<script>
export default {
  beforeDestroy() {
    this.$monocle.destroy();
  },

  methods: {
    onError(message) {
      return this.$emit('error', message);
    },

    onSuccess(token) {
      return this.$emit('success', token);
    },

    onLoad() {
      return this.$emit('load');
    },
  },

  mounted() {
    this.$monocle.init().then(() => {
      this.$monocle.on('monocle-error', this.onError);
      this.$monocle.on('monocle-success', this.onSuccess);
      this.$monocle.on('monocle-onload', this.onLoad);
    });
  },
};
</script>
