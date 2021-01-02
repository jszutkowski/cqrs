setup:
	docker-compose up -d
	docker-compose exec php composer install
	docker-compose exec php php bin/console doctrine:migrations:migrate
	docker-compose down

start:
	docker-compose up

start-deamon:
	docker-compose up -d

stop:
	docker-compose stop
