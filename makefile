init:
	bash ./dev/init.sh

docker.up:
	docker-compose -p itron-templater up -d

docker.down:
	docker-compose -p itron-templater down

docker.build.php:
	docker-compose -p itron-templater up -d --build php

connect.php:
	docker-compose -p itron-templater exec php bash

tests.php.run:
	docker-compose -p itron-templater exec php vendor/bin/phpunit

