module.exports = {
  globDirectory: '../../',
  globPatterns: ['**/*.{js,css,html,png,svg,woff2}'],
  swDest: 'sw.js',
  runtimeCaching: [
    {
      urlPattern: /\/wp-json\/roro\/v1\/.*$/,
      handler: 'NetworkFirst',
      options: { cacheName: 'api', networkTimeoutSeconds: 3 },
    },
  ],
};
