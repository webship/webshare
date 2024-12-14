const postcssRtlLogicalProperties = require('postcss-rtl-logical-properties');
const postcssRTLCSS = require('postcss-rtlcss');
const Mode = require('postcss-rtlcss').Mode;

module.exports = (ctx) => ({
  map: !ctx.env || ctx.env !== 'production' ? { inline: false } : false,
  plugins: [
    require('postcss-custom-properties')({
      preserve: false,
      importFrom: [
        'css/base/variables.css'
      ]
    }),
    require('postcss-calc'),
    require('autoprefixer')({
      cascade: false
    }),
    postcssRtlLogicalProperties(),
    postcssRTLCSS({
      mode: Mode.override,
      ignorePrefixedRules: false
    })
  ]
});
