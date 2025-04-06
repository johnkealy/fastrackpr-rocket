// Optimize images
'use strict';

const gulp = require('gulp'),
  imagemin = require('gulp-imagemin'),
  notify = require('gulp-notify');

const { config } = require('../config');
/**
 * generic error notifications
 *  - return using .on('error', function (err) { }
 */
function errorNotification(self, err) {
  // Message errors to Mac OS X, Linux or Windows
  notify().write(err.formatted);
  self.emit('end');
}
function optimizeImages() {
  const mySrc = config.images.src,
    myDest = config.images.dest;

  return gulp.src(mySrc)
    .pipe(imagemin([
        imagemin.mozjpeg({optimizationLevel: 2 }),
        imagemin.optipng({progressive: true}),
        imagemin.svgo(
          { removeUselessDefs: false },
          { cleanupIDs: false },
          { removeXMLNS: false },
          { removeViewBox: false }
        )
      ]
    ))
    .on('error', function (err) {
      return errorNotification(this, err);
    })
    .pipe(gulp.dest(myDest)).on('end', function (msg) {
      notify().write('Images optimized');
    });
}
gulp.task('images', function () {
  return optimizeImages();
});

/**
 * Exports
 */
exports.optimizeImages = optimizeImages;
