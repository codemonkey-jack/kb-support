/* jshint node:true */
'use strict';

module.exports = function( grunt ) {

	// auto load grunt tasks
	require( 'load-grunt-tasks' )( grunt );

	var pluginConfig = {

		// gets the package vars
		pkg: grunt.file.readJSON( 'package.json' ),

		// plugin directories
		dirs: {
			main: {
				js: 'assets/js',
				css: 'assets/css',
				images: 'assets/images',
				lang: 'languages'
			},
			templates: {
				css: 'templates',
			}
		},

		// pot file
		makepot: {
			target: {
				options: {
					domainPath: '<%= dirs.main.lang %>/',    // Where to save the POT file.
					exclude: ['build/.*'],
					mainFile: 'kb-support.php',    // Main project file.
					potFilename: 'kb-support.pot',    // Name of the POT file.
					potHeaders: {
						poedit: true,                 // Includes common Poedit headers.
						'x-poedit-keywordslist': true // Include a list of all possible gettext functions.
								},
					type: 'wp-plugin',    // Type of project (wp-plugin or wp-theme).
					updateTimestamp: true,    // Whether the POT-Creation-Date should be updated without other changes.
					processPot: function( pot ) {
						pot.headers['report-msgid-bugs-to'] = 'https://kb-support.com/';
						pot.headers['last-translator'] = 'WP-Translations (http://wp-translations.org/)';
						pot.headers['language-team'] = 'WP-Translations <wpt@wp-translations.org>';
						pot.headers.language = 'en_US';
						var translation, // Exclude meta data from pot.
							excluded_meta = [
								'KB Support',
								'https://kb-support.com',
								'Mike Howard',
								'http://mikesplugins.co.uk'
							];
							for ( translation in pot.translations[''] ) {
								if ( 'undefined' !== typeof pot.translations[''][ translation ].comments.extracted ) {
									if ( excluded_meta.indexOf( pot.translations[''][ translation ].comments.extracted ) >= 0 ) {
										console.log( 'Excluded meta: ' + pot.translations[''][ translation ].comments.extracted );
										delete pot.translations[''][ translation ];
									}
								}
							}
						return pot;
					}
				}
			}
		},

		// checktextdomain
		checktextdomain: {
			options:{
				text_domain: 'kb-support',
				create_report_file: false,
				keywords: [
					'__:1,2d',
					'_e:1,2d',
					'_x:1,2c,3d',
					'esc_html__:1,2d',
					'esc_html_e:1,2d',
					'esc_html_x:1,2c,3d',
					'esc_attr__:1,2d',
					'esc_attr_e:1,2d',
					'esc_attr_x:1,2c,3d',
					'_ex:1,2c,3d',
					'_n:1,2,3,4d',
					'_nx:1,2,4c,5d',
					'_n_noop:1,2,3d',
					'_nx_noop:1,2,3c,4d',
					' __ngettext:1,2,3d',
					'__ngettext_noop:1,2,3d',
					'_c:1,2d',
					'_nc:1,2,4c,5d'
					]
			},
			files: {
				src: [
					'**/*.php', // Include all files
					'!node_modules/**', // Exclude node_modules/
					'!build/.*', // Exclude build/
					'!tests/**', // Exclude tests
					'!includes/KBS_SL_Plugin_Updater.php',
					'!includes/EDD_SL_Plugin_Updater.php'
					],
				expand: true
			}
		},

		// potomo
		/*potomo: {
			dist: {
				options: {
					poDel: true
				},
				files: [{
					expand: true,
					cwd: '<%= dirs.main.lang %>',
					src: ['*.po'],
					dest: '<%= dirs.main.lang %>',
					ext: '.mo',
					nonull: true
				}]
			}
		},*/

		// glotpress
		/*glotpress_download: {
			core: {
				options: {
					domainPath: '<%= dirs.main.lang %>',
					url: 'https://translate.wordpress.org',
					slug: 'wp-plugins/kb-support/stable',
					textdomain: 'kb-support',
					filter: {
						minimum_percentage: 1,
					}
				}
			},
		},*/

		// svn settings
		svn_settings: {
			path: 'https://plugins.svn.wordpress.org/kb-support/<%= pkg.name %>',
			tag: '<%= svn_settings.path %>/tags/<%= pkg.version %>',
			trunk: '<%= svn_settings.path %>/trunk',
			exclude: [
				'.editorconfig',
				'.git/',
				'.gitignore',
				'.jshintrc',
				'.sass-cache/',
				'node_modules/',
				'phpunit.xml',
				'tests/',
				'Gruntfile.js',
				'README.md',
				'package.json',
				'*.zip'
			]
		},

		// javascript linting with jshint
		jshint: {
			options: {
				jshintrc: '.jshintrc'
			},
			all: [
				'Gruntfile.js',
				'<%= dirs.main.js %>/admin-scripts.js',
				'<%= dirs.main.js %>/kbs-ajax.js',
				'<%= dirs.main.js %>/kbs-live-search.js'
			]
		},

		// cssmin
		cssmin:	{
			build:	{
				files: {
					'<%= dirs.main.css %>/admin/kbs-admin.min.css': ['<%= dirs.main.css %>/admin/kbs-admin.css'],
                    '<%= dirs.main.css %>/admin/kbs-admin-bar.min.css': ['<%= dirs.main.css %>/admin/kbs-admin-bar.css'],
					'<%= dirs.templates.css %>/kbs.min.css': ['<%= dirs.templates.css %>/kbs.css']
				}
			}
		},

		// uglify to concat and minify
		uglify: {
			dist: {
				files: {
					'<%= dirs.main.js %>/admin/admin-scripts.min.js': ['<%= dirs.main.js %>/admin/admin-scripts.js'],
					'<%= dirs.main.js %>/admin/ticket-actions.min.js': ['<%= dirs.main.js %>/admin/ticket-actions.js'],
					'<%= dirs.main.js %>/kbs-ajax.min.js': ['<%= dirs.main.js %>/kbs-ajax.js'],
					'<%= dirs.main.js %>/kbs-live-search.min.js': ['<%= dirs.main.js %>/kbs-live-search.js']
				}
			}
		},

		// watch for changes and trigger jshint and uglify
		watch: {
			js: {
				files: [
					'<%= jshint.all %>'
				],
				tasks: ['jshint', 'uglify']
			}
		},

		// image optimization
		imagemin: {
			dist: {
				options: {
					optimizationLevel: 7,
					progressive: true
				},
				files: [
					{
						expand: true,
						cwd: '<%= dirs.main.images %>/',
						src: '**/*.{png,jpg,gif}',
						dest: '<%= dirs.main.images %>/'
					},
					{
						expand: true,
						cwd: './',
						src: 'screenshot-*.png',
						dest: './'
					}
				]
			}
		},

		// rsync commands used to take the files to svn repository
		rsync: {
			options: {
				args: ['--verbose'],
				exclude: '<%= svn_settings.exclude %>',
				syncDest: true,
				recursive: true
			},
			tag: {
				options: {
					src: './',
					dest: '<%= svn_settings.tag %>'
				}
			},
			trunk: {
				options: {
				src: './',
				dest: '<%= svn_settings.trunk %>'
				}
			}
		},

		compress: {
			main: {
				options: {
					archive: 'kb-support.zip',
					mode: 'zip'
				},
				files: [{
					src: [
						'*',
						'**',
						'!_notes/**',
						'!node_modules/**',
						'!.git/**',
						'!.editorconfig',
						'!.jshintrc',
						'!.gitignore',
						'!.gitattributes',
						'!composer.json',
						'!composer.lock',
						'!README.md',
						'!CONTRIBUTING.md',
						'!Gruntfile.js',
						'!package.json',
						'!package-lock.json',
						'!phpunit.xml',
						'!kb-support.zip'
					]
				}]
			}
		},

		// shell command to commit the new version of the plugin
		shell: {
			// Remove delete files.
			svn_remove: {
				command: 'svn st | grep \'^!\' | awk \'{print $2}\' | xargs svn --force delete',
				options: {
					stdout: true,
					stderr: true,
					execOptions: {
						cwd: '<%= svn_settings.path %>'
					}
				}
			},
			// Add new files.
			svn_add: {
				command: 'svn add --force * --auto-props --parents --depth infinity -q',
				options: {
					stdout: true,
					stderr: true,
					execOptions: {
						cwd: '<%= svn_settings.path %>'
					}
				}
			},
			// Commit the changes.
			svn_commit: {
				command: 'svn commit -m "updated the plugin version to <%= pkg.version %>"',
				options: {
					stdout: true,
					stderr: true,
					execOptions: {
						cwd: '<%= svn_settings.path %>'
					}
				}
			}
		}
	};

	// initialize grunt config
	// --------------------------
	grunt.initConfig( pluginConfig );

	// register tasks
	// --------------------------

	// default task
	grunt.registerTask( 'default', [
		'checktextdomain',
		'cssmin',
		'jshint',
		'uglify',
		'makepot'
		//'potomo',
		//'glotpress_download'
	] );

	grunt.registerTask( 'release', [
		'compress'
	] );

	// deploy task
	grunt.registerTask( 'deploy', [
		'default',
		'rsync:tag',
		'rsync:trunk',
		'shell:svn_remove',
		'shell:svn_add',
		'shell:svn_commit'
	] );
};
