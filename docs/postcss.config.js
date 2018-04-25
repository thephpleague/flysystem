module.exports = {
    plugins: [
        require('postcss-import'),
        require('tailwindcss')('./tailwind.js'),
        require('postcss-csso')
    ]
};