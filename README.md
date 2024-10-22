Set up:
- docker-compose build
- docker-compose up -d
- composer install

Tests:
- docker exec -it php bash
- php bin/phpunit tests
