# ph_backend - Comandos Docker (guía práctica Docker)
# Uso diario: make stop (fin del día), make start (inicio del día)
# Solo cuando haga falta: make down y make up

RED = \033[0;31m
GREEN = \033[0;32m
YELLOW = \033[0;33m
NC = \033[0m

.PHONY: up down stop start restart wait-db show-urls day-end day-start app artisan composer shell env-docker db

up:
	@echo -e '$(GREEN)=> Iniciando contenedores Docker$(NC)'
	@if [ ! -f .env ]; then cp .env.docker .env; echo -e '$(GREEN)=> .env creado desde .env.docker$(NC)'; fi
	docker compose up -d --build
	@$(MAKE) wait-db
	@$(MAKE) show-urls

down:
	@echo -e '$(YELLOW)=> Deteniendo contenedores (volúmenes se conservan)$(NC)'
	docker compose down

stop:
	@echo -e '$(YELLOW)=> Deteniendo contenedores (ideal para fin del día)$(NC)'
	docker compose stop
	@echo -e '$(GREEN)=> Mañana: make start$(NC)'

start:
	@echo -e '$(GREEN)=> Iniciando contenedores existentes$(NC)'
	docker compose start
	@$(MAKE) wait-db
	@$(MAKE) show-urls

show-urls:
	@echo ''; echo -e '$(GREEN)=> Acceso:$(NC)'; \
	echo -e '   App (API):  http://localhost:8000'; \
	echo -e '   API docs:   http://localhost:8000/docs/api'; \
	echo -e '   PostgreSQL: localhost:5432 (usuario postgres, contraseña root)'; echo ''

day-end: stop
day-start: start

wait-db:
	@echo -e '$(YELLOW)=> Esperando a PostgreSQL...$(NC)'; \
	for i in 1 2 3 4 5 6 7 8 9 10; do \
		if docker compose exec -T db pg_isready -U postgres -q 2>/dev/null; then \
			echo -e '$(GREEN)=> PostgreSQL listo$(NC)'; exit 0; \
		fi; \
		sleep 2; \
	done; \
	echo -e '$(RED)=> PostgreSQL no respondió a tiempo$(NC)'; exit 1

app:
	@echo -e '$(GREEN)=> Accediendo al contenedor de la aplicación$(NC)'
	docker compose exec app bash

artisan:
	docker compose exec app php artisan $(filter-out $@,$(MAKECMDGOALS))

composer:
	docker compose exec app composer $(filter-out $@,$(MAKECMDGOALS))

shell:
	@echo -e '$(GREEN)=> Accediendo al contenedor de la aplicación$(NC)'
	docker compose exec app bash

db:
	@echo -e '$(GREEN)=> Accediendo a PostgreSQL (psql)$(NC)'
	docker compose exec db psql -U postgres -d ph_backend

env-docker:
	@echo -e '$(GREEN)=> Cambiando a configuración Docker$(NC)'
	@if [ -f .env.docker ]; then \
		cp .env.docker .env; \
		echo -e '$(GREEN)=> .env actualizado desde .env.docker$(NC)'; \
	else \
		echo -e '$(RED)=> Error: .env.docker no existe$(NC)'; \
	fi

%:
	@:
