'use strict';
module.exports = function(grunt) {

  grunt.initConfig({
    jshint: {
      options: {
        jshintrc: '.jshintrc'
      },
      all: [
        'Gruntfile.js',
        'assets/js/*.js',
        '!assets/js/scripts.min.js'
      ]
    },
    sass: {
        dev: {
            files: {
                'assets/css/styles.css': 'assets/sass/styles.scss'
            },
            options: {
                bundleExec: true,
                require: ['bourbon'],
                compass: false,
                debugInfo: true,
                lineNumbers: true,
                sourcemap: true,
                precision: 7,
                loadPath: 'assets/bower_components/',
                style: 'expanded'
            }
        },
        dist: {
            files: {
                'assets/css/styles.min.css': 'assets/sass/styles.scss'
            },
            options: {
                bundleExec: true,
                compass: false,
                debugInfo: false,
                lineNumbers: false,
                sourcemap: false,
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
          'assets/bower_components/jquery/dist/jquery.min.js',
          'assets/bower_components/photoset-grid/jquery.photoset-grid.min.js',
          'assets/bower_components/RWD-FitText.js/jquery.fittext.min.js',
          'assets/bower_components/enquire/dist/enquire.min.js',
          'assets/bower_components/snapjs/src/snap.js',
          'assets/bower_components/blueimp-gallery/js/blueimp-gallery.min.js',
          'assets/bower_components/iscroll/build/iscroll.js',
          'assets/js/source/main.js'
          ]
        }
      }
    },
    rsync: {
        options: {
            args: ["--verbose --update"],
            exclude: ['node_modules', 'assets/sass', 'assets/bk*', 'assets/bower_components', 'assets/css', '.*', '.sass-cache/', 'Gemfile', 'Gemfile.lock', 'Gruntfile.js', '*.md','screenshot.png', 'lang', 'package.json', 'bower.json'],
            recursive: true
        },
        stage: {
            options: {
                src: "",
                dest: "",
                host: "",
                syncDestIgnoreExcl: false
            }
        },
        prod: {
            options: {
                src: "../pressroom/",
                dest: "",
                host: "",
                syncDestIgnoreExcl: false
            }
        }
    },
    watch: {
      js: {
        files: [
          '<%= jshint.all %>',
          'js/main.js'
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
        // tasks: ['clean','uglify', 'sass', 'cssmin']
      },
      livereload: {
        // Browser live reloading
        // https://github.com/gruntjs/grunt-contrib-watch#live-reloading
        options: {
          livereload: true
        },
        files: [
          'assets/css/styles.min.css',
          'assets/js/scripts.min.js',
          '**/*.php'
        ]
      }
    },
    clean: {
      dist: [
        'assets/css/styles.min.css',
        'assets/js/scripts.min.js'
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

  // Register tasks
  grunt.registerTask('default', [
    'clean',
    'sass',
    'uglify',
    'cssmin'
  ]);
  grunt.registerTask('dev', [
    'watch'
  ]);
  grunt.registerTask('dploy', ['rsync:prod']);
};
