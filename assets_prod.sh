sudo app/console cache:clear --env=prod;sudo app/console oro:assets:install --env=prod --symlink;sudo app/console assetic:dump --env=prod;sudo app/console oro:requirejs:build --env=prod;
