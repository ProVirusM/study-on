COMPOSE=docker-compose
PHP=$(COMPOSE) exec php
CONSOLE=$(PHP) bin/console
COMPOSER=$(PHP) composer

up:
	@$(COMPOSE) up -d

down:
	@$(COMPOSE) down

clear:
	@$(CONSOLE) cache:clear

migration:
	@$(CONSOLE) make:migration

migrate:
	@$(CONSOLE) doctrine:migrations:migrate

fixtload:
	@$(CONSOLE) doctrine:fixtures:load
encore_dev:
	@${COMPOSE} run node yarn encore dev
phpunit:
	@${PHP} bin/phpunit
encore_prod:
	@${COMPOSE} run node yarn encore production
# Подключение локального Makefile (если есть)
-include local.mk
