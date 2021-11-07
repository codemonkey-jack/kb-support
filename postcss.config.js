const tailwindcss = require('tailwindcss');
module.exports = {
	plugins: [
		tailwindcss,
		require('autoprefixer'),
		'postcss-preset-env',
	]
 }