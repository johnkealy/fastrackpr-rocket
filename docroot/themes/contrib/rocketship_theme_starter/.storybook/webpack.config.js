const path = require('path');
const ESLintPlugin = require('eslint-webpack-plugin');
const globImporter = require('node-sass-glob-importer');
const createCompiler = require('@storybook/addon-docs/mdx-compiler-plugin');

module.exports = async ({ config }) => {
  config.mode = 'development';

  // Twig
  config.module.rules.push({
    test: /\.(html.twig|twig)$/,
    use: [
      {
        loader: 'twig-loader',
        options: {
          twigOptions: {
            namespaces: {
              "rocketship-theme-starter-atoms": path.resolve(
                __dirname,
                '../',
                'components/00-theme/01-atoms'
              ),
              "rocketship-theme-starter-molecules": path.resolve(
                __dirname,
                '../',
                'components/00-theme/02-molecules',
              ),
              "rocketship-theme-starter-organisms": path.resolve(
                __dirname,
                '../',
                'components/00-theme/03-organisms',
              ),
              "rocketship-theme-starter-templates": path.resolve(
                __dirname,
                '../',
                'components/00-theme/04-templates',
              ),
              "rocketship-theme-starter-pages": path.resolve(
                __dirname,
                '../',
                'components/00-theme/05-pages',
              ),
              "rocketship-theme-starter-cb-molecules": path.resolve(
                __dirname,
                '../',
                'components/01-content-blocks/02-molecules',
              ),
              "rocketship-theme-starter-cb-organisms": path.resolve(
                __dirname,
                '../',
                'components/01-content-blocks/03-organisms',
              ),
              "rocketship-theme-starter-cb": path.resolve(
                __dirname,
                '../',
                'components/01-content-blocks',
              ),
              "rocketship-theme-starter-features": path.resolve(
                __dirname,
                '../',
                'components/02-features',
              ),
              "rocketship_core": path.resolve(
                __dirname,
                '../',
                '../../../modules/contrib/rocketship_core/templates',
              ),
            },
          },
        },
      },
    ],
  });

  // SCSS
  config.module.rules.push({
    test: /\.s[ac]ss$/i,
    use: [
      'style-loader',
      {
        loader: 'css-loader',
        options: {
          sourceMap: true,
          url: false // otherwise get error or relative url paths in css not resolving
        },
      },
      {
        loader: 'sass-loader',
        options: {
          sourceMap: true,
          sassOptions: {
            importer: globImporter(),
          },
        },
      },
    ],
  });

  // YAML
  config.module.rules.push({
    test: /\.ya?ml$/,
    loader: 'js-yaml-loader',
  });

  // JS
  // https://stackoverflow.com/questions/70377900/eslint-invalid-options-when-create-next-app/70378398#70378398
  // https://stackoverflow.com/questions/73700682/storybook-6-error-in-no-files-matching-path-to-project-storybook-init-framew
  /*
  config.plugins.push(
    new ESLintPlugin({
      context: path.resolve(__dirname, '../', 'components'),
      extensions: ['js'],
      exclude: path.resolve(__dirname, '../', 'node_modules'),
      cache: true,
      failOnError: false,
      failOnWarning: false,
      quiet: true,
      baseConfig: {
        parserOptions: {
          sourceType: "module",
          ecmaFeatures: {
            globalReturn: false,
          },
          requireConfigFile: false,
          allowImportExportEverywhere: false,
          babelOptions: {
            configFile: ".babelrc"
          },
          plugins: ["jsx"]
        },
      },
      overrideConfig: {
        globals: {
          'jQuery': true,
          'Blazy': true,
          'dBlazy': true
        }
      }
    })
  );
   */

  // MDX

  // 1. Loading non-story mdx files

  // config.module.rules.push({
  //   test: /(?!\.stories)\.mdx$/,
  //   exclude: /node_modules/,
  //   use: [
  //     {
  //       loader: 'babel-loader',
  //       // may or may not need this line depending on your app's setup
  //       options: {
  //         plugins: ['@babel/plugin-transform-react-jsx'],
  //       },
  //     },
  //     {
  //       loader: '@mdx-js/loader',
  //     },
  //   ],
  // });

  // 2a. Load `.stories.mdx` files as CSF and generate
  //     the docs page from the markdown
  config.module.rules.push({
    test: /\.stories\.mdx$/,
    exclude: /node_modules/,
    use: [
      {
        loader: 'babel-loader',
        // may or may not need this line depending on your app's setup
        options: {
          plugins: ['@babel/plugin-transform-react-jsx'],
        },
      },
      {
        loader: '@mdx-js/loader',
        options: {
          compilers: [createCompiler({})],
        },
      },
    ],
  });

  // 2b. Run `source-loader` on story files to show their source code
  //     automatically in `DocsPage` or the `Source` doc block.
  config.module.rules.push({
    test: /\.stories\.[tj]sx?$/,
    loader: require.resolve('@storybook/source-loader'),
    exclude: [/node_modules/],
    enforce: 'pre',
  });

  // Logo
  config.module.rules.push({
    test: /\.(png|svg|jpg|gif)$/i,
    use: [
      {
        loader: 'file-loader',
        options: {
          name: '[name].[ext]'
        }
      }
    ],
    include: [path.resolve(__dirname, '../', './')],
  });

  // Images
  config.module.rules.push({
    test: /\.(png|svg|jpg|gif)$/i,
    use: [
      {
        loader: 'file-loader',
        options: {
          name: '[name].[ext]'
        }
      }
    ],
    include: [path.resolve(__dirname, '../', 'images')],
  });

  // Icons
  // config.module.rules.push({
  //   test: /\.svg$/i,
  //   use: [
  //     {
  //       loader: 'file-loader',
  //       options: {
  //         name: '[name].[ext]'
  //       }
  //     }
  //   ],
  //   include: [path.resolve(__dirname, '../', 'icons')],
  // });

  // Fonts
  config.module.rules.push({
    test: /\.(woff|woff2|ttf|eot|otf|svg)$/i,
    use: [
      {
        loader: 'file-loader',
        options: {
          name: '[name].[ext]'
        }
      }
    ],
    include: [path.resolve(__dirname, '../', 'fonts')],
  });

  config.resolve.extensions.push(".ts", ".tsx");

  // remove webpack routing error: warn: "Missing fs and path modules. ReferenceError: process is not defined"
  config.resolve.alias['core-js/modules'] = path.resolve(__dirname, '..', 'node_modules/core-js/modules');
  config.resolve.alias['path'] = path.resolve(__dirname, '..', 'node_modules/path/modules');

  return config;
};
