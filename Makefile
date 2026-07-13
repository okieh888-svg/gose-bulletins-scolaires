.PHONY: up down build sh console schema fixtures test logs

up: ## Démarre la stack (build inclus si nécessaire)
	docker-compose up -d --build

down: ## Arrête et supprime les conteneurs
	docker-compose down

build: ## Reconstruit l'image PHP
	docker-compose build php

sh: ## Ouvre un shell dans le conteneur PHP
	docker-compose exec php sh

console: ## Exécute bin/console (usage: make console cmd="cache:clear")
	docker-compose exec php php bin/console $(cmd)

schema: ## Crée le schéma de base de données (prototype : pas de migrations)
	docker-compose exec php php bin/console doctrine:schema:create

fixtures: ## Charge le jeu de données fictif de démonstration
	docker-compose exec php php bin/console doctrine:fixtures:load --no-interaction

test: ## Lance les tests unitaires (moteur de calcul + invariants RBAC)
	docker-compose exec php php vendor/bin/phpunit

logs: ## Suit les logs de tous les conteneurs
	docker-compose logs -f
