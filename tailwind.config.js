module.exports = {
	mode: 'jit',
	purge: ['./src/*.js', './src/*/*.js', './src/*/*/*.js', './src/*/*/*/*.js'],
	darkMode: false, //you can set it to media(uses prefers-color-scheme) or class(better for toggling modes via a button)
	theme: {
		extend: {},
	},
	variants: {},
	plugins: [],
}