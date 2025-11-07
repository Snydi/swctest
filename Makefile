.PHONY: install up down reinstall clean _wait_postgres _composer_install _run_migrations _remove_volumes _storage_link _start_queue

install:
	$(MAKE) up
	$(MAKE) _wait_postgres
	$(MAKE) _composer_install
	$(MAKE) _run_migrations
	$(MAKE) _storage_link
	docker-compose -p swctest exec -T php sh -c "cd /var/www/html && php artisan key:generate"
	$(MAKE) _start_queue
	@echo "Installation complete."

up:
	docker-compose -p swctest build --no-cache --pull
	docker-compose -p swctest up -d --force-recreate

down:
	docker-compose -p swctest down

clean:
	-docker-compose -p swctest down --rmi all --volumes --remove-orphans
	$(MAKE) _remove_volumes
	docker system prune -a --volumes --force
	@if exist vendor rmdir /s /q vendor

reinstall:
	$(MAKE) clean
	$(MAKE) install

_wait_postgres:
	docker-compose -p swctest exec -T postgres sh -c 'until pg_isready -U $${POSTGRES_USER:-postgres} -d $${POSTGRES_DB:-postgres}; do sleep 2; echo "Waiting for PostgreSQL..."; done'
	docker-compose -p swctest exec -T postgres sh -c 'psql -U $${POSTGRES_USER:-postgres} -tc "SELECT 1 FROM pg_database WHERE datname = '\''$${POSTGRES_DB:-swctest}'\''" | grep -q 1 || psql -U $${POSTGRES_USER:-postgres} -c "CREATE DATABASE $${POSTGRES_DB:-swctest}"'

_composer_install:
	docker-compose -p swctest exec -T php sh -c "\
		cd /var/www/html && \
		composer clear-cache && \
		composer install --no-interaction --prefer-dist"

_run_migrations:
	docker-compose -p swctest exec -T php sh -c "cd /var/www/html && php artisan migrate:fresh --seed"

_storage_link:
	docker-compose -p swctest exec -T php sh -c "cd /var/www/html && php artisan storage:link"

_start_queue:
	docker-compose -p swctest exec -d php sh -c "cd /var/www/html && php artisan queue:work --daemon --sleep=3 --tries=3"

_remove_volumes:
	@docker volume rm -f swctest_postgres_data 2> nul
