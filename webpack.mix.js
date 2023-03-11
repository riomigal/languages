let mix = require('laravel-mix');


if(mix.inProduction()) {
    mix.js('resources/js/app.js', 'js').version()
        .postCss('resources/css/app.css', 'css', [
            //
        ]).setPublicPath('public');
} else {
    mix.js('resources/js/app.js', 'js').version()
        .postCss('resources/css/app.css', 'css', [
            //
        ]).setPublicPath('../../../public');
    // mix.browserSync({
    //         proxy: 'laravel.test',
    //     files: ["resources/views/*.blade.php"]
    //     });
}


