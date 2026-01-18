"use strict";

// Libraries
var path = require('path');
var gulp = require('gulp');
var sass = require('gulp-sass');
var log = require('fancy-log');
var rename = require('gulp-rename');
var sassVariables = require('gulp-sass-variables');
var sourcemaps = require('gulp-sourcemaps');
var strip = require('gulp-strip-comments');
var replace = require('gulp-replace');
var fs = require('fs');
var map = require('map-stream');

// Names
var coreName = 'template_core';
var sassDest = '/css/sass/';
var varFileName = '_variables.sass';

// Variables to replace in RTL file
var rtlVars = {
    '$is-ltr': false,
    '$left': 'right',
    '$right': 'left',
    '$flex-end': 'flex-start',
};

// RTL file name sufix
var rtlSufix = {suffix: '-rtl'};

// Template dirs
var src = __dirname;
var variablesFile = path.resolve(src, 'css', 'sass', '_variables.sass');
var sassDirs = [
    '!' + path.resolve(src, 'node_modules', '**', '*'),
    path.resolve(src, '**', '*.sass'),
    '!' + variablesFile
];

// Template core dirs
var coreSrc = src.replace(/([^\/\\]+)$/, coreName);
var coreSassDirs = [
    path.resolve(coreSrc, '**', '*.sass')
];

/**
 * Manage not required components building
 *
 * Ignore not necessary components and build components listed
 * in components.json file only
 */
var manageComponents = function() {
    var componentSettings = [];
    try {
        componentSettings = JSON.parse(fs.readFileSync('./components.json'));
    } catch(e) {}

    // Ignore components
    var ignoreComponent = [
        'search-on-map-box'
    ];

    for (var file of ignoreComponent) {
        if (componentSettings.required && componentSettings.required.indexOf(file) >= 0) {
            continue;
        }

        coreSassDirs.push('!' + path.resolve(coreSrc + '/components/' + file, '*'));
    }

    // Gallery components handler
    var galleryCompanents = [
        'listing-details-gallery',
        'listing-details-fancyapps-gallery',
        'listing-details-wide-gallery'
    ];
    for (var file of galleryCompanents) {
        if (componentSettings.listingDetailsGalleryComponents) {
            if (componentSettings.listingDetailsGalleryComponents.indexOf(file) < 0) {
                coreSassDirs.push('!' + path.resolve(coreSrc + '/components/' + file, '*'));
            }
        } else {
            if (file != galleryCompanents[0]) {
                coreSassDirs.push('!' + path.resolve(coreSrc + '/components/' + file, '*'));
            }
        }
    };
}

manageComponents();

/**
 * Fix destination directory path
 *
 * Sass files stored in css/sass should be compiled to the 
 * parent directory (css)
 * 
 * @return function rename results
 */
var fixDest = function() {
    return rename(function(path) {
        if (path.dirname == 'css/sass') {
            path.dirname = 'css';
        }
    });
}

/**
 * Fix css import in RTL mode
 *
 * Rename the css file name to it's rtl version in @import
 * directive
 * 
 * @return function replace results
 */
var fixImport = function(isRtl) {
    if (!isRtl) {
        return replace({});
    }

    return replace(/@import url\("([^"]+)\.([a-zA-Z]+)"\)/g, '@import url("$1-rtl.$2")');
}

/**
 * Migrate legacy style files content to main style.css file
 *
 * @return File<Buffer>
 */
var migration = function() {
    return map(function(file, callback) {
        var file_name = path.parse(file.path).base;

        if (file_name == 'style.css' || file_name == 'style-rtl.css') {
            var contents = file.contents.toString();
            var original = fs.readFileSync(src + '/css/original-style.css').toString();

            contents += '\n' + original;

            if (file_name == 'style-rtl.css') {
                var rtl_content = fs.readFileSync(src + '/css/rtl.css').toString();
                contents += '\n' + rtl_content;
            }

            file.contents = Buffer.from(contents);
        }

        callback(null, file)
    });
}

/**
 * Create gulp task
 * 
 * @param  array   sourceSrc - source files directory 
 * @param  string  destSrc   - destination directory 
 * @param  boolean isRtl     - compile in RTL mode
 * @return function          - gulp task
 */
var gulpTask = function(sourceSrc, destSrc, isRtl) {
    return gulp
        .src(sourceSrc)
        .pipe(fixImport(isRtl))
        .pipe(sassVariables(isRtl ? rtlVars : {}))
        .pipe(rename(isRtl ? rtlSufix : {}))
        //.pipe(sourcemaps.init())
        .pipe(sass().on('error', log.error))
        .pipe(strip.text())
        .pipe(migration())
        //.pipe(sourcemaps.write('.'))
        .pipe(fixDest())
        .pipe(gulp.dest(destSrc))
}

// Copy template variables file task
gulp.task('copy', function() {
    return gulp
        .src(src + sassDest + varFileName)
        .pipe(gulp.dest(coreSrc + sassDest))
});

// LTR task
gulp.task('sass:compile-template-ltr', function() {
    return gulpTask(sassDirs, src);
});

// RTL task
gulp.task('sass:compile-template-rtl', function() {
    return gulpTask(sassDirs, src, true);
});

// Template tasks
gulp.task('sass:compile-template', gulp.series('sass:compile-template-ltr', 'sass:compile-template-rtl'));

// LTR task
gulp.task('sass:compile-core-ltr', function() {
    return gulpTask(coreSassDirs, src);
});

// RTL task
gulp.task('sass:compile-core-rtl', function() {
    return gulpTask(coreSassDirs, src, true);
});

// Template core tasks
gulp.task('sass:compile-core', gulp.series('sass:compile-core-ltr', 'sass:compile-core-rtl'));

// Main sass task
gulp.task('sass', gulp.series('copy', 'sass:compile-template', 'sass:compile-core'));

// Watch task
gulp.task('watcher', function() {
    gulp.watch(coreSassDirs, gulp.series('sass:compile-core'));
    gulp.watch(sassDirs, gulp.series('sass:compile-template'));
    gulp.watch(variablesFile, gulp.series('copy'));
});

gulp.task('watch', gulp.series('sass', 'watcher'));
