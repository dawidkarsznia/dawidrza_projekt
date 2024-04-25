@echo off

php bin/console make:migration
php bin/console doctrine:migrations:migrate
php bin/console app:create-user --admin