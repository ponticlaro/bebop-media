/*global module:false*/
module.exports = function(grunt) {

  // Project configuration.
  grunt.initConfig({
    meta: {
      css_path: 'assets/css',
      sass_path: 'assets/sass',
      js_path: 'assets/js',
    },
    sass: {
        options: {
            style: 'compressed'
        },
        dist: {
            files: {
                '<%= meta.css_path %>/bebop-media.css': '<%= meta.sass_path %>/bebop-media.scss'
            }
        }
    },
    jshint: {
      all: [
        '<%= meta.js_path %>/modules/',
        '<%= meta.js_path %>/bebop-media.js'
      ]
    },
    concat: {
      main: {
        src: [
          '<%= meta.js_path %>/vendor/spin.js',
          '<%= meta.js_path %>/modules/regenerate-button.js',
          '<%= meta.js_path %>/bebop-media.js'
        ],
        dest: '<%= meta.js_path %>/bebop-media.min.js'
      }
    },
    uglify: {
      main: {
        src: '<%= concat.main.dest %>',
        dest: '<%= concat.main.dest %>'
      }
    }
  });

  // These plugins provide necessary tasks.
  grunt.loadNpmTasks('grunt-contrib-sass');
  grunt.loadNpmTasks('grunt-contrib-jshint');
  grunt.loadNpmTasks('grunt-contrib-concat');
  grunt.loadNpmTasks('grunt-contrib-uglify');

  // Default task.
  grunt.registerTask('default', ['sass', 'jshint', 'concat', 'uglify']);

};
