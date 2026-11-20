const Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/')
    .setPublicPath('/bundles/markocupiccontaoschooltest')
    .setManifestKeyPrefix('')

    // Frontend script and stylesheet of both content elements (test and practice)
    .copyFiles({
        from: './assets/js',
        to: 'js/[path][name].[hash:8].[ext]',
    })
    .copyFiles({
        from: './assets/styles',
        to: 'css/[path][name].[hash:8].[ext]',
    })

    // Page stylesheet for a standalone practice page (fixed name, for the page layout)
    .copyFiles({
        from: './assets/page',
        to: 'page/[path][name].[ext]',
    })

    // Backend icons keep their names (referenced in the DCA)
    .copyFiles({
        from: './assets/icons',
        to: 'icons/[path][name].[ext]',
    })

    .disableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableSourceMaps()
    .enableVersioning()

    // enables @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = 3;
    })

    // Preprocessing SCSS to CSS
    .enableSassLoader()
    .enablePostCssLoader()
;

module.exports = Encore.getWebpackConfig();
