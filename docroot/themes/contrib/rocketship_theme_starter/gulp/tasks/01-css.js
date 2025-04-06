// Compile Our Sass
'use strict';

const gulp = require('gulp'),
  sass = require('gulp-sass')(require('sass')),
  rename = require('gulp-rename'),
  replace = require('gulp-replace'),
  flatten = require('gulp-flatten'),
  sourcemaps = require('gulp-sourcemaps'),
  autoprefixer = require('gulp-autoprefixer'),
  sassGlob = require('gulp-sass-glob'),
  cleanCSS = require('gulp-clean-css'),
  gulpif = require('gulp-if'),
  notify = require('gulp-notify'),
  bs = require('browser-sync').get('bs'),
  fs = require('fs'),
  crypto = require('crypto');


const { config } = require('../config');
const { errorNotification } = require('./00-setup');

/**
 * functions for compiling css per part
 */
function buildCSSProd(mySrc, myDest) {
  return gulp.src( mySrc )
    // Use globbing so we don't need to manually add the imports.
    .pipe(sassGlob())
    .pipe(sass({
      outputStyle: 'expanded'
  }))
    .pipe(autoprefixer())
    // Cache busting the font files.
    .pipe(replace(/\[\[:FONT:([^:]+):FONT:\]\]/g, function (match, p1, offset, string, groups) {
      const buffer = fs.readFileSync(myDest + '/' + p1);
      const hash = crypto.createHash('md5').update(buffer).digest('hex');

      return p1 + '?' + hash.substring(0, 6);
    }))
    // Replace relative paths for files.
    .pipe(flatten())
    .pipe(gulp.dest(myDest));
}

/**
 * functions for compiling css per part
 */
function buildCssComponentsProd(mySrc) {
  return gulp.src( mySrc, { base: './' } )
    .pipe(sassGlob())
    .pipe(sass({
      outputStyle: 'expanded'
    }))
    .pipe(autoprefixer())
    .pipe(gulp.dest('./'));
}

function buildCSSDev(mySrc, myDest) {
  return gulp.src( mySrc )
    // Use globbing so we don't need to manually add the imports
    .pipe(sassGlob().on('error', function (err) {
      // Message errors to Mac OS X, Linux or Windows
      notify().write(err.formatted);
      this.emit('end');
    }))
    // add sourcemaps
    .pipe(sourcemaps.init())
    // An identity sourcemap will be generated at this step
    .pipe(sourcemaps.identityMap())
    .pipe(sass({
      outputStyle: 'expanded' // don't minify here because errors with maps
    }).on('error', function (err) {
      errorNotification(this, err);
    }))
    .pipe(autoprefixer().on('error', function (err) {
      errorNotification(this, err);
    }))
    .pipe(replace(/\[\[:FONT:([^:]+):FONT:\]\]/g, function (match, p1, offset, string, groups) {
      const buffer = fs.readFileSync(myDest + '/' + p1);
      const hash = crypto.createHash('md5').update(buffer).digest('hex');

      return p1 + '?' + hash.substring(0, 6);
    }))
    .pipe(
      sourcemaps.write('./' + myDest, {
        sourceMappingURL: function (file) {
          return file.relative + '.map';
        }
      }).on('error', function (err) {
        errorNotification(this, err);
      })
    )

    // Replace relative paths for files.
    .pipe(flatten())
    // Cache busting the font files.
    .pipe(gulp.dest(myDest))
    // fix for use with browsersync url
    .pipe(
      gulpif(
        config.arg.url !== false,
        bs.stream({match: '**/*.css'})
      )
    );
}

function buildCssComponentsDev(mySrc) {
  return gulp.src( mySrc, { base: './'} )
    // Use globbing so we don't need to manually add the imports
    .pipe(sassGlob().on('error', function (err) {
      // Message errors to Mac OS X, Linux or Windows
      notify().write(err.formatted);
      this.emit('end');
    }))
    // add sourcemaps
    .pipe(sourcemaps.init())
    // An identity sourcemap will be generated at this step
    .pipe(sourcemaps.identityMap())
    .pipe(sass({
      outputStyle: 'expanded' // don't minify here because errors with maps
    }).on('error', function (err) {
      errorNotification(this, err);
    }))
    .pipe(autoprefixer().on('error', function (err) {
      errorNotification(this, err);
    }))
    // Cache busting the font files.
    .pipe(gulp.dest('./'))
    // fix for use with browsersync url
    .pipe(
      gulpif(
        config.arg.url !== false,
        bs.stream({match: '**/*.css'})
      )
    );
}

