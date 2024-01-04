<template>
  <div>
    <button @click="verifyAnonMode">
      Click me
    </button>
    <client-only>
      <Monocle />
    </client-only>
  </div>
</template>
<script>
export default {
  data() {
    return {};
  },

  methods: {
    async verifyAnonMode() {
      try {
        if (process.server) return;

        const monocleBundle = await this.$monocle.getBundle();

        const { data } = await this.$axios.get('/api/verify-anon-mode', {
          params: {
            monocle_bundle: monocleBundle,
          },
        });

        this.$notify({
          message: data.message,
        });

      } catch (error) {
        console.log('error:', error);
        return false;
      }
    },

  },
};
</script>
