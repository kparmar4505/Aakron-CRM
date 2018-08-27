sudo app/console cache:clear --env=dev;sudo app/console oro:assets:install --env=dev --symlink;sudo app/console assetic:dump --env=dev;sudo app/console oro:requirejs:build --env=dev;