/**
 * Separate tasks so we can run them async
 */

// -- for development

const buildCSSThemeDev = function() {
  return buildCSSDev(config.css.components.theme.src, config.css.dest);
};

const buildCSSContentBlocksDev = function() {
  return buildCSSDev(config.css.components.contentblocks.src, config.css.dest);
};

const buildCssSdcDev = () => {
  return buildCssComponentsDev(config.css.components.sdc.src);
};

const buildCSSFeaturesDev = function() {
  return buildCSSDev(config.css.components.features.src, config.css.dest);
};

gulp.task('css:theme:dev', async function () {
  return buildCSSThemeDev();
});

gulp.task('css:contentblocks:dev', async function () {
  return buildCSSContentBlocksDev();
});

gulp.task('css:sdc:dev', async function (){
  return buildCssSdcDev();
});

gulp.task('css:features:dev', async function () {
  return buildCSSFeaturesDev();
});

gulp.task('css:message:dev', function (done) {
  notify().write(
    `
    ===========================================================================
    Don't commit development CSS. Use 'gulp css:prod' instead.
    Sourcemap files won't work on environments and lead to errors & overhead.
    ===========================================================================`
  );
  done();
});


// -- for production
gulp.task('css:theme:prod', async function () {
  return buildCSSProd(config.css.components.theme.src, config.css.dest);
});

gulp.task('css:contentblocks:prod', async function () {
  return buildCSSProd(config.css.components.contentblocks.src, config.css.dest);
});

gulp.task('css:sdc:prod', async function (){
  return buildCssComponentsProd(config.css.components.sdc.src);
});

gulp.task('css:features:prod', async function () {
  return buildCSSProd(config.css.components.features.src, config.css.dest);
});

/**
 *  Copy & rename the css-files needed for mail and editor
 *  the 'watch' task should also listen to changes in that src,
 *  in order to properly update properly
 */
const cssMail = function() {
  return gulp.src(config.css.mail.src)
    .pipe(rename('mail.css'))
    .pipe(cleanCSS({compatibility: '*'})) //'*' = default, includes IE10+
    .pipe(gulp.dest(config.paths.base));
};

const cssEditor = function() {
  return gulp.src(config.css.editor.src)
    .pipe(rename('editor.css'))
    .pipe(gulp.dest(config.css.dest));
};

gulp.task('css:mail', function () {
  return cssMail();
});

gulp.task('css:editor', function () {
  return cssEditor();
});

/**
 * Minify fonts css because it will be injected in header
 */

const cssFonts = function() {
  return gulp.src(config.css.dest + '/style.fonts.css')
    .pipe(cleanCSS({compatibility: '*'})) //'*' = default, includes IE10+
    .pipe(gulp.dest(config.css.dest));
};

gulp.task('css:fonts', function () {
  return cssFonts();
});

/**
 * bundle the css tasks into 1 big task
 */
gulp.task('css:dev', gulp.series(
  'css:message:dev',
  'css:theme:dev',
  'css:contentblocks:dev',
  'css:features:dev',
  'css:sdc:dev',
  'css:fonts',
  'css:mail',
  'css:editor'
));

gulp.task('css:prod', gulp.series(
  'css:theme:prod',
  'css:contentblocks:prod',
  'css:features:prod',
  'css:sdc:prod',
  'css:fonts',
  'css:mail',
  'css:editor'
));

/**
 * Exports
 */
exports.cssDev = gulp.series(
  'css:message:dev',
  'css:theme:dev',
  'css:contentblocks:dev',
  'css:features:dev',
  'css:sdc:dev',
  'css:fonts',
  'css:mail',
  'css:editor'
);
exports.cssProd = gulp.series(
  'css:theme:prod',
  'css:contentblocks:prod',
  'css:features:prod',
  'css:sdc:prod',
  'css:fonts',
  'css:mail',
  'css:editor'
);

exports.buildCSSThemeDev = buildCSSThemeDev;
exports.buildCSSContentBlocksDev = buildCSSContentBlocksDev;
exports.buildCssSdcDev = buildCssSdcDev;
exports.buildCSSFeaturesDev = buildCSSFeaturesDev;
exports.build = buildCSSFeaturesDev;

exports.cssMail = cssMail;
exports.cssEditor = cssEditor;
