.PHONY: help up down logs ps shell-php shell-db test test-unit

help: ## Affiche cette aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'

up: ## Démarre la stack (build si nécessaire)
	docker compose up -d --build

down: ## Arrête et supprime les containers
	docker compose down

logs: ## Affiche les logs en temps réel
	docker compose logs -f

ps: ## Affiche l'état des services
	docker compose ps

shell-php: ## Ouvre un shell dans le container PHP
	docker compose exec php sh

shell-db: ## Ouvre un shell MySQL
	docker compose exec db mysql -u$${DB_USER} -p$${DB_PASS} $${DB_NAME}

test: ## Lance les tests curl de l'API (nécessite que la stack soit up)
	@echo "\n=== GET / ==="
	curl -s http://localhost:8080/ | python3 -m json.tool || true

	@echo "\n=== GET /tickets ==="
	curl -s http://localhost:8080/tickets | python3 -m json.tool || true

	@echo "\n=== POST /tickets ==="
	curl -s -X POST http://localhost:8080/tickets \
		-H "Content-Type: application/json" \
		-d '{"title":"Test ticket","description":"Créé via make test","priority":"low"}' \
		| python3 -m json.tool || true

test-unit: ## Lance les tests unitaires PHPUnit (SQLite en mémoire, sans toucher à MySQL)
	@test -f .phpunit.cache/phpunit.phar || { mkdir -p .phpunit.cache && curl -sSL -o .phpunit.cache/phpunit.phar https://phar.phpunit.de/phpunit-11.phar; }
	php .phpunit.cache/phpunit.phar
