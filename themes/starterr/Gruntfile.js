'use strict';
module.exports = function(grunt) {
    grunt.initConfig({
        jshint: {
          options: {
            jshintrc: '.jshintrc'
          },
          all: [
            'Gruntfile.js',
            'assets/js/source/*.js',
            '!assets/js/scripts.min.js'
          ]
        },
        sass: {
            dev: {
                files: {
                    'assets/css/styles.css': 'assets/sass/styles.scss',
                    'assets/css/toc.css': 'assets/sass/toc.scss'
                },
                options: {
                    compass: false,
                    debugInfo: true,
                    lineNumbers: true,
                    sourcemap: 'auto',
                    precision: 7,
                    loadPath: 'assets/bower_components/',
                    style: 'expanded'
                }
            },
            dist: {
                files: {
                    'assets/css/styles.css': 'assets/sass/styles.scss',
                    'assets/css/toc.css': 'assets/sass/toc.scss'
                },
                options: {
                    debugInfo: false,
                    lineNumbers: false,
                    sourcemap: 'none',
                    precision: 7,
                    loadPath: 'assets/bower_components/',
                    style: 'compressed'
                }
            }
        },
        cssmin: {
          options: {
            keepSpecialComments:0
          },
          combine: {
            files: {
              'assets/css/styles.min.css': [
                ''
              ]
            }
          }
        },
        uglify: {
            options: {
            compress: false
          },
          dist: {
            files: {
              'assets/js/scripts.min.js': [
              'assets/bower_components/modernizer/modernizr.js',
              'assets/bower_components/textFit/textFit.min.js',
              'assets/bower_components/fluidvids/src/fluidvids.js',
              'assets/bower_components/fastclick/lib/fastclick.js',
              'assets/js/source/_main__init.js'
              ],
              'assets/js/toc.min.js': [
              'assets/bower_components/modernizer/modernizr.js',
              'assets/bower_components/textFit/textFit.min.js',
              'assets/bower_components/swiper/src/idangerous.swiper.js',
              'assets/bower_components/fastclick/lib/fastclick.js',
              'assets/js/source/_toc__init.js'
              ]
            }
          }
        },
        rsync: {
            options: {
                args: ["--verbose --no-p --no-g --chmod=ugo=rwX"],
                exclude: ['node_modules', '.bowerrc', '.editorconfig', '.gitignore', '.jshintrc','assets/sass', 'assets/bk*', 'assets/js/source', 'assets/bower_components', '*.map', '.*', '.sass-cache/', 'Gemfile', 'version.json', 'Gemfile.lock', 'Gruntfile.js', '*.md', 'screenshot.png', 'lang', 'package.json', 'bower.json'],
                recursive: true
            },
            stage: {
                options: {
                    src: "../starterr/",
                    dest: "",
                    host: "",
                    syncDestIgnoreExcl: false
                }
            },
            prod: {
                options: {
                    src: "../starterr/",
                    dest: "",
                    host: "",
                    syncDestIgnoreExcl: false
                }
            }
        },
        ver: {
          myapp: {
            phases: [
              {
                files: [
                  'assets/css/*.css',
                  'assets/css/*.*.css',
                  'assets/js/*.min.js',
                  'assets/js/*.*.min.js'
                ],
                references: [
                  'layouts/basic-article.php',
                  'layouts/toc.php'
                ]
              }
            ],
            versionFile: 'version.json'
          }
        },
        watch: {
          js: {
            files: [
              '<%= jshint.all %>',
              'js/_toc__init.js',
              'js/_main__init.js'
            ],
            tasks: ['jshint', 'uglify']
          },
          sass: {
            files: [
              'assets/sass/*.sass',
              'assets/sass/*.scss',
              'assets/sass/base/*.scss',
              'assets/sass/components/*.scss',
              'assets/sass/layout/*.scss',
              'assets/sass/utility/*.scss',
              'assets/sass/pages/*.scss'
            ],
            tasks: ['clean','uglify', 'sass:dev']
          },
          livereload: {
            // Browser live reloading
            // https://github.com/gruntjs/grunt-contrib-watch#live-reloading
            options: {
              livereload: false
            },
            files: [
              'assets/css/styles.css',
              'assets/js/scripts.min.js',
              '**/*.php'
            ]
          }
        },
        clean: {
          dist: [
            'assets/css/*.css',
            'assets/css/*.map',
            'assets/js/scripts.*.js'
          ]
        }
    });

    // Load tasks
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-contrib-jshint');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-sass');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks("grunt-rsync");
    grunt.loadNpmTasks('grunt-ver');

    // Register tasks
    grunt.registerTask('default', [
    'clean',
    'sass:dist',
    'uglify',
    'ver:myapp',
    ]);

    grunt.registerTask('dev', [
    'watch'
    ]);

    grunt.registerTask('stage', ['rsync:stage']);

    grunt.registerTask('deploy', ['rsync:prod']);
};
