// Watch Files For Changes
'use strict';
const gulp = require('gulp');

const { config } = require('../config');
const {
  buildCSSThemeDev,
  buildCSSContentBlocksDev,
  buildCSSFeaturesDev,
  buildCssSdcDev,
  cssMail,
  cssEditor
} = require('./01-css');
const { cssLint } = require('./01-css-lint');
const { buildJsGlobalDev, buildJsComponentsDev } = require('./01-js');
const { jsLint } = require('./01-js-lint');
const { optimizeImages } = require('./01-images');

function watch(done) {
  // Lint the Sass + compile same sources to CSS
  gulp.watch(config.css.src, gulp.series(
    cssLint,
    buildCSSThemeDev,
    buildCSSContentBlocksDev,
    buildCSSFeaturesDev,
    buildCssSdcDev,
  ));

  // separate the mail and editor css
  gulp.watch(config.css.mail.src, gulp.series(cssMail));
  gulp.watch(config.css.editor.src, gulp.series(cssEditor));

  // lint the src JS + compile same sources
  // don't bundle the js like we did with the CSS, doesn't seem to want to compile then
  // gulp.watch(config.js.src, ['js:lint', 'js:global:dev', 'js:components:dev']);
  gulp.watch(config.js.global.src, gulp.series(jsLint, buildJsGlobalDev));
  gulp.watch(config.js.components.src, gulp.series(jsLint, buildJsComponentsDev));

  done();
}

gulp.task('watch', function (done) {
  watch(done);
});

/**
 * Exports
 */
exports.watch = watch;
