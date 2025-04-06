// Default Task
'use strict';

const gulp = require('gulp');

const { setup } = require('./00-setup');
const { cssDev, cssProd } = require('./01-css');
const { cssLint } = require('./01-css-lint');
const { jsDev, jsProd } = require('./01-js');
const { jsLint } = require('./01-js-lint');
const { watch } = require('./03-watch');

gulp.task('dev:1', gulp.series(setup, cssLint, jsLint, cssDev, jsDev));
gulp.task('dev', gulp.series('dev:1'));

gulp.task('prod:1', gulp.series(setup, cssProd, jsProd));
gulp.task('prod', gulp.series('prod:1'));

gulp.task('default', gulp.series('dev', 'watch'));
